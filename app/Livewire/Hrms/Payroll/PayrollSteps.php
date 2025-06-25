<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\PayrollStep;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class PayrollSteps extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'step_order';
    public $sortDirection = 'asc';
    public $isEditing = false;

    // Field configuration for form and table
    public array $fieldConfig = [
        'step_code_main' => ['label' => 'Step Code', 'type' => 'text'],
        'step_title' => ['label' => 'Title', 'type' => 'text'],
        'step_desc' => ['label' => 'Description', 'type' => 'textarea'],
        'required' => ['label' => 'Required', 'type' => 'switch'],
        'step_order' => ['label' => 'Order', 'type' => 'number'],
        'is_inactive' => ['label' => 'Inactive', 'type' => 'switch'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'step_code_main' => ['label' => 'Step Code', 'type' => 'text'],
        'step_title' => ['label' => 'Title', 'type' => 'text'],
        'required' => ['label' => 'Required', 'type' => 'select', 'listKey' => 'required_options'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'step_code_main' => '',
        'step_title' => '',
        'step_desc' => '',
        'required' => false,
        'step_order' => null,
        'is_inactive' => false,
    ];

    public function mount()
    {
        $this->initListsForFields();

        // Set default visible fields
        $this->visibleFields = ['step_code_main', 'step_title', 'step_desc', 'required', 'step_order'];
        $this->visibleFilterFields = ['step_code_main', 'step_title', 'required'];

        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
    }

    protected function initListsForFields(): void
    {
        // Get required options
        $this->listsForFields['required_options'] = [
            '1' => 'Yes',
            '0' => 'No'
        ];
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
        return PayrollStep::query()
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->filters['step_code_main'], fn($query, $value) =>
                $query->where('step_code_main', 'like', "%{$value}%"))
            ->when($this->filters['step_title'], fn($query, $value) =>
                $query->where('step_title', 'like', "%{$value}%"))
            ->when($this->filters['required'] !== '', fn($query, $value) =>
                $query->where('required', $value))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    protected function rules()
    {
        return [
            'formData.step_code_main' => 'required|string|max:255',
            'formData.step_title' => 'required|string|max:255',
            'formData.step_desc' => 'nullable|string',
            'formData.required' => 'boolean',
            'formData.step_order' => 'required|integer|min:1',
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
            $payrollStep = PayrollStep::findOrFail($this->formData['id']);
            $payrollStep->update($validatedData['formData']);
            $toastMsg = 'Payroll step updated successfully';
        } else {
            PayrollStep::create($validatedData['formData']);
            $toastMsg = 'Payroll step added successfully';
        }

        $this->resetForm();
        $this->modal('mdl-payroll-step')->close();
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
        $this->formData['required'] = false;
        $this->isEditing = false;
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $payrollStep = PayrollStep::findOrFail($id);
        $this->formData = $payrollStep->toArray();
        $this->modal('mdl-payroll-step')->show();
    }

    public function delete($id)
    {
        $payrollStep = PayrollStep::findOrFail($id);
        
        if ($payrollStep->payroll_slots()->count() > 0) {
            Flux::toast(
                variant: 'error',
                heading: 'Cannot Delete',
                text: 'This payroll step has related records and cannot be deleted.',
            );
            return;
        }

        $payrollStep->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Payroll step has been deleted successfully',
        );
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/payroll-steps.blade.php'));
    }
} 