<?php

namespace App\Livewire\Hrms\Attendance;

use Livewire\Component;
use App\Models\Hrms\EmpAttendance;
use App\Models\Hrms\Employee;
use App\Models\Settings\Department;
use App\Models\Settings\Designation;
use App\Models\Settings\Joblocation;
use App\Models\Hrms\AttendanceLocation;
use Carbon\Carbon;
use Livewire\WithPagination;
use Flux;
use Illuminate\Support\Facades\Session;
use App\Models\Hrms\EmpPunch;
use Illuminate\Support\Facades\DB;
use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Hrms\FlexiWeekOff;

class ViewEmpAttendances extends Component
{
    use WithPagination;

    public $month;
    public $year;
    public $daysInMonth;
    public $showDetailsModal = false;
    public $selectedAttendance;
    public $selectedPunches = [];
    public $isEditing = false;
    public $editAttendanceId = null;
    public $batches;
    public $selectedBatch = null;
    public $selectedBatchId = null;
    public $formData = [
        'attendance_status_main' => '',
        'ideal_working_hours' => 8,
        'actual_worked_hours' => 8,
        'final_day_weightage' => 1,
        'attend_remarks' => '',
    ];
    protected $paginationTheme = 'tailwind';
    
    // Properties for bulk attendance
    public $employeeSearch = '';
    public $selectedEmployees = [];
    public $selectedStatus = '';
    public $dateRange = null;
    public $firm_id;

