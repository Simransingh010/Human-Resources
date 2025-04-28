<?php

namespace App\Livewire\Hrms\Leave\LeaveMeta;

use App\Models\Hrms\EmpLeaveRequest;
use App\Models\Hrms\EmpLeaveRequestLog;
use App\Models\Hrms\Employee;
use App\Models\Hrms\LeaveType;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class EmpLeaveRequestLogs extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'status_datetime';
    public $sortDirection = 'desc';
    public $isEditing = false;
    public $leaveRequestId;

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'emp_leave_request_id' => null,
        'status_datetime' => '',
        'remarks' => '',
        'status' => '',
        'action_by' => null,
    ];

    public $filters = [
        'search' => '',
        'employees' => [],
        'leave_types' => [],
        'status' => ''
    ];

    public $listsForFields = [];

    protected function rules()
    {
        return [
            'formData.status' => 'required|string',
            'formData.status_datetime' => 'required|date',
            'formData.remarks' => 'nullable|string',
        ];
    }

    public function mount($leaveRequestId)
    {
        $this->leaveRequestId = $leaveRequestId;
        $this->initListsForFields();
        $this->resetPage();
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['employees'] = Employee::where('firm_id', session('firm_id'))
            ->pluck('fname', 'id');
        $this->listsForFields['leave_types'] = LeaveType::where('firm_id', session('firm_id'))
            ->pluck('leave_title', 'id');
        $this->listsForFields['statuses'] = EmpLeaveRequest::STATUS_SELECT;
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['status'] = '';
        $this->formData['status_datetime'] = '';
        $this->formData['remarks'] = '';
        $this->isEditing = false;
    }

    public function store()
    {
        $validatedData = $this->validate($this->rules());

        if ($this->isEditing) {
            $log = EmpLeaveRequestLog::findOrFail($this->formData['id']);
            $log->update($validatedData['formData']);
            session()->flash('message', 'Log entry updated successfully.');
        } else {
            $validatedData['formData']['firm_id'] = session('firm_id');
            $validatedData['formData']['emp_leave_request_id'] = $this->leaveRequestId;
            $validatedData['formData']['action_by'] = auth()->id();
            EmpLeaveRequestLog::create($validatedData['formData']);
            session()->flash('message', 'Log entry added successfully.');
        }

        $this->resetForm();
        $this->modal('mdl-leave-request-log')->close();
        Flux::toast(
            heading: 'Changes saved.',
            text: 'Leave request logs have been updated.',
        );
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $log = EmpLeaveRequestLog::findOrFail($id);
        $this->formData = $log->toArray();
        $this->formData['status_datetime'] = $log->status_datetime ? $log->status_datetime->format('Y-m-d\TH:i') : '';
        $this->modal('mdl-leave-request-log')->show();
    }

    public function delete($id)
    {
        try {
            $log = EmpLeaveRequestLog::findOrFail($id);
            $log->delete();

            Flux::toast(
                variant: 'success',
                heading: 'Record Deleted.',
                text: 'Log entry has been deleted successfully',
            );
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to delete log entry: ' . $e->getMessage(),
            );
        }
    }

    public function clearFilters()
    {
        $this->reset('filters');
        $this->resetPage();
    }

    public function applyFilters()
    {
        $this->resetPage();
    }

    #[Computed]
    public function list()
    {
        return EmpLeaveRequestLog::query()
            ->with(['action_by_user'])
            ->where('emp_leave_request_id', $this->leaveRequestId)
            ->where('firm_id', Session::get('firm_id'))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Leave/LeaveMeta/blades/emp-leave-request-logs.blade.php'));
    }
} 