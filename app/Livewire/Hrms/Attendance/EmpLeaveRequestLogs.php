<?php

namespace App\Livewire\Hrms\Attendance;

use Livewire\Component;
use App\Models\Hrms\EmpLeaveRequestLog;
use App\Models\Hrms\EmpLeaveRequest;
use Carbon\Carbon;
use Flux;

class EmpLeaveRequestLogs extends Component
{
    use \Livewire\WithPagination;

    public $logData = [
        'id' => null,
        'firm_id' => null,
        'emp_leave_request_id' => '',
        'status_datetime' => '',
        'remarks' => '',
        'status' => '',
        'action_by' => null,
    ];

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;
    public $selectedLogId = null;

    public function mount()
    {
        $this->logData['status_datetime'] = now()->format('Y-m-d H:i:s');
    }

    public function sort($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    #[\Livewire\Attributes\Computed]
    public function logsList()
    {
        return EmpLeaveRequestLog::query()
            ->with(['emp_leave_request.employee'])
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->where('firm_id', session('firm_id'))
            ->paginate(10);
    }

    #[\Livewire\Attributes\Computed]
    public function leaveRequestsList()
    {
        return EmpLeaveRequest::where('firm_id', session('firm_id'))
            ->with('employee')
            ->get()
            ->map(function($request) {
                return [
                    'id' => $request->id,
                    'name' => $request->employee->fname . ' ' . $request->employee->lname . 
                            ' (' . \Carbon\Carbon::parse($request->apply_from)->format('Y-m-d') . 
                            ' to ' . \Carbon\Carbon::parse($request->apply_to)->format('Y-m-d') . ')'
                ];
            });
    }

    public function fetchLog($id)
    {
        $log = EmpLeaveRequestLog::findOrFail($id);
        $this->logData = $log->toArray();
        $this->isEditing = true;
        $this->modal('mdl-leave-request-log')->show();
    }

    public function saveLog()
    {
        $validatedData = $this->validate([
            'logData.emp_leave_request_id' => 'required|exists:emp_leave_requests,id',
            'logData.status_datetime' => 'required|date',
            'logData.remarks' => 'nullable|string|max:500',
            'logData.status' => 'required|in:pending,approved,rejected',
            'logData.action_by' => 'nullable|integer',
        ]);

        try {
            if ($this->isEditing) {
                $log = EmpLeaveRequestLog::findOrFail($this->logData['id']);
                $log->update($validatedData['logData']);
                $message = 'Leave request log updated successfully.';
            } else {
                $validatedData['logData']['firm_id'] = session('firm_id');
                $validatedData['logData']['action_by'] = auth()->id();
                EmpLeaveRequestLog::create($validatedData['logData']);
                $message = 'Leave request log added successfully.';
            }

            $this->resetForm();
            $this->modal('mdl-leave-request-log')->close();
            
            Flux::toast(
                heading: 'Success',
                text: $message,
                position: 'top-right',
            );
        } catch (\Exception $e) {
            Flux::toast(
                heading: 'Error',
                text: 'Failed to save log: ' . $e->getMessage(),
                variant: 'error',
                position: 'top-right',
            );
        }
    }

    public function deleteLog($id)
    {
        try {
            $log = EmpLeaveRequestLog::findOrFail($id);
            $log->delete();

            Flux::toast(
                heading: 'Success',
                text: 'Leave request log deleted successfully.',
                position: 'top-right',
            );
        } catch (\Exception $e) {
            Flux::toast(
                heading: 'Error',
                text: 'Failed to delete log.',
                variant: 'error',
                position: 'top-right',
            );
        }
    }

    public function resetForm()
    {
        $this->logData = [
            'id' => null,
            'firm_id' => null,
            'emp_leave_request_id' => '',
            'status_datetime' => now()->format('Y-m-d H:i:s'),
            'remarks' => '',
            'status' => '',
            'action_by' => null,
        ];
        $this->isEditing = false;
    }

    public function render()
    {
        return view('livewire.hrms.attendance.emp-leave-request-logs');
    }
}