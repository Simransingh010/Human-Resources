<?php

namespace App\Livewire\Hrms\Attendance;

use Livewire\Component;
use App\Models\Hrms\LeaveType;
use Flux;

class LeaveTypes extends Component
{
    use \Livewire\WithPagination;

    public $leaveTypeData = [
        'id' => null,
        'firm_id' => null,
        'leave_title' => '',
        'leave_desc' => '',
        'leave_code' => '',
        'leave_nature' => '',
        'max_days' => null,
        'carry_forward' => false,
        'encashable' => false,
    ];

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;

    public function mount()
    {
    
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
    public function leaveTypesList()
    {
        return LeaveType::query()
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->where('firm_id', session('firm_id'))
            ->paginate(5);
    }

    public function fetchLeaveType($id)
    {
        $leaveType = LeaveType::findOrFail($id);
        $this->leaveTypeData = $leaveType->toArray();
        $this->isEditing = true;
        $this->modal('mdl-leave-type')->show();
    }

    public function saveLeaveType()
    {
        $validatedData = $this->validate([
            'leaveTypeData.leave_title' => 'required|string|max:255',
            'leaveTypeData.leave_desc' => 'nullable|string',
            'leaveTypeData.leave_code' => 'nullable|string|max:50',
            'leaveTypeData.leave_nature' => [
                'required',
                'string',
                'in:' . implode(',', array_keys(LeaveType::LEAVE_NATURE_SELECT))
            ],
            'leaveTypeData.max_days' => 'nullable|integer|min:0',
            'leaveTypeData.carry_forward' => 'boolean',
            'leaveTypeData.encashable' => 'boolean',
        ]);

        if ($this->isEditing) {
            $leaveType = LeaveType::findOrFail($this->leaveTypeData['id']);
            $leaveType->update($validatedData['leaveTypeData']);
            session()->flash('message', 'Leave type updated successfully.');
        } else {
            $validatedData['leaveTypeData']['firm_id'] = session('firm_id');
            LeaveType::create($validatedData['leaveTypeData']);
            session()->flash('message', 'Leave type added successfully.');
        }

        $this->resetForm();
        $this->modal('mdl-leave-type')->close();
        Flux::toast(
            heading: 'Changes saved.',
            text: 'Leave type has been updated.',
        );
    }
    public function deleteLeaveType($id)
    {
        try {
            $policy = LeaveType::findOrFail($id);
            $policy->delete();

            Flux::toast(
                heading: 'Success',
                text: 'Leave Type deleted successfully.',
            );
        } catch (\Exception $e) {
            Flux::toast(
                heading: 'Error',
                text: 'Failed to delete leave type.',
                variant: 'error'
            );
        }
    }
    public function resetForm()
    {
        $this->leaveTypeData = [
            'id' => null,
            'firm_id' => null,
            'leave_title' => '',
            'leave_desc' => '',
            'leave_code' => '',
            'leave_nature' => 'paid',
            'max_days' => null,
            'carry_forward' => false,
            'encashable' => false,
        ];
        $this->isEditing = false;
    }

    public function render()
    {
        return view('livewire.hrms.attendance.leave-types');
    }
} 