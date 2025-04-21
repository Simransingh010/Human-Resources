<?php

namespace App\Livewire\Hrms\WorkShiftMeta;

use Livewire\Component;
use App\Models\Hrms\WorkShiftDaysBreak;
use App\Models\Hrms\WorkBreak;
use App\Models\Hrms\WorkShiftDay;
use Flux;

class WorkShiftDaysBreaks extends Component
{
    use \Livewire\WithPagination;

    public $breakData = [
        'id' => null,
        'firm_id' => null,
        'work_shift_day_id' => '',
        'work_break_id' => '',
    ];

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;

    public function mount()
    {
        session()->put('firm_id', session('firm_id'));
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
            ->with(['work_break', 'work_shift_day'])
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->where('firm_id', session('firm_id'))
            ->get();
    }

    #[\Livewire\Attributes\Computed]
    public function workBreaks()
    {
        return WorkBreak::where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->get();
    }

    #[\Livewire\Attributes\Computed]
    public function workShiftDays()
    {
        return WorkShiftDay::where('firm_id', session('firm_id'))
            ->get();
    }

    public function fetchBreak($id)
    {
        $break = WorkShiftDaysBreak::findOrFail($id);
        $this->breakData = $break->toArray();
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
            $break = WorkShiftDaysBreak::findOrFail($this->breakData['id']);
            $break->update($validatedData['breakData']);
            $toast = 'Work shift day break updated successfully.';
        } else {
            $validatedData['breakData']['firm_id'] = session('firm_id');
            WorkShiftDaysBreak::create($validatedData['breakData']);
            $toast = 'Work shift day break added successfully.';
        }

        $this->resetForm();
        $this->modal('mdl-shift-day-break')->close();
        Flux::toast(
            heading: 'Changes saved.',
            text: $toast,
        );
    }

    public function deleteBreak($breakId)
    {
        try {
            $break = WorkShiftDaysBreak::findOrFail($breakId);
            $break->delete();

            Flux::toast(
                heading: 'Success',
                text: 'Work shift day break deleted successfully.',
            );
        } catch (\Exception $e) {
            Flux::toast(
                heading: 'Error',
                text: 'Failed to delete work shift day break.',
                variant: 'error'
            );
        }
    }

    public function resetForm()
    {
        $this->breakData = [
            'id' => null,
            'firm_id' => null,
            'work_shift_day_id' => '',
            'work_break_id' => '',
        ];
        $this->isEditing = false;
    }

    public function render()
    {
        return view('livewire.hrms.work-shift-meta.work-shift-days-breaks');
    }
} 