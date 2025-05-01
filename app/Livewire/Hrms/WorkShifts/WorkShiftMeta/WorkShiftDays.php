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

    // Add filter properties
    public $filters = [
        'search_shift' => '',
        'search_date' => '',
        'search_status' => '',
    ];

    public array $listsForFields = [];

    public function mount()
    {
        $this->resetPage();
        $this->getWorkShiftsForSelect();
        $this->getDayStatusesForSelect();
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
            ->when($this->filters['search_shift'], function($query) {
                $query->whereHas('work_shift', function($q) {
                    $q->where('shift_title', 'like', '%' . $this->filters['search_shift'] . '%');
                });
            })
            ->when($this->filters['search_date'], function($query) {
                $query->whereDate('work_date', $this->filters['search_date']);
            })
            ->when($this->filters['search_status'], function($query) {
                $query->where('work_shift_day_status_id', $this->filters['search_status']);
            })
            ->with(['work_shift', 'day_status'])
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate(5);
    }

    public function store()
    {
        $validatedData = $this->validate([
            'formData.work_shift_id' => 'required|exists:work_shifts,id',
            'formData.work_date' => 'required|date',
            'formData.work_shift_day_status_id' => 'required|exists:work_shift_day_statuses,id',
            'formData.start_time' => 'required|date_format:H:i',
            'formData.end_time' => 'required|date_format:H:i|after:formData.start_time',
            'formData.day_status_main' => 'nullable|string',
            'formData.paid_percent' => 'required|numeric|min:0|max:100',
        ]);

        // Convert empty strings to null
        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(function($val) {
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
        $validatedData['formData']['firm_id'] = session('firm_id');

        if ($this->isEditing) {
            $day = WorkShiftDay::findOrFail($this->formData['id']);
            $day->update($validatedData['formData']);
            $toastMsg = 'Work Shift Day updated successfully';
        } else {
            WorkShiftDay::create($validatedData['formData']);
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
} 