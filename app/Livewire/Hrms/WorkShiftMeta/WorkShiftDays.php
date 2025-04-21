<?php

namespace App\Livewire\Hrms\WorkShiftMeta;

use Livewire\Component;
use App\Models\Hrms\WorkShiftDay;
use App\Models\Hrms\WorkShift;
use App\Models\Hrms\WorkShiftDayStatus;
use Flux;

class WorkShiftDays extends Component
{
    use \Livewire\WithPagination;

    public WorkShift $workShift;
    public array $listsForFields = [];

    public $dayData = [
        'id' => null,
        'firm_id' => null,
        'work_shift_id' => '',
        'work_date' => '',
        'work_shift_day_status_id' => '',
        'start_time' => '',
        'end_time' => '',
    ];

    public $sortBy = 'work_date';
    public $sortDirection = 'desc';
    public $isEditing = false;

    public function mount($workShiftId)
    {
        $this->workShift = WorkShift::findOrFail($workShiftId);
        $this->dayData['work_shift_id'] = $workShiftId;
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
    public function daysList()
    {
        return WorkShiftDay::query()
            ->with(['day_status'])
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->where('work_shift_id', $this->workShift->id)
            ->where('firm_id', session('firm_id'))
            ->get();
    }

    #[\Livewire\Attributes\Computed]
    public function workshiftdayStatusList()
    {
        return WorkShiftDayStatus::query()
            ->where('firm_id', session('firm_id'))
            ->get();
    }

    public function fetchDay($id)
    {
        $day = WorkShiftDay::findOrFail($id);
        $this->dayData = $day->toArray();
        $this->isEditing = true;
        $this->modal('mdl-day')->show();
    }

    public function saveDay()
    {
        $validatedData = $this->validate([
            'dayData.work_date' => 'required|date',
            'dayData.start_time' => 'required',
            'dayData.work_shift_day_status_id' => 'required|exists:work_shift_day_statuses,id',
            'dayData.end_time' => 'required|after:dayData.start_time',
        ]);

        if ($this->isEditing) {
            $day = WorkShiftDay::findOrFail($this->dayData['id']);
            $day->update($validatedData['dayData']);
            $toast = 'Work shift day updated successfully.';
        } else {
            $validatedData['dayData']['firm_id'] = session('firm_id');
            $validatedData['dayData']['work_shift_id'] = $this->workShift->id;
            WorkShiftDay::create($validatedData['dayData']);
            $toast = 'Work shift day added successfully.';
        }

        $this->resetForm();
        $this->modal('mdl-day')->close();
        Flux::toast(
            heading: 'Changes saved.',
            text: $toast,
        );
    }

    public function deleteDay($dayId)
    {
        try {
            $day = WorkShiftDay::findOrFail($dayId);
            $day->delete();

            Flux::toast(
                heading: 'Success',
                text: 'Work shift day deleted successfully.',
            );
        } catch (\Exception $e) {
            Flux::toast(
                heading: 'Error',
                text: 'Failed to delete work shift day.',
                variant: 'error'
            );
        }
    }

    public function resetForm()
    {
        $this->dayData = [
            'id' => null,
            'firm_id' => null,
            'work_shift_id' => $this->workShift->id,
            'work_date' => '',
            'day_status' => '',
            'start_time' => '',
            'end_time' => '',
        ];
        $this->isEditing = false;
    }

    public function render()
    {
        return view('livewire.hrms.work-shift-meta.work-shift-days');
    }
} 