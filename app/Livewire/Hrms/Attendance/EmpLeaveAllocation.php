<?php

namespace App\Livewire\Hrms\Attendance;

use Livewire\Component;
use App\Models\Hrms\EmpLeaveAllocation;
use App\Models\Hrms\Employee;
use App\Models\Hrms\LeaveType;
use App\Models\Hrms\LeavesQuotaTemplate;
use Flux;

class EmpLeaveAllocations extends Component
{
    use \Livewire\WithPagination;

    public $allocationData = [
        'id' => null,
        'firm_id' => null,
        'employee_id' => '',
        'leaves_quota_template_id' => '',
        'leave_type_id' => '',
        'days_assigned' => '',
        'start_date' => '',
        'end_date' => '',
        'days_balance' => '',
    ];

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;

    public function mount()
    {
        session()->put('firm_id', 3);
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
    public function allocationsList()
    {
        return EmpLeaveAllocation::query()
            ->with(['employee', 'leave_type', 'leaves_quota_template'])
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->where('firm_id', session('firm_id'))
            ->paginate(5);
    }

    #[\Livewire\Attributes\Computed]
    public function employeesList()
    {
        return Employee::where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->get()
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

    #[\Livewire\Attributes\Computed]
    public function quotaTemplatesList()
    {
        return LeavesQuotaTemplate::where('firm_id', session('firm_id'))
            ->get();
    }

    public function fetchAllocation($id)
    {
        $allocation = EmpLeaveAllocation::findOrFail($id);
        $this->allocationData = $allocation->toArray();
        $this->isEditing = true;
        $this->modal('mdl-leave-allocation')->show();
    }

    public function saveAllocation()
    {
        $validatedData = $this->validate([
            'allocationData.employee_id' => 'required|exists:employees,id',
            'allocationData.leave_type_id' => 'required|exists:leave_types,id',
            'allocationData.leaves_quota_template_id' => 'nullable|exists:leaves_quota_templates,id',
            'allocationData.days_assigned' => 'required|integer|min:0',
            'allocationData.start_date' => 'required|date',
            'allocationData.end_date' => 'required|date|after_or_equal:allocationData.start_date',
            'allocationData.days_balance' => 'required|integer|min:0',
        ]);

        if ($this->isEditing) {
            $allocation = EmpLeaveAllocation::findOrFail($this->allocationData['id']);
            $allocation->update($validatedData['allocationData']);
            $message = 'Leave allocation updated successfully.';
        } else {
            $validatedData['allocationData']['firm_id'] = session('firm_id');
            EmpLeaveAllocation::create($validatedData['allocationData']);
            $message = 'Leave allocation added successfully.';
        }

        $this->resetForm();
        $this->modal('mdl-leave-allocation')->close();
        Flux::toast(
            heading: 'Changes saved.',
            text: $message,
            position: 'top-right',
        );
    }

    public function resetForm()
    {
        $this->allocationData = [
            'id' => null,
            'firm_id' => null,
            'employee_id' => '',
            'leaves_quota_template_id' => '',
            'leave_type_id' => '',
            'days_assigned' => '',
            'start_date' => '',
            'end_date' => '',
            'days_balance' => '',
        ];
        $this->isEditing = false;
    }

    public function deleteAllocation($id)
    {
        try {
            $allocation = EmpLeaveAllocation::findOrFail($id);
            $employeeName = $allocation->employee->fname . ' ' . $allocation->employee->lname;
            $allocation->delete();

            Flux::toast(
                heading: 'Success',
                text: "Leave allocation for {$employeeName} deleted successfully.",
                position: 'top-right',
            );
        } catch (\Exception $e) {
            Flux::toast(
                heading: 'Error',
                text: 'Failed to delete leave allocation.',
                variant: 'error',
                position: 'top-right',
            );
        }
    }

    public function render()
    {
        return view('livewire.hrms.attendance.emp-leave-allocations', [
            'employees' => $this->employeesList,
            'leaveTypes' => $this->leaveTypesList,
            'quotaTemplates' => $this->quotaTemplatesList,
        ]);
    }
}