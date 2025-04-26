<?php

namespace App\Livewire\Settings\LocationHierarchy;
use App\Models\Settings\CitiesOrVillage;
use App\Models\Settings\Country;
use App\Models\Settings\District;
use App\Models\Settings\Postoffice;
use App\Models\Settings\State;
use App\Models\Settings\Subdivision;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
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
        $this->listsForFields['cities_or_village'] = [];
    }

    public function triggerUpdate($selectchanged = null)
    {
        if ($selectchanged == 'countrychanged') {
            $this->updateStates();
        } elseif ($selectchanged == 'statechanged') {
            $this->updateDistricts();
        } elseif ($selectchanged == 'districtchanged') {
            $this->updateSubdivisions();
        } elseif ($selectchanged == 'subdivisionchanged') {
            $this->updateCities();
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

    private function updateCities()
    {
        $this->listsForFields['cities_or_village'] = CitiesOrVillage::query()
            ->where('firm_id', Session::get('firm_id'))
            ->where('is_inactive', 0)
            ->when($this->formData['subdivision_id'], fn($q) => $q->where('subdivision_id', $this->formData['subdivision_id']))
            ->pluck('name', 'id')
            ->toArray();
    }

    public function updatedFormDataCountryId()
    {
        $this->formData['state_id'] = '';
        $this->formData['district_id'] = '';
        $this->formData['subdivision_id'] = '';
        $this->formData['city_or_village_id'] = '';
        $this->triggerUpdate('countrychanged');
    }

    public function updatedFormDataStateId()
    {
        $this->formData['district_id'] = '';
        $this->formData['subdivision_id'] = '';
        $this->formData['city_or_village_id'] = '';
        $this->triggerUpdate('statechanged');
    }

    public function updatedFormDataDistrictId()
    {
        $this->formData['subdivision_id'] = '';
        $this->formData['city_or_village_id'] = '';
        $this->triggerUpdate('districtchanged');
    }

    public function updatedFormDataSubdivisionId()
    {
        $this->formData['city_or_village_id'] = '';
        $this->triggerUpdate('subdivisionchanged');
    }

    public function updatedFiltersSearchCountry()
    {
        $this->filters['search_state'] = '';
        $this->filters['search_district'] = '';
        $this->filters['search_subdivision'] = '';
        $this->filters['search_city'] = '';
        $this->formData['country_id'] = $this->filters['search_country'];
        $this->triggerUpdate('countrychanged');
    }

    public function updatedFiltersSearchState()
    {
        $this->filters['search_district'] = '';
        $this->filters['search_subdivision'] = '';
        $this->filters['search_city'] = '';
        $this->formData['state_id'] = $this->filters['search_state'];
        $this->triggerUpdate('statechanged');
    }

    public function updatedFiltersSearchDistrict()
    {
        $this->filters['search_subdivision'] = '';
        $this->filters['search_city'] = '';
        $this->formData['district_id'] = $this->filters['search_district'];
        $this->triggerUpdate('districtchanged');
    }

    public function updatedFiltersSearchSubdivision()
    {
        $this->filters['search_city'] = '';
        $this->formData['subdivision_id'] = $this->filters['search_subdivision'];
        $this->triggerUpdate('subdivisionchanged');
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


        $this->triggerUpdate('countrychanged');
        $this->triggerUpdate('statechanged');
        $this->triggerUpdate('districtchanged');
        $this->triggerUpdate('subdivisionchanged');

        $this->isEditing = true;
        $this->modal('mdl-postoffice')->show();
    }

    public function delete($id)
    {

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