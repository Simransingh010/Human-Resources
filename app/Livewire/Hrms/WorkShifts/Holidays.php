<?php

namespace App\Livewire\Hrms\Workshifts;

use App\Models\Hrms\Holiday;
use App\Models\Hrms\HolidayCalendar;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Flux;

class Holidays extends Component
{
    use WithPagination;

    public $selectedHolidayId = null;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $statuses;
    public $formData = [
        'id' => null,
        'holiday_title' => '',
        'holiday_desc' => '',
        'start_date' => '',
        'end_date' => '',
        'repeat_annually' => false,
        'is_inactive' => false,
        'holiday_calendar_id' => '',
        'day_status_main' => '',
    ];

    public $isEditing = false;
    public $modal = false;
    public $perPage = 10;

    // Field configuration for form and table
    public array $fieldConfig = [
        'holiday_title' => ['label' => 'Title', 'type' => 'text'],
        'holiday_desc' => ['label' => 'Description', 'type' => 'textarea'],
        'holiday_calendar_id' => ['label' => 'Calendar', 'type' => 'select', 'listKey' => 'holiday_calendars'],
        'start_date' => ['label' => 'Start Date', 'type' => 'date'],
        'end_date' => ['label' => 'End Date', 'type' => 'date'],
        'repeat_annually' => ['label' => 'Repeat Annually', 'type' => 'boolean'],
        'is_inactive' => ['label' => 'Status', 'type' => 'boolean'],
        'day_status_main' => ['label' => 'Work Status', 'type' => 'select', 'listKey' => 'work_statuses'],
    ];

    public array $filterFields = [
        'search_title' => ['label' => 'Title', 'type' => 'text'],
        'search_date' => ['label' => 'Date', 'type' => 'date'],
        'holiday_calendar_id' => ['label' => 'Calendar', 'type' => 'select', 'listKey' => 'holiday_calendars'],
        'repeat_annually' => ['label' => 'Repeat Annually', 'type' => 'boolean'],
        'is_inactive' => ['label' => 'Status', 'type' => 'boolean'],
    ];

    // Add filter properties
    public $filters = [
        'search_title' => '',
        'search_date' => '',
        'holiday_calendar_id' => '',
        'repeat_annually' => '',
        'is_inactive' => '',
    ];

    public array $visibleFields = [];
    public array $visibleFilterFields = [];
    public array $listsForFields = [];

    public $holidayCalendarId = null;

    public function mount($holidayCalendarId = null)
    {
        $this->holidayCalendarId = $holidayCalendarId;
        $this->resetPage();
        $this->refreshStatuses();

        // If we have a calendar ID, set it in the form data
        if ($this->holidayCalendarId) {
            $this->formData['holiday_calendar_id'] = $this->holidayCalendarId;
        }

        // Set default visible fields and filters
        $this->visibleFields = ['holiday_title', 'holiday_desc', 'start_date', 'end_date', 'day_status_main'];
        $this->visibleFilterFields = ['search_title', 'search_date'];

        // Add work statuses to lists
        $this->listsForFields['work_statuses'] = Holiday::WORK_STATUS_SELECT;

        // Only get holiday calendars list if no specific calendar is selected
        if (!$this->holidayCalendarId) {
            $this->getHolidayCalendarsForSelect();
        }
    }

    private function getHolidayCalendarsForSelect()
    {
        $this->listsForFields['holiday_calendars'] = HolidayCalendar::where('firm_id', session('firm_id'))
            ->where('is_inactive', 0)
            ->pluck('title', 'id')
            ->toArray();
    }

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        $query = Holiday::query()
            ->where('firm_id', session('firm_id'));

        if ($this->holidayCalendarId) {
            $query->where('holiday_calendar_id', $this->holidayCalendarId);
        }

        return $query->when($this->filters['search_title'], function ($query) {
            $query->where('holiday_title', 'like', '%' . $this->filters['search_title'] . '%');
        })
            ->when($this->filters['search_date'], function ($query) {
                $query->where('start_date', 'like', '%' . $this->filters['search_date'] . '%');
            })
            ->when($this->filters['holiday_calendar_id'], function ($query) {
                $query->where('holiday_calendar_id', $this->filters['holiday_calendar_id']);
            })
            ->when($this->filters['repeat_annually'] !== '', function ($query) {
                $query->where('repeat_annually', $this->filters['repeat_annually']);
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
            $rules = [
                'formData.holiday_title' => 'required|string|max:255',
                'formData.holiday_desc' => 'nullable|string',
                'formData.start_date' => 'required|date',
                'formData.end_date' => 'nullable|date|after_or_equal:formData.start_date',
                'formData.repeat_annually' => 'boolean',
                'formData.is_inactive' => 'boolean',
                'formData.day_status_main' => 'required|numeric|in:' . implode(',', array_keys(Holiday::WORK_STATUS_SELECT)),
            ];

            // Only add holiday_calendar_id validation if not using a specific calendar
            if (!$this->holidayCalendarId) {
                $rules['formData.holiday_calendar_id'] = 'required|exists:holiday_calendars,id';
            }

            $validatedData = $this->validate($rules);

            $data = collect($validatedData['formData'])
                ->map(fn($val) => $val === '' ? null : $val)
                ->toArray();

            $data['firm_id'] = session('firm_id');

            // Always use the passed calendar ID if available
            if ($this->holidayCalendarId) {
                $data['holiday_calendar_id'] = $this->holidayCalendarId;
            }

            if ($this->isEditing) {
                $holiday = Holiday::findOrFail($this->formData['id']);
                $holiday->update($data);
                $toastMsg = 'Holiday updated successfully';
            } else {
                Holiday::create($data);
                $toastMsg = 'Holiday added successfully';
            }

            $this->resetForm();
            $this->refreshStatuses();
            $this->modal('mdl-holiday')->close();
            $this->dispatch('refreshCalendarList');

            Flux::toast(
                variant: 'success',
                heading: 'Changes saved.',
                text: $toastMsg,
            );
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to save holiday: ' . $e->getMessage(),
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
        $this->formData = Holiday::findOrFail($id)->toArray();
        $this->isEditing = true;
        $this->modal('mdl-holiday')->show();
    }

    public function delete($id)
    {
        $holiday = Holiday::findOrFail($id);
        $holiday->delete();

        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Holiday has been deleted successfully',
        );
    }

    public function resetForm()
    {
        $this->reset(['formData', 'isEditing']);
        $this->formData = [
            'holiday_title' => '',
            'holiday_desc' => '',
            'start_date' => '',
            'end_date' => '',
            'repeat_annually' => false,
            'is_inactive' => false,
            'holiday_calendar_id' => '',
            'day_status_main' => '',
        ];

        // If we have a calendar ID, set it back in the form data
        if ($this->holidayCalendarId) {
            $this->formData['holiday_calendar_id'] = $this->holidayCalendarId;
        }
    }

    public function refreshStatuses()
    {
        $this->statuses = Holiday::where('firm_id', session('firm_id'))
            ->pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool) $val])
            ->toArray();
    }

    public function toggleStatus($holidayId)
    {
        $holiday = Holiday::find($holidayId);
        $holiday->is_inactive = !$holiday->is_inactive;
        $holiday->save();

        $this->statuses[$holidayId] = $holiday->is_inactive;
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
        return view()->file(app_path('Livewire/Hrms/WorkShifts/blades/holidays.blade.php'));
    }
}
