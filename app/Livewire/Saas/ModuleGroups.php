<?php

namespace App\Livewire\Saas;

use App\Models\Saas\ModuleGroup;
use App\Models\Saas\App;
use Livewire\Component;
use Livewire\WithPagination;
use Flux;

class ModuleGroups extends Component
{
    use WithPagination;

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $statuses;
    public array $listsForFields = [];
    public $formData = [
        'id' => null,
        'name' => '',
        'description' => '',
        'app_id' => null,
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
        $this->statuses = ModuleGroup::pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool)$val])
            ->toArray();
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['apps'] = App::pluck('name', 'id')
            ->toArray();
    }

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        return ModuleGroup::with('app')
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate(5);
    }

    public function store()
    {
        $validatedData = $this->validate([
            'formData.name' => 'required|string|max:255',
            'formData.description' => 'nullable|string',
            'formData.app_id' => 'required|exists:apps,id',
            'formData.is_inactive' => 'boolean',
        ]);

        // Convert empty strings to null
        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        if ($this->isEditing) {
            $moduleGroup = ModuleGroup::findOrFail($this->formData['id']);
            $moduleGroup->update($validatedData['formData']);
            $toastMsg = 'Module Group updated successfully';
        } else {
            $moduleGroup = ModuleGroup::create($validatedData['formData']);
            $toastMsg = 'Module Group added successfully';
        }

        $this->resetForm();
        $this->refreshStatuses();
        $this->modal('mdl-module-group')->close();
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
        $this->isEditing = false;
    }

    public function edit($id)
    {
        $moduleGroup = ModuleGroup::findOrFail($id);
        $this->formData = $moduleGroup->toArray();
        $this->isEditing = true;
        $this->modal('mdl-module-group')->show();
    }

    public function delete($id)
    {
        ModuleGroup::findOrFail($id)->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Module Group Deleted.',
            text: 'Module Group has been deleted successfully',
        );
    }

    public function toggleStatus($moduleGroupId)
    {
        $moduleGroup = ModuleGroup::find($moduleGroupId);
        $moduleGroup->is_inactive = !$moduleGroup->is_inactive;
        $moduleGroup->save();

        $this->statuses[$moduleGroupId] = $moduleGroup->is_inactive;
        $this->refreshStatuses();
    }
} 