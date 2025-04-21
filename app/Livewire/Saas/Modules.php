<?php

namespace App\Livewire\Saas;

use App\Models\Saas\Agency;
use App\Models\Saas\Module;
use Livewire\Component;
use Livewire\WithPagination;
use Flux;

class Modules extends Component
{
    use WithPagination;
    public $selectedModuleId = null;
    public array $listsForFields = [];
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $statuses;
    public $formData = [
        'id' => null,
        'name' => '',
        'code' => '',
        'wire' => '',
        'description' => '',
        'icon' => '',
        'order' => 0,
        'is_inactive'=> 0,
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
        return Module::query()
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
            'formData.wire' => 'nullable|string|max:255',
            'formData.description' => 'nullable|string',
            'formData.icon' => 'nullable|string',
            'formData.order' => 'nullable|integer',
            'formData.is_inactive' => 'boolean',

        ]);

        // Convert empty strings to null
        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        if ($this->isEditing) {
            // Editing: Update the record
            Firm::findOrFail($this->formData['id'])->update($validatedData['formData']);
            $toastMsg = 'Record updated successfully';
        } else {
            firm::create($validatedData['formData']);
            $toastMsg = 'Record added successfully';
        }

        // Reset the form and editing state after saving
        $this->resetForm();
        $this->refreshStatuses();
        $this->refreshSetMasterStatus();
        $this->modal('mdl-module')->close();
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: $toastMsg,
        );

    }
    public function showComponentSync($selectedModuleId)
    {
        $this->selectedModuleId = $selectedModuleId;
        $this->modal('component-sync')->show();
    }

    public function edit($id)
    {
        $this->formData = Module::findOrFail($id)->toArray();
        $this->isEditing = true;
        $this->modal('mdl-module')->show();

    }

    public function delete($id)
    {
        Firm::findOrFail($id)->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Record has been deleted successfully',
        );
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['is_inactive'] = 0; // or false
        $this->isEditing = false;
    }

    public function refreshStatuses()
    {
        $this->statuses = Module::pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool)$val])
            ->toArray();
    }
    public function toggleStatus($firmId)
    {
        $firm = Module::find($firmId);
        $firm->is_inactive = !$firm->is_inactive;
        $firm->save();

        $this->statuses[$firmId] = $firm->is_inactive;
        $this->refreshStatuses();
    }


    public function render()
    {
        return view()->file(app_path('Livewire/Saas/blades/modules.blade.php'));
    }

}
