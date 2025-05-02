<?php

namespace App\Livewire\Hrms\Leave;

use App\Models\Hrms\EmpLeaveRequest;
use App\Models\Hrms\LeaveType;
use App\Models\Hrms\LeaveRequestEvent;
use App\Models\Hrms\EmpLeaveRequestApproval;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class MyLeaves extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;
    public $selectedId = null;

    // Field configuration for form and table
    public array $fieldConfig = [
        'leave_type_id' => ['label' => 'Leave Type', 'type' => 'select', 'listKey' => 'leave_types'],
        'apply_from' => ['label' => 'Apply From', 'type' => 'date'],
        'apply_to' => ['label' => 'Apply To', 'type' => 'date'],
        'apply_days' => ['label' => 'Apply Days', 'type' => 'number'],
        'reason' => ['label' => 'Reason', 'type' => 'text'],
        'status' => ['label' => 'Status', 'type' => 'select', 'listKey' => 'statuses'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'leave_type_id' => ['label' => 'Leave Type', 'type' => 'select', 'listKey' => 'leave_types'],
        'apply_from' => ['label' => 'Apply From', 'type' => 'date'],
        'apply_to' => ['label' => 'Apply To', 'type' => 'date'],
        'status' => ['label' => 'Status', 'type' => 'select', 'listKey' => 'statuses'],
        'created_at' => ['label' => 'Created At', 'type' => 'date'],
        'updated_at' => ['label' => 'Updated At', 'type' => 'date'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'employee_id' => null,
        'leave_type_id' => null,
        'apply_from' => '',
        'apply_to' => '',
        'apply_days' => 0,
        'reason' => '',
        'status' => '',
    ];

    protected function rules()
    {
        return [
            'formData.leave_type_id' => 'required|integer|exists:leave_types,id',
            'formData.apply_from' => 'required|date',
            'formData.apply_to' => 'required|date|after:formData.apply_from',
            'formData.apply_days' => 'required|numeric|min:1',
            'formData.reason' => 'nullable|string',
        ];
    }

    public function mount()
    {
        $this->initListsForFields();
        
        // Set default visible fields - excluding created_at and updated_at
        $this->visibleFields = ['leave_type_id', 'apply_from', 'apply_to', 'apply_days', 'status', 'reason'];
        $this->visibleFilterFields = ['leave_type_id', 'apply_from', 'apply_to', 'status'];
        
        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['leave_types'] = LeaveType::where('firm_id', session('firm_id'))
            ->pluck('leave_title', 'id');
        $this->listsForFields['statuses'] = EmpLeaveRequest::STATUS_SELECT;
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
        return EmpLeaveRequest::query()
            ->with(['employee', 'leave_type'])
            ->where('firm_id', Session::get('firm_id'))
            ->where('employee_id', auth()->user()->employee->id)
            ->when($this->filters['leave_type_id'], fn($query, $value) => 
                $query->where('leave_type_id', $value))
            ->when($this->filters['apply_from'], fn($query, $value) => 
                $query->where('apply_from', '>=', $value))
            ->when($this->filters['apply_to'], fn($query, $value) => 
                $query->where('apply_to', '<=', $value))
            ->when($this->filters['status'], fn($query, $value) => 
                $query->where('status', $value))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function store()
    {
        $validatedData = $this->validate($this->rules());

        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        $validatedData['formData']['firm_id'] = session('firm_id');
        $validatedData['formData']['employee_id'] = auth()->user()->employee->id;
        $validatedData['formData']['status'] = 'applied';

        if ($this->isEditing) {
            $leaveRequest = EmpLeaveRequest::findOrFail($this->formData['id']);
            $oldStatus = $leaveRequest->status;
            $leaveRequest->update($validatedData['formData']);
            
            // Create event record for status change
            LeaveRequestEvent::create([
                'emp_leave_request_id' => $leaveRequest->id,
                'user_id' => auth()->id(),
                'event_type' => 'status_change',
                'from_status' => $oldStatus,
                'to_status' => $validatedData['formData']['status'],
                'remarks' => 'Leave request status updated',
                'firm_id' => session('firm_id')
            ]);

            // Create approval record
            EmpLeaveRequestApproval::create([
                'emp_leave_request_id' => $leaveRequest->id,
                'approval_level' => 1,
                'approver_id' => auth()->id(),
                'status' => $validatedData['formData']['status'],
                'remarks' => 'Leave request status updated',
                'acted_at' => now(),
                'firm_id' => session('firm_id')
            ]);

            $toastMsg = 'Leave request updated successfully';
        } else {
            $leaveRequest = EmpLeaveRequest::create($validatedData['formData']);
            
            // Create event record for new request
            LeaveRequestEvent::create([
                'emp_leave_request_id' => $leaveRequest->id,
                'user_id' => auth()->id(),
                'event_type' => 'created',
                'from_status' => null,
                'to_status' => 'applied',
                'remarks' => 'New leave request created',
                'firm_id' => session('firm_id')
            ]);

            // Create initial approval record
            EmpLeaveRequestApproval::create([
                'emp_leave_request_id' => $leaveRequest->id,
                'approval_level' => 1,
                'approver_id' => auth()->id(),
                'status' => 'applied',
                'remarks' => 'Initial leave request submission',
                'acted_at' => now(),
                'firm_id' => session('firm_id')
            ]);

            $toastMsg = 'Leave request added successfully';
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
        $this->formData['apply_days'] = 0;
        $this->isEditing = false;
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $leaveRequest = EmpLeaveRequest::findOrFail($id);
        
        // Check if the leave request belongs to the authenticated user
        if ($leaveRequest->employee_id !== auth()->user()->employee->id) {
            Flux::toast(
                variant: 'error',
                heading: 'Access Denied',
                text: 'You can only edit your own leave requests.',
            );
            return;
        }
        
        $this->formData = $leaveRequest->toArray();
        $this->formData['apply_from'] = $leaveRequest->apply_from ? $leaveRequest->apply_from->format('Y-m-d') : '';
        $this->formData['apply_to'] = $leaveRequest->apply_to ? $leaveRequest->apply_to->format('Y-m-d') : '';
        $this->modal('mdl-leave-request')->show();
    }

    public function delete($id)
    {
        $leaveRequest = EmpLeaveRequest::findOrFail($id);
        
        // Check if the leave request belongs to the authenticated user
        if ($leaveRequest->employee_id !== auth()->user()->employee->id) {
            Flux::toast(
                variant: 'error',
                heading: 'Access Denied',
                text: 'You can only delete your own leave requests.',
            );
            return;
        }
        
        // Check if leave request has related records
        if ($leaveRequest->emp_leave_request_approvals()->count() > 0 || 
            $leaveRequest->leave_request_events()->count() > 0) {
            Flux::toast(
                variant: 'error',
                heading: 'Cannot Delete',
                text: 'This leave request has related records and cannot be deleted.',
            );
            return;
        }

        $leaveRequest->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Leave request has been deleted successfully',
        );
    }

    public function showLeaveRequestEvents($selectedId)
    {

        $this->selectedId = $selectedId;
        $this->modal('leave-request-events-modal')->show();
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Leave/blades/my-leaves.blade.php'));
    }
} 