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
use Flux;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Hrms\EmpAttendance;
use App\Models\Hrms\PayrollStepPayrollSlotCmd;

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

    public function mount()
    {
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
        $query = PayrollSlot::where('firm_id', Session::get('firm_id'))
            ->where('salary_cycle_id', $this->selectedCycleId)
            ->where('salary_execution_group_id', $groupId);
        $this->payrollSlots = $query->orderBy('from_date', 'asc')->get();
        //        dd($this->payrollSlots);
    }

    public function startPayroll($slot_id)
    {
        try {
            // Get all active payroll steps
            $payrollSteps = PayrollStep::where('firm_id', Session::get('firm_id'))
                ->where('is_inactive', false)
                ->orderBy('step_order')
                ->get();

            // Store each step in PayrollStepPayrollSlot
            foreach ($payrollSteps as $payrollStep) {
                PayrollStepPayrollSlot::create([
                    'firm_id' => Session::get('firm_id'),
                    'payroll_slot_id' => $slot_id,
                    'payroll_step_id' => $payrollStep->id,
                    'step_code_main' => $payrollStep->step_code_main,
                    'payroll_step_status' => 'NS' // Not Started
                ]);
            }

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

            Flux::toast(
                variant: 'success',
                heading: 'Changes saved.',
                text: 'Payroll Started',
            );

            // Refresh slot details
            $this->loadSlotDetails($slot_id);

        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to start payroll: ' . $e->getMessage(),
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

            Flux::toast(
                variant: 'success',
                heading: 'Changes saved.',
                text: 'Payroll Re-Started',
            );

            // Refresh slot details
            $this->loadSlotDetails($slot_id);

        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to start payroll: ' . $e->getMessage(),
            );
        }
    }

    public function completePayroll($slot_id, $cycle_id, $execution_group_id)
    {
        try {

            // Create completion command log first as we have to update the payroll_slots_cmd_id  in table payroll_components_employees_tracks
            $payroll_slots_cmd_rec = PayrollSlotsCmd::create([
                'firm_id' => Session::get('firm_id'),
                'payroll_slot_id' => $slot_id,
                'user_id' => auth()->user()->id,
                'run_payroll_remarks' => 'Completed Payroll',
                'payroll_slot_status' => 'CM',
            ]);

            $this->createPayrollTracks($slot_id, $cycle_id, $execution_group_id, $payroll_slots_cmd_rec->id);


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

            // Refresh slot details
            $this->loadSlotDetails($slot_id);

            Flux::toast(
                variant: 'success',
                heading: 'Changes saved.',
                text: 'Payroll Completed Successfully',
            );

        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to complete payroll: ' . $e->getMessage(),
            );
        }
    }

    protected function createPayrollTracks($slot_id, $cycle_id, $execution_group_id, $payroll_slots_cmd_id)
    {
        try {
            // Get the payroll slot
            $payrollSlot = PayrollSlot::findOrFail($slot_id);

            // Get employees from the selected execution group
            $employeeIds = EmployeesSalaryExecutionGroup::where('firm_id', Session::get('firm_id'))
                ->where('salary_execution_group_id', $execution_group_id)
                ->pluck('employee_id');

            // Get the salary cycle for calculating days
            $salaryCycle = SalaryCycle::findOrFail($cycle_id);
            $cycleDays = 30;

            foreach ($employeeIds as $employeeId) {
                // Get employee's salary components
                $salaryComponents = SalaryComponentsEmployee::where('firm_id', Session::get('firm_id'))
                    ->where('employee_id', $employeeId)
                    ->where(function ($query) use ($payrollSlot) {
                        $query->where(function ($q) use ($payrollSlot) {
                            $q->where('effective_from', '<=', $payrollSlot->to_date)
                                ->where(function ($q2) use ($payrollSlot) {
                                    $q2->whereNull('effective_to')
                                        ->orWhere('effective_to', '>=', $payrollSlot->from_date);
                                });
                        });
                    })
                    ->get();
//                if($employeeId == 1244){dd($salaryComponents);}if ($employeeId == 1244) {
//                    $debug = $salaryComponents->map(function($component) {
//                        return [
//                            'name' => optional($component->salary_component)->title, // or $component->salary_component->title if relation loaded
//                            'amount' => $component->amount,
//                            'calculation_json' => $component->calculation_json,
//                        ];
//                    });
//                    dd($debug);
//                }

                // Get employee's salary days
                $salaryDays = EmployeesSalaryDay::where('firm_id', Session::get('firm_id'))
                    ->where('payroll_slot_id', $slot_id)
                    ->where('employee_id', $employeeId)
                    ->first();

                $totalDeductionDays = 0;
                if ($salaryDays) {
                    $totalDeductionDays = $salaryDays->void_days_count + $salaryDays->lop_days_count;
                }

                // First create all non-tax components
                foreach ($salaryComponents as $component) {
                    if ($component->component_type === 'tds' || $component->amount_type==='static_unknown' ||  $component->amount_type==='calculated_unknown' ) {
                        continue; // Skip tax components for now
                    }

                    // Calculate per day amount
                    $perDayAmount = $component->amount / $cycleDays;

                    // Calculate deduction amount
                    $deductionAmount = $perDayAmount * $totalDeductionDays;

                    // Calculate final payable amount
                    $amountPayable = $component->amount - $deductionAmount;

//                    if($employeeId == 1244 ) {
//                        dd($component, $perDayAmount, $deductionAmount, $amountPayable);
//                    }

                    // Create or update PayrollComponentsEmployeesTrack
                    PayrollComponentsEmployeesTrack::updateOrCreate(
                        [
                            'firm_id' => Session::get('firm_id'),
                            'payroll_slot_id' => $slot_id,
                            'employee_id' => $employeeId,
                            'salary_component_id' => $component->salary_component_id
                        ],
                        [
                            'payroll_slots_cmd_id' => $payroll_slots_cmd_id,
                            'salary_template_id' => $component->salary_template_id,
                            'salary_component_group_id' => $component->salary_component_group_id,
                            'sequence' => $component->sequence,
                            'nature' => $component->nature,
                            'component_type' => $component->component_type,
                            'amount_type' => $component->amount_type,
                            'taxable' => $component->taxable,
                            'calculation_json' => $component->calculation_json,
                            'salary_period_from' => $payrollSlot->from_date,
                            'salary_period_to' => $payrollSlot->to_date,
                            'user_id' => Session::get('user_id'),
                            'amount_full' => $component->amount,
                            'amount_payable' => $amountPayable,
                            'amount_paid' => 0,
                            'salary_advance_id' => null,
                            'salary_arrear_id' => null,
                            'salary_cycle_id' => $cycle_id
                        ]
                    );
                }

                // Now calculate and create tax component
                $this->calculateAndCreateTaxComponent($employeeId, $slot_id, $cycle_id, $payrollSlot,$payroll_slots_cmd_id);
            }
        } catch (\Exception $e) {
            throw new \Exception("Error creating payroll tracks: " . $e->getMessage());
        }
    }

    protected function calculateAndCreateTaxComponent($employeeId, $slot_id, $cycle_id, $payrollSlot,$payroll_slots_cmd_id)
    {
        try {
            // Get TDS component first - we'll need it for both calculations
            $tdsComponent = SalaryComponent::where('firm_id', Session::get('firm_id'))
                ->where('component_type', 'tds')
                ->with('salary_component_group')
                ->first();

            if (!$tdsComponent) {
                throw new \Exception("TDS component not found");
            }

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
                throw new \Exception("No active tax regime found for employee");
            }

            // 2. Get total earnings for the month from salary components
            $salaryCycleEarnings = SalaryComponentsEmployee::where('employee_id', $employeeId)
                ->where('firm_id', Session::get('firm_id'))
                ->where('nature', 'earning')
                ->where('taxable', true)
                ->where(function ($query) {
                    $query->whereNull('effective_to')
                        ->orWhere('effective_to', '>', now());
                })
                ->sum('amount');

            $tdsComponentEmployee = SalaryComponentsEmployee::where('employee_id', $employeeId)
                ->where('firm_id', Session::get('firm_id'))
                ->where('component_type', 'tds')
                ->first();

            // Get existing earnings from payroll tracks for this slot
            $existingEarnings = PayrollComponentsEmployeesTrack::where('employee_id', $employeeId)
                ->where('firm_id', Session::get('firm_id'))
                ->where('payroll_slot_id', $slot_id)
                ->where('nature', 'earning')
                ->where('taxable', true)
                ->sum('amount_payable');

            $monthlyEarnings = $salaryCycleEarnings + $existingEarnings;

            // 3. Calculate annual income
            $annualIncome = ($monthlyEarnings * 12) - 75000; // Standard deduction of 75000

            // 4. Get tax brackets for the regime
            $taxBrackets = $employeeTaxRegime->tax_regime->tax_brackets()
                ->where('type', 'SLAB')
                ->orderBy('income_from')
                ->get();

            // 5. Calculate tax for each slab
            $totalTax = 0;
            $remainingIncome = $annualIncome;

            foreach ($taxBrackets as $bracket) {
                $slabAmount = min(
                    $remainingIncome,
                    ($bracket->income_to ?? PHP_FLOAT_MAX) - $bracket->income_from
                );

                if ($slabAmount > 0) {
                    $taxForSlab = round(($slabAmount * $bracket->rate) / 100);
                    $totalTax += $taxForSlab;
                    $remainingIncome -= $slabAmount;
                }

                if ($remainingIncome <= 0) {
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

            $monthlyTax = ($total_tds_remaining_for_year) / $total_count_of_salary_slots_remaining;
            $monthlyTax = $this->roundOffTax($monthlyTax);

            // Check if TDS entry already exists for this slot
//            $existingTdsEntry = PayrollComponentsEmployeesTrack::where('firm_id', Session::get('firm_id'))
//                ->where('payroll_slot_id', $slot_id)
//                ->where('employee_id', $employeeId)
//                ->where('salary_component_id', $tdsComponent->id)
//                ->first();
//
//            if ($existingTdsEntry) {
//                // Skip if entry already exists
//                return;
//            }

            // 8. Create PayrollComponentsEmployeesTrack for TDS
            PayrollComponentsEmployeesTrack::firstOrCreate(
                [
                    'firm_id' => Session::get('firm_id'),
                    'payroll_slot_id' => $slot_id,
                    'employee_id' => $employeeId,
                    'salary_component_id' => $tdsComponent->id,
                ],
                [

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
                    'salary_cycle_id' => $cycle_id
                ]
            );

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

    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/payroll-cycles.blade.php'));
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

            if ($step->step_code_main === 'fetch_attendance') {
                // Fetch employees for the slot
                $employeeIds = EmployeesSalaryExecutionGroup::where('firm_id', Session::get('firm_id'))
                    ->where('salary_execution_group_id', $slot->salary_execution_group_id)
                    ->pluck('employee_id')
                    ->toArray();

                // Fetch attendance records
                $attendanceRecords = EmpAttendance::where('firm_id', Session::get('firm_id'))
                    ->whereIn('employee_id', $employeeIds)
                    ->whereBetween('work_date', [$slot->from_date, $slot->to_date])
                    ->get();

                // Calculate cycle days
                $fromDate = \Carbon\Carbon::parse($slot->from_date);
                $toDate = \Carbon\Carbon::parse($slot->to_date);
                $cycleDays = $fromDate->diffInDays($toDate) + 1;

                // Process attendance for each employee and update EmployeesSalaryDay
                foreach ($employeeIds as $employeeId) {
                    $employeeAttendance = $attendanceRecords->where('employee_id', $employeeId);
                    $lopDaysCount = $employeeAttendance->where('attendance_status_main', 'A')->count();

                    EmployeesSalaryDay::updateOrCreate(
                        [
                            'firm_id' => Session::get('firm_id'),
                            'payroll_slot_id' => $slotId,
                            'employee_id' => $employeeId,
                        ],
                        [
                            'cycle_days' => $cycleDays,
                            'void_days_count' => 0,
                            'lop_days_count' => $lopDaysCount,
                        ]
                    );
                }

                // Update step status in PayrollStepPayrollSlot
                $stepSlot->update(['payroll_step_status' => 'RN']); // Running

                // Create entry in PayrollStepPayrollSlotCmd for this run
                PayrollStepPayrollSlotCmd::create([
                    'firm_id' => Session::get('firm_id'),
                    'payroll_step_payroll_slot_id' => $stepSlot->id,
                    'payroll_step_status' => 'RN', // Run
                    'step_remarks' => sprintf(
                        'Attendance fetched and processed for %d employees. Found %d LOP days.',
                        count($employeeIds),
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
                    'step_title' => PayrollStep::find($stepId)->step_title,
                    'remark' => PayrollStep::find($stepId)->step_title . "Completed", // <-- whatever remark you want to pass, set $remark earlier
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

    public function employeeTaxComponentsStep($stepId, $slotId)
    {
        $this->selectedSlotId = $slotId;
        $this->selectedStepId = $stepId;
        $this->modal('employee-tax-components')->show();
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

}
