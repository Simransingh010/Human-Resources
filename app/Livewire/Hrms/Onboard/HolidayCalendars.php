<?php

namespace App\Livewire\Hrms\Onboard;

use Livewire\Component;
use App\Models\Hrms\HolidayCalendar;
use Flux;

class HolidayCalendars extends Component
{
    use \Livewire\WithPagination;
    
    public array $calendarStatuses = [];
    public array $listsForFields = [];
    public $calendarData = [
        'id' => null,
        'title' => '',
        'description' => '',
        'is_inactive' => false,
    ];

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;
    public $selectedCalendarId = null;
    public $filters = [
        'search' => '',
        'status' => ''
    ];

    public function mount()
    {
        $this->loadCalendarStatuses();
        $this->initListsForFields();
    }

    private function loadCalendarStatuses()
    {
        $this->calendarStatuses = HolidayCalendar::pluck('is_inactive', 'id')
            ->map(function ($status) {
                return !$status;
            })
            ->toArray();
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
    public function calendarlist()
    {
        return HolidayCalendar::query()
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->when($this->filters['search'], function($query) {
                $query->where(function($q) {
                    $search = '%' . $this->filters['search'] . '%';
                    $q->where('title', 'like', $search)
                        ->orWhere('description', 'like', $search);
                });
            })
            ->when(!empty($this->filters['calendars']), function($query) {
                $query->whereIn('id', $this->filters['calendars']);
            })
            ->when($this->filters['status'] !== '', function($query) {
                $query->where('is_inactive', $this->filters['status'] === 'inactive');
            })
            ->where('firm_id', session('firm_id'))
            ->paginate(2);
    }

    public function fetchCalendar($id)
    {
        $calendar = HolidayCalendar::findOrFail($id);
        $this->calendarData = $calendar->toArray();
        $this->isEditing = true;
        $this->modal('mdl-calendar')->show();
    }

    public function saveCalendar()
    {
        $validatedData = $this->validate([
            'calendarData.title' => 'required|string|max:255',
            'calendarData.description' => 'nullable|string',
            'calendarData.is_inactive' => 'boolean',
        ]);

        if ($this->isEditing) {
            $calendar = HolidayCalendar::findOrFail($this->calendarData['id']);
            $calendar->update($validatedData['calendarData']);
            $toast = 'Holiday calendar updated successfully.';
        } else {
            $validatedData['calendarData']['firm_id'] = session('firm_id');
            HolidayCalendar::create($validatedData['calendarData']);
            $toast = 'Holiday calendar added successfully.';
        }

        $this->resetForm();
        $this->modal('mdl-calendar')->close();
        Flux::toast(
            heading: 'Changes saved.',
            text: $toast,
        );
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['calendars'] = HolidayCalendar::where('firm_id', session('firm_id'))->pluck('title', 'id');
    }

    public function resetForm()
    {
        $this->calendarData = [
            'id' => null,
            'title' => '',
            'description' => '',
            'is_inactive' => false,
        ];
        $this->isEditing = false;
    }

    public function applyFilters()
    {
        $this->filters = $this->filters;
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset('filters');
        $this->resetPage();
    }

    public function toggleStatus($calendarId)
    {
        $calendar = HolidayCalendar::findOrFail($calendarId);
        $calendar->is_inactive = !$calendar->is_inactive;
        $calendar->save();

        $this->calendarStatuses[$calendarId] = !$calendar->is_inactive;

        Flux::toast(
            heading: 'Status Updated',
            text: $calendar->is_inactive ? 'Calendar has been deactivated.' : 'Calendar has been activated.'
        );
    }

    public function deleteCalendar($calendarId)
    {
        $calendar = HolidayCalendar::findOrFail($calendarId);
        $calendarTitle = $calendar->title;

        $calendar->delete();

        Flux::toast(
            heading: 'Calendar Deleted',
            text: "Holiday calendar '{$calendarTitle}' has been deleted successfully."
        );
    }

    public function showmodal_holidays($calendarId)
    {
        $this->selectedCalendarId = $calendarId;
        $this->modal('add-holidays')->show();
    }

    public function render()
    {
        return view('livewire.hrms.onboard.holiday-calendars');
    }
} 