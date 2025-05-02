<?php

namespace App\Livewire\Hrms\Leave\EmpLeaveRequests;

use App\Models\Hrms\LeaveRequestEvent;
use App\Models\Hrms\EmpLeaveRequest;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class LeaveRequestEvents extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;
    public $emp_leave_request_id = null;

    // Field configuration for form and table
    public array $fieldConfig = [
        'emp_leave_request_id' => ['label' => 'Leave Request', 'type' => 'select', 'listKey' => 'leave_requests', 'showInForm' => true],
        'user_id' => ['label' => 'User', 'type' => 'select', 'listKey' => 'users', 'showInForm' => true],
        'event_type' => ['label' => 'Event Type', 'type' => 'text', 'showInForm' => true],
        'from_status' => ['label' => 'From Status', 'type' => 'text', 'showInForm' => true],
        'to_status' => ['label' => 'To Status', 'type' => 'text', 'showInForm' => true],
        'remarks' => ['label' => 'Remarks', 'type' => 'textarea', 'showInForm' => true],
        'created_at' => ['label' => 'Created At', 'type' => 'datetime', 'showInForm' => false],
        'deleted_at' => ['label' => 'Deleted At', 'type' => 'datetime', 'showInForm' => false],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'emp_leave_request_id' => ['label' => 'Leave Request', 'type' => 'select', 'listKey' => 'leave_requests'],
        'user_id' => ['label' => 'User', 'type' => 'select', 'listKey' => 'users'],
        'event_type' => ['label' => 'Event Type', 'type' => 'text'],
        'from_status' => ['label' => 'From Status', 'type' => 'text'],
        'to_status' => ['label' => 'To Status', 'type' => 'text'],
        'created_at' => ['label' => 'Created At', 'type' => 'date'],
        'deleted_at' => ['label' => 'Deleted At', 'type' => 'date'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'emp_leave_request_id' => null,
        'user_id' => null,
        'event_type' => '',
        'from_status' => '',
        'to_status' => '',
        'remarks' => '',
    ];

    protected function rules()
    {
        return [
            'formData.emp_leave_request_id' => 'required|integer|exists:emp_leave_requests,id',
            'formData.user_id' => 'nullable|integer|exists:users,id',
            'formData.event_type' => 'required|string',
            'formData.from_status' => 'nullable|string',
            'formData.to_status' => 'nullable|string',
            'formData.remarks' => 'nullable|string',
        ];
    }

    public function mount($empLeaveRequestId = null)
    {
        $this->emp_leave_request_id = $empLeaveRequestId;
        $this->initListsForFields();
        $this->visibleFields = ['emp_leave_request_id', 'user_id', 'event_type', 'from_status', 'to_status', 'remarks', 'created_at'];
        $this->visibleFilterFields = ['user_id', 'event_type', 'from_status', 'to_status'];
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['leave_requests'] = EmpLeaveRequest::where('firm_id', session('firm_id'))
            ->with('employee')
            ->get()
            ->pluck('employee.fname', 'id');
        $this->listsForFields['users'] = User::whereHas('firms', function($query) {
                $query->where('firms.id', session('firm_id'));
            })
            ->pluck('name', 'id');
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
        return LeaveRequestEvent::query()
            ->with(['emp_leave_request.employee', 'user'])
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->emp_leave_request_id, fn($query) => 
                $query->where('emp_leave_request_id', $this->emp_leave_request_id))
            ->when($this->filters['user_id'], fn($query, $value) => 
                $query->where('user_id', $value))
            ->when($this->filters['event_type'], fn($query, $value) => 
                $query->where('event_type', 'like', "%$value%"))
            ->when($this->filters['from_status'], fn($query, $value) => 
                $query->where('from_status', 'like', "%$value%"))
            ->when($this->filters['to_status'], fn($query, $value) => 
                $query->where('to_status', 'like', "%$value%"))
            ->when($this->filters['created_at'], fn($query, $value) => 
                $query->whereDate('created_at', $value))
            ->when($this->filters['deleted_at'], fn($query, $value) => 
                $query->whereDate('deleted_at', $value))
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
            $event = LeaveRequestEvent::findOrFail($this->formData['id']);
            $event->update($validatedData['formData']);
            $toastMsg = 'Leave request event updated successfully';
        } else {
            LeaveRequestEvent::create($validatedData['formData']);
            $toastMsg = 'Leave request event added successfully';
        }

        $this->resetForm();
        $this->modal('mdl-leave-request-event')->close();
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
        $event = LeaveRequestEvent::findOrFail($id);
        $this->formData = $event->toArray();
        $this->modal('mdl-leave-request-event')->show();
    }

    public function delete($id)
    {
        $event = LeaveRequestEvent::findOrFail($id);
        $event->delete();
        
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Leave request event has been deleted successfully',
        );
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Leave/EmpLeaveRequests/blades/emp-leave-request-events.blade.php'));
    }
} 