<?php

namespace App\Livewire\Settings\LocationHierarchy;

use App\Models\Settings\Country;
use App\Models\Settings\District;
use App\Models\Settings\State;
use App\Models\Settings\Subdivision;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\View;
use Illuminate\Database\Eloquent\Builder;
use Flux;

class Subdivisions extends Component
{
    use WithPagination;
    
    public $selectedSubdivisionId = null;
    public array $listsForFields = [];
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $statuses;
    public $formData = [
        'id' => null,
        'name' => '',
        'code' => '',
        'type' => '',
        'country_id' => '',
        'state_id' => '',
        'district_id' => '',
        'is_inactive' => 0,
    ];

    public $isEditing = false;
    public $modal = false;

    // Add filter properties
    public $filters = [
        'search_name' => '',
        'search_code' => '',
        'search_type' => '',
        'search_country' => '',
        'search_state' => '',
        'search_district' => '',
    ];

    public function mount()
    {
        $this->resetPage();
        $this->refreshStatuses();
        $this->getCountriesForSelect();
        $this->getStatesForSelect();
        $this->getDistrictsForSelect();
    }

    private function getCountriesForSelect()
    {
        $this->listsForFields['countries'] = Country::query()
            ->where('firm_id', Session::get('firm_id'))
            ->where('is_inactive', 0)
            ->pluck('name', 'id')
            ->map(fn($name, $id) => (string)$name)
            ->toArray();
    }

    private function getStatesForSelect()
    {
        $countryId = $this->formData['country_id'] ?? $this->filters['search_country'] ?? null;
        
        $query = State::query()
            ->where('firm_id', Session::get('firm_id'))
            ->where('is_inactive', 0);
            
        if ($countryId) {
            $query->where('country_id', $countryId);
        }
        
        $this->listsForFields['states'] = $query->pluck('name', 'id')
            ->map(fn($name, $id) => (string)$name)
            ->toArray();
    }

    private function getDistrictsForSelect()
    {
        $stateId = $this->formData['state_id'] ?? $this->filters['search_state'] ?? null;
        
        $query = District::query()
            ->where('firm_id', Session::get('firm_id'))
            ->where('is_inactive', 0);
            
        if ($stateId) {
            $query->where('state_id', $stateId);
        }
        
        $this->listsForFields['districts'] = $query->pluck('name', 'id')
            ->map(fn($name, $id) => (string)$name)
            ->toArray();
    }

    public function updatedFormDataCountryId()
    {
        $this->formData['state_id'] = '';
        $this->formData['district_id'] = '';
        $this->getStatesForSelect();
        $this->getDistrictsForSelect();
    }

    public function updatedFormDataStateId()
    {
        $this->formData['district_id'] = '';
        $this->getDistrictsForSelect();
    }

    public function updatedFiltersSearchCountry()
    {
        $this->filters['search_state'] = '';
        $this->filters['search_district'] = '';
        $this->getStatesForSelect();
        $this->getDistrictsForSelect();
    }

    public function updatedFiltersSearchState()
    {
        $this->filters['search_district'] = '';
        $this->getDistrictsForSelect();
    }

    #[Computed]
    public function list()
    {
        return Subdivision::query()
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->filters['search_name'], function($query) {
                $query->where('name', 'like', '%' . $this->filters['search_name'] . '%');
            })
            ->when($this->filters['search_code'], function($query) {
                $query->where('code', 'like', '%' . $this->filters['search_code'] . '%');
            })
            ->when($this->filters['search_type'], function($query) {
                $query->where('type', 'like', '%' . $this->filters['search_type'] . '%');
            })
            ->when($this->filters['search_district'], function($query) {
                $query->where('district_id', $this->filters['search_district']);
            })
            ->when($this->filters['search_state'], function($query) {
                $query->whereHas('district', function($q) {
                    $q->where('state_id', $this->filters['search_state']);
                });
            })
            ->when($this->filters['search_country'], function($query) {
                $query->whereHas('district.state', function($q) {
                    $q->where('country_id', $this->filters['search_country']);
                });
            })
            ->with(['district.state.country'])
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate(5);
    }

    public function store()
    {
        $validatedData = $this->validate([
            'formData.name' => 'required|string|max:255',
            'formData.code' => 'nullable|string|max:255',
            'formData.type' => 'nullable|string|max:255',
            'formData.district_id' => 'required|exists:districts,id',
            'formData.is_inactive' => 'boolean',
        ]);

        // Convert empty strings to null
        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        // Add firm_id from session
        $validatedData['formData']['firm_id'] = session('firm_id');

        if ($this->isEditing) {
            $subdivision = Subdivision::findOrFail($this->formData['id']);
            $subdivision->update($validatedData['formData']);
            $toastMsg = 'Subdivision updated successfully';
        } else {
            Subdivision::create($validatedData['formData']);
            $toastMsg = 'Subdivision added successfully';
        }

        $this->resetForm();
        $this->refreshStatuses();
        $this->modal('mdl-subdivision')->close();
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
        $subdivision = Subdivision::findOrFail($id);
        $this->formData = $subdivision->toArray();
        $this->formData['state_id'] = $subdivision->district->state_id;
        $this->formData['country_id'] = $subdivision->district->state->country_id;
        $this->getStatesForSelect();
        $this->getDistrictsForSelect();
        $this->isEditing = true;
        $this->modal('mdl-subdivision')->show();
    }

    public function delete($id)
    {
        // Check if subdivision has related records
        $subdivision = Subdivision::findOrFail($id);
        if ($subdivision->cities_or_villages()->count() > 0 || 
            $subdivision->employee_addresses()->count() > 0 || 
            $subdivision->joblocations()->count() > 0) {
            Flux::toast(
                variant: 'error',
                heading: 'Cannot Delete',
                text: 'This subdivision has related records and cannot be deleted.',
            );
            return;
        }

        $subdivision->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Subdivision has been deleted successfully',
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
        $this->statuses = Subdivision::where('firm_id', session('firm_id'))
            ->pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool)$val])
            ->toArray();
    }

    public function toggleStatus($subdivisionId)
    {
        $subdivision = Subdivision::find($subdivisionId);
        $subdivision->is_inactive = !$subdivision->is_inactive;
        $subdivision->save();

        $this->statuses[$subdivisionId] = $subdivision->is_inactive;
        $this->refreshStatuses();
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Settings/LocationHierarchy/blades/subdivisions.blade.php'));
    }
} 