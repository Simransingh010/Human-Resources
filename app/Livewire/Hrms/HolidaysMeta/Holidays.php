<?php

namespace App\Livewire\Hrms\HolidaysMeta;

use Livewire\Component;
use App\Models\Hrms\Holiday;
use App\Models\Hrms\HolidayCalendar;
use Flux;
use Carbon\Carbon;

class Holidays extends Component
{
    use \Livewire\WithPagination;

    public HolidayCalendar $calendar;
    public array $holidayStatuses = [];

    public $holidayData = [
        'id' => null,
        'holiday_calendar_id' => '',
        'holiday_title' => '',
        'holiday_desc' => '',
        'start_date' => '',
        'end_date' => '',
        'repeat_annually' => false,
        'is_inactive' => false,
    ];

    protected $listeners = ['modalClosed' => 'resetForm'];
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;

    public function mount($calendarId)
    {
        $this->calendar = HolidayCalendar::findOrFail($calendarId);
        $this->holidayslist();
        $this->loadHolidayStatuses();
    }

    private function loadHolidayStatuses()
    {
        $this->holidayStatuses = Holiday::pluck('is_inactive', 'id')
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
    public function holidayslist()
    {
        return Holiday::query()
            ->with('holiday_calendar')
            ->where('firm_id', session('firm_id'))
            ->where('holiday_calendar_id', $this->calendar->id)
            ->get()
            ->map(function ($holiday) {
                $dateFormat = session('dateFormat', 'd-M-Y'); // fallback format
                $holiday->start_date = Carbon::parse($holiday->start_date)->format($dateFormat);
                $holiday->end_date = Carbon::parse($holiday->end_date)->format($dateFormat);
                return $holiday;
            });
    }

    public function fetchHoliday($id)
    {
        $holiday = Holiday::findOrFail($id);
        $this->holidayData = $holiday->toArray();
        $this->isEditing = true;
        $this->modal('mdl-holiday')->show();
    }

    public function saveHoliday()
    {
        $validatedData = $this->validate([
            'holidayData.holiday_title' => 'required|string|max:255',
            'holidayData.holiday_desc' => 'nullable|string',
            'holidayData.start_date' => 'required|date',
            'holidayData.end_date' => 'nullable|date|after_or_equal:holidayData.start_date',
            'holidayData.repeat_annually' => 'boolean',
            'holidayData.is_inactive' => 'boolean',
        ]);

        $validatedData['holidayData']['holiday_calendar_id'] = $this->calendar->id;

        if ($this->isEditing) {
            $holiday = Holiday::findOrFail($this->holidayData['id']);
            $holiday->update($validatedData['holidayData']);
            session()->flash('message', 'Holiday updated successfully.');
        } else {
            $validatedData['holidayData']['firm_id'] = session('firm_id');
            Holiday::create($validatedData['holidayData']);
            session()->flash('message', 'Holiday added successfully.');
        }

        $this->resetForm();
        $this->modal('mdl-holiday')->close();
        Flux::toast(
            heading: 'Changes saved.',
            text: 'Holiday details have been updated successfully.',
        );
    }

    public function resetForm()
    {
        $this->holidayData = [
            'id' => null,
            'holiday_calendar_id' => '',
            'holiday_title' => '',
            'holiday_desc' => '',
            'start_date' => '',
            'end_date' => '',
            'repeat_annually' => false,
            'is_inactive' => false,
        ];

        $this->isEditing = false;
    }

    public function update_rec_status($holidayId)
    {
        $holiday = Holiday::find($holidayId);
        if ($holiday) {
            $holiday->is_inactive = !$holiday->is_inactive;
            $holiday->save();

            $this->holidayStatuses[$holidayId] = !$holiday->is_inactive;

            Flux::toast(
                heading: 'Status Updated',
                text: $holiday->is_inactive ? 'Holiday has been deactivated.' : 'Holiday has been activated.'
            );
        }
    }

    public function deleteHoliday($holidayId)
    {
        $holiday = Holiday::find($holidayId);
        if ($holiday) {
            $holiday->delete();
            Flux::toast(
                heading: 'Holiday Deleted',
                text: 'The holiday has been deleted successfully.',
            );
        }
        $this->modal('delete-holiday-' . $holidayId)->close();
    }

}
