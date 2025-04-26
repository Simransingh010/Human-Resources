<?php

namespace App\Livewire\Settings\OnboardSettings;

use App\Models\Settings\EmploymentType;
use Livewire\Component;
use Livewire\WithPagination;
use Flux;

class EmploymentTypes extends Component
{
    use WithPagination;
    
    public $selectedEmploymentTypeId = null;
    public array $listsForFields = [];
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $statuses;
    public $formData = [
        'id' => null,
        'title' => '',
        'code' => '',
        'description' => '',
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
    }

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        return EmploymentType::query()
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
            'formData.code' => 'required|string|max:255',
            'formData.description' => 'nullable|string',
            'formData.is_inactive' => 'boolean',
        ],
        [
            'formData.title.required' => 'Required.',
            'formData.title.max' => 'The Title may not be greater than :max characters.',
            'formData.code.required' => 'Required.',
            'formData.code.max' => 'The Code may not be greater than :max characters.',
        ]
        );

        // Convert empty strings to null
        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        // Add firm_id from session
        $validatedData['formData']['firm_id'] = session('firm_id');

        if ($this->isEditing) {
            $employmentType = EmploymentType::findOrFail($this->formData['id']);
            $employmentType->update($validatedData['formData']);
            $toastMsg = 'Employment Type updated successfully';
        } else {
            EmploymentType::create($validatedData['formData']);
            $toastMsg = 'Employment Type added successfully';
        }

        $this->resetForm();
        $this->refreshStatuses();
        $this->modal('mdl-employment-type')->close();
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
        $this->formData = EmploymentType::findOrFail($id)->toArray();
        $this->isEditing = true;
        $this->modal('mdl-employment-type')->show();
    }

    public function delete($id)
    {
        EmploymentType::findOrFail($id)->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Employment Type has been deleted successfully',
        );
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['is_inactive'] = 0;
        $this->isEditing = false;
    }

    public function refreshStatuses()
    {
        $this->statuses = EmploymentType::where('firm_id', session('firm_id'))
            ->pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool)$val])
            ->toArray();
    }

    public function toggleStatus($employmentTypeId)
    {
        $employmentType = EmploymentType::find($employmentTypeId);
        $employmentType->is_inactive = !$employmentType->is_inactive;
        $employmentType->save();

        $this->statuses[$employmentTypeId] = $employmentType->is_inactive;
        $this->refreshStatuses();
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Settings/OnboardSettings/blades/employment-types.blade.php'));
    }
} 