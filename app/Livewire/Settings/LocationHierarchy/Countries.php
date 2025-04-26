<?php

namespace App\Livewire\Settings\LocationHierarchy;

use App\Models\Settings\Country;
use Livewire\Component;
use Livewire\WithPagination;
use Flux;

class Countries extends Component
{
    use WithPagination;
    
    public $selectedCountryId = null;
    public array $listsForFields = [];
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $statuses;
    public $formData = [
        'id' => null,
        'name' => '',
        'code' => '',
        'is_inactive' => 0,
    ];

    public $isEditing = false;
    public $modal = false;

    // Add filter properties
    public $filters = [
        'search_name' => '',
        'search_code' => '',
    ];

    public function mount()
    {
        $this->resetPage();
        $this->refreshStatuses();
    }

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        return Country::query()
            ->where('firm_id', session('firm_id'))
            ->when($this->filters['search_name'], function($query) {
                $query->where('name', 'like', '%' . $this->filters['search_name'] . '%');
            })
            ->when($this->filters['search_code'], function($query) {
                $query->where('code', 'like', '%' . $this->filters['search_code'] . '%');
            })
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate(5);
    }

    public function store()
    {
        $validatedData = $this->validate([
            'formData.name' => 'required|string|max:255',
            'formData.code' => 'nullable|string|max:255',
            'formData.is_inactive' => 'boolean',
        ]);

        // Convert empty strings to null
        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

            

        // Add firm_id from session
        $validatedData['formData']['firm_id'] = session('firm_id');

        if ($this->isEditing) {
            $country = Country::findOrFail($this->formData['id']);
            $country->update($validatedData['formData']);
            $toastMsg = 'Country updated successfully';
        } else {
            Country::create($validatedData['formData']);
            $toastMsg = 'Country added successfully';
        }

        $this->resetForm();
        $this->refreshStatuses();
        $this->modal('mdl-country')->close();
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
        $this->formData = Country::findOrFail($id)->toArray();
        $this->isEditing = true;
        $this->modal('mdl-country')->show();
    }

    public function delete($id)
    {
        // Check if country has related records
        $country = Country::findOrFail($id);
        if ($country->states()->count() > 0 || 
            $country->employee_addresses()->count() > 0 || 
            $country->joblocations()->count() > 0) {
            Flux::toast(
                variant: 'error',
                heading: 'Cannot Delete',
                text: 'This country has related records and cannot be deleted.',
            );
            return;
        }

        $country->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Country has been deleted successfully',
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
        $this->statuses = Country::where('firm_id', session('firm_id'))
            ->pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool)$val])
            ->toArray();
    }

    public function toggleStatus($countryId)
    {
        $country = Country::find($countryId);
        $country->is_inactive = !$country->is_inactive;
        $country->save();

        $this->statuses[$countryId] = $country->is_inactive;
        $this->refreshStatuses();
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Settings/LocationHierarchy/blades/countries.blade.php'));
    }
}
