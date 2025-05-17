<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\SalaryComponentGroup;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class SalaryComponentGroups extends Component
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
        'parent_salary_component_group_id' => ['label' => 'Parent Group', 'type' => 'select', 'listKey' => 'parent_groups'],
        'is_inactive' => ['label' => 'Inactive', 'type' => 'switch'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'title' => ['label' => 'Title', 'type' => 'text'],
        'parent_salary_component_group_id' => ['label' => 'Parent Group', 'type' => 'select', 'listKey' => 'parent_groups'],
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
        'parent_salary_component_group_id' => null,
        'is_inactive' => false,
    ];

    public function mount()
    {
        $this->initListsForFields();

        // Set default visible fields
        $this->visibleFields = ['title', 'description', 'parent_salary_component_group_id', 'is_inactive'];
        $this->visibleFilterFields = ['title', 'parent_salary_component_group_id'];

        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
    }

    protected function initListsForFields(): void
    {
        // Get parent groups for dropdown
        $this->listsForFields['parent_groups'] = SalaryComponentGroup::where('firm_id', Session::get('firm_id'))
            ->whereNull('parent_salary_component_group_id')
            ->pluck('title', 'id')
            ->toArray();
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
        return SalaryComponentGroup::query()
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->filters['title'], fn($query, $value) =>
                $query->where('title', 'like', "%{$value}%"))
            ->when($this->filters['parent_salary_component_group_id'], fn($query, $value) =>
                $query->where('parent_salary_component_group_id', $value))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    protected function rules()
    {
        return [
            'formData.title' => 'required|string|max:255',
            'formData.description' => 'nullable|string',
            'formData.parent_salary_component_group_id' => 'nullable|integer',
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
            $salaryComponentGroup = SalaryComponentGroup::findOrFail($this->formData['id']);
            $salaryComponentGroup->update($validatedData['formData']);
            $toastMsg = 'Salary component group updated successfully';
        } else {
            SalaryComponentGroup::create($validatedData['formData']);
            $toastMsg = 'Salary component group added successfully';
        }

        $this->resetForm();
        $this->modal('mdl-salary-component-group')->close();
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: $toastMsg,
        );
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['is_inactive'] = false;
        $this->isEditing = false;
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $salaryComponentGroup = SalaryComponentGroup::findOrFail($id);
        $this->formData = $salaryComponentGroup->toArray();
        $this->modal('mdl-salary-component-group')->show();
    }

    public function delete($id)
    {
        // Check if salary component group has related records
        $salaryComponentGroup = SalaryComponentGroup::findOrFail($id);
        if (
            $salaryComponentGroup->salary_components()->count() > 0 ||
            $salaryComponentGroup->salary_components_employees()->count() > 0 ||
            $salaryComponentGroup->salary_templates_components()->count() > 0 ||
            $salaryComponentGroup->salary_component_groups()->count() > 0
        ) {
            Flux::toast(
                variant: 'error',
                heading: 'Cannot Delete',
                text: 'This salary component group has related records and cannot be deleted.',
            );
            return;
        }

        $salaryComponentGroup->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Salary component group has been deleted successfully',
        );
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/salary-component-groups.blade.php'));
    }
}
