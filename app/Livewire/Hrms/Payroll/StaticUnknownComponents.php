<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\Employee;
use App\Models\Hrms\SalaryComponent;
use App\Models\Hrms\PayrollComponentsEmployeesTrack;
use App\Models\Hrms\PayrollSlot;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class StaticUnknownComponents extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'fname';              // switch to a real column for sorting
    public $sortDirection = 'asc';
    public $payrollSlotId;

    // form & table field configuration
    public array $fieldConfig = [
        'employee_name'    => ['label' => 'Employee Name', 'type' => 'text'],
        'component_title'  => ['label' => 'Component',     'type' => 'text'],
        'nature'           => ['label' => 'Nature',        'type' => 'select', 'listKey' => 'nature'],
        'component_type'   => ['label' => 'Type',          'type' => 'select', 'listKey' => 'component_type'],
    ];

    public array $filterFields = [
        'employee_name'    => ['label' => 'Employee Name', 'type' => 'text'],
        'component_title'  => ['label' => 'Component',     'type' => 'text'],
        'nature'           => ['label' => 'Nature',        'type' => 'select', 'listKey' => 'nature'],
        'component_type'   => ['label' => 'Type',          'type' => 'select', 'listKey' => 'component_type'],
    ];

    public array $listsForFields = [];
    public array $filters           = [];
    public array $visibleFields     = [];
    public array $visibleFilterFields = [];

    public array $staticUnknownComponents = [];
    public array $componentAmounts        = [];

    public function mount($payrollSlotId)
    {
        $this->payrollSlotId = $payrollSlotId;
        $this->initListsForFields();
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');

        $this->visibleFields = ['employee_name', 'component_title', 'nature', 'component_type'];
        $this->visibleFilterFields = ['employee_name', 'component_title', 'nature', 'component_type'];

        $this->loadStaticUnknownComponents();
        $this->loadComponentAmounts();
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['nature'] = [
            'earning'   => 'Earning',
            'deduction' => 'Deduction'
        ];

        $this->listsForFields['component_type'] = [
            'allowance'    => 'Allowance',
            'bonus'        => 'Bonus',
            'reimbursement'=> 'Reimbursement',
            'other'        => 'Other'
        ];
    }

    protected function loadStaticUnknownComponents(): void
    {
        $entries = PayrollComponentsEmployeesTrack::where('firm_id', Session::get('firm_id'))
            ->where('payroll_slot_id', $this->payrollSlotId)
            ->where('component_type', 'unknown')
            ->with('salary_component')
            ->get();

        $this->staticUnknownComponents = $entries
            ->pluck('salary_component')
            ->unique('id')
            ->values()
            ->all();
    }

    protected function loadComponentAmounts(): void
    {
        $entries = PayrollComponentsEmployeesTrack::where('firm_id', Session::get('firm_id'))
            ->where('payroll_slot_id', $this->payrollSlotId)
            ->where('component_type', 'unknown')
            ->get();

        foreach ($entries as $entry) {
            $this->componentAmounts[$entry->employee_id][$entry->salary_component_id] = $entry->amount_payable;
        }
    }

    #[Computed]
    public function employees()
    {
        $query = Employee::query()
            ->whereIn('id', PayrollComponentsEmployeesTrack::where('firm_id', Session::get('firm_id'))
                ->where('payroll_slot_id', $this->payrollSlotId)
                ->where('component_type', 'unknown')
                ->pluck('employee_id')
                ->unique()
                ->toArray()
            );

        // Employee Name filter
        if ($this->filters['employee_name']) {
            $value = $this->filters['employee_name'];
            $query->where(function($q) use ($value) {
                $q->where('fname', 'like', "%{$value}%")
                    ->orWhere('mname', 'like', "%{$value}%")
                    ->orWhere('lname', 'like', "%{$value}%");
            });
        }

        // Component Title filter via SalaryComponent
        if ($this->filters['component_title']) {
            $val = $this->filters['component_title'];
            $componentIds = SalaryComponent::where('firm_id', Session::get('firm_id'))
                ->where('title', 'like', "%{$val}%")
                ->pluck('id')
                ->toArray();

            $empIds = PayrollComponentsEmployeesTrack::where('firm_id', Session::get('firm_id'))
                ->where('payroll_slot_id', $this->payrollSlotId)
                ->whereIn('salary_component_id', $componentIds)
                ->pluck('employee_id')
                ->unique()
                ->toArray();

            $query->whereIn('id', $empIds);
        }

        // Nature filter
        if ($this->filters['nature']) {
            $empIds = PayrollComponentsEmployeesTrack::where('firm_id', Session::get('firm_id'))
                ->where('payroll_slot_id', $this->payrollSlotId)
                ->where('nature', $this->filters['nature'])
                ->pluck('employee_id')
                ->unique()
                ->toArray();

            $query->whereIn('id', $empIds);
        }

        // Component Type filter
        if ($this->filters['component_type']) {
            $empIds = PayrollComponentsEmployeesTrack::where('firm_id', Session::get('firm_id'))
                ->where('payroll_slot_id', $this->payrollSlotId)
                ->where('component_type', $this->filters['component_type'])
                ->pluck('employee_id')
                ->unique()
                ->toArray();

            $query->whereIn('id', $empIds);
        }

        // Sorting & pagination
        return $query
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
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

    public function saveComponentAmounts()
    {
        foreach ($this->componentAmounts as $empId => $components) {
            foreach ($components as $compId => $amount) {
                PayrollComponentsEmployeesTrack::updateOrCreate(
                    [
                        'firm_id'              => Session::get('firm_id'),
                        'payroll_slot_id'      => $this->payrollSlotId,
                        'employee_id'          => $empId,
                        'salary_component_id'  => $compId,
                    ],
                    [
                        'amount_payable'       => $amount,
                        'user_id'              => auth()->id(),
                    ]
                );
            }
        }

        Flux::toast(
            variant: 'success',
            heading: 'Saved',
            text: 'Static unknown component amounts updated.'
        );

        $this->dispatch('close-modal', 'static-unknown-components');
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/over-ride-unknown-component.blade.php'));
    }
}
