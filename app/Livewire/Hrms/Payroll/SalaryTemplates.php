<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\SalaryTemplate;
use App\Models\Hrms\SalaryTemplateGroup;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class SalaryTemplates extends Component
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
        'salary_template_group_id' => ['label' => 'Template Group', 'type' => 'select', 'listKey' => 'template_groups'],
        'effective_from' => ['label' => 'Effective From', 'type' => 'date'],
        'effective_to' => ['label' => 'Effective To', 'type' => 'date'],
        'is_inactive' => ['label' => 'Inactive', 'type' => 'switch'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'title' => ['label' => 'Title', 'type' => 'text'],
        'salary_template_group_id' => ['label' => 'Template Group', 'type' => 'select', 'listKey' => 'template_groups'],
        'effective_from' => ['label' => 'Effective From', 'type' => 'date'],
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
        'salary_template_group_id' => null,
        'effective_from' => null,
        'effective_to' => null,
        'is_inactive' => false,
    ];

    public function mount()
    {
        $this->initListsForFields();

        // Set default visible fields
        $this->visibleFields = ['title', 'salary_template_group_id', 'effective_from', 'effective_to'];
        $this->visibleFilterFields = ['title', 'salary_template_group_id', 'effective_from'];

        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
    }

    protected function initListsForFields(): void
    {
        // Get template groups for dropdown
        $this->listsForFields['template_groups'] = SalaryTemplateGroup::where('firm_id', Session::get('firm_id'))
            ->where('is_inactive', false)
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
        return SalaryTemplate::query()
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->filters['title'], fn($query, $value) =>
                $query->where('title', 'like', "%{$value}%"))
            ->when($this->filters['salary_template_group_id'], fn($query, $value) =>
                $query->where('salary_template_group_id', $value))
            ->when($this->filters['effective_from'], fn($query, $value) =>
                $query->whereDate('effective_from', '>=', $value))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    protected function rules()
    {
        return [
            'formData.title' => 'required|string|max:255',
            'formData.description' => 'nullable|string',
            'formData.salary_template_group_id' => 'required|integer',
            'formData.effective_from' => 'required|date',
            'formData.effective_to' => 'nullable|date|after:formData.effective_from',
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
            $salaryTemplate = SalaryTemplate::findOrFail($this->formData['id']);
            $salaryTemplate->update($validatedData['formData']);
            $toastMsg = 'Salary template updated successfully';
        } else {
            SalaryTemplate::create($validatedData['formData']);
            $toastMsg = 'Salary template added successfully';
        }

        $this->resetForm();
        $this->modal('mdl-salary-template')->close();
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
        $salaryTemplate = SalaryTemplate::findOrFail($id);
        $this->formData = $salaryTemplate->toArray();
        $this->modal('mdl-salary-template')->show();
    }

    public function delete($id)
    {
        // Check if salary template has related records
        $salaryTemplate = SalaryTemplate::findOrFail($id);
        if (
            $salaryTemplate->salary_components_employees()->count() > 0 ||
            $salaryTemplate->salary_templates_components()->count() > 0
        ) {
            Flux::toast(
                variant: 'error',
                heading: 'Cannot Delete',
                text: 'This salary template has related records and cannot be deleted.',
            );
            return;
        }

        $salaryTemplate->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Salary template has been deleted successfully',
        );
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/salary-templates.blade.php'));
    }
}
