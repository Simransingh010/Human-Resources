<?php

namespace App\Livewire\Hrms\Leave;

use App\Models\Hrms\LeaveApprovalRule;
use App\Models\Hrms\LeaveType;
use App\Models\Hrms\Department;
use App\Models\Hrms\Employee;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class LeaveApprovalRules extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;
    public $statuses = [];
    public $selectedRuleId = null;

    // Field configuration for form and table
    public array $fieldConfig = [
        'leave_type_id' => ['label' => 'Leave Type', 'type' => 'select', 'listKey' => 'leave_types_list'],
        'approver_id' => ['label' => 'Approver', 'type' => 'select', 'listKey' => 'approvers_list'],
        'department_scope' => ['label' => 'Department Scope', 'type' => 'select', 'listKey' => 'scope_list'],
        'employee_scope' => ['label' => 'Employee Scope', 'type' => 'select', 'listKey' => 'scope_list'],
        'approval_level' => ['label' => 'Approval Level', 'type' => 'number'],
        'approval_mode' => ['label' => 'Approval Mode', 'type' => 'select', 'listKey' => 'approval_modes'],
        'auto_approve' => ['label' => 'Auto Approve', 'type' => 'switch'],
        'min_days' => ['label' => 'Min Days', 'type' => 'number'],
        'max_days' => ['label' => 'Max Days', 'type' => 'number'],
        'period_start' => ['label' => 'Period Start', 'type' => 'date'],
        'period_end' => ['label' => 'Period End', 'type' => 'date'],
        'is_inactive' => ['label' => 'Status', 'type' => 'switch'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'leave_type_id' => ['label' => 'Leave Type', 'type' => 'select', 'listKey' => 'leave_types_list'],
        'approver_id' => ['label' => 'Approver', 'type' => 'select', 'listKey' => 'approvers_list'],
        'department_scope' => ['label' => 'Department Scope', 'type' => 'select', 'listKey' => 'scope_list'],
        'approval_mode' => ['label' => 'Approval Mode', 'type' => 'select', 'listKey' => 'approval_modes'],
        'is_inactive' => ['label' => 'Status', 'type' => 'select', 'listKey' => 'status_list'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'leave_type_id' => '',
        'approver_id' => '',
        'department_scope' => '',
        'employee_scope' => '',
        'approval_level' => 1,
        'approval_mode' => '',
        'auto_approve' => false,
        'min_days' => null,
        'max_days' => null,
        'period_start' => null,
        'period_end' => null,
        'is_inactive' => false,
    ];

    protected $rules = [
        'formData.leave_type_id' => 'nullable|exists:leave_types,id',
        'formData.approver_id' => 'required|exists:users,id',
        'formData.department_scope' => 'required|string',
        'formData.employee_scope' => 'required|string',
        'formData.approval_level' => 'required|integer|min:1',
        'formData.approval_mode' => 'required|string',
        'formData.auto_approve' => 'boolean',
        'formData.min_days' => 'nullable|numeric|min:0',
        'formData.max_days' => 'nullable|numeric|min:0',
        'formData.period_start' => 'required|date',
        'formData.period_end' => 'required|date|after:period_start',
        'formData.is_inactive' => 'boolean',
    ];

    public function mount()
    {
        $this->initListsForFields();
        
        // Set default visible fields
        $this->visibleFields = ['leave_type_id', 'approver_id', 'approval_level', 'approval_mode', 'auto_approve', 'is_inactive'];
        $this->visibleFilterFields = ['leave_type_id', 'approver_id', 'approval_mode', 'is_inactive'];
        
        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');

        // Initialize statuses
        $this->refreshStatuses();
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['leave_types_list'] = LeaveType::where('firm_id', session('firm_id'))
            ->pluck('leave_title', 'id')
            ->toArray();

        $this->listsForFields['approvers_list'] = User::whereHas('firms', function($query) {
                $query->where('firms.id', session('firm_id'));
            })
            ->pluck('name', 'id')
            ->toArray();

        $this->listsForFields['scope_list'] = [
            'all' => 'All',
            'selected' => 'Selected',
            'none' => 'None'
        ];

        $this->listsForFields['approval_modes'] = [
            'sequential' => 'Sequential',
            'parallel' => 'Parallel',
            'any' => 'Any'
        ];

        $this->listsForFields['status_list'] = [
            '0' => 'Active',
            '1' => 'Inactive'
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
        return LeaveApprovalRule::query()
            ->with(['leave_type', 'user'])
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->filters['leave_type_id'], fn($query, $value) => 
                $query->where('leave_type_id', $value))
            ->when($this->filters['approver_id'], fn($query, $value) => 
                $query->where('approver_id', $value))
            ->when($this->filters['department_scope'], fn($query, $value) => 
                $query->where('department_scope', $value))
            ->when($this->filters['approval_mode'], fn($query, $value) => 
                $query->where('approval_mode', $value))
            ->when($this->filters['is_inactive'] !== '', fn($query, $value) => 
                $query->where('is_inactive', $value))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function refreshStatuses()
    {
        $this->statuses = LeaveApprovalRule::where('firm_id', session('firm_id'))
            ->pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => !(bool)$val])
            ->toArray();
    }

    public function toggleStatus($id)
    {
        $rule = LeaveApprovalRule::find($id);
        $rule->is_inactive = !$rule->is_inactive;
        $rule->save();

        $this->statuses[$id] = !$rule->is_inactive;
        $this->refreshStatuses();
    }

    public function store()
    {
        $validatedData = $this->validate();

        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        $validatedData['formData']['firm_id'] = session('firm_id');

        if ($this->isEditing) {
            $rule = LeaveApprovalRule::findOrFail($this->formData['id']);
            $rule->update($validatedData['formData']);
            $toastMsg = 'Approval rule updated successfully';
        } else {
            LeaveApprovalRule::create($validatedData['formData']);
            $toastMsg = 'Approval rule added successfully';
        }

        $this->resetForm();
        $this->refreshStatuses();
        $this->modal('mdl-approval-rule')->close();
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: $toastMsg,
        );
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['approval_level'] = 1;
        $this->formData['auto_approve'] = false;
        $this->formData['is_inactive'] = false;
        $this->isEditing = false;
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $rule = LeaveApprovalRule::findOrFail($id);
        $this->formData = $rule->toArray();
        $this->modal('mdl-approval-rule')->show();
    }

    public function delete($id)
    {
        try {
            $rule = LeaveApprovalRule::findOrFail($id);
            
            // Check for related records
            if ($rule->departments()->count() > 0 || $rule->employees()->count() > 0) {
                Flux::toast(
                    variant: 'error',
                    heading: 'Cannot Delete',
                    text: 'This approval rule has related records and cannot be deleted.',
                );
                return;
            }

            $rule->delete();
            Flux::toast(
                variant: 'success',
                heading: 'Record Deleted.',
                text: 'Approval rule has been deleted successfully',
            );
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to delete approval rule: ' . $e->getMessage(),
            );
        }
    }

    public function showDepartmentRules($id)
    {
        $this->selectedRuleId = $id;
        $this->modal('department-rules-modal')->show();
    }

    public function showEmployeeRules($id)
    {
        $this->selectedRuleId = $id;
        $this->modal('employee-rules-modal')->show();
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Leave/blades/leave-approval-rules.blade.php'));
    }
} 