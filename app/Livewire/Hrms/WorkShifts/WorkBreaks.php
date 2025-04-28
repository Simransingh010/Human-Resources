<?php

namespace App\Livewire\Hrms\Workshifts;

use App\Models\Hrms\WorkBreak;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Flux;

class WorkBreaks extends Component
{
    use WithPagination;
    
    public $selectedBreakId = null;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $statuses;
    public $formData = [
        'id' => null,
        'break_title' => '',
        'break_desc' => '',
        'start_time' => '',
        'end_time' => '',
        'is_inactive' => false,
    ];

    public $isEditing = false;
    public $modal = false;

    // Add filter properties
    public $filters = [
        'search_title' => '',
        'search_time' => '',
    ];

    public function mount()
    {
        $this->resetPage();
        $this->refreshStatuses();
    }

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        return WorkBreak::query()
            ->where('firm_id', session('firm_id'))
            ->when($this->filters['search_title'], function($query) {
                $query->where('break_title', 'like', '%' . $this->filters['search_title'] . '%');
            })
            ->when($this->filters['search_time'], function($query) {
                $query->where('start_time', 'like', '%' . $this->filters['search_time'] . '%');
            })
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate(5);
    }

    public function store()
    {
        $validatedData = $this->validate([
            'formData.break_title' => 'required|string|max:255',
            'formData.break_desc' => 'nullable|string',
            'formData.start_time' => 'required|date_format:H:i',
            'formData.end_time' => 'required|date_format:H:i|after:formData.start_time',
            'formData.is_inactive' => 'boolean',
        ]);

        // Convert empty strings to null
        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        // Add firm_id from session
        $validatedData['formData']['firm_id'] = session('firm_id');

        // Convert time strings to datetime for storage
        $validatedData['formData']['start_time'] = Carbon::createFromFormat('H:i', $validatedData['formData']['start_time']);
        $validatedData['formData']['end_time'] = Carbon::createFromFormat('H:i', $validatedData['formData']['end_time']);

        if ($this->isEditing) {
            $break = WorkBreak::findOrFail($this->formData['id']);
            $break->update($validatedData['formData']);
            $toastMsg = 'Work Break updated successfully';
        } else {
            WorkBreak::create($validatedData['formData']);
            $toastMsg = 'Work Break added successfully';
        }

        $this->resetForm();
        $this->refreshStatuses();
        $this->modal('mdl-break')->close();
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
        $break = WorkBreak::findOrFail($id);
        $this->formData = array_merge($break->toArray(), [
            'start_time' => $break->start_time->format('H:i'),
            'end_time' => $break->end_time->format('H:i'),
        ]);
        $this->isEditing = true;
        $this->modal('mdl-break')->show();
    }

    public function delete($id)
    {
        $break = WorkBreak::findOrFail($id);
        
        // Check if break has related records
        if ($break->work_shift_days_breaks()->count() > 0) {
            Flux::toast(
                variant: 'error',
                heading: 'Cannot Delete',
                text: 'This break is assigned to work shifts and cannot be deleted.',
            );
            return;
        }

        $break->delete();
        
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Work Break has been deleted successfully',
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
        $this->statuses = WorkBreak::where('firm_id', session('firm_id'))
            ->pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool)$val])
            ->toArray();
    }

    public function toggleStatus($breakId)
    {
        $break = WorkBreak::find($breakId);
        $break->is_inactive = !$break->is_inactive;
        $break->save();

        $this->statuses[$breakId] = $break->is_inactive;
        $this->refreshStatuses();
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/WorkShifts/blades/work-breaks.blade.php'));
    }
} 