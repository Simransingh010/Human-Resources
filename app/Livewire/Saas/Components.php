<?php

namespace App\Livewire\Saas;

use App\Models\Saas\Component;
use App\Models\Saas\AppModule;
use Livewire\Component as LivewireComponent;
use Livewire\WithPagination;
use Flux;

class Components extends LivewireComponent
{
    use WithPagination;

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $statuses;
    public array $listsForFields = [];
    public $formData = [
        'id' => null,
        'name' => '',
        'code' => '',
        'description' => '',
        'app_module_id' => null,
        'icon' => '',
        'route' => '',
        'color' => '',
        'tooltip' => '',
        'order' => 0,
        'badge' => '',
        'custom_css' => '',
        'is_inactive' => 0,
    ];

    public $isEditing = false;

    // Add filter properties
    public $filters = [
        'search_name' => '',
        'search_code' => '',
        'search_module_id' => '',
    ];

    public function mount()
    {
        $this->refreshStatuses();
        $this->initListsForFields();
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['modules'] = AppModule::where('is_inactive', false)
            ->get()
            ->mapWithKeys(function ($module) {
                return [$module->id => $module->name . ' (' . $module->app->name . ')'];
            })
            ->toArray();
//        dd($this->listsForFields['modules']);
    }

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        return Component::query()
            // ->with('app_module')  // Eager load the module relationship
            ->when($this->filters['search_name'], function($query) {
                $query->where('name', 'like', '%' . $this->filters['search_name'] . '%');
            })
            ->when($this->filters['search_code'], function($query) {
                $query->where('code', 'like', '%' . $this->filters['search_code'] . '%');
            })
            ->when($this->filters['search_module_id'], function($query) {
                $query->where('app_module_id', $this->filters['search_module_id']);
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
            'formData.icon' => 'nullable|string|max:255',
            'formData.color' => 'nullable|string|max:50',
            'formData.tooltip' => 'nullable|string|max:255',
            'formData.order' => 'required|integer|min:0',
            'formData.badge' => 'nullable|string|max:255',
            'formData.custom_css' => 'nullable|string',
            'formData.is_inactive' => 'boolean',
        ]);

        // Convert empty strings to null
        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        if ($this->isEditing) {
            $component = Component::findOrFail($this->formData['id']);
            $component->update($validatedData['formData']);
            $toastMsg = 'Component updated successfully';
        } else {
            Component::create($validatedData['formData']);
            $toastMsg = 'Component added successfully';
        }

        $this->resetForm();
        $this->refreshStatuses();
        $this->modal('mdl-component')->close();
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: $toastMsg,
        );
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['is_inactive'] = 0;
        $this->formData['order'] = 0;
        $this->isEditing = false;
    }

    public function edit($id)
    {
        $component = Component::findOrFail($id);
        $this->formData = $component->toArray();
        $this->isEditing = true;
        $this->modal('mdl-component')->show();
    }

    public function delete($id)
    {
        Component::findOrFail($id)->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Component Deleted.',
            text: 'Component has been deleted successfully',
        );
    }

    public function toggleStatus($componentId)
    {
        $component = Component::find($componentId);
        $component->is_inactive = !$component->is_inactive;
        $component->save();

        $this->statuses[$componentId] = $component->is_inactive;
        $this->refreshStatuses();
    }

    public function refreshStatuses()
    {
        $this->statuses = Component::pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool)$val])
            ->toArray();
    }

    public function clearFilters()
    {
        $this->reset('filters');
        $this->resetPage();
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Saas/blades/components.blade.php'));
    }
} 