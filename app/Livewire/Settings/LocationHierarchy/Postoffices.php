<?php

namespace App\Livewire\Settings\LocationHierarchy;

use App\Models\Settings\Country;
use App\Models\Settings\District;
use App\Models\Settings\State;
use App\Models\Settings\Subdivision;
use App\Models\Settings\CitiesOrVillage;
use App\Models\Settings\Postoffice;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\View;
use Illuminate\Database\Eloquent\Builder;
use Flux;

class Postoffices extends Component
{
    use WithPagination;
    
    public $selectedPostofficeId = null;
    public array $listsForFields = [];
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $statuses;
    public $formData = [
        'id' => null,
        'name' => '',
        'code' => '',
        'pincode' => '',
        'country_id' => '',
        'state_id' => '',
        'district_id' => '',
        'subdivision_id' => '',
        'city_or_village_id' => '',
        'is_inactive' => 0,
    ];

    public $isEditing = false;
    public $modal = false;

    // Add filter properties
    public $filters = [
        'search_name' => '',
        'search_code' => '',
        'search_pincode' => '',
        'search_country' => '',
        'search_state' => '',
        'search_district' => '',
        'search_subdivision' => '',
        'search_city' => '',
    ];

    public function mount()
    {
        $this->resetPage();
        $this->refreshStatuses();
        $this->getCountriesForSelect();
        $this->getStatesForSelect();
        $this->getDistrictsForSelect();
        $this->getSubdivisionsForSelect();
        $this->getCitiesForSelect();
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

    private function getSubdivisionsForSelect()
    {
        $districtId = $this->formData['district_id'] ?? $this->filters['search_district'] ?? null;
        
        $query = Subdivision::query()
            ->where('firm_id', Session::get('firm_id'))
            ->where('is_inactive', 0);
            
        if ($districtId) {
            $query->where('district_id', $districtId);
        }
        
        $this->listsForFields['subdivisions'] = $query->pluck('name', 'id')
            ->map(fn($name, $id) => (string)$name)
            ->toArray();
    }

    private function getCitiesForSelect()
    {
        $districtId = $this->formData['district_id'] ?? $this->filters['search_district'] ?? null;
        $subdivisionId = $this->formData['subdivision_id'] ?? $this->filters['search_subdivision'] ?? null;
        
        $query = CitiesOrVillage::query()
            ->where('firm_id', Session::get('firm_id'))
            ->where('is_inactive', 0);
            
        if ($districtId) {
            $query->where('district_id', $districtId);
        }
        
        if ($subdivisionId) {
            $query->where('subdivision_id', $subdivisionId);
        }
        
        $this->listsForFields['cities'] = $query->pluck('name', 'id')
            ->map(fn($name, $id) => (string)$name)
            ->toArray();
    }

    public function updatedFormDataCountryId()
    {
        $this->formData['state_id'] = '';
        $this->formData['district_id'] = '';
        $this->formData['subdivision_id'] = '';
        $this->formData['city_or_village_id'] = '';
        $this->getStatesForSelect();
        $this->getDistrictsForSelect();
        $this->getSubdivisionsForSelect();
        $this->getCitiesForSelect();
    }

    public function updatedFormDataStateId()
    {
        $this->formData['district_id'] = '';
        $this->formData['subdivision_id'] = '';
        $this->formData['city_or_village_id'] = '';
        $this->getDistrictsForSelect();
        $this->getSubdivisionsForSelect();
        $this->getCitiesForSelect();
    }

    public function updatedFormDataDistrictId()
    {
        $this->formData['subdivision_id'] = '';
        $this->formData['city_or_village_id'] = '';
        $this->getSubdivisionsForSelect();
        $this->getCitiesForSelect();
    }

    public function updatedFormDataSubdivisionId()
    {
        $this->formData['city_or_village_id'] = '';
        $this->getCitiesForSelect();
    }

    public function updatedFiltersSearchCountry()
    {
        $this->filters['search_state'] = '';
        $this->filters['search_district'] = '';
        $this->filters['search_subdivision'] = '';
        $this->filters['search_city'] = '';
        $this->getStatesForSelect();
        $this->getDistrictsForSelect();
        $this->getSubdivisionsForSelect();
        $this->getCitiesForSelect();
    }

    public function updatedFiltersSearchState()
    {
        $this->filters['search_district'] = '';
        $this->filters['search_subdivision'] = '';
        $this->filters['search_city'] = '';
        $this->getDistrictsForSelect();
        $this->getSubdivisionsForSelect();
        $this->getCitiesForSelect();
    }

    public function updatedFiltersSearchDistrict()
    {
        $this->filters['search_subdivision'] = '';
        $this->filters['search_city'] = '';
        $this->getSubdivisionsForSelect();
        $this->getCitiesForSelect();
    }

    public function updatedFiltersSearchSubdivision()
    {
        $this->filters['search_city'] = '';
        $this->getCitiesForSelect();
    }

    #[Computed]
    public function list()
    {
        return Postoffice::query()
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->filters['search_name'], function($query) {
                $query->where('name', 'like', '%' . $this->filters['search_name'] . '%');
            })
            ->when($this->filters['search_code'], function($query) {
                $query->where('code', 'like', '%' . $this->filters['search_code'] . '%');
            })
            ->when($this->filters['search_pincode'], function($query) {
                $query->where('pincode', 'like', '%' . $this->filters['search_pincode'] . '%');
            })
            ->when($this->filters['search_city'], function($query) {
                $query->where('city_or_village_id', $this->filters['search_city']);
            })
            ->when($this->filters['search_subdivision'], function($query) {
                $query->whereHas('cities_or_village', function($q) {
                    $q->where('subdivision_id', $this->filters['search_subdivision']);
                });
            })
            ->when($this->filters['search_district'], function($query) {
                $query->whereHas('cities_or_village', function($q) {
                    $q->where('district_id', $this->filters['search_district']);
                });
            })
            ->when($this->filters['search_state'], function($query) {
                $query->whereHas('cities_or_village.district', function($q) {
                    $q->where('state_id', $this->filters['search_state']);
                });
            })
            ->when($this->filters['search_country'], function($query) {
                $query->whereHas('cities_or_village.district.state', function($q) {
                    $q->where('country_id', $this->filters['search_country']);
                });
            })
            ->with(['cities_or_village.district.state.country', 'cities_or_village.subdivision'])
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate(5);
    }

