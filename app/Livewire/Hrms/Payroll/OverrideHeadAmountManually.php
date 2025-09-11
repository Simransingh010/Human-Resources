<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\Employee;
use App\Models\Hrms\EmployeesSalaryExecutionGroup;
use App\Models\Hrms\PayrollSlot;
use App\Models\Hrms\PayrollSlotsCmd;
use App\Models\Hrms\SalaryComponent;
use App\Models\Hrms\PayrollComponentsEmployeesTrack;
use App\Models\Hrms\SalaryComponentsEmployee;
use Livewire\Component;
use Illuminate\Support\Facades\Session;
use Flux;


class OverrideHeadAmountManually extends Component
{
    public $salcomponentEmployees;
    public $salcomponents;

    public $entries = []; // [employee_id][component_id] = amount
    public $dirtyCells = []; // ["employeeId:componentId" => true]
    public $payrollSlotId;
    public $salary_execution_group_id;
    public $slot;
    public $payroll_slots_cmd_id;

    public $searchName = '';

    public function mount($payrollSlotId)
    {
        $this->payrollSlotId = $payrollSlotId;
        $this->slot = PayrollSlot::find($payrollSlotId);
        $this->salary_execution_group_id = $this->slot->salary_execution_group_id;

        $employeeIds = EmployeesSalaryExecutionGroup::where('firm_id', Session::get('firm_id'))
            ->where('salary_execution_group_id', $this->salary_execution_group_id)
            ->pluck('employee_id');

        $this->salcomponentEmployees = SalaryComponentsEmployee::where('firm_id', Session::get('firm_id'))
            ->whereIn('amount_type', ['static_known','calculated_known'])
            ->whereIn('employee_id', $employeeIds)
            ->with(['salary_component', 'employee.emp_job_profile'])
            ->get()
            ->groupBy('employee_id')
            ->map(function ($group) {
                return $group->first()->employee;
            })
            ->values();

        $this->salcomponents = SalaryComponent::whereHas('salary_components_employees', function ($query) use ($employeeIds) {
            $query->where('firm_id', Session::get('firm_id'))
                ->whereIn('amount_type', ['static_known','calculated_known'])
                ->whereIn('employee_id', $employeeIds);
        })->get();

        // Initialize all to empty string
        foreach ($this->salcomponentEmployees as $salcomponentEmployee) {
            foreach ($this->salcomponents as $salcomponent) {
                $this->entries[$salcomponentEmployee->id][$salcomponent->id] = '';
            }
        }

        // Load previously saved entries from payroll_components_employees_tracks
        $savedEntries = PayrollComponentsEmployeesTrack::where('payroll_slot_id', $this->slot->id)
            ->whereIn('employee_id', $employeeIds)
            ->whereIn('salary_component_id', $this->salcomponents->pluck('id'))
            ->get();

        foreach ($savedEntries as $entry) {
            $this->entries[$entry->employee_id][$entry->salary_component_id] = $entry->amount_full;
        }

        // Create Slot Command Log (if not already created)
        $payroll_slots_cmd_rec = PayrollSlotsCmd::create([
            'firm_id' => Session::get('firm_id'),
            'payroll_slot_id' => $this->slot->id,
            'user_id' => auth()->user()->id,
            'run_payroll_remarks' => 'Overide Head Amount Manually',
            'payroll_slot_status' => 'IP',
        ]);
        $this->payroll_slots_cmd_id = $payroll_slots_cmd_rec->id;
    }

    public function getFilteredSalcomponentEmployeesProperty()
    {
        if (empty(trim($this->searchName))) {
            return $this->salcomponentEmployees;
        }

        return $this->salcomponentEmployees->filter(function ($employee) {
            $fullName = trim($employee->fname . ' ' . ($employee->mname ?? '') . ' ' . $employee->lname);
            $employeeCode = optional($employee->emp_job_profile)->employee_code;
            $query = strtolower(trim($this->searchName));
            return str_contains(strtolower($fullName), $query) || ($employeeCode && str_contains(strtolower($employeeCode), $query));
        });
    }

