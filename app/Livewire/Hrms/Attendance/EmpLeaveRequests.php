<?php

namespace App\Livewire\Hrms\Attendance;

use Livewire\Component;
use App\Models\Hrms\EmpLeaveRequest;
use App\Models\Hrms\Employee;
use App\Models\Hrms\LeaveType;
use Flux;

class EmpLeaveRequests extends Component
{
    use \Livewire\WithPagination;

    public $employeeId;

    public $leaveRequestData = [
        'id' => null,
        'firm_id' => null,
        'employee_id' => '',
        'leave_type_id' => '',
        'apply_from' => '',
        'apply_to' => '',
        'apply_days' => '',
        'reason' => '',
        'status' => 'applied',
    ];

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;

    public function mount($employeeId = null)
    {
        $this->employeeId = $employeeId;
        if ($employeeId) {
            $this->leaveRequestData['employee_id'] = $employeeId;
        }
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
    public function leaveRequestsList()
    {
        return EmpLeaveRequest::query()
            ->with(['employee', 'leave_type'])
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->when($this->employeeId, fn($query) => $query->where('employee_id', $this->employeeId))
            ->where('firm_id', session('firm_id'))
            ->paginate(5);
    }

    #[\Livewire\Attributes\Computed]
    public function employeesList()
    {
        $query = Employee::where('firm_id', session('firm_id'))
            ->where('is_inactive', false);
        
        if ($this->employeeId) {
            $query->where('id', $this->employeeId);
        }

        return $query->get()
            ->map(function($employee) {
                return [
                    'id' => $employee->id,
                    'name' => $employee->fname . ' ' . $employee->lname . ' (' . $employee->email . ')'
                ];
            });
    }

    #[\Livewire\Attributes\Computed]
    public function leaveTypesList()
    {
        return LeaveType::where('firm_id', session('firm_id'))
            ->get();
    }

    public function fetchLeaveRequest($id)
    {
        $leaveRequest = EmpLeaveRequest::findOrFail($id);
        $this->leaveRequestData = $leaveRequest->toArray();
        $this->isEditing = true;
        $this->modal('mdl-leave-request')->show();
    }

    public function saveLeaveRequest()
    {
        $validatedData = $this->validate([
            'leaveRequestData.employee_id' => 'required|exists:employees,id',
            'leaveRequestData.leave_type_id' => 'required|exists:leave_types,id',
            'leaveRequestData.apply_from' => 'required|date',
            'leaveRequestData.apply_to' => 'required|date|after_or_equal:leaveRequestData.apply_from',
            'leaveRequestData.apply_days' => 'required|integer|min:1',
            'leaveRequestData.reason' => 'nullable|string|max:500',
            'leaveRequestData.status' => [
                'required',
                'in:' . implode(',', array_keys(EmpLeaveRequest::STATUS_SELECT))
            ],
        ]);

        if ($this->isEditing) {
            $leaveRequest = EmpLeaveRequest::findOrFail($this->leaveRequestData['id']);
            $leaveRequest->update($validatedData['leaveRequestData']);
            $message = 'Leave request updated successfully.';
        } else {
            $validatedData['leaveRequestData']['firm_id'] = session('firm_id');
            if ($this->employeeId) {
                $validatedData['leaveRequestData']['employee_id'] = $this->employeeId;
            }
            EmpLeaveRequest::create($validatedData['leaveRequestData']);
            $message = 'Leave request added successfully.';
        }

        $this->resetForm();
        $this->modal('mdl-leave-request')->close();
        Flux::toast(
            heading: 'Changes saved.',
            text: $message,
            position: 'top-right',
        );
    }

    public function resetForm()
    {
        $this->leaveRequestData = [
            'id' => null,
            'firm_id' => null,
            'employee_id' => $this->employeeId ?? '',
            'leave_type_id' => '',
            'apply_from' => '',
            'apply_to' => '',
            'apply_days' => '',
            'reason' => '',
            'status' => 'applied',
        ];
        $this->isEditing = false;
    }

    public function deleteLeaveRequest($id)
    {
        try {
            $leaveRequest = EmpLeaveRequest::findOrFail($id);
            $employeeName = $leaveRequest->employee->fname . ' ' . $leaveRequest->employee->lname;
            $leaveRequest->delete();

            Flux::toast(
                heading: 'Success',
                text: "Leave request for {$employeeName} deleted successfully.",
                position: 'top-right',
            );
        } catch (\Exception $e) {
            Flux::toast(
                heading: 'Error',
                text: 'Failed to delete leave request.',
                variant: 'error',
                position: 'top-right',
            );
        }
    }

    public function render()
    {
        return view('livewire.hrms.attendance.emp-leave-requests', [
            'employees' => $this->employeesList,
            'leaveTypes' => $this->leaveTypesList,
        ]);
    }
} 