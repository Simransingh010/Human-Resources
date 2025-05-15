<?php

namespace App\Livewire\Hrms\Workshifts;

use App\Models\Hrms\HolidayCalendar;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Flux;

class HolidayCalendars extends Component
{
    use WithPagination;

    public $selectedCalendarId = null;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $statuses;
    public $perPage = 10;
    public $showHolidaysModal = false;

    public $formData = [
        'id' => null,
        'title' => '',
        'description' => '',
        'start_date' => '',
        'end_date' => '',
        'is_inactive' => false,
    ];

    public $isEditing = false;
    public $modal = false;

    // Field configuration for form and table
    public array $fieldConfig = [
        'title' => ['label' => 'Title', 'type' => 'text'],
        'description' => ['label' => 'Description', 'type' => 'textarea'],
        'start_date' => ['label' => 'Start Date', 'type' => 'date'],
        'end_date' => ['label' => 'End Date', 'type' => 'date'],
        'is_inactive' => ['label' => 'Status', 'type' => 'boolean'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'search_title' => ['label' => 'Title', 'type' => 'text'],
        'search_date' => ['label' => 'Date', 'type' => 'date'],
        'is_inactive' => ['label' => 'Status', 'type' => 'boolean'],
    ];

    // Add filter properties
    public $filters = [
        'search_title' => '',
        'search_date' => '',
        'is_inactive' => '',
    ];

    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    protected $listeners = ['refreshCalendarList' => '$refresh'];

    public function mount()
    {
        $this->resetPage();
        $this->refreshStatuses();

        // Set default visible fields and filters
        $this->visibleFields = ['title', 'description', 'start_date', 'end_date'];
        $this->visibleFilterFields = ['search_title', 'search_date'];
    }

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        return HolidayCalendar::query()
            ->where('firm_id', session('firm_id'))
            ->when($this->filters['search_title'], function ($query) {
                $query->where('title', 'like', '%' . $this->filters['search_title'] . '%');
            })
            ->when($this->filters['search_date'], function ($query) {
                $query->where('start_date', 'like', '%' . $this->filters['search_date'] . '%');
            })
            ->when($this->filters['is_inactive'] !== '', function ($query) {
                $query->where('is_inactive', $this->filters['is_inactive']);
            })
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate($this->perPage);
    }

    public function store()
    {
        $validatedData = $this->validate([
            'formData.title' => 'required|string|max:255',
            'formData.description' => 'nullable|string',
            'formData.start_date' => 'nullable|date',
            'formData.end_date' => 'nullable|date|after_or_equal:formData.start_date',
            'formData.is_inactive' => 'boolean',
        ]);

        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        $validatedData['formData']['firm_id'] = session('firm_id');

        if ($this->isEditing) {
            $calendar = HolidayCalendar::findOrFail($this->formData['id']);
            $calendar->update($validatedData['formData']);
            $toastMsg = 'Holiday Calendar updated successfully';
        } else {
            HolidayCalendar::create($validatedData['formData']);
            $toastMsg = 'Holiday Calendar added successfully';
        }

        $this->resetForm();
        $this->refreshStatuses();
        $this->modal('mdl-calendar')->close();
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
        $this->formData = HolidayCalendar::findOrFail($id)->toArray();
        $this->isEditing = true;
        $this->modal('mdl-calendar')->show();
    }

    public function delete($id)
    {
        $calendar = HolidayCalendar::findOrFail($id);

        if ($calendar->holidays()->count() > 0) {
            Flux::toast(
                variant: 'error',
                heading: 'Cannot Delete',
                text: 'This calendar has related holidays and cannot be deleted.',
            );
            return;
        }

        $calendar->delete();

        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Holiday Calendar has been deleted successfully',
        );
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['is_inactive'] = false;
        $this->isEditing = false;
    }

    public function refreshStatuses()
    {
        $this->statuses = HolidayCalendar::where('firm_id', session('firm_id'))
            ->pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool) $val])
            ->toArray();
    }

    public function toggleStatus($calendarId)
    {
        $calendar = HolidayCalendar::find($calendarId);
        $calendar->is_inactive = !$calendar->is_inactive;
        $calendar->save();

        $this->statuses[$calendarId] = $calendar->is_inactive;
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

    public function showCalendarHolidays($id)
    {
        $this->selectedCalendarId = $id;
        $this->showHolidaysModal = true;
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/WorkShifts/blades/holiday-calendars.blade.php'));
    }
}