    public function saveSingleEntry($employeeId, $componentId, $value)
    {
        $this->entries[$employeeId][$componentId] = $value;
        $amount = is_numeric($value) ? (float)$value : 0;
        $salcomponentEmployeesDet = SalaryComponentsEmployee::where('firm_id', Session::get('firm_id'))
            ->where('salary_component_id', $componentId)
            ->where('employee_id', $employeeId)
            ->first(); // Later.. From date and End Date also need to be check in future as there may be  same components allocated  at different period


            PayrollComponentsEmployeesTrack::updateOrCreate(
                [
                    'payroll_slot_id' => $this->slot->id,
                    'employee_id' => $employeeId,
                    'salary_component_id' => $componentId,
                    'salary_period_from' => $this->slot->from_date,
                    'salary_period_to' => $this->slot->to_date,
                ],
                [
                    'payroll_slots_cmd_id' => $this->payroll_slots_cmd_id,
                    'salary_template_id' => $salcomponentEmployeesDet->salary_template_id,
                    'salary_component_group_id' => $salcomponentEmployeesDet->salary_component_group_id,
                    'amount_full' => $amount,
                    'amount_payable' => $amount,
                    'amount_paid' => 0,
                    'firm_id' => $this->slot->firm_id,
                    'nature' => $salcomponentEmployeesDet->nature,
                    'component_type' => $salcomponentEmployeesDet->component_type,
                    'amount_type' => $salcomponentEmployeesDet->amount_type,
                    'sequence' => $salcomponentEmployeesDet->sequence,
                    'taxable' => $salcomponentEmployeesDet->taxable,
                    'calculation_json' => $salcomponentEmployeesDet->calculation_json,
                    'salary_advance_id' => NULL,
                    'salary_arrear_id' => NULL,
                    'user_id' => auth()->user()->id,
                    'salary_cycle_id' => $this->slot->salary_cycle_id,
                    'entry_type' => 'override',
                ]);

                $employee = $this->salcomponentEmployees->firstWhere('id', $employeeId);
                $component = $this->salcomponents->firstWhere('id', $componentId);
                $employeeName = $employee ? trim(($employee->fname ?? '') . ' ' . ($employee->mname ?? '') . ' ' . ($employee->lname ?? '')) : (string) $employeeId;
                $employeeCode = $employee && $employee->emp_job_profile ? $employee->emp_job_profile->employee_code : null;
                if ($employeeCode) {
                    $employeeName .= ' (' . $employeeCode . ')';
                }
                $componentName = $component ? ($component->title . (isset($component->nature) ? ' [' . $component->nature . ']' : '')) : (string) $componentId;

                Flux::toast($employeeName . ' — ' . $componentName . ': ' . number_format((float) $amount, 2));

                // Clear dirty flag for this cell if set
                $key = $employeeId . ':' . $componentId;
                if (isset($this->dirtyCells[$key])) {
                    unset($this->dirtyCells[$key]);
                }

    }

    public function markDirty($employeeId, $componentId)
    {
        $this->dirtyCells[$employeeId . ':' . $componentId] = true;
    }

    public function save()
    {
        // Save only dirty (changed) cells
        if (empty($this->dirtyCells)) {
            Flux::toast('Nothing to save.');
            return;
        }

        foreach (array_keys($this->dirtyCells) as $key) {
            [$employeeId, $componentId] = explode(':', $key);
            $amount = $this->entries[$employeeId][$componentId] ?? '';
            if ($amount === '') {
                continue;
            }

            $value = is_numeric($amount) ? (float)$amount : 0;
            $salcomponentEmployeesDet = SalaryComponentsEmployee::where('firm_id', Session::get('firm_id'))
                ->where('salary_component_id', $componentId)
                ->where('employee_id', $employeeId)
                ->first();

            if ($salcomponentEmployeesDet) {
                PayrollComponentsEmployeesTrack::updateOrCreate(
                    [
                        'payroll_slot_id' => $this->slot->id,
                        'employee_id' => $employeeId,
                        'salary_component_id' => $componentId,
                        'salary_period_from' => $this->slot->from_date,
                        'salary_period_to' => $this->slot->to_date,
                    ],
                    [
                        'payroll_slots_cmd_id' => $this->payroll_slots_cmd_id,
                        'salary_template_id' => $salcomponentEmployeesDet->salary_template_id,
                        'salary_component_group_id' => $salcomponentEmployeesDet->salary_component_group_id,
                        'amount_full' => $value,
                        'amount_payable' => $value,
                        'amount_paid' => 0,
                        'firm_id' => $this->slot->firm_id,
                        'nature' => $salcomponentEmployeesDet->nature,
                        'component_type' => $salcomponentEmployeesDet->component_type,
                        'amount_type' => $salcomponentEmployeesDet->amount_type,
                        'sequence' => $salcomponentEmployeesDet->sequence,
                        'taxable' => $salcomponentEmployeesDet->taxable,
                        'calculation_json' => $salcomponentEmployeesDet->calculation_json,
                        'salary_advance_id' => NULL,
                        'salary_arrear_id' => NULL,
                        'user_id' => auth()->user()->id,
                        'salary_cycle_id' => $this->slot->salary_cycle_id,
                        'entry_type' => 'override',
                    ]
                );
            }

            // Clear dirty flag after save
            unset($this->dirtyCells[$key]);
        }

        Flux::toast('Only changed entries have been saved.');
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/override-head-amount-manually.blade.php'));

    }
}
