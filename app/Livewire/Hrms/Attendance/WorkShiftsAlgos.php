<?php

namespace App\Livewire\Hrms\Attendance;

use Livewire\Component;
use App\Models\Hrms\WorkShiftsAlgo;
use App\Models\Hrms\WorkShift;
use Flux;

class WorkShiftsAlgos extends Component
{
    use \Livewire\WithPagination;

    public $algoData = [
        'id' => null,
        'firm_id' => null,
        'work_shift_id' => null,
        'week_off_pattern' => '',
        'holiday_calendar_id' => null,
        'allow_wfh' => false,
        'half_day_rule' => '',
        'overtime_rule' => '',
        'rules_config' => '',
        'is_active' => true,
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
    public function algosList()
    {
        return WorkShiftsAlgo::query()
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

    public function fetchAlgo($id)
    {
        $shiftAlgo = WorkShiftsAlgo::findOrFail($id);
        $this->algoData = $shiftAlgo->toArray();
        $this->isEditing = true;
        $this->modal('mdl-shift-algo')->show();
    }

    public function saveAlgo()
    {
        $validatedData = $this->validate([
            'algoData.work_shift_id' => 'required|exists:work_shifts,id',
            'algoData.week_off_pattern' => 'nullable|string',
            'algoData.holiday_calendar_id' => 'nullable|integer',
            'algoData.allow_wfh' => 'boolean',
            'algoData.half_day_rule' => 'nullable|string',
            'algoData.overtime_rule' => 'nullable|string',
            'algoData.rules_config' => 'nullable|string',
            'algoData.is_active' => 'boolean',
        ]);

        if ($this->isEditing) {
            $shiftAlgo = WorkShiftsAlgo::findOrFail($this->algoData['id']);
            $shiftAlgo->update($validatedData['algoData']);
            session()->flash('message', 'Shift algorithm updated successfully.');
        } else {
            $validatedData['algoData']['firm_id'] = session('firm_id');
            WorkShiftsAlgo::create($validatedData['algoData']);
            session()->flash('message', 'Shift algorithm added successfully.');
        }

        $this->resetForm();
        $this->modal('mdl-shift-algo')->close();
        Flux::toast(
            variant: 'success',
            position: 'top-right',
            heading: 'Changes saved.',
            text: 'Work shift algorithm has been updated.',
        );
    }

    public function toggleStatus($algoId)
    {
        $shiftAlgo = WorkShiftsAlgo::findOrFail($algoId);
        $shiftAlgo->update([
            'is_active' => !$shiftAlgo->is_active
        ]);
        
        Flux::toast(
            position: 'top-right',
            variant: 'primary',
            heading: 'Status Updated',
            text: 'Algorithm status has been changed.',
        );
    }
    public function deleteBreak($id)
    {
        try {
            $policy = WorkShiftsAlgo::findOrFail($id);
            $policy->delete();

            Flux::toast(
                position: 'top-right',
                variant: 'danger',
                heading: 'Success',
                text: 'Algorithm status deleted successfully.',
            );
        } catch (\Exception $e) {
            Flux::toast(
                position: 'top-right',
                heading: 'Error',
                text: 'Failed to delete Algorithm status.',
                variant: 'error'
            );
        }
    }
    public function resetForm()
    {
        $this->algoData = [
            'id' => null,
            'firm_id' => null,
            'work_shift_id' => null,
            'week_off_pattern' => '',
            'holiday_calendar_id' => null,
            'allow_wfh' => false,
            'half_day_rule' => '',
            'overtime_rule' => '',
            'rules_config' => '',
            'is_active' => true,
        ];
        $this->isEditing = false;
    }

    public function render()
    {
        return view('livewire.hrms.attendance.work-shifts-algos');
    }
} 