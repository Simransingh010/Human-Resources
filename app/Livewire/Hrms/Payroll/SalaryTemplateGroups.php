<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\SalaryTemplateGroup;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class SalaryTemplateGroups extends Component
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
        'parent_salary_template_group_id' => ['label' => 'Parent Group', 'type' => 'select', 'listKey' => 'parent_groups'],
        'cycle_unit' => ['label' => 'Cycle Unit', 'type' => 'select', 'listKey' => 'cycle_units'],
        'cycle_value' => ['label' => 'Cycle Value', 'type' => 'text'],
        'cycle_start_unit' => ['label' => 'Cycle Start Unit', 'type' => 'select', 'listKey' => 'cycle_start_units'],
        'cycle_start_value' => ['label' => 'Cycle Start Value', 'type' => 'text'],
        'is_inactive' => ['label' => 'Inactive', 'type' => 'switch'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'title' => ['label' => 'Title', 'type' => 'text'],
        'cycle_unit' => ['label' => 'Cycle Unit', 'type' => 'select', 'listKey' => 'cycle_units'],
        'parent_salary_template_group_id' => ['label' => 'Parent Group', 'type' => 'select', 'listKey' => 'parent_groups'],
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
        'parent_salary_template_group_id' => null,
        'cycle_unit' => '',
        'cycle_value' => '',
        'cycle_start_unit' => '',
        'cycle_start_value' => '',
        'is_inactive' => false,
    ];

    public function mount()
    {
        $this->initListsForFields();

        // Set default visible fields
        $this->visibleFields = ['title', 'cycle_unit', 'cycle_value', 'parent_salary_template_group_id'];
        $this->visibleFilterFields = ['title', 'cycle_unit', 'parent_salary_template_group_id'];

        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['cycle_units'] = SalaryTemplateGroup::CYCLE_UNIT_SELECT;
        $this->listsForFields['cycle_start_units'] = SalaryTemplateGroup::CYCLE_START_UNIT_SELECT;

        // Get parent groups for dropdown
        $this->listsForFields['parent_groups'] = SalaryTemplateGroup::where('firm_id', Session::get('firm_id'))
            ->whereNull('parent_salary_template_group_id')
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
        return SalaryTemplateGroup::query()
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->filters['title'], fn($query, $value) =>
                $query->where('title', 'like', "%{$value}%"))
            ->when($this->filters['cycle_unit'], fn($query, $value) =>
                $query->where('cycle_unit', $value))
            ->when($this->filters['parent_salary_template_group_id'], fn($query, $value) =>
                $query->where('parent_salary_template_group_id', $value))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    protected function rules()
    {
        return [
            'formData.title' => 'required|string|max:255',
            'formData.description' => 'nullable|string',
            'formData.parent_salary_template_group_id' => 'nullable|integer',
            'formData.cycle_unit' => 'required|string',
            'formData.cycle_value' => 'required|string',
            'formData.cycle_start_unit' => 'nullable|string',
            'formData.cycle_start_value' => 'nullable|string',
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
            $salaryTemplateGroup = SalaryTemplateGroup::findOrFail($this->formData['id']);
            $salaryTemplateGroup->update($validatedData['formData']);
            $toastMsg = 'Salary template group updated successfully';
        } else {
            SalaryTemplateGroup::create($validatedData['formData']);
            $toastMsg = 'Salary template group added successfully';
        }

        $this->resetForm();
        $this->modal('mdl-salary-template-group')->close();
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
        $salaryTemplateGroup = SalaryTemplateGroup::findOrFail($id);
        $this->formData = $salaryTemplateGroup->toArray();
        $this->modal('mdl-salary-template-group')->show();
    }

    public function delete($id)
    {
        // Check if salary template group has related records
        $salaryTemplateGroup = SalaryTemplateGroup::findOrFail($id);
        if ($salaryTemplateGroup->salary_templates()->count() > 0 || $salaryTemplateGroup->salary_template_groups()->count() > 0) {
            Flux::toast(
                variant: 'error',
                heading: 'Cannot Delete',
                text: 'This salary template group has related records and cannot be deleted.',
            );
            return;
        }

        $salaryTemplateGroup->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Salary template group has been deleted successfully',
        );
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/salary-tempate-groups.blade.php'));
    }
}
