<?php

namespace App\Livewire\Settings\LocationHierarchy;

use App\Models\Settings\Country;
use App\Models\Settings\District;
use App\Models\Settings\State;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\View;
use Illuminate\Database\Eloquent\Builder;
use Flux;

class Districts extends Component
{
    use WithPagination;
    
    public $selectedDistrictId = null;
    public array $listsForFields = [];
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $statuses;
    public $formData = [
        'id' => null,
        'name' => '',
        'code' => '',
        'country_id' => '',
        'state_id' => '',
        'is_inactive' => 0,
    ];

    public $isEditing = false;
    public $modal = false;

    // Add filter properties
    public $filters = [
        'search_name' => '',
        'search_code' => '',
        'search_country' => '',
        'search_state' => '',
    ];

    public function mount()
    {
        $this->resetPage();
        $this->refreshStatuses();
        $this->getCountriesForSelect();
        $this->getStatesForSelect();
    }

    private function getCountriesForSelect()
    {
        $this->listsForFields['countries'] = Country::query()
            ->where('firm_id', Session::get('firm_id'))
            ->where('is_inactive', 0)
            ->pluck('name', 'id')
            ->map(fn($name, $id) => "$name")
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
            ->map(fn($name, $id) => "$name")
            ->toArray();
    }

    public function updatedFormDataCountryId()
    {
        $this->formData['state_id'] = '';
        $this->getStatesForSelect();
    }

    public function updatedFiltersSearchCountry()
    {
        $this->filters['search_state'] = '';
        $this->getStatesForSelect();
    }

    #[Computed]
    public function list()
    {
        return District::query()
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->filters['search_name'], function($query) {
                $query->where('name', 'like', '%' . $this->filters['search_name'] . '%');
            })
            ->when($this->filters['search_code'], function($query) {
                $query->where('code', 'like', '%' . $this->filters['search_code'] . '%');
            })
            ->when($this->filters['search_country'], function($query) {
                $query->whereHas('state', function($q) {
                    $q->where('country_id', $this->filters['search_country']);
                });
            })
            ->when($this->filters['search_state'], function($query) {
                $query->where('state_id', $this->filters['search_state']);
            })
            ->with(['state.country'])
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate(5);
    }

    public function store()
    {
        $validatedData = $this->validate([
            'formData.name' => 'required|string|max:255',
            'formData.code' => 'nullable|string|max:255',
            'formData.state_id' => 'required|exists:states,id',
            'formData.is_inactive' => 'boolean',
        ]);

        // Convert empty strings to null
        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        // Add firm_id from session
        $validatedData['formData']['firm_id'] = session('firm_id');

        if ($this->isEditing) {
            $district = District::findOrFail($this->formData['id']);
            $district->update($validatedData['formData']);
            $toastMsg = 'District updated successfully';
        } else {
            District::create($validatedData['formData']);
            $toastMsg = 'District added successfully';
        }

        $this->resetForm();
        $this->refreshStatuses();
        $this->modal('mdl-district')->close();
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
        $district = District::findOrFail($id);
        $this->formData = $district->toArray();
        $this->formData['country_id'] = $district->state->country_id;
        $this->getStatesForSelect();
        $this->isEditing = true;
        $this->modal('mdl-district')->show();
    }

    public function delete($id)
    {
        // Check if district has related records
        $district = District::findOrFail($id);
        if ($district->cities_or_villages()->count() > 0 || 
            $district->employee_addresses()->count() > 0 || 
            $district->joblocations()->count() > 0 ||
            $district->subdivisions()->count() > 0) {
            Flux::toast(
                variant: 'error',
                heading: 'Cannot Delete',
                text: 'This district has related records and cannot be deleted.',
            );
            return;
        }

        $district->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'District has been deleted successfully',
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
        $this->statuses = District::where('firm_id', session('firm_id'))
            ->pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool)$val])
            ->toArray();
    }

    public function toggleStatus($districtId)
    {
        $district = District::find($districtId);
        $district->is_inactive = !$district->is_inactive;
        $district->save();

        $this->statuses[$districtId] = $district->is_inactive;
        $this->refreshStatuses();
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Settings/LocationHierarchy/blades/districts.blade.php'));
    }
}
 