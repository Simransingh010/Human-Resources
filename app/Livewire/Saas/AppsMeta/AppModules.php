<?php

namespace App\Livewire\Saas\AppsMeta;

use App\Models\Saas\AppModule;
use App\Models\Saas\App;
use App\Models\Saas\ModuleGroup;
use Livewire\Component;
use Livewire\WithPagination;
use Flux;

class AppModules extends Component
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
        'app_id' => null,
        'module_group_id' => null,
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
        $this->statuses = AppModule::pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool)$val])
            ->toArray();
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['apps'] = App::where('is_inactive', false)
            ->pluck('name', 'id')
            ->toArray();

        $this->listsForFields['moduleGroups'] = ModuleGroup::where('is_inactive', false)
            ->get()
            ->mapWithKeys(function ($group) {
                return [$group->id => $group->name . ' (' . $group->app->name . ')'];
            })
            ->toArray();
    }

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        return AppModule::with(['app', 'module_group'])
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate(5);
    }

    public function store()
    {
        $validatedData = $this->validate([
            'formData.name' => 'required|string|max:255',
            'formData.code' => 'nullable|string|max:255',
            'formData.description' => 'nullable|string',
            'formData.app_id' => 'required|exists:apps,id',
            'formData.module_group_id' => 'nullable|exists:module_groups,id',
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
            $appModule = AppModule::findOrFail($this->formData['id']);
            $appModule->update($validatedData['formData']);
            $toastMsg = 'App Module updated successfully';
        } else {
            $appModule = AppModule::create($validatedData['formData']);
            $toastMsg = 'App Module added successfully';
        }

        $this->resetForm();
        $this->refreshStatuses();
        $this->modal('mdl-app-module')->close();
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
        $appModule = AppModule::findOrFail($id);
        $this->formData = $appModule->toArray();
        $this->isEditing = true;
        $this->modal('mdl-app-module')->show();
    }

    public function delete($id)
    {
        AppModule::findOrFail($id)->delete();
        Flux::toast(
            variant: 'success',
            heading: 'App Module Deleted.',
            text: 'App Module has been deleted successfully',
        );
    }

    public function toggleStatus($appModuleId)
    {
        $appModule = AppModule::find($appModuleId);
        $appModule->is_inactive = !$appModule->is_inactive;
        $appModule->save();

        $this->statuses[$appModuleId] = $appModule->is_inactive;
        $this->refreshStatuses();
    }

    public function render()
    {
        return view('livewire.saas.apps-meta.app-modules');
    }
} 