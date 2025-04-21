<?php

namespace App\Livewire\Hrms\WorkShiftMeta;

use Livewire\Component;
use App\Models\Hrms\WorkShiftsAlgo;
use App\Models\Hrms\WorkShift;
use App\Models\Hrms\HolidayCalendar;
use Flux;

class WorkShiftsAlgos extends Component
{
    use \Livewire\WithPagination;
    public WorkShift $workShift;
    public $algoData = [
        'id' => null,
        'firm_id' => null,
        'work_shift_id' => '',
        'start_date' => '',
        'end_date' => '',
        'start_time' => '',
        'end_time' => '',
        'week_off_pattern' => '',
        'work_breaks' => '',
        'holiday_calendar_id' => null,
        'allow_wfh' => false,
        'half_day_rule' => '',
        'overtime_rule' => '',
        'rules_config' => '',
        'late_panelty' => '',
        'comp_off' => false,
        'is_inactive' => false,
    ];

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;

    public function mount($workShiftId)
    {
        $this->workShift = WorkShift::findOrFail($workShiftId);
        $this->algoData['work_shift_id'] = $workShiftId;
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
    public function algosList()
    {
        return WorkShiftsAlgo::query()
            ->with(['work_shift'])
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->where('work_shift_id', $this->workShift->id)
            ->where('firm_id', session('firm_id'))
            ->get();
    }

    #[\Livewire\Attributes\Computed]
    public function workShifts()
    {
        return WorkShift::where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->get();
    }

    #[\Livewire\Attributes\Computed]
    public function holidayCalendars()
    {
        return HolidayCalendar::where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->get();
    }

    public function fetchAlgo($id)
    {
        $algo = WorkShiftsAlgo::findOrFail($id);
        $this->algoData = $algo->toArray();
        $this->isEditing = true;
        $this->modal('mdl-shift-algo')->show();
    }

    public function saveAlgo()
    {
        $validatedData = $this->validate([
            'algoData.start_date' => 'required',
            'algoData.end_date' => 'nullable',
            'algoData.start_time' => 'required',
            'algoData.end_time' => 'required',
            'algoData.week_off_pattern' => 'nullable|string',
            'algoData.work_breaks' => 'nullable|string',
            'algoData.holiday_calendar_id' => 'nullable|exists:holiday_calendars,id',
            'algoData.allow_wfh' => 'boolean',
            'algoData.half_day_rule' => 'nullable|string',
            'algoData.overtime_rule' => 'nullable|string',
            'algoData.rules_config' => 'nullable|string',
            'algoData.late_panelty' => 'nullable|string',
            'algoData.comp_off' => 'boolean',
            'algoData.is_inactive' => 'boolean',
        ]);

        if ($this->isEditing) {
            $algo = WorkShiftsAlgo::findOrFail($this->algoData['id']);
            $algo->update($validatedData['algoData']);
            $toast = 'Work shift algorithm updated successfully.';
        } else {
            $validatedData['algoData']['work_shift_id'] = $this->workShift->id;
            $validatedData['algoData']['firm_id'] = session('firm_id');
            WorkShiftsAlgo::create($validatedData['algoData']);
            $toast = 'Work shift algorithm added successfully.';
        }

        $this->resetForm();
        $this->modal('mdl-shift-algo')->close();
        Flux::toast(
            heading: 'Changes saved.',
            text: $toast,
        );
    }

    public function toggleStatus($algoId)
    {
        $algo = WorkShiftsAlgo::findOrFail($algoId);
        $algo->update([
            'is_inactive' => !$algo->is_inactive
        ]);

        Flux::toast(
            heading: 'Status Updated',
            text: 'Algorithm status has been changed.',
        );
    }

    public function deleteAlgo($algoId)
    {
        try {
            $algo = WorkShiftsAlgo::findOrFail($algoId);
            $algo->delete();

            Flux::toast(
                heading: 'Success',
                text: 'Work shift algorithm deleted successfully.',
            );
        } catch (\Exception $e) {
            Flux::toast(
                heading: 'Error',
                text: 'Failed to delete work shift algorithm.',
                variant: 'error'
            );
        }
    }

    public function resetForm()
    {
        $this->algoData = [
            'id' => null,
            'firm_id' => null,
            'work_shift_id' => $this->workShift->id,
            'start_date' => '',
            'end_date' => '',
            'start_time' => '',
            'end_time' => '',
            'week_off_pattern' => '',
            'work_breaks' => '',
            'holiday_calendar_id' => null,
            'allow_wfh' => false,
            'half_day_rule' => '',
            'overtime_rule' => '',
            'rules_config' => '',
            'late_panelty' => '',
            'comp_off' => false,
            'is_inactive' => false,
        ];
        $this->isEditing = false;
    }

    public function render()
    {
        return view('livewire.hrms.work-shift-meta.work-shifts-algos');
    }
} 