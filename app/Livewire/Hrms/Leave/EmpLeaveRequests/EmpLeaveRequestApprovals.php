<?php

namespace App\Livewire\Hrms\Leave\EmpLeaveRequests;

use App\Models\Hrms\EmpLeaveRequestApproval;
use App\Models\Hrms\EmpLeaveRequest;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class EmpLeaveRequestApprovals extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;

    // Field configuration for form and table
    public array $fieldConfig = [
        'emp_leave_request_id' => ['label' => 'Leave Request', 'type' => 'select', 'listKey' => 'leave_requests', 'showInForm' => true],
        'approval_level' => ['label' => 'Approval Level', 'type' => 'number', 'showInForm' => true],
        'approver_id' => ['label' => 'Approver', 'type' => 'select', 'listKey' => 'approvers', 'showInForm' => true],
        'status' => ['label' => 'Status', 'type' => 'select', 'listKey' => 'statuses', 'showInForm' => true],
        'remarks' => ['label' => 'Remarks', 'type' => 'textarea', 'showInForm' => true],
        'acted_at' => ['label' => 'Acted At', 'type' => 'datetime-local', 'showInForm' => true],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'emp_leave_request_id' => ['label' => 'Leave Request', 'type' => 'select', 'listKey' => 'leave_requests'],
        'approver_id' => ['label' => 'Approver', 'type' => 'select', 'listKey' => 'approvers'],
        'status' => ['label' => 'Status', 'type' => 'select', 'listKey' => 'statuses'],
        'approval_level' => ['label' => 'Approval Level', 'type' => 'number'],
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
        'emp_leave_request_id' => null,
        'approval_level' => null,
        'approver_id' => null,
        'status' => '',
        'remarks' => '',
        'acted_at' => null,
    ];

    protected function rules()
    {
        return [
            'formData.emp_leave_request_id' => 'required|integer|exists:emp_leave_requests,id',
            'formData.approval_level' => 'required|integer|min:1',
            'formData.approver_id' => 'required|integer|exists:users,id',
            'formData.status' => 'required|string',
            'formData.remarks' => 'nullable|string',
            'formData.acted_at' => 'nullable|date',
        ];
    }

    public function mount()
    {
        $this->initListsForFields();
        
        // Set default visible fields - excluding created_at and updated_at
        $this->visibleFields = ['emp_leave_request_id', 'approval_level', 'approver_id', 'status', 'remarks', 'acted_at'];
        $this->visibleFilterFields = ['emp_leave_request_id', 'approver_id', 'status', 'approval_level'];
        
        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['leave_requests'] = EmpLeaveRequest::where('firm_id', session('firm_id'))
            ->with('employee')
            ->get()
            ->pluck('employee.fname', 'id');
            
        $this->listsForFields['approvers'] = User::whereHas('firms', function($query) {
                $query->where('firms.id', session('firm_id'));
            })
            ->pluck('name', 'id');
            
        $this->listsForFields['statuses'] = EmpLeaveRequestApproval::STATUS_SELECT;
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
        return EmpLeaveRequestApproval::query()
            ->with(['emp_leave_request.employee', 'user'])
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->filters['emp_leave_request_id'], fn($query, $value) => 
                $query->where('emp_leave_request_id', $value))
            ->when($this->filters['approver_id'], fn($query, $value) => 
                $query->where('approver_id', $value))
            ->when($this->filters['status'], fn($query, $value) => 
                $query->where('status', $value))
            ->when($this->filters['approval_level'], fn($query, $value) => 
                $query->where('approval_level', $value))
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

        if ($this->isEditing) {
            $approval = EmpLeaveRequestApproval::findOrFail($this->formData['id']);
            $approval->update($validatedData['formData']);
            $toastMsg = 'Leave request approval updated successfully';
        } else {
            EmpLeaveRequestApproval::create($validatedData['formData']);
            $toastMsg = 'Leave request approval added successfully';
        }

        $this->resetForm();
        $this->modal('mdl-leave-request-approval')->close();
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: $toastMsg,
        );
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->isEditing = false;
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $approval = EmpLeaveRequestApproval::findOrFail($id);
        $this->formData = $approval->toArray();
        if ($this->formData['acted_at']) {
            $this->formData['acted_at'] = date('Y-m-d\TH:i', strtotime($this->formData['acted_at']));
        }
        $this->modal('mdl-leave-request-approval')->show();
    }

    public function delete($id)
    {
        $approval = EmpLeaveRequestApproval::findOrFail($id);
        $approval->delete();
        
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Leave request approval has been deleted successfully',
        );
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Leave/EmpLeaveRequests/blades/emp-leave-request-approvals.blade.php'));
    }
} 