<?php

namespace App\Livewire\Saas;

use App\Models\Saas\Action;
use App\Models\Saas\Component;
use Livewire\Component as LivewireComponent;
use Livewire\WithPagination;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use Flux;

class Actions extends LivewireComponent
{
    use WithPagination;

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $statuses;
    public array $listsForFields = [];
    
    // Add filter properties
    public $filters = [
        'search_name' => '',
        'search_code' => '',
        'search_component_id' => '',
    ];

    public $formData = [
        'id' => null,
        'name' => '',
        'code' => '',
        'description' => '',
        'component_id' => null,
        'icon' => '',
        'color' => '',
        'tooltip' => '',
        'order' => 0,
        'badge' => '',
        'custom_css' => '',
        'actioncluster_id' => null,
        'is_inactive' => 0,
        'action_type' => '',
    ];

    public $isEditing = false;

    public $actionTypes = [];

    public function mount()
    {
        $this->resetPage();
        $this->refreshStatuses();
        $this->initListsForFields();
        $this->actionTypes = \App\Models\Saas\Action::ACTION_TYPE_MAIN_SELECT;
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['components'] = Component::query()
            ->where('is_inactive', false)
            ->get()
            ->mapWithKeys(function ($component) {
                return [$component->id => $component->name];
            })
            ->toArray();
    }

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        return Action::query()
            ->with('component')
            ->when($this->filters['search_name'], function($query) {
                $query->where('name', 'like', '%' . $this->filters['search_name'] . '%');
            })
            ->when($this->filters['search_code'], function($query) {
                $query->where('code', 'like', '%' . $this->filters['search_code'] . '%');
            })
            ->when($this->filters['search_component_id'], function($query) {
                $query->where('component_id', $this->filters['search_component_id']);
            })
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate(5);
    }

    public function store()
    {
        $validatedData = $this->validate([
            'formData.name' => 'required|string|max:255',
            'formData.code' => 'nullable|string|max:255',
            'formData.description' => 'nullable|string',
            'formData.component_id' => 'required|exists:components,id',
            'formData.icon' => 'nullable|string|max:255',
            'formData.color' => 'nullable|string|max:255',
            'formData.tooltip' => 'nullable|string|max:255',
            'formData.order' => 'required|integer',
            'formData.badge' => 'nullable|string|max:255',
            'formData.custom_css' => 'nullable|string',
            'formData.actioncluster_id' => 'nullable|integer',
            'formData.is_inactive' => 'boolean',
            'formData.action_type' => 'nullable|string',
        ]);

        // Convert empty strings to null
        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        DB::beginTransaction();
        try {
            if ($this->isEditing) {
                $action = Action::findOrFail($this->formData['id']);
                $action->update($validatedData['formData']);
                $toastMsg = 'Action updated successfully';
            } else {
                Action::create($validatedData['formData']);
                $toastMsg = 'Action added successfully';
            }
            DB::commit();

            $this->resetForm();
            $this->refreshStatuses();
            $this->modal('mdl-action')->close();
            Flux::toast(
                variant: 'success',
                heading: 'Changes saved.',
                text: $toastMsg,
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Flux::toast(
                variant: 'danger',
                heading: 'Error',
                text: 'Failed to save action. ' . $e->getMessage(),
            );
        }
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
        try {
            $action = Action::findOrFail($id);
            $this->formData = $action->toArray();
            $this->isEditing = true;
            $this->modal('mdl-action')->show();
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'danger',
                heading: 'Error',
                text: 'Failed to load action. ' . $e->getMessage(),
            );
        }
    }
    public function clearFilters()
    {
        $this->reset('filters');
        $this->resetPage();
    }
    public function delete($id)
    {
        try {
            Action::findOrFail($id)->delete();
            Flux::toast(
                variant: 'success',
                heading: 'Action Deleted.',
                text: 'Action has been deleted successfully',
            );
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'danger',
                heading: 'Error',
                text: 'Failed to delete action. ' . $e->getMessage(),
            );
        }
    }

    public function toggleStatus($actionId)
    {
        try {
            $action = Action::findOrFail($actionId);
            $action->is_inactive = !$action->is_inactive;
            $action->save();

            $this->statuses[$actionId] = $action->is_inactive;
            $this->refreshStatuses();
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'danger',
                heading: 'Error',
                text: 'Failed to toggle status. ' . $e->getMessage(),
            );
        }
    }

    public function refreshStatuses()
    {
        $this->statuses = Action::query()
            ->pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool)$val])
            ->toArray();
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Saas/blades/actions.blade.php'));
    }
} 