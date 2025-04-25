<?php

namespace App\Livewire\Settings\OnboardSettings;

use App\Models\Settings\Joblocation;
use App\Models\Settings\Country;
use App\Models\Settings\State;
use App\Models\Settings\District;
use App\Models\Settings\Subdivision;
use App\Models\Settings\CitiesOrVillage;
use App\Models\Settings\Postoffice;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use Livewire\WithPagination;
use Flux;

class Joblocations extends Component
{
    use WithPagination;
    
    public $selectedJoblocationId = null;
    public array $listsForFields = [];
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $statuses;
    public $formData = [
        'id' => null,
        'name' => '',
        'code' => '',
        'description' => '',
        'parent_joblocation_id' => '',
        'country_id' => '',
        'state_id' => '',
        'district_id' => '',
        'subdivision_id' => '',
        'city_or_village_id' => '',
        'postoffice_id' => '',
        'is_inactive' => 0,
    ];

    public $isEditing = false;
    public $modal = false;

    // Add filter properties
    public $filters = [
        'search_name' => '',
        'search_code' => '',
        'search_parent' => '',
        'search_country' => '',
        'search_state' => '',
        'search_district' => '',
        'search_subdivision' => '',
        'search_city' => '',
        'search_postoffice' => '',
    ];

    public function mount()
    {
        $this->resetPage();
        $this->refreshStatuses();
        $this->getJoblocationsForSelect();
        $this->getCountriesForSelect();
        $this->getStatesForSelect();
        $this->getDistrictsForSelect();
        $this->getSubdivisionsForSelect();
        $this->getCitiesForSelect();
        $this->getPostofficesForSelect();
    }

