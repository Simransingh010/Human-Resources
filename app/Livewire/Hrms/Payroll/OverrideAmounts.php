<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\Employee;
use App\Models\Hrms\EmployeesSalaryExecutionGroup;
use App\Models\Hrms\PayrollSlot;
use App\Models\Hrms\PayrollSlotsCmd;
use App\Models\Hrms\SalaryComponent;
use Livewire\WithPagination;
use App\Models\Hrms\PayrollComponentsEmployeesTrack;
use App\Models\Hrms\SalaryComponentsEmployee;
use Livewire\Component;
use Illuminate\Support\Facades\Session;
use Flux;
use Illuminate\Support\Facades\DB;

class OverrideAmounts extends Component
{
    use WithPagination;
    public $salcomponentEmployees;
    public $salcomponents;

    public $salaryComponentEmployeesGrouped;
    public $entries = []; // [employee_id][component_id] = amount
    public $payrollSlotId;
    public $salary_execution_group_id;
    public $slot;
    public $payroll_slots_cmd_id;

    public $perPage = 10;

    public $visibleComponentIds = [];
    public $hiddenComponentIds = [];
    public $showRemarkModal = false;
    public $remark = '';
    public $currentEmployeeId = null;
    public $currentComponentId = null;

    public function mountOld($payrollSlotId)
    {
        $this->payrollSlotId = $payrollSlotId;

        // Fetch slot and group ID with eager loading
        $this->slot = PayrollSlot::findOrFail($payrollSlotId);
        $this->salary_execution_group_id = $this->slot->salary_execution_group_id;
        $firmId = Session::get('firm_id');

        // Single DB call to get employee IDs
        $employeeIds = EmployeesSalaryExecutionGroup::where('firm_id', $firmId)
            ->where('salary_execution_group_id', $this->salary_execution_group_id)
            ->pluck('employee_id');

        // Load employees efficiently
//        $employees = Employee::whereIn('id', $employeeIds)->paginate($this->perPage);
// Fetch paginated results (no additional get())
        $paginatedEmployees = Employee::whereIn('id', $employeeIds)->paginate($this->perPage);

// Key the paginated results by id if needed
        $employees = $paginatedEmployees->getCollection()->keyBy('id');

        // Eager-load relevant salary components employees (avoid grouping here)
        $salComponentsEmployees = SalaryComponentsEmployee::with(['salary_component'])
            ->where('firm_id', $firmId)
            ->whereIn('employee_id', $employeeIds)
            ->get();

        // Map employees directly, avoiding unnecessary group operations
        $this->salcomponentEmployees = $employees->values();

        // Fetch Salary Components efficiently (distinct to avoid duplicates)
        $this->salcomponents = SalaryComponent::whereHas('salary_components_employees', function ($query) use ($firmId, $employeeIds) {
            $query->where('firm_id', $firmId)
                ->whereIn('employee_id', $employeeIds)
                ->whereIn('amount_type', ['static_known', 'calculated_known']);
        })->distinct()->get();

        // Set default visible/hidden components clearly
        $componentIds = $this->salcomponents->pluck('id')->toArray();
        $this->visibleComponentIds = array_slice($componentIds, 0, 20);
        $this->hiddenComponentIds = array_slice($componentIds, 20);

        // Initialize entries efficiently
        foreach ($employeeIds as $employeeId) {
            foreach ($componentIds as $componentId) {
                $this->entries[$employeeId][$componentId] = '';
            }
        }

        // Fetch saved entries efficiently
        $savedEntries = PayrollComponentsEmployeesTrack::where('payroll_slot_id', $this->slot->id)
            ->whereIn('employee_id', $employeeIds)
            ->whereIn('salary_component_id', $componentIds)
            ->pluck('amount_full', DB::raw('CONCAT(employee_id, "_", salary_component_id)'));

        // Populate saved entries quickly
        foreach ($savedEntries as $key => $amount) {
            [$employeeId, $componentId] = explode('_', $key);
            $this->entries[$employeeId][$componentId] = $amount;
        }

        // Create Slot Command Log (only if needed)
        $payroll_slots_cmd_rec = PayrollSlotsCmd::firstOrCreate(
            [
                'firm_id' => $firmId,
                'payroll_slot_id' => $this->slot->id,
                'user_id' => auth()->id(),
                'payroll_slot_status' => 'IP',
            ],
            ['run_payroll_remarks' => 'Override Amounts']
        );

        $this->payroll_slots_cmd_id = $payroll_slots_cmd_rec->id;
    }
    public function mount($payrollSlotId)
    {
        $this->payrollSlotId = $payrollSlotId;
        $this->slot = PayrollSlot::find($payrollSlotId);
        $this->salary_execution_group_id = $this->slot->salary_execution_group_id;

        $employeeIds = EmployeesSalaryExecutionGroup::where('firm_id', Session::get('firm_id'))
            ->where('salary_execution_group_id', $this->salary_execution_group_id)
            ->pluck('employee_id');
        // Step 1: Paginate employees first
        $paginatedEmployees = Employee::whereIn('id', $employeeIds)
            ->paginate($this->perPage);

// Step 2: Fetch related SalaryComponentsEmployee records efficiently
        $employeeIdsOnPage = $paginatedEmployees->pluck('id');

        $salaryComponentEmployees = SalaryComponentsEmployee::where('firm_id', Session::get('firm_id'))
            ->whereIn('employee_id', $employeeIdsOnPage)
            ->with('salary_component')
            ->get()
            ->groupBy('employee_id');

// Assign to public properties for usage in Blade
        $this->salcomponentEmployees = $paginatedEmployees;
        $this->salaryComponentEmployeesGrouped = $salaryComponentEmployees;


//        $this->salcomponentEmployees = SalaryComponentsEmployee::where('firm_id', Session::get('firm_id'))
//            ->whereIn('employee_id', $employeeIds)
//            ->with(['salary_component', 'employee'])
//            ->get()
//            ->groupBy('employee_id')
//            ->map(function ($group) {
//                return $group->first()->employee;
//            })
//            ->values();
//
        $this->salcomponents = SalaryComponent::whereHas('salary_components_employees', function ($query) use ($employeeIds) {
            $query->where('firm_id', Session::get('firm_id'))
                ->whereIn('employee_id', $employeeIds)
                ->whereIn('amount_type', ['static_known', 'calculated_known']);
        })->get();

        // Set default visible/hidden components (show first 5 as visible)
        $this->visibleComponentIds = $this->salcomponents->pluck('id')->take(20)->toArray();
        $this->hiddenComponentIds = $this->salcomponents->pluck('id')->slice(0)->toArray();

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
            'run_payroll_remarks' => 'Override Amounts',
            'payroll_slot_status' => 'IP',
        ]);
        $this->payroll_slots_cmd_id = $payroll_slots_cmd_rec->id;
    }


    public function prepareRemark($employeeId, $componentId)
    {
        $this->currentEmployeeId = $employeeId;
        $this->currentComponentId = $componentId;

        // Load existing remark if available
        $track = PayrollComponentsEmployeesTrack::where('payroll_slot_id', $this->slot->id)
            ->where('employee_id', $employeeId)
            ->where('salary_component_id', $componentId)
            ->first();

        $this->remark = $track ? ($track->remarks ?? '') : '';
        
        // Show the modal after setting the data
        $this->modal('mdl-remark')->show();
    }

    public function resetRemark()
    {
        $this->reset(['remark', 'currentEmployeeId', 'currentComponentId']);
    }

    public function saveRemark()
    {
        if (!$this->currentEmployeeId || !$this->currentComponentId) {
            return;
        }

        PayrollComponentsEmployeesTrack::updateOrCreate(
            [
                'payroll_slot_id' => $this->slot->id,
                'employee_id' => $this->currentEmployeeId,
                'salary_component_id' => $this->currentComponentId,
                'salary_period_from' => $this->slot->from_date,
                'salary_period_to' => $this->slot->to_date,
            ],
            [
                'remarks' => $this->remark,
                'entry_type' => 'manual'
            ]
        );

        $this->resetRemark();
        $this->modal('mdl-remark')->close();
        
        Flux::toast(
            variant: 'success',
            heading: 'Success',
            text: 'Remark saved successfully',
        );
    }

    public function saveSingleEntry($employeeId, $componentId, $value)
    {
        $this->entries[$employeeId][$componentId] = $value;
        $amount = is_numeric($value) ? (float)$value : 0;
        $salcomponentEmployeesDet = SalaryComponentsEmployee::where('firm_id', Session::get('firm_id'))
            ->where('salary_component_id', $componentId)
            ->where('employee_id', $employeeId)
            ->first();

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
                'salary_cycle_id' => $this->slot->salary_cycle_id
            ]);
    }

    public function showAllComponents()
    {
        $this->visibleComponentIds = $this->salcomponents->pluck('id')->toArray();
        $this->hiddenComponentIds = [];
    }

    public function showDefaultComponents()
    {
        $this->visibleComponentIds = $this->salcomponents->pluck('id')->take(20)->toArray();
        $this->hiddenComponentIds = $this->salcomponents->pluck('id')->slice(0)->toArray();
    }

    public function toggleComponentVisibility($componentId)
    {
        if (in_array($componentId, $this->visibleComponentIds)) {
            $this->visibleComponentIds = array_diff($this->visibleComponentIds, [$componentId]);
            $this->hiddenComponentIds[] = $componentId;
        } else {
            $this->visibleComponentIds[] = $componentId;
            $this->hiddenComponentIds = array_diff($this->hiddenComponentIds, [$componentId]);
        }
    }

    public function updatedShowRemarkModal($value)
    {
        if (!$value) {
            $this->reset(['remark', 'currentEmployeeId', 'currentComponentId']);
        }
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/override-amounts.blade.php'), [
            'salcomponents' => $this->salcomponents,
        ]);
    }
}
