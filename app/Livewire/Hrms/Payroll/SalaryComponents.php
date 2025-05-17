<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\SalaryComponent;
use App\Models\Hrms\SalaryComponentGroup;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class SalaryComponents extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'title';
    public $sortDirection = 'asc';
    public $isEditing = false;

    // Field configuration for form and table
    public array $fieldConfig = [
        'title' => ['label' => 'Title', 'type' => 'text'],
        'description' => ['label' => 'Description', 'type' => 'textarea'],
        'salary_component_group_id' => ['label' => 'Component Group', 'type' => 'select', 'listKey' => 'component_groups'],
        'nature' => ['label' => 'Nature', 'type' => 'select', 'listKey' => 'natures'],
        'component_type' => ['label' => 'Component Type', 'type' => 'select', 'listKey' => 'component_types'],
        'amount_type' => ['label' => 'Amount Type', 'type' => 'select', 'listKey' => 'amount_types'],
        'taxable' => ['label' => 'Taxable', 'type' => 'switch'],
        'calculation_json' => ['label' => 'Calculation', 'type' => 'textarea'],
        'document_required' => ['label' => 'Document Required', 'type' => 'switch'],
        'is_inactive' => ['label' => 'Inactive', 'type' => 'switch'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'title' => ['label' => 'Title', 'type' => 'text'],
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
        'title' => '',
        'description' => '',
        'salary_component_group_id' => null,
        'nature' => '',
        'component_type' => '',
        'amount_type' => '',
        'taxable' => false,
        'calculation_json' => null,
        'document_required' => false,
        'is_inactive' => false,
    ];

    public function mount()
    {
        $this->initListsForFields();

        // Set default visible fields
        $this->visibleFields = ['title', 'salary_component_group_id', 'nature', 'component_type', 'taxable'];
        $this->visibleFilterFields = ['title', 'salary_component_group_id', 'nature', 'component_type'];

        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
    }

    protected function initListsForFields(): void
    {
        // Get component groups for dropdown
        $this->listsForFields['component_groups'] = SalaryComponentGroup::where('firm_id', Session::get('firm_id'))
            ->where('is_inactive', false)
            ->pluck('title', 'id')
            ->toArray();

        // Get lists from model constants
        $this->listsForFields['natures'] = SalaryComponent::NATURE_SELECT;
        $this->listsForFields['component_types'] = SalaryComponent::COMPONENT_TYPE_SELECT;
        $this->listsForFields['amount_types'] = SalaryComponent::AMOUNT_TYPE_SELECT;
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
        return SalaryComponent::query()
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->filters['title'], fn($query, $value) =>
                $query->where('title', 'like', "%{$value}%"))
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
            'formData.title' => 'required|string|max:255',
            'formData.description' => 'nullable|string',
            'formData.salary_component_group_id' => 'nullable|integer',
            'formData.nature' => 'required|string',
            'formData.component_type' => 'required|string',
            'formData.amount_type' => 'required|string',
            'formData.taxable' => 'boolean',
            'formData.calculation_json' => 'nullable|json',
            'formData.document_required' => 'boolean',
            'formData.is_inactive' => 'boolean'
        ];
    }

    public function store()
    {
        $validatedData = $this->validate();

        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        $validatedData['formData']['firm_id'] = session('firm_id');

        if ($this->isEditing) {
            $salaryComponent = SalaryComponent::findOrFail($this->formData['id']);
            $salaryComponent->update($validatedData['formData']);
            $toastMsg = 'Salary component updated successfully';
        } else {
            SalaryComponent::create($validatedData['formData']);
            $toastMsg = 'Salary component added successfully';
        }

        $this->resetForm();
        $this->modal('mdl-salary-component')->close();
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: $toastMsg,
        );
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['taxable'] = false;
        $this->formData['document_required'] = false;
        $this->formData['is_inactive'] = false;
        $this->isEditing = false;
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $salaryComponent = SalaryComponent::findOrFail($id);
        $this->formData = $salaryComponent->toArray();
        $this->modal('mdl-salary-component')->show();
    }

    public function delete($id)
    {
        // Check if salary component has related records
        $salaryComponent = SalaryComponent::findOrFail($id);
        if (
            $salaryComponent->employees()->count() > 0 ||
            $salaryComponent->salary_templates_components()->count() > 0
        ) {
            Flux::toast(
                variant: 'error',
                heading: 'Cannot Delete',
                text: 'This salary component has related records and cannot be deleted.',
            );
            return;
        }

        $salaryComponent->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Salary component has been deleted successfully',
        );
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/salary-components.blade.php'));
    }
}
