<?php

namespace App\Livewire\Hrms\Offboard;

use App\Models\Hrms\ExitApprovalStep;
use App\Models\Settings\Department;
use App\Models\Settings\Designation;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use Flux;

class ExitApprovalSteps extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'flow_order';
    public $sortDirection = 'asc';
    public $isEditing = false;

    // Field configuration for form and table
    public array $fieldConfig = [
        'exit_employee_department_id' => ['label' => 'Employee Department', 'type' => 'select', 'listKey' => 'departments'],
        'exit_employee_designation_id' => ['label' => 'Employee Designation', 'type' => 'select', 'listKey' => 'designations'],
        'flow_order' => ['label' => 'Flow Order', 'type' => 'number'],
        'approval_type' => ['label' => 'Approval Type', 'type' => 'select', 'listKey' => 'approval_types'],
        'department_id' => ['label' => 'Approver Department', 'type' => 'select', 'listKey' => 'departments'],
        'is_inactive' => ['label' => 'Inactive', 'type' => 'switch'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'exit_employee_department_id' => ['label' => 'Employee Department', 'type' => 'select', 'listKey' => 'departments'],
        'exit_employee_designation_id' => ['label' => 'Employee Designation', 'type' => 'select', 'listKey' => 'designations'],
        'approval_type' => ['label' => 'Approval Type', 'type' => 'select', 'listKey' => 'approval_types'],
        'is_inactive' => ['label' => 'Inactive', 'type' => 'select', 'listKey' => 'inactive_options'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'exit_employee_department_id' => '',
        'exit_employee_designation_id' => '',
        'flow_order' => null,
        'approval_type' => '',
        'department_id' => '',
        'is_inactive' => false,
    ];

    public function mount()
    {
        $this->initListsForFields();

        // Set default visible fields
        $this->visibleFields = ['exit_employee_department_id', 'exit_employee_designation_id', 'flow_order', 'approval_type', 'department_id'];
        $this->visibleFilterFields = ['exit_employee_department_id', 'exit_employee_designation_id', 'approval_type'];

        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
    }

    protected function initListsForFields(): void
    {
        $firmId = Session::get('firm_id');
        $cacheKey = "exit_approval_steps_lists_{$firmId}";

        // Use caching for better performance
        $this->listsForFields = Cache::remember($cacheKey, 300, function () use ($firmId) {
            return [
                'departments' => Department::where('firm_id', $firmId)
                    ->where('is_inactive', false)
                    ->orderBy('title')
                    ->pluck('title', 'id')
                    ->toArray(),
                'designations' => Designation::where('firm_id', $firmId)
                    ->where('is_inactive', false)
                    ->orderBy('title')
                    ->pluck('title', 'id')
                    ->toArray(),
                // approval types from model constant
                'approval_types' => ExitApprovalStep::APPROVAL_TYPE_SELECT,
                'inactive_options' => [
                    '1' => 'Yes',
                    '0' => 'No'
                ]
            ];
        });
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
        $firmId = Session::get('firm_id');
        
        return ExitApprovalStep::query()
            ->with(['exitEmployeeDepartment', 'exitEmployeeDesignation', 'department'])
            ->where('firm_id', $firmId)
            ->when($this->filters['exit_employee_department_id'], fn($query, $value) =>
                $query->where('exit_employee_department_id', $value))
            ->when($this->filters['exit_employee_designation_id'], fn($query, $value) =>
                $query->where('exit_employee_designation_id', $value))
            ->when($this->filters['approval_type'], fn($query, $value) =>
                $query->where('approval_type', $value))
            ->when($this->filters['is_inactive'] !== '', fn($query, $value) =>
                $query->where('is_inactive', $value))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    protected function rules()
    {
        return [
            'formData.exit_employee_department_id' => 'required|exists:departments,id',
            'formData.exit_employee_designation_id' => 'required|exists:designations,id',
            'formData.flow_order' => 'required|integer|min:1',
            'formData.approval_type' => 'required|string|max:255',
            'formData.department_id' => 'required|exists:departments,id',
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
            $exitApprovalStep = ExitApprovalStep::findOrFail($this->formData['id']);
            $exitApprovalStep->update($validatedData['formData']);
            $toastMsg = 'Exit approval step updated successfully';
        } else {
            ExitApprovalStep::create($validatedData['formData']);
            $toastMsg = 'Exit approval step added successfully';
        }

        // Clear cache to refresh lists
        $this->clearCache();
        
        $this->resetForm();
        $this->modal('mdl-exit-approval-step')->close();
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
        $exitApprovalStep = ExitApprovalStep::findOrFail($id);
        $this->formData = $exitApprovalStep->toArray();
        $this->modal('mdl-exit-approval-step')->show();
    }

    public function delete($id)
    {
        $exitApprovalStep = ExitApprovalStep::findOrFail($id);
        
        if ($exitApprovalStep->exitApprovalActions()->count() > 0) {
            Flux::toast(
                variant: 'error',
                heading: 'Cannot Delete',
                text: 'This exit approval step has related approval actions and cannot be deleted.',
            );
            return;
        }

        $exitApprovalStep->delete();
        
        // Clear cache to refresh lists
        $this->clearCache();
        
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Exit approval step has been deleted successfully',
        );
    }

    protected function clearCache()
    {
        $firmId = Session::get('firm_id');
        Cache::forget("exit_approval_steps_lists_{$firmId}");
        // Also clear actions lists cache so dropdown updates after step changes
        Cache::forget("exit_approval_actions_lists_{$firmId}");
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Offboard/blades/exit-approval-steps.blade.php'));
    }
}
