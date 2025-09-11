<?php

namespace App\Http\Controllers\API\Hrms\Payroll;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Models\Hrms\EmpWorkShift;
use App\Models\Hrms\WorkShift;
use App\Models\Hrms\WorkShiftsAlgo;
use App\Models\Hrms\Holiday;
use App\Models\Hrms\HolidayCalendar;
use App\Models\Hrms\Employee;
use App\Models\Hrms\PayrollSlot;
use App\Models\Hrms\PayrollSlotsCmd;
use App\Models\Hrms\PayrollComponentsEmployeesTrack;
use App\Services\SalarySlipService;
use Barryvdh\DomPDF\Facade\Pdf;

class PayrollController extends Controller
{
    /**
     * Get employee holidays based on their work shift and holiday calendar
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEmployeeHolidays(Request $request)
    {
        try {
            // Get authenticated user
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            // Get employee from authenticated user
            $employee = $user->employee;
            if (!$employee) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Employee profile not found'
                ], 404);
            }

            // Optional filter parameters
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::now()->startOfYear();
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : Carbon::now()->endOfYear();

            // Get employee's work shift assignment
            $workShiftAssignment = EmpWorkShift::where('employee_id', $employee->id)
                ->where('firm_id', $employee->firm_id)
                ->where(function($query) use ($startDate) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>=', $startDate);
                })
                ->with('work_shift')
                ->first();

            // Variables to store results
            $holidays = collect();
            $calendarInfo = null;
            $workShiftInfo = null;
            $holidayCalendar = null;

            if ($workShiftAssignment && $workShiftAssignment->work_shift) {
                // Get work shift info safely
                $workShift = $workShiftAssignment->work_shift;
                $workShiftInfo = [
                    'id' => $workShift->id,
                    'title' => $workShift->shift_title,
                    'assignment_start_date' => null,
                    'assignment_end_date' => null
                ];
                
                // Handle dates separately with proper checks
                if ($workShiftAssignment->start_date) {
                    if ($workShiftAssignment->start_date instanceof \DateTime) {
                        $workShiftInfo['assignment_start_date'] = $workShiftAssignment->start_date->format('Y-m-d');
                    } else {
                        $workShiftInfo['assignment_start_date'] = $workShiftAssignment->start_date;
                    }
                }
                
                if ($workShiftAssignment->end_date) {
                    if ($workShiftAssignment->end_date instanceof \DateTime) {
                        $workShiftInfo['assignment_end_date'] = $workShiftAssignment->end_date->format('Y-m-d');
                    } else {
                        $workShiftInfo['assignment_end_date'] = $workShiftAssignment->end_date;
                    }
                }
                
                // Find the work shift's algorithm that includes holiday calendar reference
                $workShiftAlgo = WorkShiftsAlgo::where('work_shift_id', $workShift->id)
                    ->where('firm_id', $employee->firm_id)
                // Assuming active status
                    ->whereNotNull('holiday_calendar_id')
                    ->first();
                
                if ($workShiftAlgo && $workShiftAlgo->holiday_calendar_id) {
                    // Get the holiday calendar
                    $holidayCalendar = HolidayCalendar::find($workShiftAlgo->holiday_calendar_id);
                }
            }
   //$ php artisan make:migration add_role_mains_to_users_table --table=users
            // If no holiday calendar was found via work shift, use default
            if (!$holidayCalendar) {
                $holidayCalendar = HolidayCalendar::where('firm_id', $employee->firm_id)
//                    ->where('is_inactive', false)
                    ->where('title', 'like', '%default%')
                    ->first();
                
                if (!$holidayCalendar) {
                    return response()->json([
                        'message_type' => 'error',
                        'message_display' => 'flash',
                        'message' => 'No holiday calendar found for this employee'
                    ], 404);
                }
                
                $calendarInfo = [
                    'id' => $holidayCalendar->id,
                    'title' => $holidayCalendar->title,
                    'description' => $holidayCalendar->description,
                    'is_default' => true
                ];
            } else {
                $calendarInfo = [
                    'id' => $holidayCalendar->id,
                    'title' => $holidayCalendar->title,
                    'description' => $holidayCalendar->description,
                    'is_default' => false
                ];
            }
            
            // Get holidays from the calendar
            $holidays = Holiday::where('holiday_calendar_id', $holidayCalendar->id)
//                ->where('is_inactive', false)
                ->where(function($query) use ($startDate, $endDate) {
                    $query->whereBetween('start_date', [$startDate, $endDate])
                          ->orWhereBetween('end_date', [$startDate, $endDate])
                          ->orWhere(function($q) use ($startDate, $endDate) {
                              $q->where('repeat_annually', true)
                                ->whereRaw('MONTH(start_date) * 100 + DAY(start_date) BETWEEN ? AND ?', [
                                    $startDate->format('n') * 100 + $startDate->format('j'),
                                    $endDate->format('n') * 100 + $endDate->format('j')
                                ]);
                          });
                })
                ->orderBy('start_date')
                ->get();

            // Format holidays data
            $formattedHolidays = $holidays->map(function($holiday) {
                $data = [
                    'id' => $holiday->id,
                    'title' => $holiday->holiday_title,
                    'description' => $holiday->holiday_desc,
                    'repeat_annually' => (bool) $holiday->repeat_annually,
                    'day_status' => $holiday->day_status_main ?? 'H',
                    'day_status_label' => Holiday::WORK_STATUS_SELECT[$holiday->day_status_main ?? 'H'] ?? 'Holiday'
                ];
                
                // Handle start_date
                if ($holiday->start_date) {
                    if ($holiday->start_date instanceof \DateTime) {
                        $data['start_date'] = $holiday->start_date->format('Y-m-d');
                    } else {
                        $data['start_date'] = $holiday->start_date;
                    }
                } else {
                    $data['start_date'] = null;
                }
                
                // Handle end_date
                if ($holiday->end_date) {
                    if ($holiday->end_date instanceof \DateTime) {
                        $data['end_date'] = $holiday->end_date->format('Y-m-d');
                    } else {
                        $data['end_date'] = $holiday->end_date;
                    }
                } else if ($holiday->start_date) {
                    // If end_date is not set, use start_date
                    if ($holiday->start_date instanceof \DateTime) {
                        $data['end_date'] = $holiday->start_date->format('Y-m-d');
                    } else {
                        $data['end_date'] = $holiday->start_date;
                    }
                } else {
                    $data['end_date'] = null;
                }
                
                return $data;
            });

            // Ensure dates are formatted correctly
            $formattedStartDate = $startDate instanceof \DateTime ? $startDate->format('Y-m-d') : $startDate;
            $formattedEndDate = $endDate instanceof \DateTime ? $endDate->format('Y-m-d') : $endDate;

            return response()->json([
                'message_type' => 'success',
                'message_display' => 'none',
                'message' => 'Employee holidays fetched successfully',
                'data' => [
                    'holidays' => $formattedHolidays->map(function($holiday) {
                        return [
                            'id' => $holiday['id'],
                            'title' => $holiday['title'],
                            'description' => $holiday['description'],
                            'repeat_annually' => (bool) $holiday['repeat_annually'],
                            'day_status' => $holiday['day_status'] ?? 'H',
                            'day_status_label' => Holiday::WORK_STATUS_SELECT[$holiday['day_status'] ?? 'H'] ?? 'Holiday',
                            'start_date' => $holiday['start_date'] ? $holiday['start_date'] : null,
                            'end_date' => $holiday['end_date'] ? $holiday['end_date'] : ($holiday['start_date'] ? $holiday['start_date'] : null)
                        ];
                    })->toArray(),
                    'calendar' => $calendarInfo ? [
                        'id' => $calendarInfo['id'],
                        'title' => $calendarInfo['title'],
                        'description' => $calendarInfo['description'],
                        'is_default' => $calendarInfo['is_default']
                    ] : [],
                    'work_shift' => $workShiftInfo ? [
                        'id' => $workShiftInfo['id'],
                        'title' => $workShiftInfo['title'],
                        'assignment_start_date' => $workShiftInfo['assignment_start_date'],
                        'assignment_end_date' => $workShiftInfo['assignment_end_date']
                    ] : [],
                    'filter_period' => [
                        'start_date' => $formattedStartDate,
                        'end_date' => $formattedEndDate
                    ]
                ]
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'popup',
                'message' => 'Server error: ' . $e->getMessage(),
                'data' => [
                    'holidays' => [],
                    'calendar' => [],
                    'work_shift' => [],
                    'filter_period' => []
                ]
            ], 500);
        }
    }   

    /**
     * Get employee salary structure with all component heads and calculated gross amount
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEmployeeSalaryStructure(Request $request)
    {
        try {
            // Get authenticated user
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            // Get employee from authenticated user
            $employee = $user->employee;
            if (!$employee) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Employee profile not found'
                ], 404);
            }

            // Get salary components for the employee with their base component details
            $components = DB::table('salary_components_employees')
                ->where('salary_components_employees.employee_id', $employee->id)
                ->where('salary_components_employees.firm_id', $employee->firm_id)
                ->where(function ($query) {
                    $query->whereNull('salary_components_employees.effective_to')
                        ->orWhere('salary_components_employees.effective_to', '>', now());
                })
                ->join('salary_components', 'salary_components_employees.salary_component_id', '=', 'salary_components.id')
                ->select(
                    'salary_components.id',
                    'salary_components.title',
                    'salary_components_employees.nature',
                    'salary_components.component_type',
                    'salary_components_employees.amount_type',
                    'salary_components_employees.amount'
                )
                ->get();

            // If no components found, return appropriate message
            if ($components->isEmpty()) {
                return response()->json([
                    'message_type' => 'info',
                    'message_display' => 'popup',
                    'message' => 'No salary components found for this employee',
                    'data' => [
                        'employee' => [
                            'id' => $employee->id,
                            'name' => trim($employee->fname . ' ' . $employee->mname . ' ' . $employee->lname),
                            'employee_code' => $employee->emp_job_profile ? $employee->emp_job_profile->employee_code : null,
                        ],
                        'components' => [],
                        'totals' => [
                            'earnings' => 0,
                            'deductions' => 0,
                            'net_salary' => 0
                        ],
                        'requires_sync' => false
                    ]
                ], 200);
            }

            // Group components by nature (earnings/deductions)
            $groupedComponents = [
                'earnings' => [],
                'deductions' => []
            ];
            
            $totalEarnings = 0;
            $totalDeductions = 0;
            $requiresSync = false;
            
            foreach ($components as $component) {
                $componentData = [
                    'id' => $component->id,
                    'title' => $component->title,
                    'amount' => (float)$component->amount,
                    'nature' => $component->nature,
                    'component_type' => $component->component_type,
                    'amount_type' => $component->amount_type,
                ];
                
                // Check if component is calculated and has zero amount (needs sync)
                if (strpos($component->amount_type, 'calculated_') === 0 && $component->amount == 0) {
                    $requiresSync = true;
                }
                
                // Add to appropriate group
                if ($component->nature === 'earning') {
                    $groupedComponents['earnings'][] = $componentData;
                    $totalEarnings += (float)$component->amount;
                } elseif ($component->nature === 'deduction') {
                    $groupedComponents['deductions'][] = $componentData;
                    $totalDeductions += (float)$component->amount;
                }
            }
            
            // Calculate net salary
            $netSalary = $totalEarnings - $totalDeductions;
            
            // Sort components by title within each group
            usort($groupedComponents['earnings'], function($a, $b) {
                return strcmp($a['title'], $b['title']);
            });
            
            usort($groupedComponents['deductions'], function($a, $b) {
                return strcmp($a['title'], $b['title']);
            });

            // Get employee basic information for response
            $employeeData = [
                'id' => $employee->id,
                'name' => trim($employee->fname . ' ' . ($employee->mname ? $employee->mname . ' ' : '') . $employee->lname),
                'employee_code' => $employee->emp_job_profile ? $employee->emp_job_profile->employee_code : null,
                'department' => $employee->emp_job_profile && $employee->emp_job_profile->department ? $employee->emp_job_profile->department->title : null,
                'designation' => $employee->emp_job_profile && $employee->emp_job_profile->designation ? $employee->emp_job_profile->designation->title : null,
            ];

            $responseMessage = $requiresSync 
                ? 'Salary structure retrieved. Some calculated components need synchronization.'
                : 'Salary structure retrieved successfully.';

            return response()->json([
                'message_type' => 'success',
                'message_display' => 'none',
                'message' => $responseMessage,
                'data' => [
                    'employee' => $employeeData ? [
                        'id' => $employeeData['id'],
                        'name' => $employeeData['name'],
                        'employee_code' => $employeeData['employee_code'],
                        'department' => $employeeData['department'],
                        'designation' => $employeeData['designation']
                    ] : [],
                    'components' => [
                        'earnings' => collect($groupedComponents['earnings'])->map(function($component) {
                            return [
                                'id' => $component['id'],
                                'title' => $component['title'],
                                'amount' => (float)$component['amount'],
                                'nature' => $component['nature'],
                                'component_type' => $component['component_type'],
                                'amount_type' => $component['amount_type']
                            ];
                        })->toArray(),
                        'deductions' => collect($groupedComponents['deductions'])->map(function($component) {
                            return [
                                'id' => $component['id'],
                                'title' => $component['title'],
                                'amount' => (float)$component['amount'],
                                'nature' => $component['nature'],
                                'component_type' => $component['component_type'],
                                'amount_type' => $component['amount_type']
                            ];
                        })->toArray()
                    ],
                    'totals' => [
                        'earnings' => $totalEarnings,
                        'deductions' => $totalDeductions,
                        'net_salary' => $netSalary
                    ],
                    'requires_sync' => $requiresSync
                ]
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'popup',
                'message' => 'Server error: ' . $e->getMessage(),
                'data' => [
                    'employee' => [],
                    'components' => [
                        'earnings' => [],
                        'deductions' => []
                    ],
                    'totals' => [
                        'earnings' => 0,
                        'deductions' => 0,
                        'net_salary' => 0
                    ],
                    'requires_sync' => false
                ]
            ], 500);
        }
    }

    /**
     * Get payroll slots for the authenticated employee
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEmployeePayrollSlots(Request $request)
    {
        try {
            // Get authenticated user
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            // Get employee from authenticated user
            $employee = $user->employee;
            if (!$employee) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Employee profile not found'
                ], 404);
            }

            // Load employee's salary execution groups
            $employee->load('salary_execution_groups');
            
            // Check if employee has any salary execution groups
            if ($employee->salary_execution_groups->isEmpty()) {
                return response()->json([
                    'message_type' => 'info',
                    'message_display' => 'popup',
                    'message' => 'No salary execution groups assigned to this employee',
                    'data' => []
                ], 200);
            }
            
            // Get salary execution group IDs
            $salaryExecutionGroupIds = $employee->salary_execution_groups->pluck('id')->toArray();
            
            // Optional date range filtering
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::now()->subMonths(3);
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : Carbon::now()->addMonths(3);

            // Get payroll slots for the employee's salary execution groups
            $payrollSlots = PayrollSlot::whereIn('salary_execution_group_id', $salaryExecutionGroupIds)
                ->where('firm_id', $employee->firm_id)
                ->where('payroll_slot_status', 'PB')
                ->where(function($query) use ($startDate, $endDate) {
                    $query->whereBetween('from_date', [$startDate, $endDate])
                          ->orWhereBetween('to_date', [$startDate, $endDate]);
                })
                ->with('payroll_slots_cmds') // Load the relationship with PayrollSlotsCmd
                ->orderBy('from_date', 'desc')
                ->get();

            if ($payrollSlots->isEmpty()) {
                return response()->json([
                    'message_type' => 'info',
                    'message_display' => 'none',
                    'message' => 'No payroll slots found for the specified period, Please Publish Salary to see slots',
                    'data' => [
                        'salary_execution_groups' => $employee->salary_execution_groups->map(function($group) {
                            return [
                                'id' => $group->id,
                                'title' => $group->title,
                                'description' => $group->description,
                                'is_inactive' => (bool) $group->is_inactive
                            ];
                        }),
                        'payroll_slots' => []
                    ]
                ], 200);
            }

            // If no salary execution groups or no payroll slots, return empty array
            if ($employee->salary_execution_groups->isEmpty() || $payrollSlots->isEmpty()) {
                return response()->json([
                    'message_type' => $employee->salary_execution_groups->isEmpty() ? 'info' : 'success',
                    'message_display' => $employee->salary_execution_groups->isEmpty() ? 'popup' : 'none',
                    'message' => $employee->salary_execution_groups->isEmpty() ? 'No salary execution groups assigned to this employee' : 'No payroll slots found for the specified period',
                    'data' => []
                ], 200);
            }

            // Build the array of maps for each payroll slot
            $data = $payrollSlots->map(function($slot) use ($employee, $startDate, $endDate) {
                $group = $employee->salary_execution_groups->firstWhere('id', $slot->salary_execution_group_id);
                return [
                    'salary_execution_group' => $group ? [
                        'id' => $group->id,
                        'title' => $group->title,
                        'description' => $group->description,
                        'is_inactive' => (bool) $group->is_inactive
                    ] : [],
                    'payroll_slot' => [
                        'id' => $slot->id,
                        'title' => $slot->title,
                        'from_date' => $slot->from_date->format('Y-m-d'),
                        'to_date' => $slot->to_date->format('Y-m-d'),
                        'payroll_slot_status' => $slot->payroll_slot_status,
                        'payroll_slot_status_label' => PayrollSlot::PAYROLL_SLOT_STATUS[$slot->payroll_slot_status] ?? null,
                        'salary_execution_group_id' => $slot->salary_execution_group_id,
                        'salary_cycle_id' => $slot->salary_cycle_id,
                        'command_history' => $slot->payroll_slots_cmds->map(function($cmd) {
                            return [
                                'id' => $cmd->id,
                                'cmd_status' => $cmd->payroll_slot_status,
                                'cmd_status_label' => PayrollSlotsCmd::PAYROLL_SLOT_STATUS[$cmd->payroll_slot_status] ?? null,
                                'remarks' => is_string($cmd->run_payroll_remarks) ? json_decode($cmd->run_payroll_remarks, true) : $cmd->run_payroll_remarks,
                                'created_at' => $cmd->created_at->format('Y-m-d H:i:s'),
                                'user_id' => $cmd->user_id
                            ];
                        })->sortByDesc('created_at')->values()->all(),
                        'latest_cmd_status' => optional($slot->payroll_slots_cmds->sortByDesc('created_at')->first())->payroll_slot_status,
                        'latest_cmd_status_label' => optional($slot->payroll_slots_cmds->sortByDesc('created_at')->first()) ? PayrollSlotsCmd::PAYROLL_SLOT_STATUS[optional($slot->payroll_slots_cmds->sortByDesc('created_at')->first())->payroll_slot_status] ?? null : null
                    ],
                    'filter_period' => [
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d')
                    ]
                ];
            })->values()->toArray();

            return response()->json([
                'message_type' => 'success',
                'message_display' => 'none',
                'message' => count($payrollSlots) . ' payroll slots found',
                'data' => $data
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'popup',
                'message' => 'Server error: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Get payroll components for an employee's specific payroll slot
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEmployeePayrollComponents(Request $request)
    {
        try {
            // Validate request parameters
            $validator = Validator::make($request->all(), [
                'payroll_slot_id' => 'required|integer|exists:payroll_slots,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'popup',
                    'message' => 'Invalid parameters',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get the payroll slot ID from request
            $payrollSlotId = $request->input('payroll_slot_id');

            // Get authenticated user
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            // Get employee from authenticated user
            $employee = $user->employee;
            if (!$employee) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Employee profile not found'
                ], 404);
            }

            // Get payroll slot details
            $payrollSlot = PayrollSlot::where('id', $payrollSlotId)
                ->with('payroll_slots_cmds')
                ->first();

            if (!$payrollSlot) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'popup',
                    'message' => 'Payroll slot not found'
                ], 404);
            }

            // Check if payroll has been completed or is in a state where components are available
            $validStatusCodes = ['ST', 'RS', 'IP', 'CM']; // Started, Restarted, In Progress, Completed
            
            $latestCommand = $payrollSlot->payroll_slots_cmds->sortByDesc('created_at')->first();
            
            // First, check if there are actual payroll components for this employee
            $payrollComponents = PayrollComponentsEmployeesTrack::where('payroll_slot_id', $payrollSlotId)
                ->where('employee_id', $employee->id)
                ->with('salary_component')
                ->orderBy('nature')
                ->orderBy('sequence')
                ->get();
            
            // If we have components, payroll is processed regardless of command status
            $isPayrollProcessed = !$payrollComponents->isEmpty();
            
            // If no components, then check command status as fallback
            if (!$isPayrollProcessed) {
                $isPayrollProcessed = $latestCommand && in_array($latestCommand->payroll_slot_status, $validStatusCodes);
            }
            
            // Build the clean response structure
            $response = [
                'payroll_slot' => [
                    'id' => $payrollSlot->id,
                    'title' => $payrollSlot->title,
                    'from_date' => $payrollSlot->from_date->format('Y-m-d'),
                    'to_date' => $payrollSlot->to_date->format('Y-m-d'),
                    'status' => $payrollSlot->payroll_slot_status,
                    'status_label' => PayrollSlot::PAYROLL_SLOT_STATUS[$payrollSlot->payroll_slot_status] ?? null,
                ],
                'employee' => [
                    'id' => $employee->id,
                    'name' => trim($employee->fname . ' ' . ($employee->mname ? $employee->mname . ' ' : '') . $employee->lname),
                    'employee_code' => $employee->emp_job_profile ? $employee->emp_job_profile->employee_code : null,
                    'department' => $employee->emp_job_profile && $employee->emp_job_profile->department ? $employee->emp_job_profile->department->title : null,
                    'designation' => $employee->emp_job_profile && $employee->emp_job_profile->designation ? $employee->emp_job_profile->designation->title : null,
                ],
                'is_processed' => $isPayrollProcessed,
                'latest_command' => $latestCommand ? [
                    'status' => $latestCommand->payroll_slot_status,
                    'status_label' => PayrollSlotsCmd::PAYROLL_SLOT_STATUS[$latestCommand->payroll_slot_status] ?? null,
                    'remarks' => is_string($latestCommand->run_payroll_remarks) ? json_decode($latestCommand->run_payroll_remarks, true) : $latestCommand->run_payroll_remarks,
                    'created_at' => $latestCommand->created_at->format('Y-m-d H:i:s')
                ] : null,
                'components' => [],
                'summary' => [
                    'total_earnings' => 0,
                    'total_deductions' => 0,
                    'net_salary' => 0,
                    'components_count' => 0
                ]
            ];

            if ($isPayrollProcessed && !$payrollComponents->isEmpty()) {
                // Calculate totals
                $totalEarnings = $payrollComponents->where('nature', 'earning')->sum('amount_payable');
                $totalDeductions = $payrollComponents->where('nature', 'deduction')->sum('amount_payable');
                
                // Build clean components array
                $components = $payrollComponents->map(function($component) {
                    return [
                        'id' => $component->id,
                        'component_id' => $component->salary_component_id,
                        'name' => $component->salary_component->title ?? 'Unknown Component',
                        'nature' => $component->nature,
                        'type' => $component->component_type,
                        'amount_type' => $component->amount_type,
                        'amount_full' => $component->amount_full,
                        'amount_payable' => $component->amount_payable,
                        'amount_paid' => $component->amount_paid,
                        'taxable' => (bool)$component->taxable,
                        'sequence' => $component->sequence
                    ];
                })->values()->toArray();

                $response['components'] = $components;
                $response['summary'] = [
                    'total_earnings' => $totalEarnings,
                    'total_deductions' => $totalDeductions,
                    'net_salary' => $totalEarnings - $totalDeductions,
                    'components_count' => count($components)
                ];
            } else {
                // Payroll not processed yet
                return response()->json([
                    'message_type' => 'info',
                    'message_display' => 'popup',
                    'message' => 'Payroll has not been processed yet for this period.',
                    'data' => $response
                ], 200);
            }

            return response()->json([
                'message_type' => 'success',
                'message_display' => 'none',
                'message' => 'Payroll components fetched successfully',
                'data' => $response
            ], 200);
            
        } catch (\Throwable $e) {
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'popup',
                'message' => 'Server error: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Download salary slip PDF for an employee and payroll slot
     *
     * @param Request $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function downloadSalarySlipPdf(Request $request)
    {
        $request->validate([
            'payroll_slot_id' => 'required|integer|exists:payroll_slots,id',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'flash',
                'message' => 'Unauthenticated'
            ], 401);
        }

        $employee = $user->employee;
        if (!$employee) {
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'flash',
                'message' => 'Employee profile not found'
            ], 404);
        }

        $employeeId = $employee->id;
        $payrollSlotId = $request->input('payroll_slot_id');
        $firmId = $employee->firm_id;

        try {
            $service = new \App\Services\SalarySlipService();
            $data = $service->getSalarySlipData($employeeId, $payrollSlotId, $firmId);

            // Generate filename
            $employeeName = $data['selectedEmployee']->fname . '_' . $data['selectedEmployee']->lname;
            $period = $data['rawComponents']->first() ? date('F_Y', strtotime($data['rawComponents']->first()->salary_period_from)) : '';
            $filename = $employeeName . '_Salary_Slip_' . $period . '_' . uniqid() . '.pdf';

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('livewire.hrms.payroll.blades.salary-slip-pdf', $data);
            $pdf->setPaper('a4');

            // Store PDF in public/documents
            $publicPath = public_path('documents');
            if (!file_exists($publicPath)) {
                mkdir($publicPath, 0777, true);
            }
            $pdfPath = $publicPath . DIRECTORY_SEPARATOR . $filename;
            file_put_contents($pdfPath, $pdf->output());

            // Generate public URL
            $downloadUrl = asset('documents/' . $filename);

            return response()->json([
                'message_type' => 'success',
                'message_display' => 'popup',
                'message' => 'Salary slip generated successfully.',
                'data' => [
                    'download_url' => $downloadUrl,
                    'filename' => $filename
                ]
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'popup',
                'message' => 'Failed to generate salary slip: ' . $e->getMessage(),
            ], 500);
        }
    }
}
