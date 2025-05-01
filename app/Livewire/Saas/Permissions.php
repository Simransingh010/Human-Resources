<?php

namespace App\Livewire\Saas;

use App\Models\Saas\Permission;
use App\Models\Saas\AppModule;
use Livewire\Component;
use Livewire\WithPagination;
use Flux;

class Permissions extends Component
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

    public function mount()
    {
        $this->refreshStatuses();
        $this->initListsForFields();
    }

    public function refreshStatuses()
    {
        $this->statuses = Permission::pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool)$val])
            ->toArray();
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['modules'] = AppModule::where('is_inactive', false)
            ->get()
            ->mapWithKeys(function ($module) {
                return [$module->id => $module->name . ' (' . $module->app->name . ')'];
            })
            ->toArray();
    }

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        return Permission::with('app_module.app')
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate(5);
    }

    public function store()
    {
        $validatedData = $this->validate([
            'formData.name' => 'required|string|max:255',
            'formData.code' => 'nullable|string|max:255',
            'formData.title' => 'required|string|max:255',
            'formData.description' => 'nullable|string',
            'formData.app_module_id' => 'required|exists:app_modules,id',
            'formData.icon' => 'nullable|string|max:255',
            'formData.route' => 'nullable|string|max:255',
            'formData.color' => 'nullable|string|max:255',
            'formData.tooltip' => 'nullable|string|max:255',
            'formData.order' => 'required|integer',
            'formData.badge' => 'nullable|string|max:255',
            'formData.custom_css' => 'nullable|string',
            'formData.is_inactive' => 'boolean',
        ]);

        // Convert empty strings to null
        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        if ($this->isEditing) {
            $permission = Permission::findOrFail($this->formData['id']);
            $permission->update($validatedData['formData']);
            $toastMsg = 'Permission updated successfully';
        } else {
            $permission = Permission::create($validatedData['formData']);
            $toastMsg = 'Permission added successfully';
        }

        $this->resetForm();
        $this->refreshStatuses();
        $this->modal('mdl-permission')->close();
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
        $permission = Permission::findOrFail($id);
        $this->formData = $permission->toArray();
        $this->isEditing = true;
        $this->modal('mdl-permission')->show();
    }

    public function delete($id)
    {
        Permission::findOrFail($id)->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Permission Deleted.',
            text: 'Permission has been deleted successfully',
        );
    }

    public function toggleStatus($permissionId)
    {
        $permission = Permission::find($permissionId);
        $permission->is_inactive = !$permission->is_inactive;
        $permission->save();

        $this->statuses[$permissionId] = $permission->is_inactive;
        $this->refreshStatuses();
    }

    public function render()
    {
        return view('livewire.saas.permissions');
    }
} 