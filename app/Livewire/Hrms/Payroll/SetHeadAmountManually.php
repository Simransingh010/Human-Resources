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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Flux;

class SetHeadAmountManually extends Component
{
    public $salcomponentEmployees;
    public $salcomponents;
    public $entries = []; // [employee_id][component_id] = amount
    public $componentRemarks = []; // [employee_id][component_id] = remarks
    public $currentEmployee;
    public $allRemarks;
    
    public $payrollSlotId;
    public $salary_execution_group_id;
    public $slot;
    public $payroll_slots_cmd_id;
    public $assignedMatrix = []; // [employee_id][component_id] = true/false

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
            ->where('amount_type', 'static_unknown')
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
                ->where('amount_type', 'static_unknown')
                ->whereIn('employee_id', $employeeIds);
        })->get();

        // Initialize all to empty string and assigned matrix
        foreach ($this->salcomponentEmployees as $salcomponentEmployee) {
            foreach ($this->salcomponents as $salcomponent) {
                $this->entries[$salcomponentEmployee->id][$salcomponent->id] = '';
                $this->componentRemarks[$salcomponentEmployee->id][$salcomponent->id] = '';
                $this->assignedMatrix[$salcomponentEmployee->id][$salcomponent->id] = false;
            }
        }

        // Build assignment matrix based on existing assignments
        $assignments = SalaryComponentsEmployee::where('firm_id', Session::get('firm_id'))
            ->where('amount_type', 'static_unknown')
            ->whereIn('employee_id', $employeeIds)
            ->whereIn('salary_component_id', $this->salcomponents->pluck('id'))
            ->get();

        foreach ($assignments as $assn) {
            $this->assignedMatrix[$assn->employee_id][$assn->salary_component_id] = true;
        }

        // Load previously saved entries and remarks from payroll_components_employees_tracks
        $savedEntries = PayrollComponentsEmployeesTrack::where('payroll_slot_id', $this->slot->id)
            ->whereIn('employee_id', $employeeIds)
            ->whereIn('salary_component_id', $this->salcomponents->pluck('id'))
            ->get();

        foreach ($savedEntries as $entry) {
            $this->entries[$entry->employee_id][$entry->salary_component_id] = $entry->amount_full;
            $this->componentRemarks[$entry->employee_id][$entry->salary_component_id] = $entry->remarks;
        }

        // Create Slot Command Log (if not already created)
        $payroll_slots_cmd_rec = PayrollSlotsCmd::create([
            'firm_id' => Session::get('firm_id'),
            'payroll_slot_id' => $this->slot->id,
            'user_id' => auth()->user()->id,
            'run_payroll_remarks' => 'Set Head Amount Manually',
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
        // Guard: component must be assigned to the employee
        $isAssigned = $this->assignedMatrix[$employeeId][$componentId] ?? false;
        if (!$isAssigned) {

            Flux::toast(
                variant: 'error',
                heading: 'Not assigned',
                text: 'This head is not assigned to the employee. Please assign it first.'
            );
            return;
        }

        $this->entries[$employeeId][$componentId] = $value;
        if ($value === '' || $value === null) {
            $amount = 0;
        } else {
            if (!is_numeric($value)) {
                Flux::toast(
                    variant: 'error',
                    heading: 'Invalid amount',
                    text: 'Please enter a numeric amount.'
                );
                return;
            }
            $amount = (float)$value;
        }
        $salcomponentEmployeesDet = SalaryComponentsEmployee::where('firm_id', Session::get('firm_id'))
            ->where('salary_component_id', $componentId)
            ->where('employee_id', $employeeId)
            ->first();

        if (!$salcomponentEmployeesDet) {
            Flux::toast(
                variant: 'error',
                heading: 'Missing assignment',
                text: 'Component is not assigned to this employee.'
            );
            return;
        }

        try {
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
                    'remarks' => $this->componentRemarks[$employeeId][$componentId] ?? null,
                    'entry_type' => 'override'
                ]
            );

            $employee = $this->salcomponentEmployees->firstWhere('id', $employeeId);
            $component = $this->salcomponents->firstWhere('id', $componentId);
            $employeeName = $employee ? trim(($employee->fname ?? '') . ' ' . ($employee->mname ?? '') . ' ' . ($employee->lname ?? '')) : (string) $employeeId;
            $employeeCode = $employee && $employee->emp_job_profile ? $employee->emp_job_profile->employee_code : null;
            if ($employeeCode) {
                $employeeName .= ' (' . $employeeCode . ')';
            }
            $componentName = $component ? ($component->title . (isset($component->nature) ? ' [' . $component->nature . ']' : '')) : (string) $componentId;

            Flux::toast(
                variant: 'success',
                heading: 'Saved',
                text: $employeeName . ' — ' . $componentName . ': ' . number_format((float) $amount, 2)
            );
        } catch (\Throwable $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error saving entry',
                text: $e->getMessage()
            );
        }
    }

    public function saveRemarks($employeeId, $componentId)
    {
        $track = PayrollComponentsEmployeesTrack::where([
            'payroll_slot_id' => $this->slot->id,
            'employee_id' => $employeeId,
            'salary_component_id' => $componentId,
        ])->first();

        if ($track) {
            $track->update([
                'remarks' => $this->componentRemarks[$employeeId][$componentId]
            ]);
        }

        $this->dispatchBrowserEvent('close-modal', ['name' => "mdl-remarks-{$employeeId}-{$componentId}"]);
    }

    public function viewAllRemarks($employeeId)
    {
        $this->currentEmployee = $this->salcomponentEmployees->firstWhere('id', $employeeId);
        $this->allRemarks = $this->componentRemarks[$employeeId] ?? [];
        $this->dispatchBrowserEvent('open-modal', ['name' => 'mdl-view-all-remarks']);
    }

    public function save()
    {
        foreach ($this->entries as $employeeId => $componentAmounts) {
            foreach ($componentAmounts as $componentId => $amount) {
                if ($amount !== '') {
                    $this->saveSingleEntry($employeeId, $componentId, $amount);
                }
            }
        }
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/set-head-amount-manually.blade.php'));
    }
}
