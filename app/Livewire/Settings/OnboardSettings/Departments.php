<?php

namespace App\Livewire\Settings\OnboardSettings;

use App\Models\Settings\Department;
use Livewire\Component;
use Livewire\WithPagination;
use Flux;

class Departments extends Component
{
    use WithPagination;
    
    public $selectedDepartmentId = null;
    public array $listsForFields = [];
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $statuses;
    public $formData = [
        'id' => null,
        'title' => '',
        'code' => '',
        'description' => '',
        'parent_department_id' => null,
        'is_inactive' => 0,
    ];

    public $isEditing = false;
    public $modal = false;

    // Add filter properties
    public $filters = [
        'search_title' => '',
        'search_code' => '',
    ];

    public function mount()
    {
        $this->resetPage();
        $this->refreshStatuses();
        $this->initListsForFields();
    }

    public function initListsForFields()
    {
        $this->listsForFields['departments'] = Department::where('firm_id', session('firm_id'))
            ->where('is_inactive', 0)
            ->pluck('title', 'id')
            ->toArray();
    }

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        return Department::query()
            ->where('firm_id', session('firm_id'))
            ->when($this->filters['search_title'], function($query) {
                $query->where('title', 'like', '%' . $this->filters['search_title'] . '%');
            })
            ->when($this->filters['search_code'], function($query) {
                $query->where('code', 'like', '%' . $this->filters['search_code'] . '%');
            })
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate(5);
    }

    public function store()
    {
        $validatedData = $this->validate([
            'formData.title' => 'required|string|max:255',
            'formData.code' => 'nullable|string|max:255',
            'formData.description' => 'nullable|string',
            'formData.parent_department_id' => 'nullable|integer|exists:departments,id',
            'formData.is_inactive' => 'boolean',
        ],
        [
            'formData.title.required' => 'Required.',
            'formData.title.max' => 'The Title may not be greater than :max characters.',
            'formData.code.max' => 'The Code may not be greater than :max characters.',
        ]
    );

        try {
            // Convert empty strings to null
            $validatedData['formData'] = collect($validatedData['formData'])
                ->map(fn($val) => $val === '' ? null : $val)
                ->toArray();

            // Add firm_id from session
            $validatedData['formData']['firm_id'] = session('firm_id');

            if ($this->isEditing) {
                $department = Department::findOrFail($this->formData['id']);
                $department->update($validatedData['formData']);
                $toastMsg = 'Department updated successfully';
            } else {
                Department::create($validatedData['formData']);
                $toastMsg = 'Department added successfully';
            }

            $this->resetForm();
            $this->refreshStatuses();
            $this->modal('mdl-department')->close();
            Flux::toast(
                variant: 'success',
                heading: 'Changes saved.',
                text: $toastMsg,
            );
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: $e->getMessage() ?: 'Unable to save department. Please try again.',
            );
        }
    }

    public function clearFilters()
    {
        $this->reset('filters');
        $this->resetPage();
    }

    public function edit($id)
    {
        $this->formData = Department::findOrFail($id)->toArray();
        $this->isEditing = true;
        $this->modal('mdl-department')->show();
    }

    public function delete($id)
    {
        try {
            Department::findOrFail($id)->delete();
            Flux::toast(
                variant: 'success',
                heading: 'Record Deleted.',
                text: 'Department has been deleted successfully',
            );
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Unable to delete department. It may be in use.',
            );
        }
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['is_inactive'] = 0;
        $this->isEditing = false;
    }

    public function refreshStatuses()
    {
        $this->statuses = Department::where('firm_id', session('firm_id'))
            ->pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool)$val])
            ->toArray();
    }

    public function toggleStatus($departmentId)
    {
        try {
            $department = Department::find($departmentId);
            if (!$department) {
                throw new \Exception('Department not found');
            }
            $department->is_inactive = !$department->is_inactive;
            $department->save();

            $this->statuses[$departmentId] = $department->is_inactive;
            $this->refreshStatuses();
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Unable to update department status. Please try again.',
            );
        }
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Settings/OnboardSettings/blades/departments.blade.php'));
    }
}
