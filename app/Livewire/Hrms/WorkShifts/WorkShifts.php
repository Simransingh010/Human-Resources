<?php

namespace App\Livewire\Hrms\Workshifts;

use App\Models\Hrms\WorkShift;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Flux;

class WorkShifts extends Component
{
    use WithPagination;

    public $selectedShiftId = null;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $statuses;
    public $formData = [
        'id' => null,
        'shift_title' => '',
        'shift_desc' => '',
        'is_inactive' => false,
    ];

    public $isEditing = false;
    public $modal = false;
    public $perPage = 10;

    // Field configuration for form and table
    public array $fieldConfig = [
        'shift_title' => ['label' => 'Title', 'type' => 'text'],
        'shift_desc' => ['label' => 'Description', 'type' => 'textarea'],
        'is_inactive' => ['label' => 'isInactive', 'type' => 'boolean'],
    ];

    public array $filterFields = [
        'search_title' => ['label' => 'Title', 'type' => 'text'],
        'is_inactive' => ['label' => 'isInactive', 'type' => 'boolean'],
    ];

    // Add filter properties
    public $filters = [
        'search_title' => '',
        'is_inactive' => '',
    ];

    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public function mount()
    {
        $this->resetPage();
        $this->refreshStatuses();

        // Set default visible fields and filters
        $this->visibleFields = ['shift_title', 'shift_desc'];
        $this->visibleFilterFields = ['search_title', 'is_inactive'];
    }

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        return WorkShift::query()
            ->where('firm_id', session('firm_id'))
            ->when($this->filters['search_title'], function ($query) {
                $query->where('shift_title', 'like', '%' . $this->filters['search_title'] . '%');
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
            'formData.shift_title' => 'required|string|max:255',
            'formData.shift_desc' => 'nullable|string',
            'formData.is_inactive' => 'boolean',
        ]);

        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        $validatedData['formData']['firm_id'] = session('firm_id');

        if ($this->isEditing) {
            $shift = WorkShift::findOrFail($this->formData['id']);
            $shift->update($validatedData['formData']);
            $toastMsg = 'Work Shift updated successfully';
        } else {
            WorkShift::create($validatedData['formData']);
            $toastMsg = 'Work Shift added successfully';
        }

        $this->resetForm();
        $this->refreshStatuses();
        $this->modal('mdl-shift')->close();
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
        $this->formData = WorkShift::findOrFail($id)->toArray();
        $this->isEditing = true;
        $this->modal('mdl-shift')->show();
    }

    public function delete($id)
    {
        $shift = WorkShift::findOrFail($id);

        // Check if shift has related records
        if (
            $shift->work_shift_days()->count() > 0 ||
            $shift->emp_work_shifts()->count() > 0 ||
            $shift->work_shifts_algos()->count() > 0
        ) {
            Flux::toast(
                variant: 'error',
                heading: 'Cannot Delete',
                text: 'This work shift has related records and cannot be deleted.',
            );
            return;
        }

        $shift->delete();

        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Work Shift has been deleted successfully',
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
        $this->statuses = WorkShift::where('firm_id', session('firm_id'))
            ->pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool) $val])
            ->toArray();
    }

    public function toggleStatus($shiftId)
    {
        $shift = WorkShift::find($shiftId);
        $shift->is_inactive = !$shift->is_inactive;
        $shift->save();

        $this->statuses[$shiftId] = $shift->is_inactive;
        $this->refreshStatuses();
    }

    public function showWorkShiftDays($id)
    {
        $this->selectedShiftId = $id;
        $this->modal('work-shift-days-modal')->show();
    }

    public function showEmpWorkShifts($id)
    {
        $this->selectedShiftId = $id;
        $this->modal('emp-work-shifts-modal')->show();
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
        return view()->file(app_path('Livewire/Hrms/WorkShifts/blades/work-shifts.blade.php'));
    }
}