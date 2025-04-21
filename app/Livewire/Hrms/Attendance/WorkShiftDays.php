<?php

namespace App\Livewire\Hrms\Attendance;

use Livewire\Component;
use App\Models\Hrms\WorkShiftDay;
use App\Models\Hrms\WorkShift;
use Flux;

class WorkShiftDays extends Component
{
    use \Livewire\WithPagination;

    public $shiftDayData = [
        'id' => null,
        'firm_id' => null,
        'work_shift_id' => null,
        'work_date' => null,
        'day_status' => '',
        'start_time' => null,
        'end_time' => null,
    ];

    public $sortBy = 'work_date';
    public $sortDirection = 'desc';
    public $isEditing = false;

    public function mount()
    {
        session()->put('firm_id', 3); // Example firm_id
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
    public function shiftDaysList()
    {
        return WorkShiftDay::query()
            ->with('work_shift')
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->where('firm_id', session('firm_id'))
            ->paginate(5);
    }

    #[\Livewire\Attributes\Computed]
    public function workShiftsList()
    {
        return WorkShift::where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->pluck('shift_title', 'id');
    }

    public function fetchShiftDay($id)
    {
        $workShiftDay = WorkShiftDay::findOrFail($id);
        $this->shiftDayData = $workShiftDay->toArray();
        $this->isEditing = true;
        $this->modal('mdl-shift-day')->show();
    }

    public function saveShiftDay()
    {
        $validatedData = $this->validate([
            'shiftDayData.work_shift_id' => 'required|exists:work_shifts,id',
            'shiftDayData.work_date' => 'required|date',
            'shiftDayData.day_status' => 'nullable|string',
            'shiftDayData.start_time' => 'required|date_format:H:i',
            'shiftDayData.end_time' => 'required|date_format:H:i|after:shiftDayData.start_time',
        ]);

        if ($this->isEditing) {
            $workShiftDay = WorkShiftDay::findOrFail($this->shiftDayData['id']);
            $workShiftDay->update($validatedData['shiftDayData']);
            session()->flash('message', 'Shift day updated successfully.');
        } else {
            $validatedData['shiftDayData']['firm_id'] = session('firm_id');
            WorkShiftDay::create($validatedData['shiftDayData']);
            session()->flash('message', 'Shift day added successfully.');
        }

        $this->resetForm();
        $this->modal('mdl-shift-day')->close();
        Flux::toast(
            heading: 'Changes saved.',
            text: 'Work shift day has been updated.',
        );
    }
    public function deleteWorkShiftDay($id)
    {
        try {
            $policy = WorkShiftDay::findOrFail($id);
            $policy->delete();

            Flux::toast(
                heading: 'Success',
                text: 'Work Shift Day deleted successfully.',
            );
        } catch (\Exception $e) {
            Flux::toast(
                heading: 'Error',
                text: 'Failed to delete Work Shift Day.',
                variant: 'error'
            );
        }
    }
    public function resetForm()
    {
        $this->shiftDayData = [
            'id' => null,
            'firm_id' => null,
            'work_shift_id' => null,
            'work_date' => null,
            'day_status' => '',
            'start_time' => null,
            'end_time' => null,
        ];
        $this->isEditing = false;
    }

    public function render()
    {
        return view('livewire.hrms.attendance.work-shift-days');
    }
} 