    private function getJoblocationsForSelect()
    {
        $this->listsForFields['joblocations'] = Joblocation::query()
            ->where('firm_id', Session::get('firm_id'))
            ->where('is_inactive', 0)
            ->pluck('name', 'id')
            ->map(fn($name, $id) => (string)$name)
            ->toArray();
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

    private function getPostofficesForSelect()
    {
        $cityId = $this->formData['city_or_village_id'] ?? $this->filters['search_city'] ?? null;
        
        $query = Postoffice::query()
            ->where('firm_id', Session::get('firm_id'))
            ->where('is_inactive', 0);
            
        if ($cityId) {
            $query->where('city_or_village_id', $cityId);
        }
        
        $this->listsForFields['postoffices'] = $query->pluck('name', 'id')
            ->map(fn($name, $id) => (string)$name)
            ->toArray();
    }

    public function updatedFormDataCountryId()
    {
        $this->formData['state_id'] = '';
        $this->formData['district_id'] = '';
        $this->formData['subdivision_id'] = '';
        $this->formData['city_or_village_id'] = '';
        $this->formData['postoffice_id'] = '';
        $this->getStatesForSelect();
        $this->getDistrictsForSelect();
        $this->getSubdivisionsForSelect();
        $this->getCitiesForSelect();
        $this->getPostofficesForSelect();
    }

    public function updatedFormDataStateId()
    {
        $this->formData['district_id'] = '';
        $this->formData['subdivision_id'] = '';
        $this->formData['city_or_village_id'] = '';
        $this->formData['postoffice_id'] = '';
        $this->getDistrictsForSelect();
        $this->getSubdivisionsForSelect();
        $this->getCitiesForSelect();
        $this->getPostofficesForSelect();
    }

    public function updatedFormDataDistrictId()
    {
        $this->formData['subdivision_id'] = '';
        $this->formData['city_or_village_id'] = '';
        $this->formData['postoffice_id'] = '';
        $this->getSubdivisionsForSelect();
        $this->getCitiesForSelect();
        $this->getPostofficesForSelect();
    }

    public function updatedFormDataSubdivisionId()
    {
        $this->formData['city_or_village_id'] = '';
        $this->formData['postoffice_id'] = '';
        $this->getCitiesForSelect();
        $this->getPostofficesForSelect();
    }

    public function updatedFormDataCityOrVillageId()
    {
        $this->formData['postoffice_id'] = '';
        $this->getPostofficesForSelect();
    }

    public function updatedFiltersSearchCountry()
    {
        $this->filters['search_state'] = '';
        $this->filters['search_district'] = '';
        $this->filters['search_subdivision'] = '';
        $this->filters['search_city'] = '';
        $this->filters['search_postoffice'] = '';
        $this->getStatesForSelect();
        $this->getDistrictsForSelect();
        $this->getSubdivisionsForSelect();
        $this->getCitiesForSelect();
        $this->getPostofficesForSelect();
    }

    public function updatedFiltersSearchState()
    {
        $this->filters['search_district'] = '';
        $this->filters['search_subdivision'] = '';
        $this->filters['search_city'] = '';
        $this->filters['search_postoffice'] = '';
        $this->getDistrictsForSelect();
        $this->getSubdivisionsForSelect();
        $this->getCitiesForSelect();
        $this->getPostofficesForSelect();
    }

    public function updatedFiltersSearchDistrict()
    {
        $this->filters['search_subdivision'] = '';
        $this->filters['search_city'] = '';
        $this->filters['search_postoffice'] = '';
        $this->getSubdivisionsForSelect();
        $this->getCitiesForSelect();
        $this->getPostofficesForSelect();
    }

    public function updatedFiltersSearchSubdivision()
    {
        $this->filters['search_city'] = '';
        $this->filters['search_postoffice'] = '';
        $this->getCitiesForSelect();
        $this->getPostofficesForSelect();
    }

    public function updatedFiltersSearchCity()
    {
        $this->filters['search_postoffice'] = '';
        $this->getPostofficesForSelect();
    }

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        return Joblocation::query()
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->filters['search_name'], function($query) {
                $query->where('name', 'like', '%' . $this->filters['search_name'] . '%');
            })
            ->when($this->filters['search_code'], function($query) {
                $query->where('code', 'like', '%' . $this->filters['search_code'] . '%');
            })
            ->when($this->filters['search_parent'], function($query) {
                $query->where('parent_joblocation_id', $this->filters['search_parent']);
            })
            ->when($this->filters['search_postoffice'], function($query) {
                $query->where('postoffice_id', $this->filters['search_postoffice']);
            })
            ->when($this->filters['search_city'], function($query) {
                $query->where('city_or_village_id', $this->filters['search_city']);
            })
            ->when($this->filters['search_subdivision'], function($query) {
                $query->where('subdivision_id', $this->filters['search_subdivision']);
            })
            ->when($this->filters['search_district'], function($query) {
                $query->where('district_id', $this->filters['search_district']);
            })
            ->when($this->filters['search_state'], function($query) {
                $query->where('state_id', $this->filters['search_state']);
            })
            ->when($this->filters['search_country'], function($query) {
                $query->where('country_id', $this->filters['search_country']);
            })
            ->with([
                'joblocation',
                'country',
                'state',
                'district',
                'subdivision',
                'cities_or_village',
                'postoffice'
            ])
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate(5);
    }

    public function store()
    {
        $validatedData = $this->validate([
            'formData.name' => 'required|string|max:255',
            'formData.code' => 'nullable|string|max:255',
            'formData.description' => 'nullable|string',
            'formData.parent_joblocation_id' => 'nullable|exists:joblocations,id',
            'formData.country_id' => 'nullable|exists:countries,id',
            'formData.state_id' => 'nullable|exists:states,id',
            'formData.district_id' => 'nullable|exists:districts,id',
            'formData.subdivision_id' => 'nullable|exists:subdivisions,id',
            'formData.city_or_village_id' => 'nullable|exists:cities_or_villages,id',
            'formData.postoffice_id' => 'nullable|exists:postoffices,id',
            'formData.is_inactive' => 'boolean',
        ]);

        // Convert empty strings to null
        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        // Add firm_id from session
        $validatedData['formData']['firm_id'] = session('firm_id');

        if ($this->isEditing) {
            $joblocation = Joblocation::findOrFail($this->formData['id']);
            $joblocation->update($validatedData['formData']);
            $toastMsg = 'Job Location updated successfully';
        } else {
            Joblocation::create($validatedData['formData']);
            $toastMsg = 'Job Location added successfully';
        }

        $this->resetForm();
        $this->refreshStatuses();
        $this->modal('mdl-joblocation')->close();
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
        $joblocation = Joblocation::findOrFail($id);
        $this->formData = $joblocation->toArray();
        $this->getStatesForSelect();
        $this->getDistrictsForSelect();
        $this->getSubdivisionsForSelect();
        $this->getCitiesForSelect();
        $this->getPostofficesForSelect();
        $this->isEditing = true;
        $this->modal('mdl-joblocation')->show();
    }

    public function delete($id)
    {
        // Check if job location has related records
        $joblocation = Joblocation::findOrFail($id);
        if ($joblocation->employee_job_profiles()->count() > 0 || 
            $joblocation->joblocations()->count() > 0) {
            Flux::toast(
                variant: 'error',
                heading: 'Cannot Delete',
                text: 'This job location has related records and cannot be deleted.',
            );
            return;
        }

        $joblocation->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Job Location has been deleted successfully',
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
        $this->statuses = Joblocation::where('firm_id', session('firm_id'))
            ->pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool)$val])
            ->toArray();
    }

    public function toggleStatus($joblocationId)
    {
        $joblocation = Joblocation::find($joblocationId);
        $joblocation->is_inactive = !$joblocation->is_inactive;
        $joblocation->save();

        $this->statuses[$joblocationId] = $joblocation->is_inactive;
        $this->refreshStatuses();
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Settings/OnboardSettings/blades/joblocations.blade.php'));
    }
} 