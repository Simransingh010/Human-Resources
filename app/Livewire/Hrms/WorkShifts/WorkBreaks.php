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
    public $perPage = 10;

    // Field configuration for form and table
    public array $fieldConfig = [
        'break_title' => ['label' => 'Title', 'type' => 'text'],
        'break_desc' => ['label' => 'Description', 'type' => 'textarea'],
        'start_time' => ['label' => 'Start Time', 'type' => 'time'],
        'end_time' => ['label' => 'End Time', 'type' => 'time'],
        'is_inactive' => ['label' => 'Status', 'type' => 'boolean'],
    ];

    public array $filterFields = [
        'search_title' => ['label' => 'Title', 'type' => 'text'],
        'search_time' => ['label' => 'Time', 'type' => 'time'],
        'is_inactive' => ['label' => 'Status', 'type' => 'boolean'],
    ];

    // Add filter properties
    public $filters = [
        'search_title' => '',
        'search_time' => '',
        'is_inactive' => '',
    ];

    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public function mount()
    {
        $this->resetPage();
        $this->refreshStatuses();

        // Set default visible fields and filters
        $this->visibleFields = ['break_title', 'break_desc', 'start_time', 'end_time'];
        $this->visibleFilterFields = ['search_title', 'search_time', 'is_inactive'];
    }

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        return WorkBreak::query()
            ->where('firm_id', session('firm_id'))
            ->when($this->filters['search_title'], function ($query) {
                $query->where('break_title', 'like', '%' . $this->filters['search_title'] . '%');
            })
            ->when($this->filters['search_time'], function ($query) {
                $query->where('start_time', 'like', '%' . $this->filters['search_time'] . '%');
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
            'formData.break_title' => 'required|string|max:255',
            'formData.break_desc' => 'nullable|string',
            'formData.start_time' => 'required|date_format:H:i',
            'formData.end_time' => 'required|date_format:H:i|after:formData.start_time',
            'formData.is_inactive' => 'boolean',
        ]);

        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        $validatedData['formData']['firm_id'] = session('firm_id');

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
            ->mapWithKeys(fn($val, $key) => [$key => (bool) $val])
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
        return view()->file(app_path('Livewire/Hrms/WorkShifts/blades/work-breaks.blade.php'));
    }
}