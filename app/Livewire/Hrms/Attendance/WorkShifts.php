<?php

namespace App\Livewire\Hrms\Attendance;

use Livewire\Component;
use App\Models\Hrms\WorkShift;
use Flux;

class WorkShifts extends Component
{
    use \Livewire\WithPagination;

    public $shiftData = [
        'id' => null,
        'firm_id' => null,
        'shift_title' => '',
        'shift_desc' => '',
        'start_date' => null,
        'end_date' => null,
        'is_inactive' => false,
    ];

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;
    public $selectedShiftId = null;

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
    public function shiftsList()
    {
        return WorkShift::query()
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->where('firm_id', session('firm_id'))
            ->paginate(5);
    }

    public function fetchShift($id)
    {
        $workShift = WorkShift::findOrFail($id);
        $this->shiftData = $workShift->toArray();
        $this->isEditing = true;
        $this->modal('mdl-shift')->show();
    }

    public function saveShift()
    {
        $validatedData = $this->validate([
            'shiftData.shift_title' => 'required|string|max:255',
            'shiftData.shift_desc' => 'nullable|string',
            'shiftData.start_date' => 'required|date',
            'shiftData.end_date' => 'required|date|after_or_equal:shiftData.start_date',
            'shiftData.is_inactive' => 'boolean',
        ]);

        if ($this->isEditing) {
            $workShift = WorkShift::findOrFail($this->shiftData['id']);
            $workShift->update($validatedData['shiftData']);
            session()->flash('message', 'Shift updated successfully.');
        } else {
            $validatedData['shiftData']['firm_id'] = session('firm_id');
            WorkShift::create($validatedData['shiftData']);
            session()->flash('message', 'Shift added successfully.');
        }

        $this->resetForm();
        $this->modal('mdl-shift')->close();
        Flux::toast(
            heading: 'Changes saved.',
            text: 'Work shift has been updated.',
        );
    }
    public function deleteWorkShift($id)
    {
        try {
            $policy = WorkShift::findOrFail($id);
            $policy->delete();

            Flux::toast(
                heading: 'Success',
                text: 'Work Shift deleted successfully.',
            );
        } catch (\Exception $e) {
            Flux::toast(
                heading: 'Error',
                text: 'Failed to delete Work Shift.',
                variant: 'error'
            );
        }
    }
    public function toggleStatus($shiftId)
    {
        $workShift = WorkShift::findOrFail($shiftId);
        $workShift->update([
            'is_inactive' => !$workShift->is_inactive
        ]);
        
        Flux::toast(
            heading: 'Status Updated',
            text: 'Shift status has been changed.',
        );
    }

    public function resetForm()
    {
        $this->shiftData = [
            'id' => null,
            'firm_id' => null,
            'shift_title' => '',
            'shift_desc' => '',
            'start_date' => null,
            'end_date' => null,
            'is_inactive' => false,
        ];
        $this->isEditing = false;
    }

    public function showmodal_breaks($shiftId)
    {
        $this->selectedShiftId = $shiftId;
        $this->modal('add-breaks')->show();
    }

    public function showmodal_days($shiftId)
    {
        $this->selectedShiftId = $shiftId;
        $this->modal('add-days')->show();
    }

    public function showmodal_days_breaks($shiftId)
    {
        $this->selectedShiftId = $shiftId;
        $this->modal('add-days-breaks')->show();
    }

    public function showmodal_shift_algo($shiftId)
    {
        $this->selectedShiftId = $shiftId;
        $this->modal('add-shift-algo')->show();
    }

    public function render()
    {
        return view('livewire.hrms.attendance.work-shifts');
    }
} 