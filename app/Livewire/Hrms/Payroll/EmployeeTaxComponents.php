<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\SalaryComponent;
use App\Models\Hrms\PayrollComponentsEmployeesTrack;
use App\Models\Hrms\PayrollStepPayrollSlotCmd;
use App\Models\Hrms\PayrollSlot;
use App\Models\Hrms\EmployeesSalaryExecutionGroup;
use App\Models\Hrms\PayrollStepPayrollSlot;
use App\Models\Hrms\PayrollSlotsCmd;
use App\Models\Hrms\Employee;
use Livewire\Component;
use Illuminate\Support\Facades\Session;
use Flux;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;

class EmployeeTaxComponents extends Component
{
    use WithPagination;

    public $perPage = 100;
    public $sortBy = 'fname';
    public $sortDirection = 'asc';
    public $payrollSlotId;
    public $taxComponents = [];
    public $componentAmounts = [];

    // Field configuration for form and table
    public array $fieldConfig = [
        'employee_name' => ['label' => 'Employee Name', 'type' => 'text'],
        'email' => ['label' => 'Email', 'type' => 'text'],
        'phone' => ['label' => 'Phone', 'type' => 'text'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'employee_name' => ['label' => 'Employee Name', 'type' => 'text'],
        'email' => ['label' => 'Email', 'type' => 'text'],
        'phone' => ['label' => 'Phone', 'type' => 'text'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public function mount($payrollSlotId)
    {
        $this->payrollSlotId = $payrollSlotId;
        $this->loadTaxComponents();
        
        // Set default visible fields
        $this->visibleFields = ['employee_name', 'email', 'phone'];
        $this->visibleFilterFields = ['employee_name', 'email', 'phone'];
        
        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
    }

    public function applyFilters()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
        $this->resetPage();
    }

    public function toggleColumn(string $field)
    {
        if (in_array($field, $this->visibleFields)) {
            $this->visibleFields = array_filter(
                $this->visibleFields,
                fn($f) => $f !== $field
            );
        } else {
            $this->visibleFields[] = $field;
        }
    }

    public function toggleFilterColumn(string $field)
    {
        if (in_array($field, $this->visibleFilterFields)) {
            $this->visibleFilterFields = array_filter(
                $this->visibleFilterFields,
                fn($f) => $f !== $field
            );
        } else {
            $this->visibleFilterFields[] = $field;
        }
    }

    #[Computed]
    public function employees()
    {
        $slot = PayrollSlot::findOrFail($this->payrollSlotId);
        return Employee::whereIn('id', 
            EmployeesSalaryExecutionGroup::where('firm_id', Session::get('firm_id'))
                ->where('salary_execution_group_id', $slot->salary_execution_group_id)
                ->pluck('employee_id')
        )
        ->when($this->filters['employee_name'], function($query, $value) {
            $query->where(function($q) use ($value) {
                $q->where('fname', 'like', "%{$value}%")
                  ->orWhere('mname', 'like', "%{$value}%")
                  ->orWhere('lname', 'like', "%{$value}%");
            });
        })
        ->when($this->filters['email'], fn($query, $value) => 
            $query->where('email', 'like', "%{$value}%"))
        ->when($this->filters['phone'], fn($query, $value) => 
            $query->where('phone', 'like', "%{$value}%"))
        ->orderBy($this->sortBy, $this->sortDirection)
        ->paginate($this->perPage);
    }

    public function loadTaxComponents()
    {
        // Get the payroll slot
        $slot = PayrollSlot::findOrFail($this->payrollSlotId);

        // Get employees from the execution group
        $employees = EmployeesSalaryExecutionGroup::where('firm_id', Session::get('firm_id'))
            ->where('salary_execution_group_id', $slot->salary_execution_group_id)
            ->pluck('employee_id')
            ->toArray();

        // Fetch all tax components
        $this->taxComponents = SalaryComponent::where('firm_id', Session::get('firm_id'))
            ->where('component_type', 'tds')
            ->get();

        // Get existing entries from PayrollComponentsEmployeesTrack
        $existingEntries = PayrollComponentsEmployeesTrack::where('firm_id', Session::get('firm_id'))
            ->where('payroll_slot_id', $this->payrollSlotId)
            ->whereIn('employee_id', $employees)
            ->whereIn('salary_component_id', $this->taxComponents->pluck('id'))
            ->get();

        // Initialize component amounts array with existing values or zero
        foreach ($employees as $employeeId) {
            foreach ($this->taxComponents as $component) {
                $existingEntry = $existingEntries
                    ->where('employee_id', $employeeId)
                    ->where('salary_component_id', $component->id)
                    ->first();

                $this->componentAmounts[$employeeId][$component->id] = 
                    $existingEntry ? $existingEntry->amount_payable : 0;
            }
        }
    }

    public function saveComponentAmounts()
    {
        try {
            $slot = PayrollSlot::findOrFail($this->payrollSlotId);

            // Get the PayrollStepPayrollSlot record for the tax step
            $stepSlot = PayrollStepPayrollSlot::where('firm_id', Session::get('firm_id'))
                ->where('payroll_slot_id', $this->payrollSlotId)
                ->where('step_code_main', 'tds_calculation')
                ->first();

            if (!$stepSlot) {
                throw new \Exception("Tax step not found in payroll slot");
            }

            // Create a PayrollSlotsCmd record first
            $slotCmd = PayrollSlotsCmd::create([
                'firm_id' => Session::get('firm_id'),
                'payroll_slot_id' => $this->payrollSlotId,
                'payroll_slot_status' => 'IP', // In Progress
                'run_payroll_remarks' => json_encode([
                    'payroll_step_id' => $stepSlot->payroll_step_id,
                    'step_title' => 'TDS Calculation',
                    'remark' => 'TDS Calculation in progress'
                ]),
                'user_id' => auth()->id()
            ]);

            foreach ($this->componentAmounts as $employeeId => $components) {
                foreach ($components as $componentId => $amount) {
                    $component = $this->taxComponents->find($componentId);
                    
                    if ($component) {
                        PayrollComponentsEmployeesTrack::updateOrCreate(
                            [
                                'firm_id' => Session::get('firm_id'),
                                'payroll_slot_id' => $this->payrollSlotId,
                                'employee_id' => $employeeId,
                                'salary_component_id' => $componentId,
                            ],
                            [
                                'payroll_slots_cmd_id' => $slotCmd->id,
                                'salary_template_id' => null,
                                'salary_component_group_id' => $component->salary_component_group_id,
                                'sequence' => 999,
                                'nature' => 'deduction',
                                'component_type' => 'tax',
                                'amount_type' => 'calculated_known',
                                'taxable' => false,
                                'calculation_json' => null,
                                'salary_period_from' => $slot->from_date,
                                'salary_period_to' => $slot->to_date,
                                'user_id' => auth()->id(),
                                'amount_full' => $amount,
                                'amount_payable' => $amount,
                                'amount_paid' => 0,
                                'salary_cycle_id' => null
                            ]
                        );
                    }
                }
            }

            // Create log entry in PayrollStepPayrollSlotCmd using the step slot ID
            PayrollStepPayrollSlotCmd::create([
                'firm_id' => Session::get('firm_id'),
                'payroll_step_payroll_slot_id' => $stepSlot->id,
                'payroll_step_status' => 'completed',
                'step_remarks' => 'Employee tax components updated successfully',
                'user_id' => auth()->id()
            ]);

            // Show success message
            Flux::toast(
                variant: 'success',
                heading: 'Success',
                text: 'Tax component amounts saved successfully',
            );

            // Close the modal
            $this->dispatch('close-modal', 'employee-tax-components');

        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to save tax component amounts: ' . $e->getMessage(),
            );
        }
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/employee-tax-components.blade.php'));
    }
} 