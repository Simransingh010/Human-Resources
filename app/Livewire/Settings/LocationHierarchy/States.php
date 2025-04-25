<?php

namespace App\Livewire\Settings\LocationHierarchy;

use App\Models\Settings\State;
use App\Models\Settings\Country;
use Livewire\Component;
use Livewire\WithPagination;
use Flux;

class States extends Component
{
    use WithPagination;
    
    public $selectedStateId = null;
    public array $listsForFields = [];
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $statuses;
    public $formData = [
        'id' => null,
        'name' => '',
        'code' => '',
        'country_id' => '',
        'is_inactive' => 0,
    ];

    public $isEditing = false;
    public $modal = false;

    // Add filter properties
    public $filters = [
        'search_name' => '',
        'search_code' => '',
        'search_country' => '',
    ];

    public function mount()
    {
        $this->resetPage();
        $this->refreshStatuses();
        $this->getCountriesForSelect();
    }

    private function getCountriesForSelect()
    {
        $this->listsForFields['countries'] = Country::where('firm_id', session('firm_id'))
            ->where('is_inactive', 0)
            ->pluck('name', 'id')
            ->toArray();
    }

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        return State::query()
            ->where('firm_id', session('firm_id'))
            ->when($this->filters['search_name'], function($query) {
                $query->where('name', 'like', '%' . $this->filters['search_name'] . '%');
            })
            ->when($this->filters['search_code'], function($query) {
                $query->where('code', 'like', '%' . $this->filters['search_code'] . '%');
            })
            ->when($this->filters['search_country'], function($query) {
                $query->where('country_id', $this->filters['search_country']);
            })
            ->with('country')
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate(5);
    }

    public function store()
    {
        $validatedData = $this->validate([
            'formData.name' => 'required|string|max:255',
            'formData.code' => 'nullable|string|max:255',
            'formData.country_id' => 'required|exists:countries,id',
            'formData.is_inactive' => 'boolean',
        ]);

        // Convert empty strings to null
        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        // Add firm_id from session
        $validatedData['formData']['firm_id'] = session('firm_id');

        if ($this->isEditing) {
            $state = State::findOrFail($this->formData['id']);
            $state->update($validatedData['formData']);
            $toastMsg = 'State updated successfully';
        } else {
            State::create($validatedData['formData']);
            $toastMsg = 'State added successfully';
        }

        $this->resetForm();
        $this->refreshStatuses();
        $this->modal('mdl-state')->close();
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
        $this->formData = State::findOrFail($id)->toArray();
        $this->isEditing = true;
        $this->modal('mdl-state')->show();
    }

    public function delete($id)
    {
        // Check if state has related records
        $state = State::findOrFail($id);
        if ($state->districts()->count() > 0 || 
            $state->employee_addresses()->count() > 0 || 
            $state->joblocations()->count() > 0) {
            Flux::toast(
                variant: 'error',
                heading: 'Cannot Delete',
                text: 'This state has related records and cannot be deleted.',
            );
            return;
        }

        $state->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'State has been deleted successfully',
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
        $this->statuses = State::where('firm_id', session('firm_id'))
            ->pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool)$val])
            ->toArray();
    }

    public function toggleStatus($stateId)
    {
        $state = State::find($stateId);
        $state->is_inactive = !$state->is_inactive;
        $state->save();

        $this->statuses[$stateId] = $state->is_inactive;
        $this->refreshStatuses();
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Settings/LocationHierarchy/blades/states.blade.php'));
    }
} 