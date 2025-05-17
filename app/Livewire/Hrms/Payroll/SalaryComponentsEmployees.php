<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\Employee;
use App\Models\Hrms\SalaryTemplate;
use App\Models\Hrms\SalaryComponent;
use App\Models\Hrms\SalaryComponentGroup;
use App\Models\Hrms\SalaryComponentsEmployee;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class SalaryComponentsEmployees extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'sequence';
    public $sortDirection = 'asc';
    public $isEditing = false;

    // Field configuration for form and table
    public array $fieldConfig = [
        'employee_id' => ['label' => 'Employee', 'type' => 'select', 'listKey' => 'employees'],
        'salary_template_id' => ['label' => 'Salary Template', 'type' => 'select', 'listKey' => 'templates'],
        'salary_component_id' => ['label' => 'Salary Component', 'type' => 'select', 'listKey' => 'components'],
        'salary_component_group_id' => ['label' => 'Component Group', 'type' => 'select', 'listKey' => 'component_groups'],
        'sequence' => ['label' => 'Sequence', 'type' => 'number'],
        'nature' => ['label' => 'Nature', 'type' => 'select', 'listKey' => 'natures'],
        'component_type' => ['label' => 'Component Type', 'type' => 'select', 'listKey' => 'component_types'],
        'amount_type' => ['label' => 'Amount Type', 'type' => 'select', 'listKey' => 'amount_types'],
        'amount' => ['label' => 'Amount', 'type' => 'number'],
        'taxable' => ['label' => 'Taxable', 'type' => 'checkbox'],
        'effective_from' => ['label' => 'Effective From', 'type' => 'date'],
        'effective_to' => ['label' => 'Effective To', 'type' => 'date'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'employee_id' => ['label' => 'Employee', 'type' => 'select', 'listKey' => 'employees'],
        'salary_template_id' => ['label' => 'Salary Template', 'type' => 'select', 'listKey' => 'templates'],
        'salary_component_group_id' => ['label' => 'Component Group', 'type' => 'select', 'listKey' => 'component_groups'],
        'nature' => ['label' => 'Nature', 'type' => 'select', 'listKey' => 'natures'],
        'component_type' => ['label' => 'Component Type', 'type' => 'select', 'listKey' => 'component_types'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'employee_id' => null,
        'salary_template_id' => null,
        'salary_component_id' => null,
        'salary_component_group_id' => null,
        'sequence' => 0,
        'nature' => null,
        'component_type' => null,
        'amount_type' => null,
        'amount' => 0,
        'taxable' => false,
        'calculation_json' => null,
        'effective_from' => null,
        'effective_to' => null,
        'user_id' => null,
    ];

    public function mount()
    {
        $this->initListsForFields();

        // Set default visible fields
        $this->visibleFields = ['employee_id', 'salary_template_id', 'salary_component_id', 'salary_component_group_id', 'sequence', 'nature', 'component_type', 'amount_type', 'amount', 'effective_from', 'effective_to'];
        $this->visibleFilterFields = ['employee_id', 'salary_template_id', 'salary_component_group_id', 'nature', 'component_type'];

        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
    }

    protected function initListsForFields(): void
    {
        // Get employees for dropdown
        $this->listsForFields['employees'] = Employee::where('firm_id', Session::get('firm_id'))
            ->where('is_inactive', false)
            ->pluck('fname', 'id')
            ->toArray();

        // Get templates for dropdown
        $this->listsForFields['templates'] = SalaryTemplate::where('firm_id', Session::get('firm_id'))
            ->where('is_inactive', false)
            ->pluck('title', 'id')
            ->toArray();

        // Get components for dropdown
        $this->listsForFields['components'] = SalaryComponent::where('firm_id', Session::get('firm_id'))

            ->pluck('title', 'id')
            ->toArray();

        // Get component groups for dropdown
        $this->listsForFields['component_groups'] = SalaryComponentGroup::where('firm_id', Session::get('firm_id'))

            ->pluck('title', 'id')
            ->toArray();

        // Get natures for dropdown
        $this->listsForFields['natures'] = SalaryComponentsEmployee::NATURE_SELECT;

        // Get component types for dropdown
        $this->listsForFields['component_types'] = SalaryComponentsEmployee::COMPONENT_TYPE_SELECT;

        // Get amount types for dropdown
        $this->listsForFields['amount_types'] = SalaryComponentsEmployee::AMOUNT_TYPE_SELECT;
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
    public function list()
    {
        return SalaryComponentsEmployee::query()
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->filters['employee_id'], fn($query, $value) =>
                $query->where('employee_id', $value))
            ->when($this->filters['salary_template_id'], fn($query, $value) =>
                $query->where('salary_template_id', $value))
            ->when($this->filters['salary_component_group_id'], fn($query, $value) =>
                $query->where('salary_component_group_id', $value))
            ->when($this->filters['nature'], fn($query, $value) =>
                $query->where('nature', $value))
            ->when($this->filters['component_type'], fn($query, $value) =>
                $query->where('component_type', $value))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    protected function rules()
    {
        return [
            'formData.employee_id' => 'required|integer',
            'formData.salary_template_id' => 'required|integer',
            'formData.salary_component_id' => 'required|integer',
            'formData.salary_component_group_id' => 'nullable|integer',
            'formData.sequence' => 'required|integer|min:0',
            'formData.nature' => 'required|string',
            'formData.component_type' => 'required|string',
            'formData.amount_type' => 'required|string',
            'formData.amount' => 'required|numeric|min:0',
            'formData.taxable' => 'boolean',
            'formData.effective_from' => 'required|date',
            'formData.effective_to' => 'nullable|date|after:formData.effective_from',
        ];
    }

    public function store()
    {
        $validatedData = $this->validate();

        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        $validatedData['formData']['firm_id'] = session('firm_id');
        $validatedData['formData']['user_id'] = session('user_id');

        if ($this->isEditing) {
            $salaryComponentsEmployee = SalaryComponentsEmployee::findOrFail($this->formData['id']);
            $salaryComponentsEmployee->update($validatedData['formData']);
            $toastMsg = 'Employee salary component updated successfully';
        } else {
            SalaryComponentsEmployee::create($validatedData['formData']);
            $toastMsg = 'Employee salary component added successfully';
        }

        $this->resetForm();
        $this->modal('mdl-salary-component-employee')->close();
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: $toastMsg,
        );
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['sequence'] = 0;
        $this->formData['amount'] = 0;
        $this->formData['taxable'] = false;
        $this->isEditing = false;
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $salaryComponentsEmployee = SalaryComponentsEmployee::findOrFail($id);
        $this->formData = $salaryComponentsEmployee->toArray();
        $this->modal('mdl-salary-component-employee')->show();
    }

    public function delete($id)
    {
        $salaryComponentsEmployee = SalaryComponentsEmployee::findOrFail($id);
        $salaryComponentsEmployee->delete();

        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Employee salary component has been deleted successfully',
        );
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/salary-components-employees.blade.php'));
    }
}
