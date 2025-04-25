<?php

namespace App\Livewire\Settings\OnboardSettings;

use App\Models\Settings\Department;
use App\Models\Settings\Designation;
use App\Models\Settings\DepartmentsDesignation;
use Livewire\Component;
use Livewire\WithPagination;
use Flux;

class DepartmentsDesignations extends Component
{
    use WithPagination;
    
    public array $listsForFields = [];
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $formData = [
        'id' => null,
        'department_id' => '',
        'designation_id' => '',
    ];

    public $isEditing = false;
    public $modal = false;

    // Add filter properties
    public $filters = [
        'search_department' => '',
        'search_designation' => '',
    ];

    public function mount()
    {
        $this->resetPage();
        $this->initListsForFields();
    }

    public function initListsForFields()
    {
        $this->listsForFields['departments'] = Department::where('firm_id', session('firm_id'))
            ->where('is_inactive', 0)
            ->pluck('title', 'id')
            ->toArray();

        $this->listsForFields['designations'] = Designation::where('firm_id', session('firm_id'))
            ->where('is_inactive', 0)
            ->pluck('title', 'id')
            ->toArray();
    }

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        return DepartmentsDesignation::query()
            ->with(['department', 'designation'])
            ->where('firm_id', session('firm_id'))
            ->when($this->filters['search_department'], function($query) {
                $query->whereHas('department', function($q) {
                    $q->where('title', 'like', '%' . $this->filters['search_department'] . '%');
                });
            })
            ->when($this->filters['search_designation'], function($query) {
                $query->whereHas('designation', function($q) {
                    $q->where('title', 'like', '%' . $this->filters['search_designation'] . '%');
                });
            })
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate(5);
    }

    public function store()
    {
        $validatedData = $this->validate([
            'formData.department_id' => 'required|exists:departments,id',
            'formData.designation_id' => 'required|exists:designations,id',
        ]);

        // Add firm_id from session
        $validatedData['formData']['firm_id'] = session('firm_id');

        if ($this->isEditing) {
            $deptDesignation = DepartmentsDesignation::findOrFail($this->formData['id']);
            $deptDesignation->update($validatedData['formData']);
            $toastMsg = 'Department-Designation mapping updated successfully';
        } else {
            // Check if mapping already exists
            $exists = DepartmentsDesignation::where([
                'department_id' => $validatedData['formData']['department_id'],
                'designation_id' => $validatedData['formData']['designation_id'],
                'firm_id' => $validatedData['formData']['firm_id'],
            ])->exists();

            if ($exists) {
                Flux::toast(
                    variant: 'error',
                    heading: 'Error',
                    text: 'This department-designation mapping already exists.',
                );
                return;
            }

            DepartmentsDesignation::create($validatedData['formData']);
            $toastMsg = 'Department-Designation mapping added successfully';
        }

        $this->resetForm();
        $this->modal('mdl-department-designation')->close();
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
        $this->formData = DepartmentsDesignation::findOrFail($id)->toArray();
        $this->isEditing = true;
        $this->modal('mdl-department-designation')->show();
    }

    public function delete($id)
    {
        DepartmentsDesignation::findOrFail($id)->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Department-Designation mapping has been deleted successfully',
        );
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->isEditing = false;
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Settings/OnboardSettings/blades/departments-designations.blade.php'));
    }
} 