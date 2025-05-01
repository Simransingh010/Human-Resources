<?php

namespace App\Livewire\Hrms\Leave;

use App\Models\Hrms\EmpLeaveRequestApproval;
use App\Models\Hrms\EmpLeaveRequest;
use App\Models\Hrms\LeaveRequestEvent;
use App\Models\Hrms\Employee;
use App\Models\Hrms\LeaveType;
use App\Models\Hrms\LeaveApprovalRule;
use App\Models\User;
use App\Models\Saas\FirmUser;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Flux;

class TeamLeaves extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;

    // Field configuration for form and table
    public array $fieldConfig = [
        'employee_id' => ['label' => 'Employee', 'type' => 'select', 'listKey' => 'employees'],
        'leave_type_id' => ['label' => 'Leave Type', 'type' => 'select', 'listKey' => 'leave_types'],
        'apply_from' => ['label' => 'From', 'type' => 'date'],
        'apply_to' => ['label' => 'To', 'type' => 'date'],
        'apply_days' => ['label' => 'Days', 'type' => 'number'],
        'reason' => ['label' => 'Reason', 'type' => 'textarea'],
        'status' => ['label' => 'Status', 'type' => 'select', 'listKey' => 'statuses'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'employee_id' => ['label' => 'Employee', 'type' => 'select', 'listKey' => 'employees'],
        'leave_type_id' => ['label' => 'Leave Type', 'type' => 'select', 'listKey' => 'leave_types'],
        'apply_from' => ['label' => 'From', 'type' => 'date'],
        'apply_to' => ['label' => 'To', 'type' => 'date'],
        'status' => ['label' => 'Status', 'type' => 'select', 'listKey' => 'statuses'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $formData = [
        'id' => null,
        'emp_leave_request_id' => null,
        'approval_level' => 1,
        'approver_id' => null,
        'status' => '',
        'remarks' => '',
        'acted_at' => '',
        'firm_id' => null,
    ];

 

    public function mount()
    {
        $this->initListsForFields();

        $this->visibleFields = ['employee_id', 'leave_type_id', 'apply_from', 'apply_to', 'apply_days',  'reason'];
        $this->visibleFilterFields = ['employee_id', 'leave_type_id', 'status', 'apply_from'];

        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['leave_requests'] = EmpLeaveRequest::with('employee', 'leave_type')
            ->where('firm_id', session('firm_id'))
            ->get()
            ->mapWithKeys(fn($lr) => [$lr->id => ($lr->employee->fname ?? '') . ' - ' . ($lr->leave_type->leave_title ?? '')])
            ->toArray();

        $userIds = FirmUser::where('firm_id', session('firm_id'))->pluck('user_id');
        $this->listsForFields['approvers'] = User::whereIn('id', $userIds)->pluck('name', 'id');
        $this->listsForFields['statuses'] = EmpLeaveRequest::STATUS_SELECT;
        
        // Add new lists for employees and leave types
        $this->listsForFields['employees'] = Employee::where('firm_id', session('firm_id'))
            ->pluck('fname', 'id');
            
        $this->listsForFields['leave_types'] = LeaveType::where('firm_id', session('firm_id'))
            ->pluck('leave_title', 'id');
    }

    #[Computed]
    public function list()
    {
        // Get all leave requests for the current firm
        $query = EmpLeaveRequest::query()
            ->with(['employee', 'leave_type', 'emp_leave_request_approvals'])
            ->where('firm_id', Session::get('firm_id'))
            ->where('status', '!=', 'cancelled_employee')
            ->where('status', '!=', 'cancelled_hr');

        // Apply filters
        if ($this->filters['employee_id']) {
            $query->where('employee_id', $this->filters['employee_id']);
        }
        if ($this->filters['leave_type_id']) {
            $query->where('leave_type_id', $this->filters['leave_type_id']);
        }
        if ($this->filters['status']) {
            $query->where('status', $this->filters['status']);
        }
        if ($this->filters['apply_from']) {
            $query->whereDate('apply_from', '>=', $this->filters['apply_from']);
        }
        if ($this->filters['apply_to']) {
            $query->whereDate('apply_to', '<=', $this->filters['apply_to']);
        }

        return $query->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function store()
    {
        $validatedData = $this->validate($this->rules());
        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();
        $validatedData['formData']['firm_id'] = session('firm_id');

        if ($this->isEditing) {
            $approval = EmpLeaveRequestApproval::findOrFail($this->formData['id']);
            $oldStatus = $approval->status;
            $approval->update($validatedData['formData']);

            // Update related leave request status
            $leaveRequest = EmpLeaveRequest::findOrFail($approval->emp_leave_request_id);
            $leaveRequest->update(['status' => $validatedData['formData']['status']]);

            // Log event
            LeaveRequestEvent::create([
                'emp_leave_request_id' => $leaveRequest->id,
                'user_id' => auth()->id(),
                'event_type' => 'status_change',
                'from_status' => $oldStatus,
                'to_status' => $validatedData['formData']['status'],
                'remarks' => 'Approval updated via TeamLeaves',
                'firm_id' => session('firm_id')
            ]);

            $toastMsg = 'Approval updated successfully';
        } else {
            $approval = EmpLeaveRequestApproval::create($validatedData['formData']);

            // Update related leave request status
            $leaveRequest = EmpLeaveRequest::findOrFail($approval->emp_leave_request_id);
            $leaveRequest->update(['status' => $validatedData['formData']['status']]);

            // Log event
            LeaveRequestEvent::create([
                'emp_leave_request_id' => $leaveRequest->id,
                'user_id' => auth()->id(),
                'event_type' => 'status_change',
                'from_status' => null,
                'to_status' => $validatedData['formData']['status'],
                'remarks' => 'Approval created via TeamLeaves',
                'firm_id' => session('firm_id')
            ]);

            $toastMsg = 'Approval created successfully';
        }

        $this->resetForm();
        $this->modal('mdl-leave-request')->close();
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
        $this->isEditing = false;
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $approval = EmpLeaveRequestApproval::findOrFail($id);
        $this->formData = $approval->toArray();
        $this->formData['acted_at'] = $approval->acted_at ? $approval->acted_at->format('Y-m-d\TH:i') : '';
        $this->modal('mdl-leave-request')->show();
    }

    public function delete($id)
    {
        $approval = EmpLeaveRequestApproval::findOrFail($id);
        $approval->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Approval record has been deleted successfully',
        );
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

    public function canApproveLeave($leaveRequest)
    {
        // Get approval rules for the current user
        $approvalRules = LeaveApprovalRule::where('firm_id', session('firm_id'))
            ->where('approver_id', Auth::id())
            ->where(function($query) use ($leaveRequest) {
                // If leave_type_id is specified, check it matches
                $query->where('leave_type_id', $leaveRequest->leave_type_id)
                    ->orWhereNull('leave_type_id');
            })
            ->where('is_inactive', false)
            ->get();

        foreach ($approvalRules as $rule) {
            // Skip if auto_approve is true
            if ($rule->auto_approve) {
                continue;
            }

            // Check leave duration constraints
            $leaveDays = $leaveRequest->apply_days;
            if ($rule->min_days && $leaveDays < $rule->min_days) {
                continue;
            }
            if ($rule->max_days && $leaveDays > $rule->max_days) {
                continue;
            }

            // If department_scope is 'all', no need to check departments
            if ($rule->department_scope === 'all') {
                return true;
            }

            // If employee_scope is 'all', no need to check specific employees
            if ($rule->employee_scope === 'all') {
                return true;
            }

            // Check department-based rules
            if ($rule->departments->contains(function($department) use ($leaveRequest) {
                return $department->employees->contains('id', $leaveRequest->employee_id);
            })) {
                return true;
            }

            // Check direct employee assignments
            if ($rule->employees->contains('id', $leaveRequest->employee_id)) {
                return true;
            }
        }

        return false;
    }

    public function approveLeave($leaveRequestId)
    {
        $leaveRequest = EmpLeaveRequest::findOrFail($leaveRequestId);
        
        if (!$this->canApproveLeave($leaveRequest)) {
            Flux::toast(
                variant: 'error',
                heading: 'Unauthorized',
                text: 'You are not authorized to approve this leave request.',
            );
            return;
        }

        $approvalLevel = $this->getApprovalLevel($leaveRequest);
        $oldStatus = $leaveRequest->status;
        
        // Determine if this is the final approval or needs further approval
        $maxApprovalLevel = LeaveApprovalRule::where('firm_id', session('firm_id'))
            ->where(function($query) use ($leaveRequest) {
                $query->where('leave_type_id', $leaveRequest->leave_type_id)
                    ->orWhereNull('leave_type_id');
            })
            ->where('is_inactive', false)
            ->max('approval_level') ?? 1;
        
        // Count current approvals
        $approvalCount = EmpLeaveRequestApproval::where('emp_leave_request_id', $leaveRequestId)
            ->where('status', 'approved')
            ->count();
            
        // Add the current approval
        $approvalCount++;
            
        // Set new status based on approval count vs max level
        $newStatus = $approvalCount >= $maxApprovalLevel ? 'approved' : 'approved_further';
        
        $leaveRequest->update(['status' => $newStatus]);

        // Create approval record
        EmpLeaveRequestApproval::create([
            'emp_leave_request_id' => $leaveRequestId,
            'approval_level' => $approvalLevel,
            'approver_id' => Auth::id(),
            'status' => 'approved',
            'acted_at' => now(),
            'firm_id' => session('firm_id')
        ]);

        // Log the event
        LeaveRequestEvent::create([
            'emp_leave_request_id' => $leaveRequestId,
            'user_id' => Auth::id(),
            'event_type' => 'status_change',
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'remarks' => $newStatus === 'approved' ? 'Leave request approved' : 'Leave request approved and sent for further approval',
            'firm_id' => session('firm_id')
        ]);

        // Close the confirmation modal
        $this->modal("confirm-approve-{$leaveRequestId}")->close();

        // Reset pagination to first page and refresh the component
        $this->resetPage();
        $this->dispatch('leave-status-updated');

        $successMessage = $newStatus === 'approved' 
            ? 'Leave request has been fully approved' 
            : "Leave request approved ({$approvalCount}/{$maxApprovalLevel} approvals)";
            
        Flux::toast(
            variant: 'success',
            heading: 'Leave Approved',
            text: $successMessage,
        );
    }

    public function rejectLeave($leaveRequestId)
    {
        $leaveRequest = EmpLeaveRequest::findOrFail($leaveRequestId);
        
        if (!$this->canApproveLeave($leaveRequest)) {
            Flux::toast(
                variant: 'error',
                heading: 'Unauthorized',
                text: 'You are not authorized to reject this leave request.',
            );
            return;
        }

        $approvalLevel = $this->getApprovalLevel($leaveRequest);
        $oldStatus = $leaveRequest->status;
        $leaveRequest->update(['status' => 'rejected']);

        // Create approval record
        EmpLeaveRequestApproval::create([
            'emp_leave_request_id' => $leaveRequestId,
            'approval_level' => $approvalLevel,
            'approver_id' => Auth::id(),
            'status' => 'rejected',
            'acted_at' => now(),
            'firm_id' => session('firm_id')
        ]);

        // Log the event
        LeaveRequestEvent::create([
            'emp_leave_request_id' => $leaveRequestId,
            'user_id' => Auth::id(),
            'event_type' => 'status_change',
            'from_status' => $oldStatus,
            'to_status' => 'rejected',
            'remarks' => 'Leave request rejected',
            'firm_id' => session('firm_id')
        ]);

        // Close the confirmation modal
        $this->modal("confirm-reject-{$leaveRequestId}")->close();

        // Reset pagination to first page and refresh the component
        $this->resetPage();
        $this->dispatch('leave-status-updated');

        Flux::toast(
            variant: 'success',
            heading: 'Leave Rejected',
            text: 'Leave request has been rejected',
        );
    }

    public function askClarification($leaveRequestId)
    {
        $leaveRequest = EmpLeaveRequest::findOrFail($leaveRequestId);
        
        if (!$this->canApproveLeave($leaveRequest)) {
            Flux::toast(
                variant: 'error',
                heading: 'Unauthorized',
                text: 'You are not authorized to request clarification.',
            );
            return;
        }

        $approvalLevel = $this->getApprovalLevel($leaveRequest);
        $oldStatus = $leaveRequest->status;
        
        // Always set status to clarification_required
        $leaveRequest->update([
            'status' => 'clarification_required'
        ]);

        // Create approval record
        EmpLeaveRequestApproval::create([
            'emp_leave_request_id' => $leaveRequestId,
            'approval_level' => $approvalLevel,
            'approver_id' => Auth::id(),
            'status' => 'clarification_required',
            'remarks' => 'Clarification requested by ' . Auth::user()->name,
            'acted_at' => now(),
            'firm_id' => session('firm_id')
        ]);

        // Log the event
        LeaveRequestEvent::create([
            'emp_leave_request_id' => $leaveRequestId,
            'user_id' => Auth::id(),
            'event_type' => 'status_change',
            'from_status' => $oldStatus,
            'to_status' => 'clarification_required',
            'remarks' => 'Clarification requested by ' . Auth::user()->name,
            'firm_id' => session('firm_id')
        ]);

        // Close the clarification modal
        $this->modal("confirm-clarification-{$leaveRequestId}")->close();
        
        // Reset pagination to first page and refresh the component
        $this->resetPage();
        $this->dispatch('leave-status-updated');

        Flux::toast(
            variant: 'success',
            heading: 'Clarification Requested',
            text: 'Clarification has been requested from the employee',
        );
    }

    /**
     * Get the approval level for the current user from the matching rule
     * 
     * @param EmpLeaveRequest $leaveRequest
     * @return int
     */
    protected function getApprovalLevel(EmpLeaveRequest $leaveRequest): int
    {
        $approvalRule = LeaveApprovalRule::where('firm_id', session('firm_id'))
            ->where('approver_id', Auth::id())
            ->where(function($query) use ($leaveRequest) {
                $query->where('leave_type_id', $leaveRequest->leave_type_id)
                    ->orWhereNull('leave_type_id');
            })
            ->where('is_inactive', false)
            ->first();
            
        return $approvalRule ? $approvalRule->approval_level : 1; // Default to 1 if no rule found
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Leave/blades/team-leaves.blade.php'));
    }
} 