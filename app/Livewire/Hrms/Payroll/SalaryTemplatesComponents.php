<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\SalaryTemplate;
use App\Models\Hrms\SalaryComponent;
use App\Models\Hrms\SalaryComponentGroup;
use App\Models\Hrms\SalaryTemplatesComponent;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class SalaryTemplatesComponents extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'sequence';
    public $sortDirection = 'asc';
    public $isEditing = false;

    // Field configuration for form and table
    public array $fieldConfig = [
        'salary_template_id' => ['label' => 'Salary Template', 'type' => 'select', 'listKey' => 'templates'],
        'salary_component_id' => ['label' => 'Salary Component', 'type' => 'select', 'listKey' => 'components'],
        'salary_component_group_id' => ['label' => 'Component Group', 'type' => 'select', 'listKey' => 'component_groups'],
        'sequence' => ['label' => 'Sequence', 'type' => 'number'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'salary_template_id' => ['label' => 'Salary Template', 'type' => 'select', 'listKey' => 'templates'],
        'salary_component_group_id' => ['label' => 'Component Group', 'type' => 'select', 'listKey' => 'component_groups'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'salary_template_id' => null,
        'salary_component_id' => null,
        'salary_component_group_id' => null,
        'sequence' => 0,
    ];

    public function mount()
    {
        $this->initListsForFields();

        // Set default visible fields
        $this->visibleFields = ['salary_template_id', 'salary_component_id', 'salary_component_group_id', 'sequence'];
        $this->visibleFilterFields = ['salary_template_id', 'salary_component_group_id'];

        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
    }

    protected function initListsForFields(): void
    {
        // Get templates for dropdown
        $this->listsForFields['templates'] = SalaryTemplate::where('firm_id', Session::get('firm_id'))
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
        return SalaryTemplatesComponent::query()
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->filters['salary_template_id'], fn($query, $value) =>
                $query->where('salary_template_id', $value))
            ->when($this->filters['salary_component_group_id'], fn($query, $value) =>
                $query->where('salary_component_group_id', $value))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    protected function rules()
    {
        return [
            'formData.salary_template_id' => 'required|integer',
            'formData.salary_component_id' => 'required|integer',
            'formData.salary_component_group_id' => 'nullable|integer',
            'formData.sequence' => 'required|integer|min:0',
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
            $salaryTemplatesComponent = SalaryTemplatesComponent::findOrFail($this->formData['id']);
            $salaryTemplatesComponent->update($validatedData['formData']);
            $toastMsg = 'Salary template component updated successfully';
        } else {
            SalaryTemplatesComponent::create($validatedData['formData']);
            $toastMsg = 'Salary template component added successfully';
        }

        $this->resetForm();
        $this->modal('mdl-salary-template-component')->close();
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
        $this->isEditing = false;
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $salaryTemplatesComponent = SalaryTemplatesComponent::findOrFail($id);
        $this->formData = $salaryTemplatesComponent->toArray();
        $this->modal('mdl-salary-template-component')->show();
    }

    public function delete($id)
    {
        $salaryTemplatesComponent = SalaryTemplatesComponent::findOrFail($id);
        $salaryTemplatesComponent->delete();

        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Salary template component has been deleted successfully',
        );
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/salary-templates-components.blade.php'));
    }
}
