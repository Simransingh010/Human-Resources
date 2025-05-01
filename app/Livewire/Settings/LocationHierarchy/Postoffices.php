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
        
        // Initialize all list arrays with empty collections
        $this->filterLists = [
            'countrieslist' => collect(),
            'states' => collect(),
            'districts' => collect(),
            'subdivisions' => collect(),
            'cities' => collect()
        ];
        
        $this->createFormLists = [
            'countrieslist' => collect(),
            'states' => collect(),
            'districts' => collect(),
            'subdivisions' => collect(),
            'cities' => collect()
        ];
        
        $this->editFormLists = [
            'countrieslist' => collect(),
            'states' => collect(),
            'districts' => collect(),
            'subdivisions' => collect(),
            'cities' => collect()
        ];
        
        $this->initListsForFields();
    }

    public function triggerUpdate($selectchanged = null)
    {
        if ($selectchanged == 'countrychanged') {
            $this->formData['state_id'] = '';
            $this->formData['district_id'] = '';
            $this->formData['subdivision_id'] = '';
            $this->formData['city_or_village_id'] = '';
            $this->getStatesForForm();
            if ($this->isEditing) {
                $this->editFormLists['districts'] = collect();
                $this->editFormLists['subdivisions'] = collect();
                $this->editFormLists['cities'] = collect();
            } else {
                $this->createFormLists['districts'] = collect();
                $this->createFormLists['subdivisions'] = collect();
                $this->createFormLists['cities'] = collect();
            }
        } elseif ($selectchanged == 'statechanged') {
            $this->formData['district_id'] = '';
            $this->formData['subdivision_id'] = '';
            $this->formData['city_or_village_id'] = '';
            $this->getDistrictsForForm();
            if ($this->isEditing) {
                $this->editFormLists['subdivisions'] = collect();
                $this->editFormLists['cities'] = collect();
            } else {
                $this->createFormLists['subdivisions'] = collect();
                $this->createFormLists['cities'] = collect();
            }
        } elseif ($selectchanged == 'districtchanged') {
            $this->formData['subdivision_id'] = '';
            $this->formData['city_or_village_id'] = '';
            $this->getSubdivisionsForForm();
            if ($this->isEditing) {
                $this->editFormLists['cities'] = collect();
            } else {
                $this->createFormLists['cities'] = collect();
            }
        } elseif ($selectchanged == 'subdivisionchanged') {
            $this->formData['city_or_village_id'] = '';
            $this->getCitiesForForm();
        }
    }

    public function triggerFilterUpdate($selectchanged = null)
    {
        if ($selectchanged == 'countrychanged') {
            $this->filters['search_state'] = '';
            $this->filters['search_district'] = '';
            $this->filters['search_subdivision'] = '';
            $this->filters['search_city'] = '';
            $this->getStatesForFilter();
            $this->filterLists['districts'] = collect();
            $this->filterLists['subdivisions'] = collect();
            $this->filterLists['cities'] = collect();
        } elseif ($selectchanged == 'statechanged') {
            $this->filters['search_district'] = '';
            $this->filters['search_subdivision'] = '';
            $this->filters['search_city'] = '';
            $this->getDistrictsForFilter();
            $this->filterLists['subdivisions'] = collect();
            $this->filterLists['cities'] = collect();
        } elseif ($selectchanged == 'districtchanged') {
            $this->filters['search_subdivision'] = '';
            $this->filters['search_city'] = '';
            $this->getSubdivisionsForFilter();
            $this->filterLists['cities'] = collect();
        } elseif ($selectchanged == 'subdivisionchanged') {
            $this->filters['search_city'] = '';
            $this->getCitiesForFilter();
        }
    }

    protected function initListsForFields(): void
    {
        $countries = Country::where('firm_id', session('firm_id'))
            ->pluck('name', 'id');

        $this->filterLists['countrieslist'] = $countries;
        $this->createFormLists['countrieslist'] = $countries;
        $this->editFormLists['countrieslist'] = $countries;

        // Initialize lists for filters if they are set
        if ($this->filters['search_country']) {
            $this->getStatesForFilter();
        }
        if ($this->filters['search_state']) {
            $this->getDistrictsForFilter();
        }
        if ($this->filters['search_district']) {
            $this->getSubdivisionsForFilter();
        }
        if ($this->filters['search_subdivision']) {
            $this->getCitiesForFilter();
        }

        // Initialize lists for form if they are set
        if ($this->formData['country_id']) {
            $this->getStatesForForm();
        }
        if ($this->formData['state_id']) {
            $this->getDistrictsForForm();
        }
        if ($this->formData['district_id']) {
            $this->getSubdivisionsForForm();
        }
        if ($this->formData['subdivision_id']) {
            $this->getCitiesForForm();
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

    private function getCitiesForForm()
    {
        $subdivisionId = $this->formData['subdivision_id'] ?? null;

        if ($subdivisionId) {
            $cities = CitiesOrVillage::where('firm_id', session('firm_id'))
                ->where('subdivision_id', $subdivisionId)
                ->pluck('name', 'id');

            if ($this->isEditing) {
                $this->editFormLists['cities'] = $cities;
            } else {
                $this->createFormLists['cities'] = $cities;
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

    private function getCitiesForFilter()
    {
        $subdivisionId = $this->filters['search_subdivision'] ?? null;

        $this->filterLists['cities'] = $subdivisionId
            ? CitiesOrVillage::where('firm_id', session('firm_id'))
                ->where('subdivision_id', $subdivisionId)
                ->pluck('name', 'id')
            : collect();
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
        $this->filterLists['states'] = collect();
        $this->filterLists['districts'] = collect();
        $this->filterLists['subdivisions'] = collect();
        $this->filterLists['cities'] = collect();
    }

    public function edit($id)
    {
        $this->isEditing = true;  // Set editing mode first
        
        $postoffice = Postoffice::with(['cities_or_village.district.state.country', 'cities_or_village.subdivision'])->findOrFail($id);

        // Set form data
        $this->formData = [
            'id' => $postoffice->id,
            'name' => $postoffice->name,
            'code' => $postoffice->code,
            'pincode' => $postoffice->pincode,
            'city_or_village_id' => $postoffice->city_or_village_id,
            'district_id' => $postoffice->cities_or_village->district_id,
            'subdivision_id' => $postoffice->cities_or_village->subdivision_id,
            'state_id' => $postoffice->cities_or_village->district->state_id,
            'country_id' => $postoffice->cities_or_village->district->state->country_id,
            'is_inactive' => $postoffice->is_inactive
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

        $this->editFormLists['cities'] = CitiesOrVillage::where('firm_id', session('firm_id'))
            ->where('subdivision_id', $this->formData['subdivision_id'])
            ->pluck('name', 'id');

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
        $this->createFormLists['states'] = collect();
        $this->createFormLists['districts'] = collect();
        $this->createFormLists['subdivisions'] = collect();
        $this->createFormLists['cities'] = collect();
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