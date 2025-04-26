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
    public $showModal = false;
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
        $this->initListsForFields();
    }

    protected function initListsForFields(): void
    {
        // Get Job Locations
        $this->listsForFields['joblocations'] = Joblocation::query()
            ->where('firm_id', Session::get('firm_id'))
            ->where('is_inactive', 0)
            ->pluck('name', 'id')
            ->toArray();

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
        $this->listsForFields['cities'] = [];
        $this->listsForFields['postoffices'] = [];
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
        } elseif ($selectchanged == 'citychanged') {
            $this->updatePostoffices();
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
        $this->listsForFields['cities'] = CitiesOrVillage::query()
            ->where('firm_id', Session::get('firm_id'))
            ->where('is_inactive', 0)
            ->when($this->formData['district_id'], fn($q) => $q->where('district_id', $this->formData['district_id']))
            ->pluck('name', 'id')
            ->toArray();
    }

    private function updatePostoffices()
    {
        $this->listsForFields['postoffices'] = Postoffice::query()
            ->where('firm_id', Session::get('firm_id'))
            ->where('is_inactive', 0)
            ->when($this->formData['city_or_village_id'], fn($q) => $q->where('city_or_village_id', $this->formData['city_or_village_id']))
            ->pluck('name', 'id')
            ->toArray();
    }

    public function updatedFormDataCountryId()
    {
        $this->formData['state_id'] = '';
        $this->formData['district_id'] = '';
        $this->formData['subdivision_id'] = '';
        $this->formData['city_or_village_id'] = '';
        $this->formData['postoffice_id'] = '';
        $this->triggerUpdate('countrychanged');
    }

    public function updatedFormDataStateId()
    {
        $this->formData['district_id'] = '';
        $this->formData['subdivision_id'] = '';
        $this->formData['city_or_village_id'] = '';
        $this->formData['postoffice_id'] = '';
        $this->triggerUpdate('statechanged');
    }

    public function updatedFormDataDistrictId()
    {
        $this->formData['subdivision_id'] = '';
        $this->formData['city_or_village_id'] = '';
        $this->formData['postoffice_id'] = '';
        $this->triggerUpdate('districtchanged');
    }

    public function updatedFormDataSubdivisionId()
    {
        $this->formData['city_or_village_id'] = '';
        $this->formData['postoffice_id'] = '';
        $this->triggerUpdate('subdivisionchanged');
    }

    public function updatedFormDataCityOrVillageId()
    {
        $this->formData['postoffice_id'] = '';
        $this->triggerUpdate('citychanged');
    }

    public function updatedFiltersSearchCountry()
    {
        $this->filters['search_state'] = '';
        $this->filters['search_district'] = '';
        $this->filters['search_subdivision'] = '';
        $this->filters['search_city'] = '';
        $this->filters['search_postoffice'] = '';
        
        if ($this->filters['search_country']) {
            $this->formData['country_id'] = $this->filters['search_country'];
            $this->triggerUpdate('countrychanged');
        }
    }

    public function updatedFiltersSearchState()
    {
        $this->filters['search_district'] = '';
        $this->filters['search_subdivision'] = '';
        $this->filters['search_city'] = '';
        $this->filters['search_postoffice'] = '';
        
        if ($this->filters['search_state']) {
            $this->formData['state_id'] = $this->filters['search_state'];
            $this->triggerUpdate('statechanged');
        }
    }

    public function updatedFiltersSearchDistrict()
    {
        $this->filters['search_subdivision'] = '';
        $this->filters['search_city'] = '';
        $this->filters['search_postoffice'] = '';
        
        if ($this->filters['search_district']) {
            $this->formData['district_id'] = $this->filters['search_district'];
            $this->triggerUpdate('districtchanged');
        }
    }

    public function updatedFiltersSearchSubdivision()
    {
        $this->filters['search_city'] = '';
        $this->filters['search_postoffice'] = '';
        
        if ($this->filters['search_subdivision']) {
            $this->formData['subdivision_id'] = $this->filters['search_subdivision'];
            $this->triggerUpdate('subdivisionchanged');
        }
    }

    public function updatedFiltersSearchCity()
    {
        $this->filters['search_postoffice'] = '';
        
        if ($this->filters['search_city']) {
            $this->formData['city_or_village_id'] = $this->filters['search_city'];
            $this->triggerUpdate('citychanged');
        }
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
        try {
            $validatedData = $this->validate(
                [
                    'formData.name' => 'required|string|max:255',
                    'formData.code' => 'nullable|string|max:255',
                    'formData.description' => 'nullable|string',
                    'formData.parent_joblocation_id' => 'nullable',
                    'formData.country_id' => 'nullable|exists:countries,id',
                    'formData.state_id' => 'nullable|exists:states,id',
                    'formData.district_id' => 'nullable|exists:districts,id',
                    'formData.subdivision_id' => 'nullable|exists:subdivisions,id',
                    'formData.city_or_village_id' => 'nullable|exists:cities_or_villages,id',
                    'formData.postoffice_id' => 'nullable|exists:postoffices,id',
                    'formData.is_inactive' => 'boolean',
                ],
                [
                    'formData.name.required' => 'The  Name field is required.',
                    'formData.name.max' => 'The  Name may not be greater than :max characters.',
                    'formData.code.max' => 'The Code may not be greater than :max characters.',
                    'formData.country_id.exists' => 'The selected Country is invalid.',
                    'formData.state_id.exists' => 'The selected State is invalid.',
                    'formData.district_id.exists' => 'The selected District is invalid.',
                    'formData.subdivision_id.exists' => 'The selected Subdivision is invalid.',
                    'formData.city_or_village_id.exists' => 'The selected City/Village is invalid.',
                    'formData.postoffice_id.exists' => 'The selected Post Office is invalid.',
                ]
            );

            // Convert empty strings to null and ensure proper type casting
            $formData = collect($validatedData['formData'])
                ->map(function ($val, $key) {
                    if ($val === '') {
                        return null;
                    }
                    // Cast IDs to integers if they're not null
                    if (str_contains($key, '_id') && $val !== null) {
                        return (int) $val;
                    }
                    return $val;
                })
                ->toArray();

            // Add firm_id from session
            $formData['firm_id'] = session('firm_id');

            if ($this->isEditing) {
                $joblocation = Joblocation::findOrFail($this->formData['id']);
                $joblocation->update($formData);
                $toastMsg = 'Job Location updated successfully';
            } else {
                Joblocation::create($formData);
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
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: $e->getMessage(),
            );
        }
    }

    public function clearFilters()
    {
        $this->reset('filters');
        $this->resetPage();
    }

    public function edit($id)
    {
        $joblocation = Joblocation::findOrFail($id);

        $this->formData = [
            'id' => $joblocation->id,
            'name' => $joblocation->name,
            'code' => $joblocation->code,
            'description' => $joblocation->description,
            'parent_joblocation_id' => $joblocation->parent_joblocation_id,
            'country_id' => $joblocation->country_id,
            'state_id' => $joblocation->state_id,
            'district_id' => $joblocation->district_id,
            'subdivision_id' => $joblocation->subdivision_id,
            'city_or_village_id' => $joblocation->city_or_village_id,
            'postoffice_id' => $joblocation->postoffice_id,
            'is_inactive' => $joblocation->is_inactive,
        ];
        $this->triggerDependentUpdates();        

        $this->isEditing = true;
        $this->modal('mdl-joblocation')->show();
    }

    protected function triggerDependentUpdates(){
        if ($this->formData['country_id']) {
            $this->triggerUpdate('countrychanged');
        }
        
        if ($this->formData['state_id']) {
            $this->triggerUpdate('statechanged');
        }
        
        if ($this->formData['district_id']) {
            $this->triggerUpdate('districtchanged');
        }
        
        if ($this->formData['subdivision_id']) {
            $this->triggerUpdate('subdivisionchanged');
        }
        
        if ($this->formData['city_or_village_id']) {
            $this->triggerUpdate('citychanged');
        }
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