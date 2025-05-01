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
    public array $filterLists = [];
    public array $createFormLists = [];
    public array $editFormLists = [];
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
        
        // Initialize all list arrays with empty collections
        $this->filterLists = [
            'countrieslist' => collect(),
            'states' => collect(),
            'districts' => collect(),
            'subdivisions' => collect()
        ];
        
        $this->createFormLists = [
            'countrieslist' => collect(),
            'states' => collect(),
            'districts' => collect(),
            'subdivisions' => collect()
        ];
        
        $this->editFormLists = [
            'countrieslist' => collect(),
            'states' => collect(),
            'districts' => collect(),
            'subdivisions' => collect()
        ];
        
        $this->initListsForFields();
    }

    public function triggerUpdate($selectchanged = null)
    {
        if ($selectchanged == 'countrychanged') {
            $this->formData['state_id'] = '';
            $this->formData['district_id'] = '';
            $this->formData['subdivision_id'] = '';
            $this->getStatesForForm();
            if ($this->isEditing) {
                $this->editFormLists['districts'] = collect();
                $this->editFormLists['subdivisions'] = collect();
            } else {
                $this->createFormLists['districts'] = collect();
                $this->createFormLists['subdivisions'] = collect();
            }
        } elseif ($selectchanged == 'statechanged') {
            $this->formData['district_id'] = '';
            $this->formData['subdivision_id'] = '';
            $this->getDistrictsForForm();
            if ($this->isEditing) {
                $this->editFormLists['subdivisions'] = collect();
            } else {
                $this->createFormLists['subdivisions'] = collect();
            }
        } elseif ($selectchanged == 'districtchanged') {
            $this->formData['subdivision_id'] = '';
            $this->getSubdivisionsForForm();
        }
    }

    public function triggerFilterUpdate($selectchanged = null)
    {
        if ($selectchanged == 'countrychanged') {
            $this->filters['search_state'] = '';
            $this->filters['search_district'] = '';
            $this->filters['search_subdivision'] = '';
            $this->getStatesForFilter();
            $this->filterLists['districts'] = collect();
            $this->filterLists['subdivisions'] = collect();
        } elseif ($selectchanged == 'statechanged') {
            $this->filters['search_district'] = '';
            $this->filters['search_subdivision'] = '';
            $this->getDistrictsForFilter();
            $this->filterLists['subdivisions'] = collect();
        } elseif ($selectchanged == 'districtchanged') {
            $this->filters['search_subdivision'] = '';
            $this->getSubdivisionsForFilter();
        }
    }

    protected function initListsForFields(): void
    {
        $countries = Country::where('firm_id', session('firm_id'))
            ->pluck('name', 'id');

        $this->filterLists['countrieslist'] = $countries;
        $this->createFormLists['countrieslist'] = $countries;
        $this->editFormLists['countrieslist'] = $countries;

        // Initialize states and districts for filters if they are set
        if ($this->filters['search_country']) {
            $this->getStatesForFilter();
        }
        if ($this->filters['search_state']) {
            $this->getDistrictsForFilter();
        }
        if ($this->filters['search_district']) {
            $this->getSubdivisionsForFilter();
        }

        // Initialize states and districts for form if they are set
        if ($this->formData['country_id']) {
            $this->getStatesForForm();
        }
        if ($this->formData['state_id']) {
            $this->getDistrictsForForm();
        }
        if ($this->formData['district_id']) {
            $this->getSubdivisionsForForm();
        }
    }

    // Functions for Form Dropdowns
    private function getStatesForForm()
    {
        $countryId = $this->formData['country_id'] ?? null;

        if ($countryId) {
            $states = State::where('firm_id', session('firm_id'))
                ->where('country_id', $countryId)
                ->pluck('name', 'id');

            if ($this->isEditing) {
                $this->editFormLists['states'] = $states;
            } else {
                $this->createFormLists['states'] = $states;
            }
        }
    }

    private function getDistrictsForForm()
    {
        $stateId = $this->formData['state_id'] ?? null;

        if ($stateId) {
            $districts = District::where('firm_id', session('firm_id'))
                ->where('state_id', $stateId)
                ->pluck('name', 'id');

            if ($this->isEditing) {
                $this->editFormLists['districts'] = $districts;
            } else {
                $this->createFormLists['districts'] = $districts;
            }
        }
    }

    private function getSubdivisionsForForm()
    {
        $districtId = $this->formData['district_id'] ?? null;

        if ($districtId) {
            $subdivisions = Subdivision::where('firm_id', session('firm_id'))
                ->where('district_id', $districtId)
                ->pluck('name', 'id');

            if ($this->isEditing) {
                $this->editFormLists['subdivisions'] = $subdivisions;
            } else {
                $this->createFormLists['subdivisions'] = $subdivisions;
            }
        }
    }

    // Functions for Filter Dropdowns
    private function getStatesForFilter()
    {
        $countryId = $this->filters['search_country'] ?? null;

        $this->filterLists['states'] = $countryId
            ? State::where('firm_id', session('firm_id'))
                ->where('country_id', $countryId)
                ->pluck('name', 'id')
            : collect();
    }

    private function getDistrictsForFilter()
    {
        $stateId = $this->filters['search_state'] ?? null;

        $this->filterLists['districts'] = $stateId
            ? District::where('firm_id', session('firm_id'))
                ->where('state_id', $stateId)
                ->pluck('name', 'id')
            : collect();
    }

    private function getSubdivisionsForFilter()
    {
        $districtId = $this->filters['search_district'] ?? null;

        $this->filterLists['subdivisions'] = $districtId
            ? Subdivision::where('firm_id', session('firm_id'))
                ->where('district_id', $districtId)
                ->pluck('name', 'id')
            : collect();
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
        $this->filterLists['states'] = collect();
        $this->filterLists['districts'] = collect();
        $this->filterLists['subdivisions'] = collect();
    }

    public function edit($id)
    {
        $this->isEditing = true;  // Set editing mode first
        
        $city = CitiesOrVillage::with(['district.state.country', 'subdivision'])->findOrFail($id);

        // Set form data
        $this->formData = [
            'id' => $city->id,
            'name' => $city->name,
            'code' => $city->code,
            'type' => $city->type,
            'district_id' => $city->district_id,
            'subdivision_id' => $city->subdivision_id,
            'state_id' => $city->district->state_id,
            'country_id' => $city->district->state->country_id,
            'is_inactive' => $city->is_inactive
        ];

        $this->editFormLists['countrieslist'] = Country::where('firm_id', session('firm_id'))
            ->pluck('name', 'id');

        $this->editFormLists['states'] = State::where('firm_id', session('firm_id'))
            ->where('country_id', $this->formData['country_id'])
            ->pluck('name', 'id');

        $this->editFormLists['districts'] = District::where('firm_id', session('firm_id'))
            ->where('state_id', $this->formData['state_id'])
            ->pluck('name', 'id');

        $this->editFormLists['subdivisions'] = Subdivision::where('firm_id', session('firm_id'))
            ->where('district_id', $this->formData['district_id'])
            ->pluck('name', 'id');

        $this->modal('mdl-city')->show();
    }

    public function delete($id)
    {
        // Check if city has related records
        $city = CitiesOrVillage::findOrFail($id);
        if ($city->employee_addresses()->count() > 0 ||
            $city->joblocations()->count() > 0) {
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
        $this->createFormLists['states'] = collect();
        $this->createFormLists['districts'] = collect();
        $this->createFormLists['subdivisions'] = collect();
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