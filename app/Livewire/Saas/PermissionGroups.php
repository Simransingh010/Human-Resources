<?php

namespace App\Livewire\Saas;

use App\Models\Saas\PermissionGroup;
use App\Models\Saas\Firm;
use Livewire\Component;
use Livewire\WithPagination;
use Flux;

class PermissionGroups extends Component
{
    use WithPagination;
    public $selectedId = null;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $statuses;
    public array $listsForFields = [];
    public $formData = [
        'id' => null,
        'name' => '',
        'description' => '',
        'firm_id' => null,
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
        $this->statuses = PermissionGroup::pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool)$val])
            ->toArray();
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['firms'] = Firm::where('is_inactive', false)
            ->pluck('name', 'id')
            ->toArray();
    }

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        return PermissionGroup::with('firm')
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate(5);
    }

    public function store()
    {
        $validatedData = $this->validate([
            'formData.name' => 'required|string|max:255',
            'formData.description' => 'nullable|string',
            'formData.firm_id' => 'required|exists:firms,id',
            'formData.is_inactive' => 'boolean',
        ]);

        // Convert empty strings to null
        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        if ($this->isEditing) {
            $permissionGroup = PermissionGroup::findOrFail($this->formData['id']);
            $permissionGroup->update($validatedData['formData']);
            $toastMsg = 'Permission Group updated successfully';
        } else {
            $permissionGroup = PermissionGroup::create($validatedData['formData']);
            $toastMsg = 'Permission Group added successfully';
        }

        $this->resetForm();
        $this->refreshStatuses();
        $this->modal('mdl-permission-group')->close();
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
        $permissionGroup = PermissionGroup::findOrFail($id);
        $this->formData = $permissionGroup->toArray();
        $this->isEditing = true;
        $this->modal('mdl-permission-group')->show();
    }

    public function delete($id)
    {
        PermissionGroup::findOrFail($id)->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Permission Group Deleted.',
            text: 'Permission Group has been deleted successfully',
        );
    }

    public function toggleStatus($permissionGroupId)
    {
        $permissionGroup = PermissionGroup::find($permissionGroupId);
        $permissionGroup->is_inactive = !$permissionGroup->is_inactive;
        $permissionGroup->save();

        $this->statuses[$permissionGroupId] = $permissionGroup->is_inactive;
        $this->refreshStatuses();
    }

    public function showPermissionSync($permissionGroupId)
    {
        $this->selectedId = $permissionGroupId;
        $this->modal('permission-sync')->show();
    }
} 