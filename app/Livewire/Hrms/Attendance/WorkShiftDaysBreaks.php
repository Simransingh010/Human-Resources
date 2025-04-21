<?php

namespace App\Livewire\Hrms\Attendance;

use Livewire\Component;
use App\Models\Hrms\WorkShiftDaysBreak;
use App\Models\Hrms\WorkShiftDay;
use App\Models\Hrms\WorkBreak;
use Flux;

class WorkShiftDaysBreaks extends Component
{
    use \Livewire\WithPagination;

    public $breakData = [
        'id' => null,
        'firm_id' => null,
        'work_shift_day_id' => null,
        'work_break_id' => null,
    ];

    public $sortBy = 'created_at';
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
    public function breaksList()
    {
        return WorkShiftDaysBreak::query()
            ->with(['work_shift_day', 'work_break'])
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->where('firm_id', session('firm_id'))
            ->paginate(5);
    }

    #[\Livewire\Attributes\Computed]
    public function workShiftDaysList()
    {
        return WorkShiftDay::where('firm_id', session('firm_id'))
            ->with('work_shift')
            ->get()
            ->map(function($day) {
                return [
                    'id' => $day->id,
                    'title' => $day->work_shift->shift_title . ' - ' . 
                              \Carbon\Carbon::parse($day->work_date)->format('Y-m-d')
                ];
            })
            ->pluck('title', 'id');
    }

    #[\Livewire\Attributes\Computed]
    public function workBreaksList()
    {
        return WorkBreak::where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->pluck('break_title', 'id');
    }

    public function fetchBreak($id)
    {
        $shiftDayBreak = WorkShiftDaysBreak::findOrFail($id);
        $this->breakData = $shiftDayBreak->toArray();
        $this->isEditing = true;
        $this->modal('mdl-shift-day-break')->show();
    }

    public function saveBreak()
    {
        $validatedData = $this->validate([
            'breakData.work_shift_day_id' => 'required|exists:work_shift_days,id',
            'breakData.work_break_id' => 'required|exists:work_breaks,id',
        ]);

        if ($this->isEditing) {
            $shiftDayBreak = WorkShiftDaysBreak::findOrFail($this->breakData['id']);
            $shiftDayBreak->update($validatedData['breakData']);
            session()->flash('message', 'Shift day break updated successfully.');
        } else {
            $validatedData['breakData']['firm_id'] = session('firm_id');
            WorkShiftDaysBreak::create($validatedData['breakData']);
            session()->flash('message', 'Shift day break added successfully.');
        }

        $this->resetForm();
        $this->modal('mdl-shift-day-break')->close();
        Flux::toast(
            heading: 'Changes saved.',
            text: 'Work shift day break has been updated.',
        );
    }
    public function deleteBreak($id)
    {
        try {
            $policy = WorkShiftDaysBreak::findOrFail($id);
            $policy->delete();

            Flux::toast(
                heading: 'Success',
                text: 'Policy deleted successfully.',
            );
        } catch (\Exception $e) {
            Flux::toast(
                heading: 'Error',
                text: 'Failed to delete policy.',
                variant: 'error'
            );
        }
    }
    public function resetForm()
    {
        $this->breakData = [
            'id' => null,
            'firm_id' => null,
            'work_shift_day_id' => null,
            'work_break_id' => null,
        ];
        $this->isEditing = false;
    }

    public function render()
    {
        return view('livewire.hrms.attendance.work-shift-days-breaks');
    }
} 