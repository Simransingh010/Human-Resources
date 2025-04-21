<?php

namespace App\Livewire\Hrms\WorkShiftMeta;

use Livewire\Component;
use App\Models\Hrms\WorkShiftDayStatus;
use App\Models\Hrms\WorkShift;
use Flux;

class WorkShiftDayStatuses extends Component
{
    use \Livewire\WithPagination;

    public WorkShift $workShift;
    public $statusData = [
        'id' => null,
        'firm_id' => null,
        'work_shift_id' => '',
        'day_status_code' => '',
        'day_status_label' => '',
        'day_status_desc' => '',
        'paid_percent' => 100.0,
        'count_as_working_day' => true,
        'is_inactive' => false,
    ];

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;

    public function mount($workShiftId)
    {
        $this->workShift = WorkShift::findOrFail($workShiftId);
        $this->statusData['work_shift_id'] = $workShiftId;
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
    public function statusList()
    {
        return WorkShiftDayStatus::query()
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

    public function fetchStatus($id)
    {
        $status = WorkShiftDayStatus::findOrFail($id);
        $this->statusData = $status->toArray();
        $this->isEditing = true;
        $this->modal('mdl-day-status')->show();
    }

    public function saveStatus()
    {
        $validatedData = $this->validate([
            'statusData.day_status_code' => 'required|string|max:50',
            'statusData.day_status_label' => 'required|string|max:100',
            'statusData.day_status_desc' => 'nullable|string',
            'statusData.paid_percent' => 'required|numeric|min:0|max:100',
            'statusData.count_as_working_day' => 'boolean',
            'statusData.is_inactive' => 'boolean',
        ]);

        if ($this->isEditing) {
            $status = WorkShiftDayStatus::findOrFail($this->statusData['id']);
            $status->update($validatedData['statusData']);
            $toast = 'Work shift day status updated successfully.';
        } else {
            $validatedData['statusData']['work_shift_id'] = $this->workShift->id;
            $validatedData['statusData']['firm_id'] = session('firm_id');
            WorkShiftDayStatus::create($validatedData['statusData']);
            $toast = 'Work shift day status added successfully.';
        }

        $this->resetForm();
        $this->modal('mdl-day-status')->close();
        Flux::toast(
            heading: 'Changes saved.',
            text: $toast,
        );
    }

    public function toggleStatus($statusId)
    {
        $status = WorkShiftDayStatus::findOrFail($statusId);
        $status->update([
            'is_inactive' => !$status->is_inactive
        ]);

        Flux::toast(
            heading: 'Status Updated',
            text: 'Day status status has been changed.',
        );
    }

    public function deleteStatus($statusId)
    {
        try {
            $status = WorkShiftDayStatus::findOrFail($statusId);
            $status->delete();

            Flux::toast(
                heading: 'Success',
                text: 'Work shift day status deleted successfully.',
            );
        } catch (\Exception $e) {
            Flux::toast(
                heading: 'Error',
                text: 'Failed to delete work shift day status.',
                variant: 'error'
            );
        }
    }

    public function resetForm()
    {
        $this->statusData = [
            'id' => null,
            'firm_id' => null,
            'work_shift_id' => $this->workShift->id,
            'day_status_code' => '',
            'day_status_label' => '',
            'day_status_desc' => '',
            'paid_percent' => 100.0,
            'count_as_working_day' => true,
            'is_inactive' => false,
        ];
        $this->isEditing = false;
    }

    public function render()
    {
        return view('livewire.hrms.work-shift-meta.work-shift-day-statuses');
    }
} 