<?php

namespace App\Livewire\Hrms\Workshifts;

use App\Models\Hrms\WorkShiftDayStatus;
use App\Models\Hrms\WorkShift;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Flux;

class WorkShiftDayStatuses extends Component
{
    use WithPagination;
    
    public $selectedStatusId = null;
    public array $listsForFields = [];
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $statuses;
    public $formData = [
        'id' => null,
        'work_shift_id' => '',
        'day_status_code' => '',
        'day_status_label' => '',
        'day_status_desc' => '',
        'paid_percent' => 100,
        'count_as_working_day' => true,
        'is_inactive' => false,
    ];

    public $isEditing = false;
    public $modal = false;

    // Add filter properties
    public $filters = [
        'search_label' => '',
        'search_code' => '',
        'search_shift' => '',
    ];

    public function mount()
    {
        $this->resetPage();
        $this->refreshStatuses();
        $this->getWorkShiftsForSelect();
    }

    private function getWorkShiftsForSelect()
    {
        $this->listsForFields['work_shifts'] = WorkShift::where('firm_id', session('firm_id'))
            ->where('is_inactive', 0)
            ->pluck('shift_title', 'id')
            ->toArray();
    }

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        return WorkShiftDayStatus::query()
            ->where('firm_id', session('firm_id'))
            ->when($this->filters['search_label'], function($query) {
                $query->where('day_status_label', 'like', '%' . $this->filters['search_label'] . '%');
            })
            ->when($this->filters['search_code'], function($query) {
                $query->where('day_status_code', 'like', '%' . $this->filters['search_code'] . '%');
            })
            ->when($this->filters['search_shift'], function($query) {
                $query->where('work_shift_id', $this->filters['search_shift']);
            })
            ->with('work_shift')
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate(5);
    }

    public function store()
    {
        $validatedData = $this->validate([
            'formData.work_shift_id' => 'required|exists:work_shifts,id',
            'formData.day_status_code' => 'required|string|max:50',
            'formData.day_status_label' => 'required|string|max:255',
            'formData.day_status_desc' => 'nullable|string',
            'formData.paid_percent' => 'required|numeric|min:0|max:100',
            'formData.count_as_working_day' => 'boolean',
            'formData.is_inactive' => 'boolean',
        ]);

        // Convert empty strings to null
        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        // Add firm_id from session
        $validatedData['formData']['firm_id'] = session('firm_id');

        if ($this->isEditing) {
            $status = WorkShiftDayStatus::findOrFail($this->formData['id']);
            $status->update($validatedData['formData']);
            $toastMsg = 'Day Status updated successfully';
        } else {
            WorkShiftDayStatus::create($validatedData['formData']);
            $toastMsg = 'Day Status added successfully';
        }

        $this->resetForm();
        $this->refreshStatuses();
        $this->modal('mdl-status')->close();
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: $toastMsg,
        );
    }

    public function clearFilters()
    {
        $this->reset('filters');
        $this->resetPage();
    }

    public function edit($id)
    {
        $this->formData = WorkShiftDayStatus::findOrFail($id)->toArray();
        $this->isEditing = true;
        $this->modal('mdl-status')->show();
    }

    public function delete($id)
    {
        $status = WorkShiftDayStatus::findOrFail($id);
        
        // Check if status has related records
        if ($status->work_shift_days()->count() > 0) {
            Flux::toast(
                variant: 'error',
                heading: 'Cannot Delete',
                text: 'This status is assigned to work shift days and cannot be deleted.',
            );
            return;
        }

        $status->delete();
        
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Day Status has been deleted successfully',
        );
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['paid_percent'] = 100;
        $this->formData['count_as_working_day'] = true;
        $this->formData['is_inactive'] = false;
        $this->isEditing = false;
    }

    public function refreshStatuses()
    {
        $this->statuses = WorkShiftDayStatus::where('firm_id', session('firm_id'))
            ->pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool)$val])
            ->toArray();
    }

    public function toggleStatus($statusId)
    {
        $status = WorkShiftDayStatus::find($statusId);
        $status->is_inactive = !$status->is_inactive;
        $status->save();

        $this->statuses[$statusId] = $status->is_inactive;
        $this->refreshStatuses();
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/WorkShifts/blades/work-shift-day-statuses.blade.php'));
    }
} 