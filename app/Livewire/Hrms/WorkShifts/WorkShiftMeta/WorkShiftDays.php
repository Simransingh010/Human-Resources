<?php

namespace App\Livewire\Hrms\Workshifts\WorkShiftMeta;

use App\Models\Hrms\WorkShiftDay;
use App\Models\Hrms\WorkShift;
use App\Models\Hrms\WorkShiftDayStatus;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Flux;

class WorkShiftDays extends Component
{
    use WithPagination;

    public $selectedDayId = null;
    public $sortBy = 'work_date';
    public $sortDirection = 'desc';
    public $formData = [
        'id' => null,
        'work_shift_id' => '',
        'work_date' => '',
        'work_shift_day_status_id' => '',
        'start_time' => '',
        'end_time' => '',
        'day_status_main' => '',
        'paid_percent' => 100,
    ];

    public $isEditing = false;
    public $modal = false;
    public $perPage = 10;

    // Field configuration for form and table
    public array $fieldConfig = [
        'work_shift_id' => ['label' => 'Work Shift', 'type' => 'select', 'listKey' => 'work_shifts'],
        'work_date' => ['label' => 'Date', 'type' => 'date'],
        'work_shift_day_status_id' => ['label' => 'Day Status', 'type' => 'select', 'listKey' => 'day_statuses'],
        'start_time' => ['label' => 'Start Time', 'type' => 'time'],
        'end_time' => ['label' => 'End Time', 'type' => 'time'],
        'day_status_main' => ['label' => 'Main Status', 'type' => 'select', 'listKey' => 'work_statuses'],
        'paid_percent' => ['label' => 'Paid Percent', 'type' => 'number'],
    ];

    public array $filterFields = [
        'search_shift' => ['label' => 'Work Shift', 'type' => 'select', 'listKey' => 'work_shifts'],
        'search_date' => ['label' => 'Date', 'type' => 'date'],
        'search_status' => ['label' => 'Day Status', 'type' => 'select', 'listKey' => 'day_statuses'],
    ];

    // Add filter properties
    public $filters = [
        'search_shift' => '',
        'search_date' => '',
        'search_status' => '',
    ];

    public array $visibleFields = [];
    public array $visibleFilterFields = [];
    public array $listsForFields = [];

    public function mount()
    {
        $this->resetPage();
        $this->getWorkShiftsForSelect();
        $this->getDayStatusesForSelect();

        // Add work statuses to lists
        $this->listsForFields['work_statuses'] = WorkShiftDay::WORK_STATUS_SELECT;

        // Set default visible fields and filters
        $this->visibleFields = ['work_shift_id', 'work_date', 'start_time', 'end_time', 'work_shift_day_status_id', 'day_status_main', 'paid_percent'];
        $this->visibleFilterFields = ['search_shift', 'search_date', 'search_status'];
    }

    private function getWorkShiftsForSelect()
    {
        $this->listsForFields['work_shifts'] = WorkShift::where('firm_id', session('firm_id'))
            ->where('is_inactive', 0)
            ->pluck('shift_title', 'id')
            ->toArray();
    }

    private function getDayStatusesForSelect()
    {
        $this->listsForFields['day_statuses'] = WorkShiftDayStatus::where('firm_id', session('firm_id'))
            ->where('is_inactive', 0)
            ->pluck('day_status_label', 'id')
            ->toArray();
    }

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        return WorkShiftDay::query()
            ->where('firm_id', session('firm_id'))
            ->when($this->filters['search_shift'], function ($query) {
                $query->whereHas('work_shift', function ($q) {
                    $q->where('shift_title', 'like', '%' . $this->filters['search_shift'] . '%');
                });
            })
            ->when($this->filters['search_date'], function ($query) {
                $query->whereDate('work_date', $this->filters['search_date']);
            })
            ->when($this->filters['search_status'], function ($query) {
                $query->where('work_shift_day_status_id', $this->filters['search_status']);
            })
            ->with(['work_shift', 'day_status'])
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate(50);
    }

    public function store()
    {
        $validatedData = $this->validate([
            'formData.work_shift_id' => 'required|exists:work_shifts,id',
            'formData.work_date' => 'required|date',
            'formData.work_shift_day_status_id' => 'nullable|exists:work_shift_day_statuses,id',
            'formData.start_time' => 'required|date_format:H:i',
            'formData.end_time' => 'required|date_format:H:i|after:formData.start_time',
            'formData.day_status_main' => 'required|numeric|in:' . implode(',', array_keys(WorkShiftDay::WORK_STATUS_SELECT)),
            'formData.paid_percent' => 'required|numeric|min:0|max:100',
        ]);

        // Convert empty strings to null
        $data = collect($validatedData['formData'])
            ->map(function ($val) use ($validatedData) {
                if ($val === '') {
                    return null;
                }
                // Convert time strings to datetime for storage
                if (in_array($this->getKeyFromValue($validatedData['formData'], $val), ['start_time', 'end_time']) && $val) {
                    return Carbon::createFromFormat('H:i', $val);
                }
                return $val;
            })
            ->toArray();

        // Add firm_id from session
        $data['firm_id'] = session('firm_id');

        if ($this->isEditing) {
            $day = WorkShiftDay::findOrFail($this->formData['id']);
            $day->update($data);
            $toastMsg = 'Work Shift Day updated successfully';
        } else {
            WorkShiftDay::create($data);
            $toastMsg = 'Work Shift Day added successfully';
        }

        $this->resetForm();
        $this->modal('mdl-day')->close();
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: $toastMsg,
        );
    }

    private function getKeyFromValue($array, $value)
    {
        return array_search($value, $array);
    }

    public function clearFilters()
    {
        $this->reset('filters');
        $this->resetPage();
    }

    public function edit($id)
    {
        $day = WorkShiftDay::findOrFail($id);
        $this->formData = array_merge($day->toArray(), [
            'start_time' => $day->start_time ? Carbon::parse($day->start_time)->format('H:i') : null,
            'end_time' => $day->end_time ? Carbon::parse($day->end_time)->format('H:i') : null,
        ]);
        $this->isEditing = true;
        $this->modal('mdl-day')->show();
    }

    public function delete($id)
    {
        $day = WorkShiftDay::findOrFail($id);

        // Check if day has related records
        if ($day->emp_attendances()->count() > 0) {
            Flux::toast(
                variant: 'error',
                heading: 'Cannot Delete',
                text: 'This day has attendance records and cannot be deleted.',
            );
            return;
        }

        $day->delete();

        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Work Shift Day has been deleted successfully',
        );
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['paid_percent'] = 100;
        $this->isEditing = false;
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/WorkShifts/WorkShiftMeta/blades/work-shift-days.blade.php'));
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
}