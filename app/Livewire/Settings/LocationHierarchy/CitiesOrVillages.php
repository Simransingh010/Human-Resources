<?php

namespace App\Livewire\Settings\LocationHierarchy;

use App\Models\Settings\Country;
use App\Models\Settings\District;
use App\Models\Settings\State;
use App\Models\Settings\Subdivision;
use App\Models\Settings\CitiesOrVillage;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\View;
use Illuminate\Database\Eloquent\Builder;
use Flux;

class CitiesOrVillages extends Component
{
    use WithPagination;
    
    public $selectedCityId = null;
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
        'subdivision_id' => '',
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
        'search_subdivision' => '',
    ];

    public function mount()
    {
        $this->resetPage();
        $this->refreshStatuses();
        $this->initListsForFields();
    }

    protected function initListsForFields(): void
    {
        // Get Countries
        $this->listsForFields['countries'] = Country::query()
            ->where('firm_id', Session::get('firm_id'))
            ->where('is_inactive', 0)
            ->pluck('name', 'id')
            ->toArray();

        // Initialize empty arrays for dependent dropdowns
        $this->listsForFields['states'] = [];
        $this->listsForFields['districts'] = [];
        $this->listsForFields['subdivisions'] = [];
    }

    public function triggerUpdate($selectchanged = null)
    {
        if ($selectchanged == 'countrychanged') {
            $this->updateStates();
        } elseif ($selectchanged == 'statechanged') {
            $this->updateDistricts();
        } elseif ($selectchanged == 'districtchanged') {
            $this->updateSubdivisions();
        }
    }

    private function updateStates()
    {
        $this->listsForFields['states'] = State::query()
            ->where('firm_id', Session::get('firm_id'))
            ->where('is_inactive', 0)
            ->when($this->formData['country_id'], fn($q) => $q->where('country_id', $this->formData['country_id']))
            ->pluck('name', 'id')
            ->toArray();
    }

    private function updateDistricts()
    {
        $this->listsForFields['districts'] = District::query()
            ->where('firm_id', Session::get('firm_id'))
            ->where('is_inactive', 0)
            ->when($this->formData['state_id'], fn($q) => $q->where('state_id', $this->formData['state_id']))
            ->pluck('name', 'id')
            ->toArray();
    }

    private function updateSubdivisions()
    {
        $this->listsForFields['subdivisions'] = Subdivision::query()
            ->where('firm_id', Session::get('firm_id'))
            ->where('is_inactive', 0)
            ->when($this->formData['district_id'], fn($q) => $q->where('district_id', $this->formData['district_id']))
            ->pluck('name', 'id')
            ->toArray();
    }

    public function updatedFormDataCountryId()
    {
        $this->formData['state_id'] = '';
        $this->formData['district_id'] = '';
        $this->formData['subdivision_id'] = '';
        $this->triggerUpdate('countrychanged');
    }

    public function updatedFormDataStateId()
    {
        $this->formData['district_id'] = '';
        $this->formData['subdivision_id'] = '';
        $this->triggerUpdate('statechanged');
    }

    public function updatedFormDataDistrictId()
    {
        $this->formData['subdivision_id'] = '';
        $this->triggerUpdate('districtchanged');
    }

    public function updatedFiltersSearchCountry()
    {
        $this->filters['search_state'] = '';
        $this->filters['search_district'] = '';
        $this->filters['search_subdivision'] = '';
        $this->formData['country_id'] = $this->filters['search_country'];
        $this->triggerUpdate('countrychanged');
    }

    public function updatedFiltersSearchState()
    {
        $this->filters['search_district'] = '';
        $this->filters['search_subdivision'] = '';
        $this->formData['state_id'] = $this->filters['search_state'];
        $this->triggerUpdate('statechanged');
    }

    public function updatedFiltersSearchDistrict()
    {
        $this->filters['search_subdivision'] = '';
        $this->formData['district_id'] = $this->filters['search_district'];
        $this->triggerUpdate('districtchanged');
    }

    #[Computed]
    public function list()
    {
        return CitiesOrVillage::query()
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
            ->when($this->filters['search_subdivision'], function($query) {
                $query->where('subdivision_id', $this->filters['search_subdivision']);
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
            ->with(['district.state.country', 'subdivision'])
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
            'formData.subdivision_id' => 'nullable|exists:subdivisions,id',
            'formData.is_inactive' => 'boolean',
        ]);

        // Convert empty strings to null
        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        // Add firm_id from session
        $validatedData['formData']['firm_id'] = session('firm_id');

        if ($this->isEditing) {
            $city = CitiesOrVillage::findOrFail($this->formData['id']);
            $city->update($validatedData['formData']);
            $toastMsg = 'City/Village updated successfully';
        } else {
            CitiesOrVillage::create($validatedData['formData']);
            $toastMsg = 'City/Village added successfully';
        }

        $this->resetForm();
        $this->refreshStatuses();
        $this->modal('mdl-city')->close();
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
        $city = CitiesOrVillage::findOrFail($id);
        $this->formData = $city->toArray();
        $this->formData['state_id'] = $city->district->state_id;
        $this->formData['country_id'] = $city->district->state->country_id;
        $this->getStatesForSelect();
        $this->getDistrictsForSelect();
        $this->getSubdivisionsForSelect();
        $this->isEditing = true;
        $this->modal('mdl-city')->show();
    }

    public function delete($id)
    {
        // Check if city/village has related records
        $city = CitiesOrVillage::findOrFail($id);
        if ($city->employee_addresses()->count() > 0 || 
            $city->joblocations()->count() > 0 ||
            $city->postoffices()->count() > 0) {
            Flux::toast(
                variant: 'error',
                heading: 'Cannot Delete',
                text: 'This city/village has related records and cannot be deleted.',
            );
            return;
        }

        $city->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'City/Village has been deleted successfully',
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
        $this->statuses = CitiesOrVillage::where('firm_id', session('firm_id'))
            ->pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool)$val])
            ->toArray();
    }

    public function toggleStatus($cityId)
    {
        $city = CitiesOrVillage::find($cityId);
        $city->is_inactive = !$city->is_inactive;
        $city->save();

        $this->statuses[$cityId] = $city->is_inactive;
        $this->refreshStatuses();
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Settings/LocationHierarchy/blades/cities-or-villages.blade.php'));
    }
} 