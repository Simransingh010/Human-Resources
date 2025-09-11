<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\PayrollSlot;
use App\Models\Hrms\PayrollSlotsCmd;
use App\Models\Hrms\PayrollStep;
use App\Models\Hrms\PayrollStepPayrollSlot;
use App\Models\Hrms\SalaryComponentsEmployee;
use App\Models\Hrms\SalaryCycle;
use App\Models\Hrms\SalaryExecutionGroup;
use App\Models\Hrms\EmployeesSalaryExecutionGroup;
use App\Models\Hrms\EmployeesSalaryDay;
use App\Models\Hrms\PayrollComponentsEmployeesTrack;
use App\Models\Hrms\EmployeeTaxRegime;
use App\Models\Hrms\SalaryComponent;
use App\Models\Hrms\EmpAttendance;
use App\Models\Hrms\PayrollStepPayrollSlotCmd;
use App\Models\Hrms\SalaryHold;
use App\Models\Hrms\SalaryAdvance;
use App\Models\Hrms\SalaryArrear;
use App\Models\Hrms\EmployeeExit;
use App\Models\Hrms\FinalSettlement;
use App\Models\Hrms\FinalSettlementItem;
use App\Models\Hrms\EmpLeaveBalance;
use App\Models\Hrms\FlexiWeekOff;
use App\Models\Hrms\LeaveType;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollCycles extends Component
{
    public $payrollCycles;
    public $executionGroups = [];
    public $selectedCycleId;
    public $selectedGroupId;
    public $cycleId;
    public $payrollSlots = [];
    public $payrollSlotDetails;
    public $payrollSlotCmds = [];
    public $selectedSlotId;
    public $selectedStepId = null;
    public $payrollSteps = [];
    public $selectedEmployees = [];
    public $stepLogs;
    public $lockConfirmation = '';

    protected $rules = [
        'lockConfirmation' => 'required|in:LOCK'
    ];

    protected $messages = [
        'lockConfirmation.required' => 'Please type LOCK to confirm.',
        'lockConfirmation.in' => 'Please type LOCK in capital letters exactly.'
    ];

    public function mount()
    {
        // dd(Session::get('LOP_deduction_type'));
        $this->payrollCycles = SalaryCycle::where('firm_id', Session::get('firm_id'))->get();
    }

    public function updatedSelectedCycleId($value)
    {
        $this->loadExecutionGroups($value);
    }

    //    public function updatedSelectedGroupId($value)
//    {
//        $this->loadPayrollSlots($value);
//    }

    protected function loadExecutionGroups($cycleId)
    {
        $query = SalaryExecutionGroup::where('firm_id', Session::get('firm_id'))
            ->where('salary_cycle_id', $cycleId)
            ->where('is_inactive', false);
        $this->executionGroups = $query->get()->toArray();
    }

    public function loadPayrollSlots($groupId)
    {
        $this->selectedGroupId = $groupId;
        
        // Clear previous slot details and logs when changing groups
        $this->payrollSlotDetails = null;
        $this->payrollSlotCmds = [];
        $this->payrollSteps = [];
        $this->selectedSlotId = null;
        
        $query = PayrollSlot::where('firm_id', Session::get('firm_id'))
            ->where('salary_cycle_id', $this->selectedCycleId)
            ->where('salary_execution_group_id', $groupId);
        $this->payrollSlots = $query->orderBy('from_date', 'asc')->get();
        //        dd($this->payrollSlots);
    }

    public function startPayroll($slot_id)
    {
        try {
            DB::transaction(function () use ($slot_id) {
            // Get all active payroll steps
            $payrollSteps = PayrollStep::where('firm_id', Session::get('firm_id'))
                ->where('is_inactive', false)
                ->orderBy('step_order')
                ->get();

            // Store each step in PayrollStepPayrollSlot
            foreach ($payrollSteps as $payrollStep) {
                PayrollStepPayrollSlot::updateOrCreate(
                    [
                        'firm_id' => Session::get('firm_id'),
                        'payroll_slot_id' => $slot_id,
                        'payroll_step_id' => $payrollStep->id,
                    ],
                    [
                        'step_code_main' => $payrollStep->step_code_main,
                        'payroll_step_status' => 'NS',
                    ]
                );
            }

            // Fetch all employees of the execution group for this slot and dd them
            $payrollSlot = PayrollSlot::findOrFail($slot_id);
            $employeeIds = EmployeesSalaryExecutionGroup::where('firm_id', Session::get('firm_id'))
                ->where('salary_execution_group_id', $payrollSlot->salary_execution_group_id)
                ->whereHas('employee', function ($q) {
                    $q->where('is_inactive', false);
                })
                ->pluck('employee_id')
                ->toArray();
//            dd($employeeIds);

            // Check for employees on salary hold and create hold entries
            $this->processSalaryHoldsAtStart($slot_id);
//            Full: 11,000.00, Effective Working Days: 30, Effective Deduction Days: 0, Deduction: 0.00, Effective From: 2025-04-01
            // Create initial command log
            PayrollSlotsCmd::create([
                'firm_id' => Session::get('firm_id'),
                'payroll_slot_id' => $slot_id,
                'user_id' => auth()->user()->id,
                'run_payroll_remarks' => 'Started Payroll',
                'payroll_slot_status' => 'ST',
            ]);

            // Update payroll slot status
            PayrollSlot::where('id', $slot_id)
                ->update(['payroll_slot_status' => 'ST']);
            }); // Transaction automatically committed here if successful

            Flux::toast(
                variant: 'success',
                heading: 'Changes saved.',
                text: 'Payroll Started',
            );

            // Refresh slot details
            $this->loadSlotDetails($slot_id);

        } catch (\Exception $e) {
            // Transaction was automatically rolled back
            Log::error('Payroll start failed for slot ' . $slot_id . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'firm_id' => Session::get('firm_id')
            ]);
            
            Flux::toast(
                variant: 'error',
                heading: 'Critical Error',
                text: 'Failed to start payroll. All changes have been reverted. Error: ' . $e->getMessage(),
            );
        }
    }

    public function loadSlotDetails($slotId)
    {
        $this->payrollSlotCmds = PayrollSlotsCmd::where('firm_id', Session::get('firm_id'))
            ->where('payroll_slot_id', $slotId)
            ->get();

        $this->payrollSlotDetails = PayrollSlot::where('firm_id', Session::get('firm_id'))
            ->where('id', $slotId)
            ->first();

        // Fetch payroll steps from PayrollStepPayrollSlot with proper join
        $this->payrollSteps = PayrollStepPayrollSlot::where('payroll_step_payroll_slot.firm_id', Session::get('firm_id'))
            ->where('payroll_step_payroll_slot.payroll_slot_id', $slotId)
            ->join('payroll_steps', 'payroll_step_payroll_slot.payroll_step_id', '=', 'payroll_steps.id')
            ->select('payroll_step_payroll_slot.*', 'payroll_steps.*')
            ->orderBy('payroll_steps.step_order')
            ->get()
            ->map(function ($stepSlot) {
                // Merge the PayrollStep data with the PayrollStepPayrollSlot data
                return (object)array_merge(
                    [
                        'id' => $stepSlot->payroll_step_id,
                        'step_code_main' => $stepSlot->step_code_main,
                        'step_title' => $stepSlot->step_title,
                        'step_desc' => $stepSlot->step_desc,
                        'required' => $stepSlot->required,
                        'step_order' => $stepSlot->step_order,
                        'is_inactive' => $stepSlot->is_inactive,
                    ],
                    ['status' => $stepSlot->payroll_step_status]
                );
            });
    }

    public function restartPayroll($slot_id)
    {
        try {
            DB::transaction(function () use ($slot_id) {
            // Get all active payroll steps
            $payrollSteps = PayrollStep::where('firm_id', Session::get('firm_id'))
                ->where('is_inactive', false)
                ->orderBy('step_order')
                ->get();

            // Store each step in PayrollStepPayrollSlot
            foreach ($payrollSteps as $payrollStep) {
                // Will only create New record if any New Payroll Step is introduced otherwise will be skipped
                PayrollStepPayrollSlot::firstOrCreate(
                    [
                        'firm_id' => Session::get('firm_id'),
                        'payroll_slot_id' => $slot_id,
                        'payroll_step_id' => $payrollStep->id,
                    ],
                    [
                        'step_code_main' => $payrollStep->step_code_main,
                        'payroll_step_status' => 'NS',
                    ]
                );
            }

            // Check for employees on salary hold and create hold entries
            $this->processSalaryHoldsAtStart($slot_id);

            PayrollSlotsCmd::create([
                'firm_id' => Session::get('firm_id'),
                'payroll_slot_id' => $slot_id,
                'user_id' => auth()->user()->id,
                'run_payroll_remarks' => 'Re-Started Payroll',
                'payroll_slot_status' => 'RS',
            ]);

            // Update payroll slot status
            PayrollSlot::where('id', $slot_id)
                ->update(['payroll_slot_status' => 'RS']);
            }); // Transaction automatically committed here if successful

            Flux::toast(
                variant: 'success',
                heading: 'Changes saved.',
                text: 'Payroll Re-Started',
            );

            // Refresh slot details
            $this->loadSlotDetails($slot_id);

            $this->modal('mdl-re-start-payroll')->close();

        } catch (\Exception $e) {
            // Transaction was automatically rolled back
            Log::error('Payroll restart failed for slot ' . $slot_id . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'firm_id' => Session::get('firm_id')
            ]);
            
            Flux::toast(
                variant: 'error',
                heading: 'Critical Error',
                text: 'Failed to restart payroll. All changes have been reverted. Error: ' . $e->getMessage(),
            );
        }
    }

    public function completePayroll($slot_id, $cycle_id, $execution_group_id)
    {
        try {
            DB::transaction(function () use ($slot_id, $cycle_id, $execution_group_id) {

            // Create completion command log first as we have to update the payroll_slots_cmd_id  in table payroll_components_employees_tracks
            $payroll_slots_cmd_rec = PayrollSlotsCmd::create([
                'firm_id' => Session::get('firm_id'),
                'payroll_slot_id' => $slot_id,
                'user_id' => auth()->user()->id,
                'run_payroll_remarks' => 'Completed Payroll',
                'payroll_slot_status' => 'CM',
            ]);

            $this->createPayrollTracks($slot_id, $cycle_id, $execution_group_id, $payroll_slots_cmd_rec->id);

            // --- NEW: Process salary arrears for all active employees in this slot ---
            $payrollSlot = PayrollSlot::findOrFail($slot_id);

            $employeeIds = EmployeesSalaryExecutionGroup::where('firm_id', Session::get('firm_id'))
                ->where('salary_execution_group_id', $execution_group_id)
                ->whereHas('employee', function ($q) {
                    $q->where('is_inactive', false);
                })
                ->pluck('employee_id')
                ->toArray();

            // Exclude employees on salary hold for this slot
            $employeesOnHold = SalaryHold::where('firm_id', Session::get('firm_id'))
                ->where('payroll_slot_id', $slot_id)
                ->pluck('employee_id')
                ->toArray();
            $activeEmployeeIds = array_diff($employeeIds, $employeesOnHold);
                // Process salary advances for all active employees in this slot
                $this->processSalaryAdvances($slot_id, $activeEmployeeIds, $payrollSlot);
                // After creating advance tracks for this slot, settle/advance the advances ledger
                $this->settleAdvancesForSlot($slot_id);
                
                // Process salary arrears for all active employees in this slot
                $this->processSalaryArrears($slot_id, $activeEmployeeIds, $payrollSlot);
                // After creating arrear tracks for this slot, settle/advance the arrears ledger
               
            // --- END NEW ---

            // Then update payroll steps and status
            $payrollsetps = PayrollStep::where('firm_id', Session::get('firm_id'))
                ->get();

            foreach ($payrollsetps as $payrollstep) {
                PayrollStepPayrollSlot::updateOrCreate(
                    [
                        'firm_id' => Session::get('firm_id'),
                        'payroll_slot_id' => $slot_id,
                        'payroll_step_id' => $payrollstep->id
                    ],
                    [
                        'step_code_main' => $payrollstep->step_code_main,
                        'payroll_step_status' => 'CM' // Set as completed
                    ]
                );
            }

            // Update payroll slot status
            PayrollSlot::updateOrCreate(
                [
                    'firm_id' => Session::get('firm_id'),
                    'id' => $slot_id,
                ],
                [
                    'payroll_slot_status' => 'CM',
                ]
            );
            }); // Transaction automatically committed here if successful

            // Refresh slot details
            $this->loadSlotDetails($slot_id);

            Flux::toast(
                variant: 'success',
                heading: 'Payroll Completed',
                text: 'Payroll Completed Successfully',
            );

        } catch (\Exception $e) {
            // Transaction was automatically rolled back
            Log::error('Payroll completion failed for slot ' . $slot_id . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'firm_id' => Session::get('firm_id')
            ]);
            
            Flux::toast(
                variant: 'error',
                heading: 'Critical Error',
                text: 'Failed to complete payroll. All changes have been reverted. Error: ' . $e->getMessage(),
            );
        }
    }

    protected function createPayrollTracks($slot_id, $cycle_id, $execution_group_id, $payroll_slots_cmd_id)
    {
        try {

            // Get the payroll slot
            $payrollSlot = PayrollSlot::findOrFail($slot_id);

            // 0. Idempotent recompute: purge system-generated tracks for this slot (preserve overrides)
            PayrollComponentsEmployeesTrack::where('firm_id', Session::get('firm_id'))
                ->where('payroll_slot_id', $slot_id)
                ->where(function ($query) {
                    $query->where('entry_type', '!=', 'override')
                        ->orWhereNull('entry_type');
                })
                ->delete();

            // Get employees from the selected execution group
            $employeeIds = EmployeesSalaryExecutionGroup::where('firm_id', Session::get('firm_id'))
                ->where('salary_execution_group_id', $execution_group_id)
                ->whereHas('employee', function ($q) {
                    $q->where('is_inactive', false);
                })
                ->pluck('employee_id');

            // Exclude employees who are in exit process (any active EmployeeExit record)
            $employeesOnExit = EmployeeExit::where('firm_id', Session::get('firm_id'))
                ->whereIn('employee_id', $employeeIds)
                ->pluck('employee_id');
            $employeeIds = $employeeIds->diff($employeesOnExit);


            // Get employees on salary hold for this payroll slot
            $employeesOnHold = SalaryHold::where('firm_id', Session::get('firm_id'))
                ->where('payroll_slot_id', $slot_id)
                ->pluck('employee_id')
                ->toArray();
                

            // Filter out employees on hold
            $activeEmployeeIds = $employeeIds->diff($employeesOnHold);

            // Calculate cycle days from slot dates (inclusive)
            $fromDate = Carbon::parse($payrollSlot->from_date);
            $toDate = Carbon::parse($payrollSlot->to_date);
            $cycleDays = $fromDate->diffInDays($toDate) + 1;

            // 1. Fetch ALL data needed in bulk BEFORE the loop to eliminate N+1 queries
            $allAssignedComponents = SalaryComponentsEmployee::where('firm_id', Session::get('firm_id'))
                ->whereIn('employee_id', $activeEmployeeIds)
                    ->where(function ($query) use ($payrollSlot) {
                        $query->where(function ($q) use ($payrollSlot) {
                            $q->where('effective_from', '<=', $payrollSlot->to_date)
                                ->where(function ($q2) use ($payrollSlot) {
                                    $q2->whereNull('effective_to')
                                        ->orWhere('effective_to', '>=', $payrollSlot->from_date);
                                });
                        });
                    })
                ->get()
                ->groupBy('employee_id');

            $allSalaryDays = EmployeesSalaryDay::where('firm_id', Session::get('firm_id'))
                    ->where('payroll_slot_id', $slot_id)
                ->whereIn('employee_id', $activeEmployeeIds)
                ->get()
                ->keyBy('employee_id');

                

            $allStaticUnknownComponents = SalaryComponentsEmployee::where('firm_id', Session::get('firm_id'))
                ->whereIn('employee_id', $activeEmployeeIds)
                        ->where('amount_type', 'static_unknown')
                        ->where(function ($query) use ($payrollSlot) {
                            $query->where(function ($q) use ($payrollSlot) {
                                $q->where('effective_from', '<=', $payrollSlot->to_date)
                                    ->where(function ($q2) use ($payrollSlot) {
                                        $q2->whereNull('effective_to')
                                            ->orWhere('effective_to', '>=', $payrollSlot->from_date);
                                    });
                            });
                        })
                        ->with('salary_component')
                ->get()
                ->groupBy('employee_id');

            $allExistingTracks = PayrollComponentsEmployeesTrack::where('firm_id', Session::get('firm_id'))
                            ->where('payroll_slot_id', $slot_id)
                ->whereIn('employee_id', $activeEmployeeIds)
                ->get()
                ->groupBy('employee_id');

            // Pre-fetch LOP deduction component to avoid N+1 queries
            $lopDeductionComponent = $this->getLopDeductionComponent();
            

            foreach ($activeEmployeeIds as $employeeId) {
                // If employee has Final Settlement to be disbursed in this slot, generate FNF tracks only
                $fnf = FinalSettlement::where('firm_id', Session::get('firm_id'))
                    ->where('employee_id', $employeeId)
                    ->where('disburse_payroll_slot_id', $slot_id)
                    ->first();

                if ($fnf) {
                    $this->createFinalSettlementTracks($fnf, $slot_id, $payroll_slots_cmd_id, $payrollSlot, $cycle_id);
                    continue; // Skip regular component processing
                }
                // Check if employee belongs to the payroll slot's execution group
                if (!$this->isEmployeeInSlotExecutionGroup($employeeId, $payrollSlot)) {
                    continue;
                }

                // Use pre-fetched data instead of individual queries
                $assignedComponents = $allAssignedComponents->get($employeeId, collect());
                $salaryDays = $allSalaryDays->get($employeeId);
                $staticUnknownComponents = $allStaticUnknownComponents->get($employeeId, collect());
                $existingTracks = $allExistingTracks->get($employeeId, collect());

                // DD for employee 1461 - Step 1: Initial data
                // if ($employeeId == 1461) {
                //     dd('Step 1: Initial data for employee 1461', [
                //         'employee_id' => $employeeId,
                //         'assigned_components' => $assignedComponents->toArray(),
                //         'salary_days' => $salaryDays ? $salaryDays->toArray() : null,
                //         'static_unknown_components' => $staticUnknownComponents->toArray(),
                //         'existing_tracks' => $existingTracks->toArray(),
                //         'lop_deduction_type' => session('LOP_deduction_type'),
                //         'payroll_slot' => $payrollSlot->toArray()
                //     ]);
                // }

                // Process static unknown components
                $this->processStaticUnknownComponents(
                    $staticUnknownComponents, 
                    $existingTracks, 
                    $slot_id, 
                    $employeeId, 
                    $payroll_slots_cmd_id, 
                    $payrollSlot, 
                    $cycle_id
                );

                // Process LOP deduction if applicable
                $this->processLopDeduction(
                    $assignedComponents,
                    $salaryDays,
                    $lopDeductionComponent,
                    $existingTracks,
                    $slot_id,
                    $employeeId,
                    $payroll_slots_cmd_id,
                    $payrollSlot,
                    $cycle_id,
                    $cycleDays
                );

                // Process regular salary components
                $this->processSalaryComponents(
                    $assignedComponents,
                    $salaryDays,
                    $existingTracks,
                    $slot_id,
                    $employeeId,
                    $payroll_slots_cmd_id,
                    $payrollSlot,
                    $cycle_id,
                    $cycleDays
                );

                // Calculate and create tax component
                $this->calculateAndCreateTaxComponent($employeeId, $slot_id, $cycle_id, $payrollSlot, $payroll_slots_cmd_id, $allExistingTracks);
            }

        } catch (\Exception $e) {
            throw new \Exception("Error creating payroll tracks: " . $e->getMessage());
        }
    }

    /**
     * Create payroll tracks for Final Settlement for a specific slot
     */
    protected function createFinalSettlementTracks(FinalSettlement $fnf, $slotId, $payrollSlotsCmdId, $payrollSlot, $cycleId): void
    {
        $items = FinalSettlementItem::where('final_settlement_id', $fnf->id)
            ->where('firm_id', Session::get('firm_id'))
            ->get();

        foreach ($items as $item) {
            // Fetch component meta if available to populate fields
            $component = SalaryComponent::where('firm_id', Session::get('firm_id'))
                ->where('id', $item->salary_component_id)
                ->first();

            PayrollComponentsEmployeesTrack::updateOrCreate(
                [
                    'firm_id' => Session::get('firm_id'),
                    'payroll_slot_id' => $slotId,
                    'employee_id' => $fnf->employee_id,
                    'salary_component_id' => $item->salary_component_id,
                    // Key by FNF item to avoid duplication on reruns
                    'remarks' => 'FNF Item ID: ' . $item->id,
                ],
                [
                    'payroll_slots_cmd_id' => $payrollSlotsCmdId,
                    'salary_template_id' => $component->salary_template_id ?? null,
                    'salary_component_group_id' => $component->salary_component_group_id ?? null,
                    'sequence' => 1,
                    'nature' => $item->nature ?? ($component->nature ?? 'earning'),
                    'component_type' => 'final_settlement',
                    'amount_type' => $component->amount_type ?? 'static_known',
                    'taxable' => (bool) ($component->taxable ?? true),
                    'calculation_json' => $component->calculation_json ?? null,
                    'salary_period_from' => $payrollSlot->from_date,
                    'salary_period_to' => $payrollSlot->to_date,
                    'user_id' => Session::get('user_id'),
                    'amount_full' => (float) $item->amount,
                    'amount_payable' => (float) $item->amount,
                    'amount_paid' => 0,
                    'salary_advance_id' => null,
                    'salary_arrear_id' => null,
                    'salary_cycle_id' => $cycleId,
                    'entry_type' => 'system'
                ]
            );
        }
    }

    /**
     * Get LOP deduction component for the firm
     */
    protected function getLopDeductionComponent()
    {
        return SalaryComponent::where('firm_id', Session::get('firm_id'))
            ->where('component_type', 'lop_deduction')
                            ->first();
    }

    /**
     * Process static unknown components
     */
    protected function processStaticUnknownComponents($staticUnknownComponents, $existingTracks, $slot_id, $employeeId, $payroll_slots_cmd_id, $payrollSlot, $cycle_id)
    {
        foreach ($staticUnknownComponents as $staticComponent) {
            $existingTrack = $existingTracks->where('salary_component_id', $staticComponent->salary_component_id)->first();

                        // If a track exists and has any non-zero value, do not change it
                        if ($existingTrack && (
                                $existingTrack->amount_full != 0 ||
                                $existingTrack->amount_payable != 0 ||
                                $existingTrack->amount_paid != 0
                            )) {
                            continue;
                        }

            // Check if a record already exists for this unique combination
            $existingStaticTrack = PayrollComponentsEmployeesTrack::where('firm_id', Session::get('firm_id'))
                ->where('payroll_slot_id', $slot_id)
                ->where('employee_id', $employeeId)
                ->where('salary_component_id', $staticComponent->salary_component_id)
                ->first();
            
            // If not exists or all values are zero, create with zero values
            if (!$existingStaticTrack) {
                PayrollComponentsEmployeesTrack::create([
                                'firm_id' => Session::get('firm_id'),
                                'payroll_slot_id' => $slot_id,
                                'employee_id' => $employeeId,
                    'salary_component_id' => $staticComponent->salary_component_id,
                                'payroll_slots_cmd_id' => $payroll_slots_cmd_id,
                                'salary_template_id' => $staticComponent->salary_template_id,
                                'salary_component_group_id' => $staticComponent->salary_component_group_id,
                                'sequence' => $staticComponent->sequence,
                                'nature' => $staticComponent->nature,
                                'component_type' => $staticComponent->component_type,
                                'amount_type' => 'static_unknown',
                                'taxable' => $staticComponent->taxable,
                                'calculation_json' => $staticComponent->calculation_json,
                                'salary_period_from' => $payrollSlot->from_date,
                                'salary_period_to' => $payrollSlot->to_date,
                                'user_id' => Session::get('user_id'),
                                'amount_full' => 0,
                                'amount_payable' => 0,
                                'amount_paid' => 0,
                                'salary_advance_id' => null,
                                'salary_arrear_id' => null,
                                'salary_cycle_id' => $cycle_id,
                                'entry_type' => 'system'
                ]);
            }
        }
    }

    /**
     * Process LOP deduction for an employee
     */
    protected function processLopDeduction($assignedComponents, $salaryDays, $lopDeductionComponent, $existingTracks, $slot_id, $employeeId, $payroll_slots_cmd_id, $payrollSlot, $cycle_id, $cycleDays)
    {
       
        // Only process if LOP deduction is calculation-wise
        // When LOP_deduction_type is NOT calculation_wise, LOP deductions are applied 
        // directly to individual salary components in calculateComponentAmount method
        if (session('LOP_deduction_type') !== 'calculation_wise') {
            return;
        }

        $lopDays = $salaryDays->lop_days_count ?? 0;
        
        // Skip if no LOP days or no LOP component
        if ($lopDays <= 0 || !$lopDeductionComponent) {
            return;
        }

        // Calculate gross salary (sum of all earning components for this employee in this slot)
        // Exclude components that have an override entry for this slot/employee
        // Note: When using calculation_wise, we use the full amounts without any LOP deductions for non-overridden components
        $grossSalary = $assignedComponents
            ->filter(function ($component) use ($existingTracks) {
                if ($component->nature !== 'earning') {
                    return false;
                }
                $overrideTrack = $existingTracks
                    ->where('salary_component_id', $component->salary_component_id)
                    ->where('entry_type', 'override')
                    ->first();
                return !$overrideTrack; // include only if not overridden
            })
            ->sum('amount');
        
        // Check if LOP deduction record already exists
        $existingLopTrack = $existingTracks->where('salary_component_id', $lopDeductionComponent->id)->first();
        
        if ($existingLopTrack) {
            return; // Skip if already exists
        }

        $lopDeductionAmount = $this->calculateSimpleLopDeduction($grossSalary, $cycleDays, $lopDays);
        
        // Create LOP deduction track
        PayrollComponentsEmployeesTrack::create([
            'firm_id' => Session::get('firm_id'),
            'payroll_slot_id' => $slot_id,
            'employee_id' => $employeeId,
            'salary_component_id' => $lopDeductionComponent->id,
            'payroll_slots_cmd_id' => $payroll_slots_cmd_id,
            'salary_template_id' => $lopDeductionComponent->salary_template_id ?? null,
            'salary_component_group_id' => $lopDeductionComponent->salary_component_group_id ?? null,
            'sequence' => 1,
            'nature' => 'deduction',
            'component_type' => 'lop_deduction',
            'amount_type' => 'calculated_known',
            'taxable' => false,
            'calculation_json' => null,
            'salary_period_from' => $payrollSlot->from_date,
            'salary_period_to' => $payrollSlot->to_date,
            'user_id' => Session::get('user_id'),
            'amount_full' => $lopDeductionAmount,
            'amount_payable' => $lopDeductionAmount,
            'amount_paid' => 0,
            'salary_advance_id' => null,
            'salary_arrear_id' => null,
            'salary_cycle_id' => $cycle_id,
            'remarks' => 'LOP deduction (calculation wise)',
            'entry_type' => 'system'
        ]);
    }

    /**
     * Process regular salary components
     */
    protected function processSalaryComponents($assignedComponents, $salaryDays, $existingTracks, $slot_id, $employeeId, $payroll_slots_cmd_id, $payrollSlot, $cycle_id, $cycleDays)
    {
        foreach ($assignedComponents as $assignedComponent) {
            // DD for employee 1461 - Step 2: Processing each component
            // if ($employeeId == 1461) {
            //     dd('Step 2: Processing component for employee 1461', [
            //         'component_id' => $assignedComponent->salary_component_id,
            //         'component_title' => $assignedComponent->salary_component->title ?? 'Unknown',
            //         'amount' => $assignedComponent->amount,
            //         'amount_type' => $assignedComponent->amount_type,
            //         'component_type' => $assignedComponent->component_type,
            //         'nature' => $assignedComponent->nature,
            //         'effective_from' => $assignedComponent->effective_from,
            //         'employee_id' => $employeeId
            //     ]);
            // }

            // Skip TDS and static_unknown/calculated_unknown components
            if ($this->shouldSkipComponent($assignedComponent)) {
                continue;
            }
            

            // Skip if override entry exists
            if ($this->hasOverrideEntry($assignedComponent, $slot_id, $employeeId)) {
                continue;
            }
            // if($employeeId == 1461){ dd($assignedComponents->toArray()); }
        

            // Skip if track already exists
            $existingTrack = $existingTracks->where('salary_component_id', $assignedComponent->salary_component_id)->first();
            if ($existingTrack) {
                continue;
            }
// dd($salaryDays,$payrollSlot,$cycleDays);
                    // Calculate component amount considering LOP and void days
            
            $deductionDetails = $this->calculateAdvancedDeductionsByDates($salaryDays, $payrollSlot, $cycleDays);
//            dd($deductionDetails);
         
//            dd($deductionDetails);
            $componentCalculation = $this->calculateComponentAmount(
                $assignedComponent,
                $deductionDetails,
                $cycleDays,
                $payrollSlot,
                session('LOP_deduction_type') === 'calculation_wise'
            );

            // DD for employee 1461 - Step 3: Component calculation result
            // if ($employeeId == 1461) {
            //     dd('Step 3: Component calculation result for employee 1461', [
            //         'component_id' => $assignedComponent->salary_component_id,
            //         'component_title' => $assignedComponent->salary_component->title ?? 'Unknown',
            //         'original_amount' => $assignedComponent->amount,
            //         'deduction_details' => $deductionDetails,
            //         'component_calculation' => $componentCalculation,
            //         'lop_deduction_type' => session('LOP_deduction_type'),
            //         'ignoreLopForEarnings' => session('LOP_deduction_type') === 'calculation_wise'
            //     ]);
            // }

                    $precision = (int) session('roundoff_precision', 0);
                    $mode = (int) session('roundoff_mode', PHP_ROUND_HALF_UP);

            PayrollComponentsEmployeesTrack::create([
                            'firm_id' => Session::get('firm_id'),
                            'payroll_slot_id' => $slot_id,
                            'employee_id' => $employeeId,
                'salary_component_id' => $assignedComponent->salary_component_id,
                            'payroll_slots_cmd_id' => $payroll_slots_cmd_id,
                            'salary_template_id' => $assignedComponent->salary_template_id,
                            'salary_component_group_id' => $assignedComponent->salary_component_group_id,
                            'sequence' => $assignedComponent->sequence,
                            'nature' => $assignedComponent->nature,
                            'component_type' => $assignedComponent->component_type,
                            'amount_type' => $assignedComponent->amount_type,
                            'taxable' => $assignedComponent->taxable,
                            'calculation_json' => $assignedComponent->calculation_json,
                            'salary_period_from' => $payrollSlot->from_date,
                            'salary_period_to' => $payrollSlot->to_date,
                            'user_id' => Session::get('user_id'),
                            'amount_full' => round($componentCalculation['full_amount'], $precision, $mode),
                            'amount_payable' => round($componentCalculation['payable_amount'], $precision, $mode),
                            'amount_paid' => 0,
                            'salary_advance_id' => null,
                            'salary_arrear_id' => null,
                            'salary_cycle_id' => $cycle_id,
                            'remarks' => $componentCalculation['remarks'],
                            'entry_type' => 'system'
            ]);
        }
    }

    /**
     * Check if component should be skipped
     */
    protected function shouldSkipComponent($component)
    {
        return $component->component_type === 'tds' || 
               $component->amount_type === 'static_unknown' || 
               $component->amount_type === 'calculated_unknown';

               
    }

    /**
     * Check if override entry exists for component
     */
    protected function hasOverrideEntry($component, $slot_id, $employeeId)
    {
        return PayrollComponentsEmployeesTrack::where('firm_id', Session::get('firm_id'))
                    ->where('payroll_slot_id', $slot_id)
                    ->where('employee_id', $employeeId)
            ->where('salary_component_id', $component->salary_component_id)
            ->where('entry_type', 'override')
            ->exists();
    }

    protected function calculateAndCreateTaxComponent($employeeId, $slot_id, $cycle_id, $payrollSlot, $payroll_slots_cmd_id, $allExistingTracks)
    {
        // 1. Get employee's tax regime
        $employeeTaxRegime = EmployeeTaxRegime::where('employee_id', $employeeId)
            ->where('firm_id', Session::get('firm_id'))
            ->where(function ($query) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>', now());
            })
            ->with('tax_regime.tax_brackets')
            ->first();

        if (!$employeeTaxRegime) {
            return; // Exit function without throwing error
        }

        try {
            // Get TDS component first - we'll need it for both calculations
            $tdsComponent = SalaryComponent::where('firm_id', Session::get('firm_id'))
                ->where('component_type', 'tds')
                ->with('salary_component_group')
                ->first();

            if (!$tdsComponent) {
                throw new \Exception("TDS component not found");
            }

            // 2. Get total earnings for the current month from payroll tracks for this slot
            $currentMonthlyEarnings = PayrollComponentsEmployeesTrack::where('employee_id', $employeeId)
                ->where('firm_id', Session::get('firm_id'))
                ->where('payroll_slot_id', $slot_id)
                ->where('nature', 'earning')
                ->where('taxable', true)
                ->sum('amount_payable');

               

            // 3. Calculate projected annual income (YTD actual + projected future)
            $projectedAnnualIncome = $this->getProjectedAnnualIncome($employeeId, $currentMonthlyEarnings, $payrollSlot->to_date);

            // 4. Get tax brackets for the regime
            $taxBrackets = $employeeTaxRegime->tax_regime->tax_brackets()
                ->where('type', 'SLAB')
                ->orderBy('income_from')
                ->get();

            

            // 5. Calculate tax for each slab
            $totalTax = 0;
            $remainingIncome = $projectedAnnualIncome;

            foreach ($taxBrackets as $bracket) {
                $slabFrom = $bracket->income_from;
                $slabTo = $bracket->income_to ?? PHP_FLOAT_MAX;
                if ($remainingIncome > $slabFrom) {
                    $slabAmount = min($remainingIncome, $slabTo) - $slabFrom;
                    if ($slabAmount > 0) {
                        $taxForSlab = round(($slabAmount * $bracket->rate) / 100);
                        $totalTax += $taxForSlab;
                        $remainingIncome -= $slabAmount;
                    }
                }
                if ($remainingIncome <= $slabFrom) {
                    break;
                }
            }

            // Calculate health and education cess
            $health_education_cess = 0.04 * $totalTax;

            // 6. Calculate total TDS for the year and remaining
            $total_tds_applicable_for_year = $totalTax + $health_education_cess;
            $total_tds_ytd = $this->calculateTdsTillytd($employeeId, $tdsComponent->id);
            $total_tds_remaining_for_year = $total_tds_applicable_for_year - $total_tds_ytd;

            // Get actual slot counts
            $total_count_of_salary_slots = $this->getTotalSlotsCount($employeeId);
            $total_count_of_salary_slots_proccessed = $this->getProcessedSlotsCount($employeeId);
            $total_count_of_salary_slots_remaining = $total_count_of_salary_slots - $total_count_of_salary_slots_proccessed;

            if ($total_count_of_salary_slots_remaining <= 0) {
                throw new \Exception("No remaining salary slots in current financial year");
            }

            // Allow negative TDS for recovery
            $monthlyTax = ($total_tds_remaining_for_year) / $total_count_of_salary_slots_remaining;
            $monthlyTax = $this->roundOffTax($monthlyTax);

            // 8. Create PayrollComponentsEmployeesTrack for TDS
            $tdsComponentEmployee = SalaryComponentsEmployee::where('employee_id', $employeeId)
                ->where('firm_id', Session::get('firm_id'))
                ->where('component_type', 'tds')
                ->first();

            // Check if TDS record already exists using pre-fetched data
            $existingTdsTrack = $allExistingTracks->get($employeeId, collect())->where('salary_component_id', $tdsComponent->id)->first();
            
            // Only create if it doesn't exist
            if (!$existingTdsTrack) {
                PayrollComponentsEmployeesTrack::create(
                [
                    'firm_id' => Session::get('firm_id'),
                    'payroll_slot_id' => $slot_id,
                    'employee_id' => $employeeId,
                    'salary_component_id' => $tdsComponent->id,
                    'payroll_slots_cmd_id' => $payroll_slots_cmd_id,
                    'salary_template_id' => $tdsComponent->salary_template_id ?? null,
                    'salary_component_group_id' => $tdsComponent->salary_component_group_id,
                    'sequence' => $tdsComponentEmployee?->sequence,
                    'nature' => 'deduction',
                    'component_type' => 'tds',
                    'amount_type' => 'calculated_known',
                    'taxable' => false, // TDS can never be taxable as it is on deduction part
                    'calculation_json' => $tdsComponent->calculation_json,
                    'salary_period_from' => $payrollSlot->from_date,
                    'salary_period_to' => $payrollSlot->to_date,
                    'user_id' => Session::get('user_id'),
                    'amount_full' => $monthlyTax,
                    'amount_payable' => $monthlyTax,
                    'amount_paid' => 0,
                    'salary_advance_id' => null,
                    'salary_arrear_id' => null,
                    'salary_cycle_id' => $cycle_id,
                    'entry_type' => 'system'
                ]
            );
            }

        } catch (\Exception $e) {
            throw new \Exception("Error calculating tax: " . $e->getMessage());
        }
    }

    protected function calculateTdsTillytd($employeeId, $tdsComponentId = null)
    {
        try {
            // If TDS component ID is not provided, try to find it
            if (!$tdsComponentId) {
                $tdsComponent = SalaryComponent::where('firm_id', Session::get('firm_id'))
                    ->where('component_type', 'tds')
                    ->where('title', 'TDS')
                    ->first();

                if (!$tdsComponent) {
                    throw new \Exception("TDS component not found");
                }
                $tdsComponentId = $tdsComponent->id;
            }

            // Get financial year from session
            $fyStart = session('fy_start');
            $fyEnd = session('fy_end');

            if (!$fyStart || !$fyEnd) {
                throw new \Exception("Financial year not set in session");
            }

            // Calculate total TDS deducted in current financial year
            $totalTdsYtd = PayrollComponentsEmployeesTrack::join('payroll_slots', 'payroll_components_employees_tracks.payroll_slot_id', '=', 'payroll_slots.id')
                ->where('payroll_components_employees_tracks.firm_id', Session::get('firm_id'))
                ->where('payroll_components_employees_tracks.employee_id', $employeeId)
                ->where('payroll_components_employees_tracks.salary_component_id', $tdsComponentId)
                ->where('payroll_components_employees_tracks.component_type', 'tds')
                ->whereBetween('payroll_components_employees_tracks.salary_period_from', [$fyStart, $fyEnd])
                ->where('payroll_slots.payroll_slot_status', 'CM')
                ->sum('payroll_components_employees_tracks.amount_payable');

            return $totalTdsYtd;

        } catch (\Exception $e) {
            throw new \Exception("Error calculating YTD TDS: " . $e->getMessage());
        }
    }

    protected function getTotalSlotsCount($employeeId)
    {
        // Get financial year from session
        $fyStart = session('fy_start');
        $fyEnd = session('fy_end');

        // Get employee's execution group
        $executionGroupId = EmployeesSalaryExecutionGroup::where('employee_id', $employeeId)
            ->where('firm_id', Session::get('firm_id'))
            ->value('salary_execution_group_id');

        // Count total slots in current financial year
        return PayrollSlot::where('firm_id', Session::get('firm_id'))
            ->where('salary_execution_group_id', $executionGroupId)
            ->whereBetween('from_date', [$fyStart, $fyEnd])
            ->count();
    }

    protected function getProcessedSlotsCount($employeeId)
    {
        // Get financial year from session
        $fyStart = session('fy_start');
        $fyEnd = session('fy_end');

        // Get employee's execution group
        $executionGroupId = EmployeesSalaryExecutionGroup::where('employee_id', $employeeId)
            ->where('firm_id', Session::get('firm_id'))
            ->value('salary_execution_group_id');

        // Count completed slots in current financial year
        return PayrollSlot::where('firm_id', Session::get('firm_id'))
            ->where('salary_execution_group_id', $executionGroupId)
            ->whereBetween('from_date', [$fyStart, $fyEnd])
            ->where('payroll_slot_status', 'CM')
            ->count();
    }

    protected function roundOffTax($amount)
    {
        return round($amount / 10) * 10;
    }

    public function resetForm()
    {
        $this->lockConfirmation = '';
    }

    public function lockPayroll($slot_id)
    {
        try {
            $this->validate();

            DB::transaction(function () use ($slot_id) {
            // Create lock command log
            PayrollSlotsCmd::create([
                'firm_id' => Session::get('firm_id'),
                'payroll_slot_id' => $slot_id,
                'user_id' => auth()->user()->id,
                'run_payroll_remarks' => json_encode([
                    'remark' => 'Payroll Locked',
                    'action' => 'LOCK'
                ]),
                'payroll_slot_status' => 'L',
            ]);

            // Update payroll slot status to locked
            PayrollSlot::where('id', $slot_id)
                ->update(['payroll_slot_status' => 'L']);
            }); // Transaction automatically committed here if successful

            // Reset the confirmation
            $this->lockConfirmation = '';

            // Refresh slot details
            $this->loadSlotDetails($slot_id);
             $this->settleArrearsForSlot($slot_id);

            Flux::toast(
                variant: 'success',
                heading: 'Success',
                text: 'Payroll has been locked successfully',
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: $e->validator->errors()->first(),
            );
        } catch (\Exception $e) {
            // Transaction was automatically rolled back
            Log::error('Payroll lock failed for slot ' . $slot_id . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'firm_id' => Session::get('firm_id')
            ]);
            
            Flux::toast(
                variant: 'error',
                heading: 'Critical Error',
                text: 'Failed to lock payroll. All changes have been reverted. Error: ' . $e->getMessage(),
            );
        }
    }


    public function showSalaryTracks($slotId)
    {
        $this->selectedSlotId = $slotId;
        
        $this->modal('salary-tracks')->show();
    }

    public function openAttendanceStep($stepId, $slotId)
    {
        $slot = PayrollSlot::findOrFail($slotId);

        // Get employees from the execution group
        $this->selectedEmployees = EmployeesSalaryExecutionGroup::where('firm_id', Session::get('firm_id'))
            ->where('salary_execution_group_id', $slot->salary_execution_group_id)
            ->pluck('employee_id')
            ->toArray();

        $this->modal('attendance-step')->show([
            'payrollSlotId' => $slotId,
            'employeeIds' => $this->selectedEmployees,
            'fromDate' => $slot->from_date,
            'toDate' => $slot->to_date
        ]);
    }

    public function runPayrollStep($stepId, $slotId)
    {
        $step = PayrollStep::findOrFail($stepId);
        $slot = PayrollSlot::findOrFail($slotId);

        try {
            // Get the PayrollStepPayrollSlot record first
            $stepSlot = PayrollStepPayrollSlot::where('firm_id', Session::get('firm_id'))
                ->where('payroll_slot_id', $slotId)
                ->where('payroll_step_id', $stepId)
                ->first();

            if (!$stepSlot) {
                throw new \Exception("Step not found in payroll slot");
            }
            //earlier  the bug was that when anyone was running the fetch attendance step, it was calculating lop days for all employees and if status was "A" then it was marking the absent days as lop days
            if ($step->step_code_main === 'fetch_attendance') {
                // Fetch employees for the slot
                $employeeIds = EmployeesSalaryExecutionGroup::where('firm_id', Session::get('firm_id'))
                    ->where('salary_execution_group_id', $slot->salary_execution_group_id)
                    ->whereHas('employee', function ($q) use ($slot) {
                        $q->where('is_inactive', false)
                            ->whereHas('emp_job_profile', function ($q2) use ($slot) {
                                $q2->where(function ($query) use ($slot) {
                                    $query->whereNull('doh')
                                        ->orWhereDate('doh', '<=', $slot->to_date);
                                });
                            });
                    })
                    ->pluck('employee_id')
                    ->toArray();

                // Get employees on salary hold for this payroll slot
                $employeesOnHold = SalaryHold::where('firm_id', Session::get('firm_id'))
                    ->where('payroll_slot_id', $slotId)
                    ->pluck('employee_id')
                    ->toArray();

                // Filter out employees on hold
                $activeEmployeeIds = array_diff($employeeIds, $employeesOnHold);

                // Fetch attendance records only for active employees
                $attendanceRecords = EmpAttendance::where('firm_id', Session::get('firm_id'))
                    ->whereIn('employee_id', $activeEmployeeIds)
                    ->whereBetween('work_date', [$slot->from_date, $slot->to_date])
                    ->get();

                // Calculate cycle days
                $fromDate = \Carbon\Carbon::parse($slot->from_date);
                $toDate = \Carbon\Carbon::parse($slot->to_date);
                $cycleDays = $fromDate->diffInDays($toDate) + 1;

                // Process attendance for each employee and update EmployeesSalaryDay
                // foreach ($activeEmployeeIds as $employeeId) {
                //     $employeeAttendance = $attendanceRecords->where('employee_id', $employeeId);
                //     $lopDaysCount = $employeeAttendance->where('attendance_status_main', 'A')->count();

                //     EmployeesSalaryDay::updateOrCreate(
                //         [
                //             'firm_id' => Session::get('firm_id'),
                //             'payroll_slot_id' => $slotId,
                //             'employee_id' => $employeeId,
                //         ],
                //         [
                //             'cycle_days' => $cycleDays,
                //             'void_days_count' => 0,
                //             'lop_days_count' => $lopDaysCount,
                //         ]
                //     );
                // }

                // Update step status in PayrollStepPayrollSlot
                $stepSlot->update(['payroll_step_status' => 'RN']); // Running

                // Create entry in PayrollStepPayrollSlotCmd for this run
                PayrollStepPayrollSlotCmd::create([
                    'firm_id' => Session::get('firm_id'),
                    'payroll_step_payroll_slot_id' => $stepSlot->id,
                    'payroll_step_status' => 'RN', // Run
                    'step_remarks' => sprintf(
                        'Attendance fetched and processed for %d active employees (excluded %d employees on hold). Found %d LOP days.',
                        count($activeEmployeeIds),
                        count($employeesOnHold),
                        $attendanceRecords->where('attendance_status_main', 'A')->count()
                    ),
                    'user_id' => auth()->user()->id
                ]);
            }

            // Refresh the logs
            $this->loadSlotDetails($slotId);

            Flux::toast(
                variant: 'success',
                heading: 'Success',
                text: 'Step run successfully',
            );
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to run step: ' . $e->getMessage(),
            );
        }
    }

    public function completePayrollStep($stepId, $slotId)
    {
        try {
            // Get the payroll slot details
            $payrollSlot = PayrollSlot::findOrFail($slotId);

            // Get the step details
            $step = PayrollStep::findOrFail($stepId);

            // Get employees from the execution group
            $employeeIds = EmployeesSalaryExecutionGroup::where('firm_id', Session::get('firm_id'))
                ->where('salary_execution_group_id', $payrollSlot->salary_execution_group_id)
                ->whereHas('employee', function ($q) use ($payrollSlot) {
                    $q->where('is_inactive', false)
                        ->whereHas('emp_job_profile', function ($q2) use ($payrollSlot) {
                            $q2->whereDate('doh', '<=', $payrollSlot->to_date);
                        });
                })
                ->pluck('employee_id')
                ->toArray();

            // Get employees on salary hold for this payroll slot
            $employeesOnHold = SalaryHold::where('firm_id', Session::get('firm_id'))
                ->where('payroll_slot_id', $slotId)
                ->pluck('employee_id')
                ->toArray();

            // Filter out employees on hold
            $activeEmployees = array_diff($employeeIds, $employeesOnHold);

            // Handle salary holds step - now just mark as completed since holds are processed at start
            if ($step->step_code_main === 'salary_holds') {
                // Salary holds are already processed at payroll start, just mark step as completed
            }

            // Handle salary advances step
            if ($step->step_code_main === 'salary_advances') {
                $this->processSalaryAdvances($slotId, $activeEmployees, $payrollSlot);
            }

            // Handle salary arrears step
            if ($step->step_code_main === 'salary_arrears') {
                $this->processSalaryArrears($slotId, $activeEmployees, $payrollSlot);
            }

            // Update the step status to completed in PayrollStepPayrollSlot
            PayrollStepPayrollSlot::where('firm_id', Session::get('firm_id'))
                ->where('payroll_slot_id', $slotId)
                ->where('payroll_step_id', $stepId)
                ->update(['payroll_step_status' => 'CM']); // Completed

            // Create single entry in PayrollSlotsCmd for completion
            PayrollSlotsCmd::create([
                'firm_id' => Session::get('firm_id'),
                'payroll_slot_id' => $slotId,
                'user_id' => auth()->user()->id,
                'run_payroll_remarks' => json_encode([
                    'payroll_step_id' => $stepId,
                    'step_title' => $step->step_title,
                    'remark' => $step->step_title . " Completed",
                ]),
                'payroll_slot_status' => 'IP',
            ]);

            // Refresh the slot details to show updated status
            $this->loadSlotDetails($slotId);

            Flux::toast(
                variant: 'success',
                heading: 'Success',
                text: 'Step completed successfully',
            );
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to complete step: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Process salary holds at the start of payroll
     */
    protected function processSalaryHoldsAtStart($slotId)
    {
        // Get salary hold component
        $holdComponent = SalaryComponent::where('firm_id', Session::get('firm_id'))
            ->where('component_type', 'salary_hold')
            ->first();

        if (!$holdComponent) {
            return;
        }

        // Get employees on salary hold for this payroll slot
        $employeesOnHold = SalaryHold::where('firm_id', Session::get('firm_id'))
            ->where('payroll_slot_id', $slotId)
            ->pluck('employee_id')
            ->toArray();

        // Get payroll slot details
        $payrollSlot = PayrollSlot::findOrFail($slotId);

        // Create entries for employees on hold
        foreach ($employeesOnHold as $employeeId) {
            PayrollComponentsEmployeesTrack::updateOrCreate(
                [
                    'firm_id' => Session::get('firm_id'),
                    'payroll_slot_id' => $slotId,
                    'employee_id' => $employeeId,
                    'salary_component_id' => $holdComponent->id,
                ],
                [
                    'payroll_slots_cmd_id' => null, // Will be set when command is created
                    'salary_template_id' => $holdComponent->salary_template_id ?? null,
                    'salary_component_group_id' => $holdComponent->salary_component_group_id ?? null,
                    'sequence' => 1,
                    'nature' => 'deduction',
                    'component_type' => 'salary_hold',
                    'amount_type' => 'static_known',
                    'taxable' => false,
                    'calculation_json' => $holdComponent->calculation_json ?? null,
                    'salary_period_from' => $payrollSlot->from_date,
                    'salary_period_to' => $payrollSlot->to_date,
                    'user_id' => Session::get('user_id'),
                    'amount_full' => 0,
                    'amount_payable' => 0,
                    'amount_paid' => 0,
                    'salary_advance_id' => null,
                    'salary_arrear_id' => null,
                    'salary_cycle_id' => $payrollSlot->salary_cycle_id,
                    'remarks' => 'Salary on hold for this payroll period',
                    'entry_type' => 'system'
                ]
            );
        }
    }

    /**
     * Process salary holds for the payroll slot
     */
    protected function processSalaryHolds($slotId, $employeeIds, $employeesOnHold)
    {
        // Get the latest payroll slots command ID
        $latestCmd = PayrollSlotsCmd::where('firm_id', Session::get('firm_id'))
            ->where('payroll_slot_id', $slotId)
            ->latest()
            ->first();

        if (!$latestCmd) {
            return;
        }

        // Get salary hold component
        $holdComponent = SalaryComponent::where('firm_id', Session::get('firm_id'))
            ->where('component_type', 'salary_hold')
            ->first();

        if (!$holdComponent) {
            return;
        }

        // Create entries for employees on hold
        foreach ($employeesOnHold as $employeeId) {
            PayrollComponentsEmployeesTrack::updateOrCreate(
                [
                    'firm_id' => Session::get('firm_id'),
                    'payroll_slot_id' => $slotId,
                    'employee_id' => $employeeId,
                    'salary_component_id' => $holdComponent->id,
                ],
                [
                    'payroll_slots_cmd_id' => $latestCmd->id,
                    'salary_template_id' => $holdComponent->salary_template_id ?? null,
                    'salary_component_group_id' => $holdComponent->salary_component_group_id ?? null,
                    'sequence' => 1,
                    'nature' => 'deduction',
                    'component_type' => 'salary_hold',
                    'amount_type' => 'static_known',
                    'taxable' => false,
                    'calculation_json' => $holdComponent->calculation_json ?? null,
                    'salary_period_from' => $this->payrollSlotDetails->from_date,
                    'salary_period_to' => $this->payrollSlotDetails->to_date,
                    'user_id' => Session::get('user_id'),
                    'amount_full' => 0,
                    'amount_payable' => 0,
                    'amount_paid' => 0,
                    'salary_advance_id' => null,
                    'salary_arrear_id' => null,
                    'salary_cycle_id' => $this->payrollSlotDetails->salary_cycle_id,
                    'remarks' => 'Salary on hold for this payroll period',
                    'entry_type' => 'system'
                ]
            );
        }
    }

    /**
     * Process salary advances for the payroll slot
     */
    protected function processSalaryAdvances($slotId, $activeEmployees, $payrollSlot)
    {
        $latestCmd = PayrollSlotsCmd::where('firm_id', Session::get('firm_id'))
            ->where('payroll_slot_id', $slotId)
            ->latest()
            ->first();
        if (!$latestCmd) {
            return;
        }
        
        $salaryAdvances = SalaryAdvance::where('firm_id', Session::get('firm_id'))
            ->whereIn('employee_id', $activeEmployees)
            ->where('is_inactive', false)
            ->where('advance_status', 'active')
            ->whereRaw('amount > recovered_amount')
            // Gate by intended slot: allow advances meant for this slot OR without a specific slot
            ->where(function ($q) use ($slotId) {
                $q->whereNull('recovery_wef_payroll_slot_id')
                    ->orWhere('recovery_wef_payroll_slot_id', $slotId);
            })
            // Optional: also ensure advance effective window overlaps the slot window
            ->where(function ($q) use ($payrollSlot) {
                $q->whereDate('advance_date', '<=', $payrollSlot->to_date);
            })
            ->get();

        // Build whitelist of eligible advance IDs for this slot
        $eligibleAdvanceIds = $salaryAdvances->pluck('id')->filter()->values();

        // Purge tracks in this slot for advances that no longer exist or are no longer eligible
        PayrollComponentsEmployeesTrack::where('firm_id', Session::get('firm_id'))
            ->where('payroll_slot_id', $slotId)
            ->where('component_type', 'salary_advance')
            ->when(!empty($activeEmployees), function ($q) use ($activeEmployees) {
                $q->whereIn('employee_id', $activeEmployees);
            })
            ->when($eligibleAdvanceIds->count() > 0, function ($q) use ($eligibleAdvanceIds) {
                $q->whereNotIn('salary_advance_id', $eligibleAdvanceIds);
            }, function ($q) {
                // If no eligible advances, remove all advance tracks for this slot/employees
                // (the when(false) branch won't run, so add a no-op alt is not needed)
            })
            ->delete();

        foreach ($salaryAdvances as $advance) {
            if (!$this->isEmployeeInSlotExecutionGroup($advance->employee_id, $payrollSlot)) {
                continue;
            }
            // Calculate remaining amount to recover
            $remainingAmount = $advance->amount - $advance->recovered_amount;
            $recoveryAmount = min($remainingAmount, $advance->installment_amount);

            // Get advance component
            $advanceComponent = SalaryComponent::where('firm_id', Session::get('firm_id'))
                ->where('component_type', 'salary_advance')
                ->first();

            if ($advanceComponent) {
                PayrollComponentsEmployeesTrack::updateOrCreate(
                    [
                        'firm_id' => Session::get('firm_id'),
                        'payroll_slot_id' => $slotId,
                        'employee_id' => $advance->employee_id,
                        'salary_component_id' => $advanceComponent->id,
                        'salary_advance_id' => $advance->id, // key on specific advance so edits map correctly
                    ],
                    [
                        'payroll_slots_cmd_id' => $latestCmd->id,
                        'salary_template_id' => $advanceComponent->salary_template_id ?? null,
                        'salary_component_group_id' => $advanceComponent->salary_component_group_id ?? null,
                        'sequence' => 1,
                        'nature' => 'deduction',
                        'component_type' => 'salary_advance',
                        'amount_type' => 'static_known',
                        'taxable' => false,
                        'calculation_json' => $advanceComponent->calculation_json ?? null,
                        'salary_period_from' => $payrollSlot->from_date,
                        'salary_period_to' => $payrollSlot->to_date,
                        'user_id' => Session::get('user_id'),
                        'amount_full' => $recoveryAmount,
                        'amount_payable' => $recoveryAmount,
                        'amount_paid' => 0,
                        'salary_advance_id' => $advance->id,
                        'salary_arrear_id' => null,
                        'salary_cycle_id' => $payrollSlot->salary_cycle_id,
                        'remarks' => "Advance recovery for advance ID: {$advance->id}",
                        'entry_type' => 'system'
                    ]
                );
            }
        }
    }

    /**
     * Process salary arrears for the payroll slot
     */
    protected function processSalaryArrears($slotId, $activeEmployees, $payrollSlot)
    {
        $latestCmd = PayrollSlotsCmd::where('firm_id', Session::get('firm_id'))
            ->where('payroll_slot_id', $slotId)
            ->latest()
            ->first();
        if (!$latestCmd) {
            return;
        }
        $salaryArrears = SalaryArrear::where('firm_id', Session::get('firm_id'))
            ->whereIn('employee_id', $activeEmployees)
            ->where('is_inactive', false)
            ->where('arrear_status', '!=', 'paid')
            ->whereRaw('total_amount > paid_amount')
            // Gate by intended slot: allow arrears meant for this slot OR without a specific slot
            ->where(function ($q) use ($slotId) {
                
                $q->whereNull('disburse_wef_payroll_slot_id')
                    ->orWhere('disburse_wef_payroll_slot_id', $slotId);
            })
            ->get();



       

        // Build whitelist of eligible arrear IDs for this slot
        $eligibleArrearIds = $salaryArrears->pluck('id')->filter()->values();

        // Purge tracks in this slot for arrears that no longer exist or are no longer eligible
        PayrollComponentsEmployeesTrack::where('firm_id', Session::get('firm_id'))
            ->where('payroll_slot_id', $slotId)
            ->where('component_type', 'salary_arrear')
            ->when(!empty($activeEmployees), function ($q) use ($activeEmployees) {
                $q->whereIn('employee_id', $activeEmployees);
            })
            ->when($eligibleArrearIds->count() > 0, function ($q) use ($eligibleArrearIds) {
                $q->whereNotIn('salary_arrear_id', $eligibleArrearIds);
            }, function ($q) {
                // If no eligible arrears, remove all arrear tracks for this slot/employees
                // (the when(false) branch won't run, so add a no-op alt is not needed)
            })
            ->delete();
        foreach ($salaryArrears as $arrear) {
            if (!$this->isEmployeeInSlotExecutionGroup($arrear->employee_id, $payrollSlot)) {
                continue;
            }
            // Calculate remaining amount to pay
            $remainingAmount = $arrear->total_amount - $arrear->paid_amount;
            $paymentAmount = min($remainingAmount, $arrear->installment_amount);

            // Fetch component details to avoid hardcoding fields like nature/amount_type/taxable
            $arrearComponent = SalaryComponent::where('firm_id', Session::get('firm_id'))
                ->where('id', $arrear->salary_component_id)
                ->first();

            $componentNature = $arrearComponent->nature ?? 'earning';
            $componentAmountType = $arrearComponent->amount_type ?? ($arrear->amount_type ?? 'static_known');
            $componentTaxable = $arrearComponent->taxable ?? true;
            $componentTemplateId = $arrearComponent->salary_template_id ?? null;
            $componentGroupId = $arrearComponent->salary_component_group_id ?? null;

            // Create/update entry using the arrear's salary component; key by slot+employee+component+arrear
            PayrollComponentsEmployeesTrack::updateOrCreate(
                [
                    'firm_id' => Session::get('firm_id'),
                    'payroll_slot_id' => $slotId,
                    'employee_id' => $arrear->employee_id,
                    'salary_component_id' => $arrear->salary_component_id,
                    'salary_arrear_id' => $arrear->id,
                ],
                [
                    'payroll_slots_cmd_id' => $latestCmd->id,
                    'salary_template_id' => $componentTemplateId,
                    'salary_component_group_id' => $componentGroupId,
                    'sequence' => 1,
                    'nature' => $componentNature,
                    // Keep component_type as 'salary_arrear' so downstream filters continue to work
                    'component_type' => 'salary_arrear',
                    'amount_type' => $componentAmountType,
                    'taxable' => (bool) $componentTaxable,
                    'calculation_json' => $arrearComponent->calculation_json ?? null,
                    'salary_period_from' => $payrollSlot->from_date,
                    'salary_period_to' => $payrollSlot->to_date,
                    'user_id' => Session::get('user_id'),
                    'amount_full' => $paymentAmount,
                    'amount_payable' => $paymentAmount,
                    'amount_paid' => 0,
                    'salary_advance_id' => null,
                    'salary_cycle_id' => $payrollSlot->salary_cycle_id,
                    'remarks' => "Arrear payment for arrear ID: {$arrear->id}",
                    'entry_type' => 'system'
                ]
            );
        }
    }

    /**
     * After a slot is completed, update SalaryArrear paid_amount and status
     * based on salary_arrear tracks generated in this slot.
     */
    protected function settleArrearsForSlot($slotId): void
    {

        // Fetch all arrear tracks for this slot grouped by salary_arrear_id
        $arrearTracks = PayrollComponentsEmployeesTrack::where('firm_id', Session::get('firm_id'))
            ->where('payroll_slot_id', $slotId)
            ->where('component_type', 'salary_arrear')
            ->select('salary_arrear_id', DB::raw('SUM(amount_payable) as total_paid'))
            ->groupBy('salary_arrear_id')
            ->get();


        foreach ($arrearTracks as $trackSummary) {
            if (!$trackSummary->salary_arrear_id) {
                continue;
            }

            $arrear = \App\Models\Hrms\SalaryArrear::where('firm_id', Session::get('firm_id'))
                ->where('id', $trackSummary->salary_arrear_id)
                ->first();
            if (!$arrear) {
                continue;
            }

            // Increment paid_amount
            $arrear->paid_amount = ($arrear->paid_amount ?? 0) + (float) $trackSummary->total_paid;

            // Update status if fully paid
            if ($arrear->paid_amount >= $arrear->total_amount) {
                $arrear->arrear_status = 'paid';
            } elseif ($arrear->paid_amount > 0 && $arrear->paid_amount < $arrear->total_amount) {
                $arrear->arrear_status = 'partially_paid';
            }

            $arrear->save();
        }
    }

    /**
     * After a slot is completed, update SalaryAdvance recovered_amount and status
     * based on salary_advance tracks generated in this slot.
     */
    protected function settleAdvancesForSlot($slotId): void
    {
        // Fetch all advance tracks for this slot grouped by salary_advance_id
        $advanceTracks = PayrollComponentsEmployeesTrack::where('firm_id', Session::get('firm_id'))
            ->where('payroll_slot_id', $slotId)
            ->where('component_type', 'salary_advance')
            ->select('salary_advance_id', DB::raw('SUM(amount_payable) as total_recovered'))
            ->groupBy('salary_advance_id')
            ->get();

        foreach ($advanceTracks as $trackSummary) {
            if (!$trackSummary->salary_advance_id) {
                continue;
            }

            $advance = \App\Models\Hrms\SalaryAdvance::where('firm_id', Session::get('firm_id'))
                ->where('id', $trackSummary->salary_advance_id)
                ->first();
            if (!$advance) {
                continue;
            }

            // Increment recovered_amount
            $advance->recovered_amount = ($advance->recovered_amount ?? 0) + (float) $trackSummary->total_recovered;

            // Update status if fully recovered
            if ($advance->recovered_amount >= $advance->amount) {
                $advance->advance_status = 'closed';
            } elseif ($advance->recovered_amount > 0 && $advance->recovered_amount < $advance->amount) {
                $advance->advance_status = 'active';
            }

            $advance->save();
        }
    }

    public function lopAdjustmentStep($slotId)
    {
        $this->selectedSlotId = $slotId;
        $this->modal('lop-adjustment-steps')->show();
    }

    public function tdsCalculationStep($slotId)
    {
        $this->selectedSlotId = $slotId;
        $this->modal('tds-calculations')->show();
    }

    public function staticUnknownComponentsStep($slotId)
    {
        $this->selectedSlotId = $slotId;
        $this->modal('set-head-amount-manually')->show();
    }

    public function overrideAmountsStep($slotId)
    {
        $this->selectedSlotId = $slotId;
        $this->modal('override-amounts')->show();
    }

    public function reviewOverrideComponentsStep($slotId)
    {
        $this->selectedSlotId = $slotId;
        $this->modal('review-override-components')->show();
    }

    public function employeeTaxComponentsStep($stepId, $slotId)
    {
        $this->selectedStepId = $stepId;
        $this->selectedSlotId = $slotId;
        $this->modal('employee-tax-components')->show();
    }

    public function viewSalaryHolds($slotId)
    {
        $this->selectedSlotId = $slotId;

        // Find the salary holds step
        $step = PayrollStepPayrollSlot::where('firm_id', Session::get('firm_id'))
            ->where('payroll_slot_id', $slotId)
            ->where('step_code_main', 'salary_holds')
            ->first();

        // Open the modal using the same method as other modals
        $this->modal('salary-holds')->show();
    }

    public function viewSalaryAdvances($slotId)
    {
        $this->selectedSlotId = $slotId;

        // Find the salary advances step
        $step = PayrollStepPayrollSlot::where('firm_id', Session::get('firm_id'))
            ->where('payroll_slot_id', $slotId)
            ->where('step_code_main', 'salary_advances')
            ->first();


        // Open the modal using the same method as other modals
        $this->modal('salary-advances')->show();
    }

    public function viewSalaryArrears($slotId)
    {
        $this->selectedSlotId = $slotId;

        // Find the salary arrears step
        $step = PayrollStepPayrollSlot::where('firm_id', Session::get('firm_id'))
            ->where('payroll_slot_id', $slotId)
            ->where('step_code_main', 'salary_arrears')
            ->first();


        // Open the modal using the same method as other modals
        $this->modal('salary-arrears')->show();
    }

    public function showLogs($stepId, $slotId)
    {
        $this->selectedSlotId = $slotId;
        $this->selectedStepId = $stepId;
        $this->stepLogs = $this->getStepLogs($stepId, $slotId);

        $this->modal('mdl-logs')->show();
    }

    public function getStepLogs($stepId, $slotId)
    {
        $this->selectedSlotId = $slotId;
        try {
            // First get the PayrollStepPayrollSlot record
            $stepSlot = PayrollStepPayrollSlot::where('firm_id', Session::get('firm_id'))
                ->where('payroll_slot_id', $slotId)
                ->where('payroll_step_id', $stepId)
                ->first();


            if (!$stepSlot) {
                return collect([]);
            }


            $stepLogs = PayrollStepPayrollSlotCmd::where('firm_id', Session::get('firm_id'))
                ->where('payroll_step_payroll_slot_id', $stepSlot->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($log) {
                    return [
                        'type' => 'step',
                        'status' => $log->payroll_step_status,
                        'remarks' => $log->step_remarks,
                        'created_at' => $log->created_at,

                        'step_title' => $log->payroll_step_payroll_slot->payroll_step->step_title
                    ];
                });

            return $stepLogs;


        } catch (\Exception $e) {
            return collect([]);
        }
    }

    public function publishPayroll($slot_id)
    {
        try {
            DB::transaction(function () use ($slot_id) {
            $payrollSlot = PayrollSlot::findOrFail($slot_id);

            if ($payrollSlot->payroll_slot_status !== 'L') {
                throw new \Exception("Payroll slot is not locked.");
            }

            // 1. Update PayrollSlot status to 'PB' (Published)
            $payrollSlot->update(['payroll_slot_status' => 'PB']);

            // 2. Create entry in PayrollSlotsCmd
            PayrollSlotsCmd::create([
                'firm_id' => Session::get('firm_id'),
                'payroll_slot_id' => $slot_id,
                'user_id' => auth()->user()->id,
                'run_payroll_remarks' => json_encode([
                    'remark' => 'Payroll Published',
                    'action' => 'PUBLISH'
                ]),
                'payroll_slot_status' => 'PB',
            ]);

            // 3. Process week-off leave balance
            $employeeIds = EmployeesSalaryExecutionGroup::where('firm_id', Session::get('firm_id'))
                ->where('salary_execution_group_id', $payrollSlot->salary_execution_group_id)
                ->pluck('employee_id');

            $weekoffLeaveType = LeaveType::where('firm_id', Session::get('firm_id'))
                ->where('leave_type_main', 'weekoff')
                ->first();

            if (!$weekoffLeaveType) {
                Log::warning("Weekoff leave type not found. Skipping weekoff allocation step during payroll publish for slot ID: {$slot_id}.");
                // Continue publishing payroll without weekoff allocation
            } else {
                foreach ($employeeIds as $employeeId) {
                    // Get available flexi week offs for the employee within the slot period
                    $weekOffsToProcess = FlexiWeekOff::join('emp_attendances', 'flexi_week_off.availed_emp_attendance_id', '=', 'emp_attendances.id')
                        ->where('flexi_week_off.employee_id', $employeeId)
                        ->where('flexi_week_off.week_off_Status', 'A') // 'A' for Available
                        ->whereBetween('emp_attendances.work_date', [$payrollSlot->from_date, $payrollSlot->to_date])
                        ->select('flexi_week_off.*')
                        ->get();

                    $weekOffDays = $weekOffsToProcess->count();

                    if ($weekOffDays > 0) {
                        $leaveBalance = EmpLeaveBalance::where('employee_id', $employeeId)
                            ->where('leave_type_id', $weekoffLeaveType->id)
                            ->where('firm_id', Session::get('firm_id'))
                            ->where('period_start', '<=', $payrollSlot->to_date)
                            ->where('period_end', '>=', $payrollSlot->from_date)
                            ->first();

                        if ($leaveBalance) {
                            $leaveBalance->allocated_days += $weekOffDays;
                            $leaveBalance->balance += $weekOffDays;
                            $leaveBalance->save();

                            // Update FlexiWeekOff status to 'C' (Consumed)
                            FlexiWeekOff::whereIn('id', $weekOffsToProcess->pluck('id'))->update(['week_off_Status' => 'C']);
                        } else {
                            Log::warning("No leave balance record found for employee {$employeeId} for weekoff leave type. Week off allocation skipped.");
                        }
                    }
                }
            }
            }); // Transaction automatically committed here if successful

            $this->loadSlotDetails($slot_id);

            // Close the modal
            $this->dispatch('close-modal', 'mdl-publish-payroll');
            $this->resetForm();

            Flux::toast(
                variant: 'success',
                heading: 'Success',
                text: 'Payroll has been published successfully',
            );
        } catch (\Exception $e) {
            // Transaction was automatically rolled back
            Log::error('Payroll publish failed for slot ' . $slot_id . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'firm_id' => Session::get('firm_id')
            ]);
            
            Flux::toast(
                variant: 'error',
                heading: 'Critical Error',
                text: 'Failed to publish payroll. All changes have been reverted. Error: ' . $e->getMessage(),
            );
        }
    }

    protected function calculateAdvancedDeductionsByDates($salaryDays, $payrollSlot, $cycleDays)
    {
        
        $deductionDetails = $this->calculateDeductionsByDates($salaryDays, $payrollSlot, $cycleDays);


        // If we have specific dates, we can do more precise calculations
        if (!empty($deductionDetails['lop_dates']) || !empty($deductionDetails['void_dates'])) {
            $allDeductionDates = array_merge($deductionDetails['lop_dates'], $deductionDetails['void_dates']);
            $deductionDetails['specific_dates'] = array_unique($allDeductionDates);

            // Calculate working days more precisely
            $fromDate = \Carbon\Carbon::parse($payrollSlot->from_date);
            $toDate = \Carbon\Carbon::parse($payrollSlot->to_date);
            $totalDays = $fromDate->diffInDays($toDate) + 1;

            $deductionDetails['working_days'] = $totalDays - count($deductionDetails['specific_dates']);
        }

        return $deductionDetails;
    }

    protected function calculateComponentAmount($component, $deductionDetails, $cycleDays, $payrollSlot, $ignoreLopForEarnings = false)
    {
        $fullAmount = $component->amount;
        $payableAmount = $fullAmount;
        $remarks = '';

        // DD for employee 1461 - Step 4: calculateComponentAmount entry
        // if ($component->employee_id == 1461) {
        //     dd('Step 4: calculateComponentAmount entry for employee 1461', [
        //         'component_id' => $component->salary_component_id,
        //         'component_title' => $component->salary_component->title ?? 'Unknown',
        //         'full_amount' => $fullAmount,
        //         'amount_type' => $component->amount_type,
        //         'component_type' => $component->component_type,
        //         'nature' => $component->nature,
        //         'effective_from' => $component->effective_from,
        //         'ignoreLopForEarnings' => $ignoreLopForEarnings,
        //         'lop_deduction_type' => session('LOP_deduction_type'),
        //         'deduction_details' => $deductionDetails,
        //         'cycle_days' => $cycleDays
        //     ]);
        // }

        // Proration: If component has effective_from within the slot, prorate full amount first
        if ($component->effective_from) {
            $componentEffectiveDate = \Carbon\Carbon::parse($component->effective_from);
            $slotStart = \Carbon\Carbon::parse($payrollSlot->from_date);
            $slotEnd = \Carbon\Carbon::parse($payrollSlot->to_date);

            // Only if the component starts during the slot window (and not after slot end)
            if ($componentEffectiveDate->betweenIncluded($slotStart, $slotEnd)) {
                $activeDays = $componentEffectiveDate->diffInDays($slotEnd) + 1; // inclusive
                $activeDays = max(0, min($activeDays, $cycleDays));
                $perDayBase = $cycleDays > 0 ? $component->amount / $cycleDays : 0;
                $fullAmount = $perDayBase * $activeDays;
            } elseif ($componentEffectiveDate->gt($slotEnd)) {
                // Effective starts after slot window; zero out
                $fullAmount = 0;
            }
        }

        // If LOP deduction is handled via a single calculation-wise component,
        // do not apply any LOP deductions to earning components here
        // But still apply LOP deductions to deduction components
        if ($ignoreLopForEarnings && $component->nature === 'earning') {
            return [
                'full_amount' => $fullAmount,
                'payable_amount' => $fullAmount,
                'per_day_amount' => 0,
                'deduction_amount' => 0,
                'remarks' => 'LOP handled via lop_deduction (calculation_wise)'
            ];
        }

        // Check if component should be affected by LOP/void days
        if (!$this->shouldComponentBeAffectedByDeductions($component)) {
            return [
                'full_amount' => $fullAmount,
                'payable_amount' => $fullAmount,
                'per_day_amount' => 0,
                'deduction_amount' => 0,
                'remarks' => 'Component not affected by LOP/Void days'
            ];
        }

        // Calculate effective deduction days based on component's effective date
        $effectiveDeductionDetails = $this->calculateEffectiveDeductionsForComponent($component, $deductionDetails, $cycleDays);

        // DD for employee 1461 - Step 5: Effective deduction details
        // if ($component->employee_id == 1461) {
        //     dd('Step 5: Effective deduction details for employee 1461', [
        //         'component_id' => $component->salary_component_id,
        //         'effective_deduction_details' => $effectiveDeductionDetails,
        //         'full_amount' => $fullAmount
        //     ]);
        // }

        // Calculate per day amount based on TOTAL cycle days to avoid inflation
        $perDayAmount = $cycleDays > 0 ? $fullAmount / $cycleDays : 0;
        $effectiveWorkingDays = $cycleDays - $effectiveDeductionDetails['effective_deduction_days'];

        // Calculate deduction based on effective deduction days
        $totalDeductionAmount = $perDayAmount * $effectiveDeductionDetails['effective_deduction_days'];
        $payableAmount = $fullAmount - $totalDeductionAmount;

        // Ensure payable amount doesn't go below 0
        $payableAmount = max(0, $payableAmount);

        // Build remarks with effective date information
        $remarks = sprintf(
            'Full: %s, Effective Working Days: %d, Effective Deduction Days: %d, Deduction: %s, Payable: %s',
            number_format($fullAmount, 2),
            $effectiveWorkingDays,
            $effectiveDeductionDetails['effective_deduction_days'],
            number_format($totalDeductionAmount, 2),
            number_format($payableAmount, 2)
        );

        if ($component->effective_from) {
            $remarks .= sprintf(', Effective From: %s', $component->effective_from->format('Y-m-d'));
        }

        // DD for employee 1461 - Step 6: Final calculation result
        // if ($component->employee_id == 1461) {
        //     dd('Step 6: Final calculation result for employee 1461', [
        //         'component_id' => $component->salary_component_id,
        //         'full_amount' => $fullAmount,
        //         'payable_amount' => $payableAmount,
        //         'per_day_amount' => $perDayAmount,
        //         'deduction_amount' => $totalDeductionAmount,
        //         'effective_working_days' => $effectiveWorkingDays,
        //         'effective_deduction_days' => $effectiveDeductionDetails['effective_deduction_days'],
        //         'remarks' => $remarks
        //     ]);
        // }

        return [
            'full_amount' => $fullAmount,
            'payable_amount' => $payableAmount,
            'per_day_amount' => $perDayAmount,
            'deduction_amount' => $totalDeductionAmount,
            'effective_working_days' => $effectiveWorkingDays,
            'effective_deduction_days' => $effectiveDeductionDetails['effective_deduction_days'],
            'remarks' => $remarks
        ];
    }

    protected function calculateEffectiveDeductionsForComponent($component, $deductionDetails, $cycleDays)
    {
        $effectiveDeductionDays = 0;
        $effectiveWorkingDays = $cycleDays;

        // If component has an effective date, calculate deductions only from that date onwards
        if ($component->effective_from) {
            $componentEffectiveDate = \Carbon\Carbon::parse($component->effective_from);

            // Filter deduction dates to only include those after component effective date
            $effectiveDeductionDates = [];

            if (!empty($deductionDetails['specific_dates'])) {
                foreach ($deductionDetails['specific_dates'] as $deductionDate) {
                    $deductionCarbonDate = \Carbon\Carbon::parse($deductionDate);
                    if ($deductionCarbonDate->gte($componentEffectiveDate)) {
                        $effectiveDeductionDates[] = $deductionDate;
                    }
                }
            }

            $effectiveDeductionDays = count($effectiveDeductionDates);

            // Calculate effective working days for this component
            $effectiveWorkingDays = $cycleDays - $effectiveDeductionDays;

        } else {
            // If no effective date, use all deduction days
            $effectiveDeductionDays = $deductionDetails['total_deduction_days'];
            $effectiveWorkingDays = $deductionDetails['working_days'];
        }

        return [
            'effective_deduction_days' => $effectiveDeductionDays,
            'effective_working_days' => $effectiveWorkingDays,
            'effective_deduction_dates' => $effectiveDeductionDates ?? []
        ];
    }

    protected function shouldComponentBeAffectedByDeductions($component)
    {
        // Components that should NOT be affected by LOP/void days
        $nonDeductibleTypes = [
            'reimbursement', // Reimbursements are usually not affected
            'one_time',      // One-time payments
            'advance',       // Advances
            'arrear',        // Arrears
            'tds',           // TDS
            'tax',           // Tax
            'employee_contribution', // Employee contributions
            'employer_contribution'  // Employer contributions
        ];

        // Components that should NOT be affected by LOP/void days based on amount type
        $nonDeductibleAmountTypes = [
            'static_unknown',
            'calculated_unknown'
        ];

        return !in_array($component->component_type, $nonDeductibleTypes) &&
            !in_array($component->amount_type, $nonDeductibleAmountTypes);
    }

    protected function calculateDeductionsByDates($salaryDays, $payrollSlot, $cycleDays)
    {
        $deductionDetails = [
            'total_deduction_days' => 0,
            'lop_days' => 0,
            'void_days' => 0,
            'lop_dates' => [],
            'void_dates' => [],
            'working_days' => $cycleDays
        ];

        if ($salaryDays) {
            $deductionDetails['lop_days'] = $salaryDays->lop_days_count;
            $deductionDetails['void_days'] = $salaryDays->void_days_count;
            $deductionDetails['total_deduction_days'] = $salaryDays->void_days_count + $salaryDays->lop_days_count;
            $deductionDetails['working_days'] = $cycleDays - $deductionDetails['total_deduction_days'];

            // Parse LOP details if available
            if ($salaryDays->lop_details) {
                $lopData = json_decode($salaryDays->lop_details, true);
                if (is_array($lopData)) {

                    $deductionDetails['lop_dates'] = $lopData['lop'] ?? [];
                    $deductionDetails['void_dates'] = $lopData['void'] ?? [];
                }
            }
        }

        return $deductionDetails;
    }

    /**
     * Simple LOP deduction calculation based on gross salary and LOP days.
     *
     * @param float $grossSalary
     * @param int $totalDaysInCycle
     * @param int $lopDays
     * @return float
     */
    protected function calculateSimpleLopDeduction($grossSalary, $totalDaysInCycle, $lopDays)
    {
        if ($totalDaysInCycle <= 0) {
            return 0;
        }
        $oneDaySalary = $grossSalary / $totalDaysInCycle;
        return $oneDaySalary * $lopDays;
    }

    /**
     * Get actual YTD (Year-To-Date) taxable earnings for the employee from FY start to current slot's to_date
     */
    protected function getActualYTDEarnings($employeeId, $fyStart, $currentSlotToDate)
    {
        return PayrollComponentsEmployeesTrack::where('employee_id', $employeeId)
            ->where('firm_id', Session::get('firm_id'))
            ->where('nature', 'earning')
            ->where('taxable', true)
            ->whereBetween('salary_period_from', [$fyStart, $currentSlotToDate])
            ->sum('amount_payable');
    }

    /**
     * Get the number of remaining months in the financial year, including the current slot's month if not fully paid
     */
    protected function getRemainingMonthsInFY($currentSlotToDate, $fyEnd)
    {
        $current = Carbon::parse($currentSlotToDate);
        $fyEnd = Carbon::parse($fyEnd);
        // If slot ends after FY, return 0
        if ($current->gt($fyEnd)) return 0;
        // Months left including current month
        return $current->diffInMonths($fyEnd) + 1;
    }

    /**
     * Projected annual income = YTD actual + (current month earnings × remaining months) - standard deduction
     */
    protected function getProjectedAnnualIncome($employeeId, $currentMonthlyEarnings, $currentSlotToDate)
    {
        $fyStart = session('fy_start');
        $fyEnd = session('fy_end');
        $actualYTDEarnings = $this->getActualYTDEarnings($employeeId, $fyStart, $currentSlotToDate);
        $remainingMonths = $this->getRemainingMonthsInFY($currentSlotToDate, $fyEnd);
        
        $projectedRemainingEarnings = $currentMonthlyEarnings * $remainingMonths;
        return $actualYTDEarnings + $projectedRemainingEarnings - 75000;
    }

    protected function isEmployeeInSlotExecutionGroup($employeeId, $payrollSlot)
    {
        return \App\Models\Hrms\EmployeesSalaryExecutionGroup::where('firm_id', Session::get('firm_id'))
            ->where('salary_execution_group_id', $payrollSlot->salary_execution_group_id)
            ->where('employee_id', $employeeId)
            ->exists();
    }


    public function render()
    {

        return view()->file(app_path('Livewire/Hrms/Payroll/blades/payroll-cycles.blade.php'));
    }

}
