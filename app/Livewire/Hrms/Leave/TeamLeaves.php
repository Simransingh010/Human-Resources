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
use Illuminate\Support\Facades\DB;
use App\Models\Hrms\EmpLeaveBalance;
use App\Models\Hrms\EmpLeaveTransaction;

class TeamLeaves extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;
    public $selectedId = null;
    public $id;
    public $showActionModal = false;
    public $selectedLeaveRequestId = null;
    public $showEventsModal = false;

    // Add Livewire hooks for debugging
    public function booted()
    {
        $this->dispatch('console-log', message: 'TeamLeaves component booted');
    }

    public function hydrate()
    {
        $this->dispatch('console-log', message: 'TeamLeaves component hydrated');
    }

    public function dehydrate()
    {
        $this->dispatch('console-log', message: 'TeamLeaves component dehydrated');
    }

    public function updating($name, $value)
    {
        $this->dispatch('console-log', message: "Updating {$name}", data: ['value' => $value]);
    }

    public function updated($name, $value)
    {
        $this->dispatch('console-log', message: "Updated {$name}", data: ['value' => $value]);
    }

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
        'action' => null,
    ];

    protected $rules = [
        'formData.remarks' => 'nullable|string|min:3',
        'formData.action' => 'required|in:approve,reject',
    ];

    protected $messages = [
        'formData.remarks.min' => 'If provided, remarks must be at least 3 characters long.',
    ];

    public function mount()
    {

        $this->initListsForFields();

        $this->visibleFields = ['employee_id', 'leave_type_id', 'apply_from', 'apply_to', 'apply_days', 'reason'];
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
            ->with([
                'employee',
                'leave_type' => function ($query) {
                    $query->select('id', 'leave_title', 'leave_nature');
                }
            ])
            ->where('firm_id', Session::get('firm_id'))
            ->where('status', '!=', 'cancelled_employee')
            ->where('status', '!=', 'cancelled_hr');

        // Debug the query
        \Log::info('Leave Request Query:', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings()
        ]);

        // Get the rules where the current user is an approver
        $userRules = LeaveApprovalRule::where('firm_id', session('firm_id'))
            ->where('approver_id', Auth::id())
            ->where('is_inactive', false)
            ->get();

        // If user has no rules, return empty result
        if ($userRules->isEmpty()) {
            return new \Illuminate\Pagination\LengthAwarePaginator(
                [],
                0,
                $this->perPage,
                1
            );
        }

        // Build query based on user's rules
        $query->where(function ($q) use ($userRules) {
            foreach ($userRules as $rule) {
                $q->orWhere(function ($subQ) use ($rule) {
                    // Match leave type if specified
                    if ($rule->leave_type_id) {
                        $subQ->where('leave_type_id', $rule->leave_type_id);
                    }

                    // Match employees based on rule scope
                    if ($rule->employees->isNotEmpty()) {
                        $subQ->whereIn('employee_id', $rule->employees->pluck('id'));
                    }

                    // If rule has departments, include their employees
                    if ($rule->departments->isNotEmpty()) {
                        $employeeIds = $rule->departments->flatMap(function ($dept) {
                            return $dept->employees->pluck('id');
                        })->unique();
                        $subQ->orWhereIn('employee_id', $employeeIds);
                    }

                    // If rule is for all employees
                    if ($rule->employee_scope === 'all' || $rule->department_scope === 'all') {
                        $subQ->orWhereNotNull('id');
                    }
                });
            }
        });


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
        $this->formData = [
            'id' => null,
            'emp_leave_request_id' => null,
            'approval_level' => 1,
            'approver_id' => null,
            'status' => '',
            'remarks' => '',
            'acted_at' => '',
            'firm_id' => null,
            'action' => null,
        ];
        $this->isEditing = false;
        $this->selectedId = null;
    }

    public function edit($id)
    {

        $this->isEditing = true;
        $this->id = $id;

        $leave = EmpLeaveRequest::findOrFail($id);
        $this->formData = $leave->toArray();
        $this->formData['acted_at'] = now()->format('Y-m-d');
        $this->modal('mdl-leave-action')->show();
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

    /**
     * Get all applicable approval rules for a leave request
     */
    protected function getApplicableRules($leaveRequest)
    {
        return LeaveApprovalRule::where('firm_id', session('firm_id'))
            ->where(function ($query) use ($leaveRequest) {
                $query->where('leave_type_id', $leaveRequest->leave_type_id)
                    ->orWhereNull('leave_type_id');
            })
            ->where('is_inactive', false)
            ->orderBy('approval_level')
            ->get();
    }

    /**
     * Check if the current user can view this leave request
     */
    public function canViewLeave($leaveRequest)
    {
        $rules = LeaveApprovalRule::where('firm_id', session('firm_id'))
            ->where('approver_id', Auth::id())
            ->where(function ($query) use ($leaveRequest) {
                $query->where('leave_type_id', $leaveRequest->leave_type_id)
                    ->orWhereNull('leave_type_id');
            })
            ->where('is_inactive', false)
            ->get();

        foreach ($rules as $rule) {
            // Check employee and department scopes
            if ($this->isEmployeeInRuleScope($leaveRequest->employee_id, $rule)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an employee falls under a rule's scope
     */
    protected function isEmployeeInRuleScope($employeeId, $rule)
    {
        // If scope is all, employee is in scope
        if ($rule->department_scope === 'all' || $rule->employee_scope === 'all') {
            return true;
        }

        // Check department-based rules
        if (
            $rule->departments->contains(function ($department) use ($employeeId) {
                return $department->employees->contains('id', $employeeId);
            })
        ) {
            return true;
        }

        // Check direct employee assignments
        return $rule->employees->contains('id', $employeeId);
    }

    /**
     * Get the highest approval level needed for this leave request
     */
    protected function getMaxApprovalLevel($leaveRequest)
    {
        return LeaveApprovalRule::where('firm_id', session('firm_id'))
            ->where(function ($query) use ($leaveRequest) {
                $query->where('leave_type_id', $leaveRequest->leave_type_id)
                    ->orWhereNull('leave_type_id');
            })
            ->where('is_inactive', false)
            ->max('approval_level') ?? 1;
    }

    /**
     * Get all approvers for a leave request
     */
    public function getApprovers($leaveRequest)
    {
        return LeaveApprovalRule::where('firm_id', session('firm_id'))
            ->where(function ($query) use ($leaveRequest) {
                $query->where('leave_type_id', $leaveRequest->leave_type_id)
                    ->orWhereNull('leave_type_id');
            })
            ->where('is_inactive', false)
            ->whereNotNull('approver_id')
            ->whereNotNull('approval_level')
            ->whereNotNull('approval_mode')
            ->with('user')
            ->get()
            ->filter(function ($rule) {
                return $rule->user !== null;
            })
            ->map(function ($rule) {
                return [
                    'user' => $rule->user,
                    'level' => $rule->approval_level,
                    'mode' => $rule->approval_mode
                ];
            });
    }


    public function canApproveLeave($leaveRequest)
    {
        // Get approval rules for the current user
        $approvalRules = LeaveApprovalRule::where('firm_id', session('firm_id'))
            ->where('approver_id', Auth::id())
            ->where(function ($query) use ($leaveRequest) {
                $query->where('leave_type_id', $leaveRequest->leave_type_id)
                    ->orWhereNull('leave_type_id');
            })
            ->where('is_inactive', false)
            ->get();

        // If no rules found, user cannot approve
        if ($approvalRules->isEmpty()) {
            return false;
        }

        foreach ($approvalRules as $rule) {
            // Skip if view-only mode
            if ($rule->approval_mode === 'view_only') {
                continue;
            }

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

            // Check if this approval level is currently needed
            $currentApprovals = EmpLeaveRequestApproval::where('emp_leave_request_id', $leaveRequest->id)
                ->where('status', 'approved')
                ->count();

            // For sequential approval, check if it's this approver's turn
            if ($rule->approval_mode === 'sequential' && $currentApprovals + 1 !== $rule->approval_level) {
                continue;
            }

            // For parallel approval, check if this approver hasn't already approved
            if ($rule->approval_mode === 'parallel') {
                $hasApproved = EmpLeaveRequestApproval::where('emp_leave_request_id', $leaveRequest->id)
                    ->where('approver_id', Auth::id())
                    ->where('status', 'approved')
                    ->exists();
                if ($hasApproved) {
                    continue;
                }
            }

            // Check if employee is in rule's scope
            if ($this->isEmployeeInRuleScope($leaveRequest->employee_id, $rule)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get pending approvers for a leave request
     */
    public function getPendingApprovers($leaveRequest)
    {
        $approvedBy = EmpLeaveRequestApproval::where('emp_leave_request_id', $leaveRequest->id)
            ->where('status', 'approved')
            ->pluck('approver_id')
            ->toArray();

        return LeaveApprovalRule::where('firm_id', session('firm_id'))
            ->where(function ($query) use ($leaveRequest) {
                $query->where('leave_type_id', $leaveRequest->leave_type_id)
                    ->orWhereNull('leave_type_id');
            })
            ->where('is_inactive', false)
            ->whereNotIn('approver_id', $approvedBy)
            ->where('approval_mode', '!=', 'view_only')
            ->with('user')
            ->get()
            ->map(function ($rule) {
                return [
                    'user' => $rule->user,
                    'level' => $rule->approval_level,
                    'mode' => $rule->approval_mode
                ];
            });
    }

    public function showActionModal($id)
    {
        try {
            $this->resetErrorBag();
            $this->resetValidation();

            $leaveRequest = EmpLeaveRequest::where('id', $id)
                ->where('firm_id', session('firm_id'))
                ->first();

            if (!$leaveRequest) {
                Flux::toast(
                    variant: 'error',
                    heading: 'Error',
                    text: 'Leave request not found.',
                );
                return;
            }

            $this->selectedId = $leaveRequest->id;

            // Reset formData with proper initialization
            $this->formData = [
                'id' => $leaveRequest->id,
                'emp_leave_request_id' => $leaveRequest->id,
                'approval_level' => $this->getApprovalLevel($leaveRequest),
                'approver_id' => Auth::id(),
                'status' => '',
                'remarks' => '',
                'acted_at' => now()->format('Y-m-d\TH:i'),
                'firm_id' => session('firm_id'),
                'action' => null,
            ];

            $this->showActionModal = true;
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: $e->getMessage(),
            );
            \Log::error('Show action modal error: ' . $e->getMessage());
        }
    }

    public function closeModal()
    {
        $this->showActionModal = false;
        $this->resetForm();
        $this->resetErrorBag();
        $this->resetValidation();
    }


    public function handleAction($action, $id)
    {
        $this->dispatch(
            'console-log',
            message: 'Handling leave action',
            data: [
                'action' => $action,
                'id' => $id,
                'user' => Auth::id()
            ]
        );

        try {
            // Validate remarks
            $this->validate([
                'formData.remarks' => 'nullable|string|min:3',
            ]);

            if (!in_array($action, ['approve', 'reject'])) {
                throw new \Exception('Invalid action');
            }

            if (!$id) {
                Flux::toast(
                    variant: 'error',
                    heading: 'Error',
                    text: 'No leave request selected.',
                );
                return;
            }

            $leaveRequest = EmpLeaveRequest::where('id', $id)
                ->where('firm_id', session('firm_id'))
                ->first();

            if (!$leaveRequest) {
                Flux::toast(
                    variant: 'error',
                    heading: 'Error',
                    text: 'Leave request not found.',
                );
                return;
            }

            if (!$this->canApproveLeave($leaveRequest)) {
                Flux::toast(
                    variant: 'error',
                    heading: 'Unauthorized',
                    text: 'You are not authorized to perform this action.',
                );
                return;
            }

            $approvalLevel = $this->getApprovalLevel($leaveRequest);
            $oldStatus = $leaveRequest->status;

            // Determine if this is the final approval or needs further approval
            $maxApprovalLevel = $this->getMaxApprovalLevel($leaveRequest);

            // Count current approvals
            $approvalCount = EmpLeaveRequestApproval::where('emp_leave_request_id', $id)
                ->where('status', 'approved')
                ->count();

            // Add the current approval if approving
            if ($action === 'approve') {
                $approvalCount++;
                // Set new status based on approval count vs max level
                $newStatus = $approvalCount >= $maxApprovalLevel ? 'approved' : 'approved_further';
            } else {
                $newStatus = 'rejected';
            }

            try {
                // Update leave request status
                $leaveRequest->update(['status' => $newStatus]);

                // Create approval record
                EmpLeaveRequestApproval::create([
                    'emp_leave_request_id' => $id,
                    'approval_level' => $approvalLevel,
                    'approver_id' => Auth::id(),
                    'status' => $action === 'approve' ? 'approved' : 'rejected',
                    'remarks' => $this->formData['remarks'],
                    'acted_at' => now(),
                    'firm_id' => session('firm_id')
                ]);

                // Update leave balance if approved
                if ($action === 'approve') {
                    $this->updateLeaveBalance($leaveRequest);
                }

                // Log the event
                LeaveRequestEvent::create([
                    'emp_leave_request_id' => $id,
                    'user_id' => Auth::id(),
                    'event_type' => 'status_change',
                    'from_status' => $oldStatus,
                    'to_status' => $newStatus,
                    'remarks' => $this->formData['remarks'],
                    'firm_id' => session('firm_id')
                ]);

                // Close modal and reset form
                $this->closeModal();

                // Reset pagination to first page and refresh the component
                $this->resetPage();

                // Emit the event for component refresh
                $this->dispatch('leave-status-updated');

                $successMessage = $action === 'approve'
                    ? ($newStatus === 'approved'
                        ? 'Leave request has been fully approved'
                        : "Leave request approved ({$approvalCount}/{$maxApprovalLevel} approvals)")
                    : 'Leave request has been rejected';

                Flux::toast(
                    variant: 'success',
                    heading: $action === 'approve' ? 'Leave Approved' : 'Leave Rejected',
                    text: $successMessage,
                );

            } catch (\Exception $e) {
                throw $e;
            }

        } catch (\Exception $e) {
            $this->dispatch(
                'console-log',
                message: 'Leave action error',
                data: ['error' => $e->getMessage()]
            );
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: $e->getMessage(),
            );
        }
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
            ->where(function ($query) use ($leaveRequest) {
                $query->where('leave_type_id', $leaveRequest->leave_type_id)
                    ->orWhereNull('leave_type_id');
            })
            ->where('is_inactive', false)
            ->first();

        return $approvalRule ? $approvalRule->approval_level : 1; // Default to 1 if no rule found
    }

    /**
     * Update employee leave balance when leave is approved
     * 
     * @param EmpLeaveRequest $leaveRequest
     * @return void
     */
    protected function updateLeaveBalance(EmpLeaveRequest $leaveRequest): void
    {
        // Only update balance if this is the final approval
        $maxApprovalLevel = $this->getMaxApprovalLevel($leaveRequest);
        $approvalCount = EmpLeaveRequestApproval::where('emp_leave_request_id', $leaveRequest->id)
            ->where('status', 'approved')
            ->count();

        if ($approvalCount >= $maxApprovalLevel) {
            // Find current leave balance
            $leaveBalance = EmpLeaveBalance::where('firm_id', session('firm_id'))
                ->where('employee_id', $leaveRequest->employee_id)
                ->where('leave_type_id', $leaveRequest->leave_type_id)
                ->where('period_start', '<=', $leaveRequest->apply_from)
                ->where('period_end', '>=', $leaveRequest->apply_to)
                ->first();

            if ($leaveBalance) {
                // Update the balance
                $leaveBalance->consumed_days += $leaveRequest->apply_days;
                $leaveBalance->balance = $leaveBalance->allocated_days + $leaveBalance->carry_forwarded_days - $leaveBalance->consumed_days - $leaveBalance->lapsed_days;
                $leaveBalance->save();

                // Create a leave transaction record
                EmpLeaveTransaction::create([
                    'leave_balance_id' => $leaveBalance->id,
                    'emp_leave_request_id' => $leaveRequest->id,
                    'transaction_type' => 'debit',
                    'transaction_date' => now(),
                    'amount' => $leaveRequest->apply_days,
                    'reference_id' => $leaveRequest->id, // Using leave request id as reference
                    'created_by' => Auth::id(), // Current authenticated user
                    'firm_id' => session('firm_id'),
                    'remarks' => 'Leave approved'
                ]);
            }
        }
    }

    public function showLeaveRequestEvents($id)
    {
        try {
            $this->id = $id;
            $this->showEventsModal = true;
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: $e->getMessage(),
            );
            \Log::error('Show events modal error: ' . $e->getMessage());
        }
    }

    public function closeEventsModal()
    {
        $this->showEventsModal = false;
        $this->selectedId = null;
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Leave/blades/team-leaves.blade.php'));
    }
}
