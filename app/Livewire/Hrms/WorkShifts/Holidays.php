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
    ];

    public $isEditing = false;
    public $modal = false;

    // Add filter properties
    public $filters = [
        'search_title' => '',
        'search_date' => '',
    ];

    public array $listsForFields = [];

    public function mount()
    {
        $this->resetPage();
        $this->refreshStatuses();
        $this->getHolidayCalendarsForSelect();
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
        return Holiday::query()
            ->where('firm_id', session('firm_id'))
            ->when($this->filters['search_title'], function($query) {
                $query->where('holiday_title', 'like', '%' . $this->filters['search_title'] . '%');
            })
            ->when($this->filters['search_date'], function($query) {
                $query->where('start_date', 'like', '%' . $this->filters['search_date'] . '%');
            })
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate(5);
    }

    public function store()
    {
        $validatedData = $this->validate([
            'formData.holiday_title' => 'required|string|max:255',
            'formData.holiday_desc' => 'nullable|string',
            'formData.start_date' => 'required|date',
            'formData.end_date' => 'nullable|date|after_or_equal:formData.start_date',
            'formData.repeat_annually' => 'boolean',
            'formData.is_inactive' => 'boolean',
            'formData.holiday_calendar_id' => 'required|exists:holiday_calendars,id',
        ]);

        // Convert empty strings to null
        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        // Add firm_id from session
        $validatedData['formData']['firm_id'] = session('firm_id');

        if ($this->isEditing) {
            $holiday = Holiday::findOrFail($this->formData['id']);
            $holiday->update($validatedData['formData']);
            $toastMsg = 'Holiday updated successfully';
        } else {
            Holiday::create($validatedData['formData']);
            $toastMsg = 'Holiday added successfully';
        }

        $this->resetForm();
        $this->refreshStatuses();
        $this->modal('mdl-holiday')->close();
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
        $this->reset(['formData']);
        $this->formData['repeat_annually'] = false;
        $this->formData['is_inactive'] = false;
        $this->isEditing = false;
    }

    public function refreshStatuses()
    {
        $this->statuses = Holiday::where('firm_id', session('firm_id'))
            ->pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool)$val])
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

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/WorkShifts/blades/holidays.blade.php'));
    }
}
