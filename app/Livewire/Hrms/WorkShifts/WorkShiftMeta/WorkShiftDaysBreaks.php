<?php

namespace App\Livewire\Hrms\Workshifts\WorkShiftMeta;

use App\Models\Hrms\WorkShiftDaysBreak;
use App\Models\Hrms\WorkShiftDay;
use App\Models\Hrms\WorkBreak;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Flux;

class WorkShiftDaysBreaks extends Component
{
    use WithPagination;
    
    public $selectedId = null;
    public $sortBy = 'id';
    public $sortDirection = 'desc';
    public $formData = [
        'id' => null,
        'work_shift_day_id' => '',
        'work_break_id' => '',
    ];

    public $isEditing = false;
    public $modal = false;

    // Add filter properties
    public $filters = [
        'search_day' => '',
        'search_break' => '',
    ];

    public array $listsForFields = [];

    public function mount()
    {
        $this->resetPage();
        $this->getWorkShiftDaysForSelect();
        $this->getWorkBreaksForSelect();
    }

    private function getWorkShiftDaysForSelect()
    {
        $this->listsForFields['work_shift_days'] = WorkShiftDay::where('firm_id', session('firm_id'))
            ->with('work_shift')
            ->get()
            ->map(function ($day) {
                return [
                    'id' => $day->id,
                    'label' => $day->work_shift->shift_title . ' - ' . Carbon::parse($day->work_date)->format('Y-m-d')
                ];
            })
            ->pluck('label', 'id')
            ->toArray();
    }

    private function getWorkBreaksForSelect()
    {
        $this->listsForFields['work_breaks'] = WorkBreak::where('firm_id', session('firm_id'))
            ->where('is_inactive', 0)
            ->pluck('break_title', 'id')
            ->toArray();
    }

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        return WorkShiftDaysBreak::query()
            ->where('firm_id', session('firm_id'))
            ->when($this->filters['search_day'], function($query) {
                $query->whereHas('work_shift_day', function($q) {
                    $q->where('id', $this->filters['search_day']);
                });
            })
            ->when($this->filters['search_break'], function($query) {
                $query->whereHas('work_break', function($q) {
                    $q->where('break_title', 'like', '%' . $this->filters['search_break'] . '%');
                });
            })
            ->with(['work_shift_day.work_shift', 'work_break'])
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate(10);
    }

    public function store()
    {
        $validatedData = $this->validate([
            'formData.work_shift_day_id' => 'required|exists:work_shift_days,id',
            'formData.work_break_id' => 'required|exists:work_breaks,id',
        ]);

        // Add firm_id from session
        $validatedData['formData']['firm_id'] = session('firm_id');

        // Check if the combination already exists
        $exists = WorkShiftDaysBreak::where('work_shift_day_id', $validatedData['formData']['work_shift_day_id'])
            ->where('work_break_id', $validatedData['formData']['work_break_id'])
            ->exists();

        if ($exists && !$this->isEditing) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'This break is already assigned to the selected work shift day.',
            );
            return;
        }

        if ($this->isEditing) {
            $workShiftDayBreak = WorkShiftDaysBreak::findOrFail($this->formData['id']);
            $workShiftDayBreak->update($validatedData['formData']);
            $toastMsg = 'Work Shift Day Break updated successfully';
        } else {
            WorkShiftDaysBreak::create($validatedData['formData']);
            $toastMsg = 'Work Shift Day Break added successfully';
        }

        $this->resetForm();
        $this->modal('mdl-day-break')->close();
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
        $workShiftDayBreak = WorkShiftDaysBreak::findOrFail($id);
        $this->formData = $workShiftDayBreak->toArray();
        $this->isEditing = true;
        $this->modal('mdl-day-break')->show();
    }

    public function delete($id)
    {
        $workShiftDayBreak = WorkShiftDaysBreak::findOrFail($id);
        $workShiftDayBreak->delete();
        
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Work Shift Day Break has been deleted successfully',
        );
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->isEditing = false;
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/WorkShifts/WorkShiftMeta/blades/work-shift-days-breaks.blade.php'));
    }
} 