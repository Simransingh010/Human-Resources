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
        'day_status_code' => '',
        'day_status_label' => '',
        'day_status_desc' => '',
        'paid_percent' => 100,
        'count_as_working_day' => true,
        'is_inactive' => false,
        'day_status_main' => '',
    ];

    public $isEditing = false;
    public $modal = false;
    public $perPage = 10;

    // Field configuration for form and table
    public array $fieldConfig = [
        'day_status_code' => ['label' => 'Day Status Code', 'type' => 'text'],
        'day_status_label' => ['label' => 'Day Status Label', 'type' => 'text'],
        'day_status_desc' => ['label' => 'Day Status Description', 'type' => 'textarea'],
        'paid_percent' => ['label' => 'Paid Percent', 'type' => 'number'],
        'count_as_working_day' => ['label' => 'Count as Working Day', 'type' => 'boolean'],
        'is_inactive' => ['label' => 'Status', 'type' => 'boolean'],
        'day_status_main' => ['label' => 'Work Status', 'type' => 'select', 'listKey' => 'work_statuses'],
    ];

    public array $filterFields = [
        'search_label' => ['label' => 'Label', 'type' => 'text'],
        'search_code' => ['label' => 'Code', 'type' => 'text'],
        'is_inactive' => ['label' => 'Status', 'type' => 'boolean'],
    ];

    // Add filter properties
    public $filters = [
        'search_label' => '',
        'search_code' => '',
        'is_inactive' => '',
    ];

    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public function mount()
    {
        $this->resetPage();
        $this->refreshStatuses();

        // Add work statuses to lists
        $this->listsForFields['work_statuses'] = WorkShiftDayStatus::WORK_STATUS_SELECT;

        // Set default visible fields and filters
        $this->visibleFields = ['day_status_code', 'day_status_label', 'day_status_desc', 'paid_percent', 'count_as_working_day', 'day_status_main'];
        $this->visibleFilterFields = ['search_label', 'search_code'];
    }

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        return WorkShiftDayStatus::query()
            ->where('firm_id', session('firm_id'))
            ->when($this->filters['search_label'], function ($query) {
                $query->where('day_status_label', 'like', '%' . $this->filters['search_label'] . '%');
            })
            ->when($this->filters['search_code'], function ($query) {
                $query->where('day_status_code', 'like', '%' . $this->filters['search_code'] . '%');
            })
            ->when($this->filters['is_inactive'] !== '', function ($query) {
                $query->where('is_inactive', $this->filters['is_inactive']);
            })
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate($this->perPage);
    }

    public function store()
    {
        try {
            $validatedData = $this->validate([
                'formData.day_status_code' => 'required|string|max:50',
                'formData.day_status_label' => 'required|string|max:255',
                'formData.day_status_desc' => 'nullable|string',
                'formData.paid_percent' => 'required|numeric|min:0|max:100',
                'formData.count_as_working_day' => 'boolean',
                'formData.is_inactive' => 'boolean',
                'formData.day_status_main' => 'required|numeric|in:' . implode(',', array_keys(WorkShiftDayStatus::WORK_STATUS_SELECT)),
            ]);

            $data = collect($validatedData['formData'])
                ->map(fn($val) => $val === '' ? null : $val)
                ->toArray();
            $data['firm_id'] = session('firm_id');
            if ($this->isEditing) {
                $status = WorkShiftDayStatus::findOrFail($this->formData['id']);
                $status->update($data);
                $toastMsg = 'Day Status updated successfully';
            } else {
                WorkShiftDayStatus::create($data);
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
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to save day status: ' . $e->getMessage(),
            );
        }
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
            ->mapWithKeys(fn($val, $key) => [$key => (bool) $val])
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

    public function toggleColumn(string $field)
    {
        if (in_array($field, $this->visibleFields)) {
            $this->visibleFields = array_filter(
                $this->visibleFields,
                fn($f) => $f !== $field
            );
        } else {
            $this->visibleFields[] = $field;
        }
    }

    public function toggleFilterColumn(string $field)
    {
        if (in_array($field, $this->visibleFilterFields)) {
            $this->visibleFilterFields = array_filter(
                $this->visibleFilterFields,
                fn($f) => $f !== $field
            );
        } else {
            $this->visibleFilterFields[] = $field;
        }
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/WorkShifts/blades/work-shift-day-statuses.blade.php'));
    }
}