    public array $availableMonths = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];

    // Field configuration for form and table
    public array $fieldConfig = [
        'employee_id' => ['label' => 'Employee', 'type' => 'select', 'listKey' => 'employees'],
        'work_date' => ['label' => 'Date', 'type' => 'date'],
        'attendance_status_main' => ['label' => 'Status', 'type' => 'select', 'listKey' => 'attendance_statuses'],
        'work_shift_day_id' => ['label' => 'Work Shift Day', 'type' => 'number'],
        'attend_location_id' => ['label' => 'Location', 'type' => 'select', 'listKey' => 'locations'],
        'ideal_working_hours' => ['label' => 'Ideal Hours', 'type' => 'number'],
        'actual_worked_hours' => ['label' => 'Actual Hours', 'type' => 'number'],
        'final_day_weightage' => ['label' => 'Day Weightage', 'type' => 'number'],
        'attend_remarks' => ['label' => 'Remarks', 'type' => 'textarea'],
    ];

    public array $listsForFields = [];

    protected $listeners = ['refreshComponent' => '$refresh'];

    public $selectedDate;
    public $isNewAttendance = false;
    public $punches = [];

    public $employeeNameFilter = '';
    public $selectedDepartment = '';

    public $isEditingPunch = false;
    public $selectedPunchId = null;
    public $punchForm = [
        'in_out' => '',
        'punch_time' => '',
    ];

    protected $allowedPunchStatuses = ['P', 'HD', 'PW', 'WFR', 'CW', 'OD'];

    public function mount()
    {
        $this->firm_id = Session::get('firm_id');
        if (!$this->firm_id) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Firm ID not found in session'
            );
            return;
        }

        $this->month = now()->month;
        $this->year = now()->year;
        $this->updateDaysInMonth();
        $this->initListsForFields();

        // Load batches
        $this->loadBatches();
    }

    public function getAvailableYears()
    {
        $currentYear = now()->year;
        $years = range($currentYear - 2, $currentYear + 2);
        return array_combine($years, $years);
    }

    public function previousMonth()
    {
        $date = Carbon::create($this->year, $this->month, 1)->subMonth();
        $this->month = $date->month;
        $this->year = $date->year;
        $this->updateDaysInMonth();
        $this->resetPage();
    }

    public function nextMonth()
    {
        $date = Carbon::create($this->year, $this->month, 1)->addMonth();
        $this->month = $date->month;
        $this->year = $date->year;
        $this->updateDaysInMonth();
        $this->resetPage();
    }

    public function setMonth($month)
    {
        $this->month = (int)$month;
        $this->updateDaysInMonth();
        $this->resetPage();
    }

    public function setYear($year)
    {
        $this->year = (int)$year;
        $this->updateDaysInMonth();
        $this->resetPage();
    }

    protected function updateDaysInMonth()
    {
        $this->daysInMonth = Carbon::create($this->year, $this->month)->daysInMonth;
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['employees'] = Employee::where('firm_id', $this->firm_id)
            ->get()
            ->map(function($employee) {
                return [
                    'id' => $employee->id,
                    'name' => $employee->fname . ' ' . $employee->lname
                ];
            })
            ->pluck('name', 'id')
            ->toArray();

        $this->listsForFields['attendance_statuses'] = EmpAttendance::ATTENDANCE_STATUS_MAIN_SELECT;

        $this->listsForFields['locations'] = AttendanceLocation::where('firm_id', $this->firm_id)
            ->where('is_inactive', false)
            ->get()
            ->pluck('title', 'id')
            ->toArray();
    }

    public function getFilteredDepartmentsWithEmployees()
    {
        return Department::where('firm_id', $this->firm_id)
            ->with([
                'employees' => function ($query) {
                    $query->when($this->employeeSearch, function ($query) {
                        $search = '%' . $this->employeeSearch . '%';
                        $query->where(function ($q) use ($search) {
                            $q->where('fname', 'like', $search)
                                ->orWhere('lname', 'like', $search)
                                ->orWhere('email', 'like', $search)
                                ->orWhere('phone', 'like', $search);
                        });
                    });
                }
            ])
            ->get()
            ->map(function ($department) {
                return [
                    'id' => $department->id,
                    'title' => $department->title,
                    'employees' => $department->employees->map(function ($employee) {
                        return [
                            'id' => $employee->id,
                            'fname' => $employee->fname,
                            'lname' => $employee->lname,
                            'email' => $employee->email,
                            'phone' => $employee->phone,
                        ];
                    })->toArray(),
                ];
            })
            ->filter(function ($department) {
                return count($department['employees']) > 0;
            })
            ->values()
            ->toArray();
    }

    protected function rules()
    {
        return [
            'selectedStatus' => 'required',
            'dateRange' => 'required|array',
            'dateRange.start' => [
                'required',
                'date',
                'before_or_equal:' . now()->endOfDay()->format('Y-m-d')
            ],
            'dateRange.end' => [
                'required',
                'date',
                'after_or_equal:dateRange.start',
                'before_or_equal:' . now()->endOfDay()->format('Y-m-d')
            ],
            'selectedEmployees' => [
                'required',
                'array',
                'min:1',
                function ($attribute, $value, $fail) {
                    // Validate all employees in one query
                    $count = Employee::whereIn('id', $value)
                        ->where('firm_id', $this->firm_id)
                        ->count();

                    if ($count !== count($value)) {
                        $fail('One or more selected employees are invalid.');
                    }
                }
            ]
        ];
    }

    protected function messages()
    {
        return [
            'selectedStatus.required' => 'Please select an attendance status',
            'dateRange.required' => 'Please select a date range',
            'dateRange.start.required' => 'Start date is required',
            'dateRange.start.date' => 'Start date must be a valid date',
            'dateRange.start.before_or_equal' => 'Start date cannot be in the future',
            'dateRange.end.required' => 'End date is required',
            'dateRange.end.date' => 'End date must be a valid date',
            'dateRange.end.after_or_equal' => 'End date must be after or equal to start date',
            'dateRange.end.before_or_equal' => 'End date cannot be in the future',
            'selectedEmployees.required' => 'Please select at least one employee',
            'selectedEmployees.min' => 'Please select at least one employee',
            'selectedEmployees.*.exists' => 'One or more selected employees are invalid',
        ];
    }

    // Save bulk attendance for selected employees
    public function saveBulkAttendance()
    {
        if (!$this->firm_id) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Firm ID not found in session'
            );
            return;
        }

        try {
            $validatedData = $this->validate($this->rules(), $this->messages());

            DB::beginTransaction();

            // Create a new batch
            $batch = new Batch();
            $batch->firm_id = $this->firm_id;
            $batch->user_id = auth()->id();
            $batch->modulecomponent = 'hrms.attendance.mark-attendance';
            $batch->action = 'bulk_mark';
            $batch->title = 'Bulk Mark Attendance';
            $batch->save();

            $startDate = Carbon::parse($validatedData['dateRange']['start']);
            $endDate = Carbon::parse($validatedData['dateRange']['end']);

            // Fix: Create date range manually to ensure exact dates
            $dateRange = collect();
            $currentDate = $startDate->copy();

            while ($currentDate->lte($endDate)) {
                $dateRange->push($currentDate->copy());
                $currentDate->addDay();
            }

            $recordsCreated = 0;
            $recordsUpdated = 0;
            $flexiWeekOffsCreated = 0;

            foreach ($validatedData['selectedEmployees'] as $employeeId) {
                foreach ($dateRange as $date) {
                    // Check if attendance already exists
                    $existingAttendance = EmpAttendance::where('employee_id', $employeeId)
                        ->where('firm_id', $this->firm_id)
                        ->whereDate('work_date', $date)
                        ->first();

                    $attendanceData = [
                        'firm_id' => $this->firm_id,
                        'employee_id' => $employeeId,
                        'work_date' => $date->format('Y-m-d'),
                        'attendance_status_main' => $validatedData['selectedStatus'],
                        'ideal_working_hours' => 8, // Default value
                        'actual_worked_hours' => 8, // Default value
                        'final_day_weightage' => 1, // Default value
                    ];

                    if ($existingAttendance) {
                        $oldStatus = $existingAttendance->attendance_status_main;
                        $existingAttendance->update($attendanceData);
                        $attendance = $existingAttendance;
                        $operation = 'update';
                        $recordsUpdated++;

                        // Handle FlexiWeekOff based on new status
                        if ($validatedData['selectedStatus'] === 'POW') {
                            // Create FlexiWeekOff entry if it doesn't exist
                            FlexiWeekOff::firstOrCreate([
                                'firm_id' => $this->firm_id,
                                'employee_id' => $employeeId,
                                'availed_emp_attendance_id' => $attendance->id,
                            ], [
                                'attendance_status_main' => 'W',
                                'consumed_emp_attendance_id' => null,
                                'week_off_Status' => 'A'
                            ]);
                            $flexiWeekOffsCreated++;
                        } 
                        // Delete FlexiWeekOff if status is changed from POW to something else
                        elseif ($oldStatus === 'POW') {
                            FlexiWeekOff::where([
                                'firm_id' => $this->firm_id,
                                'employee_id' => $employeeId,
                                'availed_emp_attendance_id' => $attendance->id
                            ])->delete();
                        }
                    } else {
                        $attendance = EmpAttendance::create($attendanceData);
                        $operation = 'insert';
                        $recordsCreated++;

                        // Create FlexiWeekOff entry if status is POW
                        if ($validatedData['selectedStatus'] === 'POW') {
                            FlexiWeekOff::create([
                                'firm_id' => $this->firm_id,
                                'employee_id' => $employeeId,
                                'attendance_status_main' => 'W',
                                'availed_emp_attendance_id' => $attendance->id,
                                'consumed_emp_attendance_id' => null,
                                'week_off_Status' => 'A'
                            ]);
                            $flexiWeekOffsCreated++;
                        }
                    }

                    // Create batch item for tracking
                    BatchItem::create([
                        'batch_id' => $batch->id,
                        'operation' => $operation,
                        'model_type' => EmpAttendance::class,
                        'model_id' => $attendance->id,
                        'new_data' => json_encode($attendance->toArray())
                    ]);
                }
            }

            DB::commit();

            $totalDays = $dateRange->count();
            $totalEmployees = count($validatedData['selectedEmployees']);

            $this->resetBulkForm();
            $this->modal('mdl-mark-attendance')->close();

            Flux::toast(
                variant: 'success',
                heading: 'Success',
                text: "Attendance marked for {$totalEmployees} employees over {$totalDays} days. Created: {$recordsCreated}, Updated: {$recordsUpdated}, FlexiWeekOffs Created: {$flexiWeekOffsCreated}"
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            $errorMessages = collect($e->validator->errors()->all())->implode("\n");
            Flux::toast(
                variant: 'error',
                heading: 'Validation Error',
                text: $errorMessages
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to mark attendance: ' . $e->getMessage()
            );
        }
    }

    // This method rolls back a batch of attendance records by batch ID
    public function rollbackBatchById($batchId)
    {
        $batch = Batch::with('items')->find($batchId);
        if (!$batch) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'No batch found to rollback'
            );
            return;
        }

        try {
            DB::beginTransaction();

            $items = $batch->items()->orderBy('id', 'desc')->get();
            $deletedCount = 0;
            $deletedFlexiCount = 0;

            foreach ($items as $item) {
                if ($item->model_type === EmpAttendance::class) {
                    // Get the attendance record
                    $attendance = EmpAttendance::find($item->model_id);
                    
                    if ($attendance) {
                        // Delete associated FlexiWeekOff records first
                        $deletedFlexiRecords = FlexiWeekOff::where([
                            'firm_id' => $attendance->firm_id,
                            'employee_id' => $attendance->employee_id,
                            'availed_emp_attendance_id' => $attendance->id
                        ])->delete();
                        
                        $deletedFlexiCount += $deletedFlexiRecords;
                        
                        // Then delete the attendance record
                        $attendance->delete();
                        $deletedCount++;
                    }
                }
            }

            $batch->items()->delete();
            $batch->delete();

            DB::commit();

            Flux::toast(
                variant: 'success',
                heading: 'Success',
                text: "Successfully rolled back $deletedCount attendance records and $deletedFlexiCount flexi week-off records for Batch ID: $batchId"
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Error during rollback: ' . $e->getMessage()
            );
        }
    }

    // This method resets the bulk form fields
    public function resetBulkForm()
    {
        $this->reset(['selectedEmployees', 'selectedStatus', 'employeeSearch']);
        $this->dateRange = [
            'start' => now()->format('Y-m-d'),
            'end' => now()->format('Y-m-d')
        ];
    }

    public function selectAllEmployees($departmentId)
    {
        $department = collect($this->getFilteredDepartmentsWithEmployees())
            ->firstWhere('id', $departmentId);

        if ($department) {
            $employeeIds = collect($department['employees'])->pluck('id')->toArray();
            $this->selectedEmployees = array_unique(array_merge($this->selectedEmployees, $employeeIds));
        }
    }

    public function deselectAllEmployees($departmentId)
    {
        $department = collect($this->getFilteredDepartmentsWithEmployees())
            ->firstWhere('id', $departmentId);

        if ($department) {
            $employeeIds = collect($department['employees'])->pluck('id')->toArray();
            $this->selectedEmployees = array_diff($this->selectedEmployees, $employeeIds);
        }
    }

    public function selectAllEmployeesGlobal()
    {
        $allEmployeeIds = collect($this->getFilteredDepartmentsWithEmployees())
            ->pluck('employees.*.id')
            ->flatten()
            ->toArray();
        $this->selectedEmployees = array_unique($allEmployeeIds);
    }

    public function deselectAllEmployeesGlobal()
    {
        $this->selectedEmployees = [];
    }

    // This method retrieves attendance data based on filters and pagination
    protected function getAttendanceData()
    {
        $employees = Employee::where('firm_id', $this->firm_id)
            ->with(['emp_job_profile.designation', 'emp_job_profile.department'])
            ->when($this->employeeNameFilter, function ($query) {
                $query->where(function ($q) {
                    $search = '%' . $this->employeeNameFilter . '%';
                    $q->where('fname', 'like', $search)
                      ->orWhere('lname', 'like', $search);
                });
            })
            ->when($this->selectedDepartment, function ($query) {
                $query->whereHas('emp_job_profile.department', function ($q) {
                    $q->where('id', $this->selectedDepartment);
                });
            })
            ->paginate(10);

        $attendances = EmpAttendance::where('firm_id', $this->firm_id)
            ->whereIn('employee_id', $employees->pluck('id'))
            ->whereMonth('work_date', $this->month)
            ->whereYear('work_date', $this->year)
            ->get()
            ->groupBy('employee_id');

        $data = [];
        foreach ($employees as $employee) {
            $employeeAttendances = $attendances->get($employee->id, collect());

            $dayWiseAttendance = [];
            for ($day = 1; $day <= $this->daysInMonth; $day++) {
                $date = Carbon::create($this->year, $this->month, $day)->format('Y-m-d');
                $attendance = $employeeAttendances->first(function($record) use ($date) {
                    return Carbon::parse($record->work_date)->format('Y-m-d') == $date;
                });

                $dayWiseAttendance[] = [
                    'day' => $day,
                    'status' => $attendance?->attendance_status_main ?? '',
                    'date' => $date,
                    'employee_id' => $employee->id
                ];
            }

            $data[] = [
                'id' => $employee->id,
                'name' => $employee->fname . ' ' . ($employee->lname ? ' ' . $employee->lname : ''),
                'designation' => $employee->emp_job_profile->designation->title ?? '',
                'department' => $employee->emp_job_profile->department->title ?? '',
                'employee_code' => $employee->emp_job_profile->employee_code ?? '',
                'email' => $employee->email ?? '',
                'phone' => $employee->phone ?? '',
                'attendance' => $dayWiseAttendance,
                'present_count' => $employeeAttendances->whereIn('attendance_status_main', ['P', 'WFR', 'OD'])->count()
            ];
        }

        return [
            'data' => $data,
            'paginator' => $employees
        ];
    }

    // This method shows the attendance details for a specific employee on a specific date
    public function showAttendanceDetails($employeeId, $date)
    {
        $attendance = EmpAttendance::with(['employee'])
            ->where('employee_id', $employeeId)
            ->where('firm_id', $this->firm_id)
            ->whereDate('work_date', $date)
            ->first();

        $this->selectedDate = $date;

        // Get employee details even if attendance doesn't exist
        $employee = Employee::find($employeeId);
        if (!$employee) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Employee not found'
            );
            return;
        }

        if ($attendance) {
            $this->selectedAttendance = $attendance;
            $this->isNewAttendance = false;
            $this->formData = [
                'attendance_status_main' => $attendance->attendance_status_main,
                'ideal_working_hours' => $attendance->ideal_working_hours,
                'actual_worked_hours' => $attendance->actual_worked_hours,
                'final_day_weightage' => $attendance->final_day_weightage,
                'attend_remarks' => $attendance->attend_remarks,
            ];
        } else {
            $this->selectedAttendance = (object)[
                'employee' => $employee,
                'id' => null
            ];
            $this->isNewAttendance = true;
            $this->formData = [
                'attendance_status_main' => '',
                'ideal_working_hours' => 8,
                'actual_worked_hours' => 8,
                'final_day_weightage' => 1,
                'attend_remarks' => '',
            ];
        }

        $this->punches = $attendance ? $this->loadPunches($attendance->id) : [];
        $this->modal('attendance-modal')->show();
    }

    // This method saves the attendance details for the selected employee
    protected function loadPunches($attendanceId)
    {
        $punches = EmpPunch::where('emp_attendance_id', $attendanceId)
            ->with('location')
            ->orderBy('punch_datetime', 'desc')
            ->get()
            ->map(function($punch) {
                $geoLocation = $this->formatGeoLocation($punch->punch_geo_location);

                return [
                    'id' => $punch->id,
                    'datetime' => $punch->punch_datetime,
                    'type' => $punch->in_out,
                    'location' => $punch->location ? $punch->location->title : 'N/A',
                    'is_final' => $punch->is_final,
                    'geo_location' => $geoLocation
                ];
            });

        return $punches;
    }

    // This method formats the geo-location data for display
    protected function formatGeoLocation($geoLocationData)
    {
        if (empty($geoLocationData)) {
            return null;
        }

        if (is_string($geoLocationData)) {
            $geoLocationData = json_decode($geoLocationData, true);
        }

        return [
            'latitude' => $geoLocationData['latitude'] ?? null,
            'longitude' => $geoLocationData['longitude'] ?? null
        ];
    }

    // This method shows the attendance modal for a specific employee on a specific date
    public function showAttendanceModal($employeeId, $date)
    {
        $attendance = EmpAttendance::with(['employee'])
            ->where('employee_id', $employeeId)
            ->where('firm_id', $this->firm_id)
            ->whereDate('work_date', $date)
            ->first();

        $this->selectedDate = $date;

        if ($attendance) {
            $this->selectedAttendance = $attendance;
            $this->isNewAttendance = false;
            $this->formData = [
                'attendance_status_main' => $attendance->attendance_status_main,
                'ideal_working_hours' => $attendance->ideal_working_hours,
                'actual_worked_hours' => $attendance->actual_worked_hours,
                'final_day_weightage' => $attendance->final_day_weightage,
                'attend_remarks' => $attendance->attend_remarks,
            ];
        } else {
            // Get employee details for new attendance
            $employee = Employee::find($employeeId);
            if (!$employee) {
                Flux::toast(
                    variant: 'error',
                    heading: 'Error',
                    text: 'Employee not found'
                );
                return;
            }

            $this->selectedAttendance = (object)[
                'employee' => $employee,
                'id' => null
            ];
            $this->isNewAttendance = true;
            $this->formData = [
                'attendance_status_main' => '',
                'ideal_working_hours' => 8,
                'actual_worked_hours' => 8,
                'final_day_weightage' => 1,
                'attend_remarks' => '',
            ];
        }

        $this->modal('attendance-modal')->show();
    }

    // This method saves the attendance details for the selected employee
    public function saveAttendance()
    {
        // Check if the attendance date is in the future
        if (Carbon::parse($this->selectedDate)->isAfter(now()->endOfDay())) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Cannot mark attendance for future dates'
            );
            return;
        }

        $validatedData = $this->validate([
            'formData.attendance_status_main' => 'required',
            'formData.ideal_working_hours' => 'nullable|numeric',
            'formData.actual_worked_hours' => 'nullable|numeric',
            'formData.final_day_weightage' => 'nullable|numeric',
            'formData.attend_remarks' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            if ($this->isNewAttendance) {
                $attendance = EmpAttendance::create([
                    'firm_id' => $this->firm_id,
                    'employee_id' => $this->selectedAttendance->employee->id,
                    'work_date' => $this->selectedDate,
                    'attendance_status_main' => $validatedData['formData']['attendance_status_main'],
                    'ideal_working_hours' => $validatedData['formData']['ideal_working_hours'],
                    'actual_worked_hours' => $validatedData['formData']['actual_worked_hours'],
                    'final_day_weightage' => $validatedData['formData']['final_day_weightage'],
                    'attend_remarks' => $validatedData['formData']['attend_remarks'],
                ]);

                // Create FlexiWeekOff entry if status is POW
                if ($validatedData['formData']['attendance_status_main'] === 'POW') {
                    FlexiWeekOff::create([
                        'firm_id' => $this->firm_id,
                        'employee_id' => $this->selectedAttendance->employee->id,
                        'attendance_status_main' => 'W',
                        'availed_emp_attendance_id' => $attendance->id,
                        'consumed_emp_attendance_id' => null,
                        'week_off_Status' => 'A'
                    ]);
                }

                Flux::toast(
                    variant: 'success',
                    heading: 'Success',
                    text: 'Attendance marked successfully'
                );
            } else {
                $attendance = EmpAttendance::findOrFail($this->selectedAttendance->id);
                $oldStatus = $attendance->attendance_status_main;

                $attendance->update($validatedData['formData']);

                // Handle FlexiWeekOff based on new status
                if ($validatedData['formData']['attendance_status_main'] === 'POW') {
                    // Create FlexiWeekOff entry if it doesn't exist
                    FlexiWeekOff::firstOrCreate([
                        'firm_id' => $this->firm_id,
                        'employee_id' => $this->selectedAttendance->employee->id,
                        'availed_emp_attendance_id' => $attendance->id,
                    ], [
                        'attendance_status_main' => 'W',
                        'consumed_emp_attendance_id' => null,
                        'week_off_Status' => 'A'
                    ]);
                } 
                // Delete FlexiWeekOff if status is changed from POW to something else
                elseif ($oldStatus === 'POW') {
                    FlexiWeekOff::where([
                        'firm_id' => $this->firm_id,
                        'employee_id' => $this->selectedAttendance->employee->id,
                        'availed_emp_attendance_id' => $attendance->id
                    ])->delete();
                }

                Flux::toast(
                    variant: 'success',
                    heading: 'Success',
                    text: 'Attendance updated successfully'
                );
            }

            DB::commit();
            $this->closeAttendanceModal();
        } catch (\Exception $e) {
            DB::rollBack();
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to save attendance: ' . $e->getMessage()
            );
        }
    }

    public function closeAttendanceModal()
    {
        $this->resetForm();
        $this->modal('attendance-modal')->close();
    }

    public function resetForm()
    {
        $this->formData = [
            'attendance_status_main' => '',
            'ideal_working_hours' => 8,
            'actual_worked_hours' => 8,
            'final_day_weightage' => 1,
            'attend_remarks' => '',
        ];
        $this->isEditing = false;
        $this->editAttendanceId = null;
        $this->selectedAttendance = null;
        $this->resetValidation();
    }

    // Update the cell click handler to show view modal instead of edit
    public function updatedCell($employeeId, $date)
    {
        $this->showAttendanceDetails($employeeId, $date);
    }

    // This method loads the batches for bulk attendance marking
    protected function loadBatches()
    {
        $this->batches = Batch::where('modulecomponent', 'hrms.attendance.mark-attendance')
            ->where('action', 'bulk_mark')
            ->where('firm_id', $this->firm_id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function selectBatch($batchId)
    {
        $this->selectedBatchId = $batchId;
        $this->selectedBatch = Batch::with('items')->find($batchId);
    }

    public function updatedEmployeeNameFilter()
    {
        $this->resetPage();
    }

    public function updatedSelectedDepartment()
    {
        $this->resetPage();
    }

    public function getDepartmentsProperty()
    {
        return Department::where('firm_id', $this->firm_id)
            ->whereHas('employee_job_profiles', function($query) {
                $query->whereHas('employee', function($q) {
                    $q->where('firm_id', $this->firm_id);
                });
            })
            ->orderBy('title')
            ->get();
    }

    public function updatePunches($employeeId, $workDate)
    {
        if (!in_array($this->formData['attendance_status_main'], $this->allowedPunchStatuses)) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Punches can only be added for Present, Half Day, Partial Working, Work from Remote, Compensatory Work, or On Duty status'
            );
            return;
        }

        $this->resetPunchForm();
        $this->isEditingPunch = false;
        $this->selectedPunchId = null;

        // Set current time by default
        $this->punchForm['punch_time'] = Carbon::now()->format('H:i');

        $this->modal('punch-modal')->show();
    }

    public function editPunch($punchId)
    {
        if (!in_array($this->formData['attendance_status_main'], $this->allowedPunchStatuses)) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Punches can only be edited for Present, Half Day, Partial Working, Work from Remote, Compensatory Work, or On Duty status'
            );
            return;
        }

        $punch = EmpPunch::findOrFail($punchId);
        $punchDateTime = Carbon::parse($punch->punch_datetime);

        $this->isEditingPunch = true;
        $this->selectedPunchId = $punchId;
        $this->punchForm = [
            'in_out' => $punch->in_out,
            'punch_time' => $punchDateTime->format('H:i'),
        ];

        $this->modal('punch-modal')->show();
    }

    // This method saves the punch data
    public function savePunch()
    {
        if (!in_array($this->formData['attendance_status_main'], $this->allowedPunchStatuses)) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Punches can only be saved for Present, Half Day, Partial Working, Work from Remote, Compensatory Work, or On Duty status'
            );
            return;
        }

        $this->validate([
            'punchForm.in_out' => 'required|in:in,out',
            'punchForm.punch_time' => 'required',
        ]);

        try {
            // Use the selected date with the new time
            $punchDateTime = Carbon::parse($this->selectedDate)->setTimeFromTimeString($this->punchForm['punch_time']);

            $punchData = [
                'firm_id' => $this->firm_id,
                'emp_attendance_id' => $this->selectedAttendance->id,
                'employee_id' => $this->selectedAttendance->employee_id,
                'work_date' => Carbon::parse($this->selectedDate)->format('Y-m-d'),
                'in_out' => $this->punchForm['in_out'],
                'punch_datetime' => $punchDateTime,
            ];

            if ($this->isEditingPunch) {
                $punch = EmpPunch::findOrFail($this->selectedPunchId);
                $punch->update($punchData);
                $message = 'Punch updated successfully';
            } else {
                EmpPunch::create($punchData);
                $message = 'Punch added successfully';
            }

            $this->closePunchModal();

            // Refresh the punches list
            $this->punches = $this->loadPunches($this->selectedAttendance->id);

            Flux::toast(
                variant: 'success',
                heading: 'Success',
                text: $message
            );
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to save punch: ' . $e->getMessage()
            );
        }
    }

    public function closePunchModal()
    {
        $this->resetPunchForm();
        $this->modal('punch-modal')->close();
    }

    protected function resetPunchForm()
    {
        $this->punchForm = [
            'in_out' => '',
            'punch_time' => '',
        ];
        $this->isEditingPunch = false;
        $this->selectedPunchId = null;
    }

    public function render()
    {
        $attendanceData = $this->getAttendanceData();

        return view()->file(app_path('Livewire/Hrms/Attendance/blades/view-emp-attendances.blade.php'), [
            'attendanceData' => $attendanceData['data'],
            'employees' => $attendanceData['paginator'],
            'departments' => $this->getFilteredDepartmentsWithEmployees(),
            'batches' => $this->batches
        ]);
    }
}