    public function store()
    {
        $validatedData = $this->validate([
            'formData.name' => 'required|string|max:255',
            'formData.code' => 'nullable|string|max:255',
            'formData.pincode' => 'nullable|string|max:255',
            'formData.city_or_village_id' => 'required|exists:cities_or_villages,id',
            'formData.is_inactive' => 'boolean',
        ]);

        // Convert empty strings to null
        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        // Add firm_id from session
        $validatedData['formData']['firm_id'] = session('firm_id');

        if ($this->isEditing) {
            $postoffice = Postoffice::findOrFail($this->formData['id']);
            $postoffice->update($validatedData['formData']);
            $toastMsg = 'Post Office updated successfully';
        } else {
            Postoffice::create($validatedData['formData']);
            $toastMsg = 'Post Office added successfully';
        }

        $this->resetForm();
        $this->refreshStatuses();
        $this->modal('mdl-postoffice')->close();
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
        $postoffice = Postoffice::findOrFail($id);
        $this->formData = $postoffice->toArray();
        $this->formData['district_id'] = $postoffice->cities_or_village->district_id;
        $this->formData['state_id'] = $postoffice->cities_or_village->district->state_id;
        $this->formData['country_id'] = $postoffice->cities_or_village->district->state->country_id;
        $this->formData['subdivision_id'] = $postoffice->cities_or_village->subdivision_id;
        $this->getStatesForSelect();
        $this->getDistrictsForSelect();
        $this->getSubdivisionsForSelect();
        $this->getCitiesForSelect();
        $this->isEditing = true;
        $this->modal('mdl-postoffice')->show();
    }

    public function delete($id)
    {
        // Check if post office has related records
        $postoffice = Postoffice::findOrFail($id);
        if ($postoffice->employee_addresses()->count() > 0 || 
            $postoffice->joblocations()->count() > 0) {
            Flux::toast(
                variant: 'error',
                heading: 'Cannot Delete',
                text: 'This post office has related records and cannot be deleted.',
            );
            return;
        }

        $postoffice->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Post Office has been deleted successfully',
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
        $this->statuses = Postoffice::where('firm_id', session('firm_id'))
            ->pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool)$val])
            ->toArray();
    }

    public function toggleStatus($postofficeId)
    {
        $postoffice = Postoffice::find($postofficeId);
        $postoffice->is_inactive = !$postoffice->is_inactive;
        $postoffice->save();

        $this->statuses[$postofficeId] = $postoffice->is_inactive;
        $this->refreshStatuses();
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Settings/LocationHierarchy/blades/postoffices.blade.php'));
    }
} 