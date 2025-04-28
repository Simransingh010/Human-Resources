<?php

namespace App\Livewire\Hrms\Workshifts;

use App\Models\Hrms\WorkShiftsAlgo;
use App\Models\Hrms\WorkShift;
use App\Models\Hrms\HolidayCalendar;
use App\Models\Hrms\WorkBreak;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Flux;

class WorkShiftsAlgos extends Component
{
    use WithPagination;
    
    public $selectedAlgoId = null;
    public array $listsForFields = [];
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $statuses;
    public $formData = [
        'id' => null,
        'work_shift_id' => '',
        'start_date' => '',
        'end_date' => '',
        'start_time' => '',
        'end_time' => '',
        'week_off_pattern' => '',
        'work_breaks' => '',
        'holiday_calendar_id' => '',
        'allow_wfh' => false,
        'half_day_rule' => '',
        'overtime_rule' => '',
        'rules_config' => '',
        'late_panelty' => '',
        'comp_off' => '',
        'is_inactive' => false,
    ];

    public $isEditing = false;
    public $modal = false;

    // Add filter properties
    public $filters = [
        'search_shift' => '',
        'search_pattern' => '',
    ];

    public function mount()
    {
        $this->resetPage();
        $this->refreshStatuses();
        $this->getWorkShiftsForSelect();
        $this->getHolidayCalendarsForSelect();
        $this->getWorkBreaksForSelect();
        $this->formData['work_breaks'] = []; // Initialize as empty array
    }

    private function getWorkShiftsForSelect()
    {
        $this->listsForFields['work_shifts'] = WorkShift::where('firm_id', session('firm_id'))
            ->where('is_inactive', 0)
            ->pluck('shift_title', 'id')
            ->toArray();
    }

    private function getHolidayCalendarsForSelect()
    {
        $this->listsForFields['holiday_calendars'] = HolidayCalendar::where('firm_id', session('firm_id'))
            ->where('is_inactive', 0)
            ->pluck('title', 'id')
            ->toArray();
    }

    private function getWorkBreaksForSelect()
    {
        $this->listsForFields['work_breaks'] = WorkBreak::where('firm_id', session('firm_id'))
            ->where('is_inactive', 0)
            ->orderBy('break_title')
            ->get()
            ->map(function($break) {
                return [
                    'id' => $break->id,
                    'title' => $break->break_title . ' (' . $break->start_time->format('H:i') . ' - ' . $break->end_time->format('H:i') . ')'
                ];
            })
            ->pluck('title', 'id')
            ->toArray();
    }

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        return WorkShiftsAlgo::query()
            ->where('firm_id', session('firm_id'))
            ->when($this->filters['search_shift'], function($query) {
                $query->whereHas('work_shift', function($q) {
                    $q->where('shift_title', 'like', '%' . $this->filters['search_shift'] . '%');
                });
            })
            ->when($this->filters['search_pattern'], function($query) {
                $query->where('week_off_pattern', 'like', '%' . $this->filters['search_pattern'] . '%');
            })
            ->with(['work_shift'])
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate(5);
    }

    public function store()
    {
        $validatedData = $this->validate([
            'formData.work_shift_id' => 'required|exists:work_shifts,id',
            'formData.start_date' => 'nullable|date',
            'formData.end_date' => 'nullable|date|after_or_equal:formData.start_date',
            'formData.start_time' => 'nullable|date_format:H:i',
            'formData.end_time' => 'nullable|date_format:H:i|after:formData.start_time',
            'formData.week_off_pattern' => 'nullable|string',
            'formData.work_breaks' => 'nullable|array',
            'formData.work_breaks.*' => 'exists:work_breaks,id',
            'formData.holiday_calendar_id' => 'nullable|exists:holiday_calendars,id',
            'formData.allow_wfh' => 'boolean',
            'formData.half_day_rule' => 'nullable|string',
            'formData.overtime_rule' => 'nullable|string',
            'formData.rules_config' => 'nullable|string',
            'formData.late_panelty' => 'nullable|string',
            'formData.comp_off' => 'nullable|string',
            'formData.is_inactive' => 'boolean',
        ]);

        // Convert empty strings to null and handle work_breaks
        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(function($val) {
                if ($val === '') {
                    return null;
                }
                // Convert work_breaks array to JSON string
                if (is_array($val) && isset($this->formData['work_breaks']) && $this->formData['work_breaks'] === $val) {
                    return !empty($val) ? json_encode($val) : null;
                }
                return $val;
            })
            ->toArray();

        // Add firm_id from session
        $validatedData['formData']['firm_id'] = session('firm_id');

        if ($this->isEditing) {
            $algo = WorkShiftsAlgo::findOrFail($this->formData['id']);
            $algo->update($validatedData['formData']);
            $toastMsg = 'Work Shift Algorithm updated successfully';
        } else {
            WorkShiftsAlgo::create($validatedData['formData']);
            $toastMsg = 'Work Shift Algorithm added successfully';
        }

        $this->resetForm();
        $this->refreshStatuses();
        $this->modal('mdl-algo')->close();
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
        $algo = WorkShiftsAlgo::findOrFail($id);
        $this->formData = array_merge($algo->toArray(), [
            'work_breaks' => json_decode($algo->work_breaks ?? '[]', true) ?? [],
            'start_time' => $algo->start_time ? Carbon::parse($algo->start_time)->format('H:i') : null,
            'end_time' => $algo->end_time ? Carbon::parse($algo->end_time)->format('H:i') : null,
        ]);
        $this->isEditing = true;
        $this->modal('mdl-algo')->show();
    }

    public function delete($id)
    {
        $algo = WorkShiftsAlgo::findOrFail($id);
        $algo->delete();
        
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Work Shift Algorithm has been deleted successfully',
        );
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['allow_wfh'] = false;
        $this->formData['is_inactive'] = false;
        $this->formData['work_breaks'] = []; // Initialize as empty array
        $this->isEditing = false;
    }

    public function refreshStatuses()
    {
        $this->statuses = WorkShiftsAlgo::where('firm_id', session('firm_id'))
            ->pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool)$val])
            ->toArray();
    }

    public function toggleStatus($algoId)
    {
        $algo = WorkShiftsAlgo::find($algoId);
        $algo->is_inactive = !$algo->is_inactive;
        $algo->save();

        $this->statuses[$algoId] = $algo->is_inactive;
        $this->refreshStatuses();
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/WorkShifts/blades/work-shifts-algos.blade.php'));
    }
} 