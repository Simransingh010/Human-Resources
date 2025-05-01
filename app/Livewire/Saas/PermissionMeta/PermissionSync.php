<?php

namespace App\Livewire\Saas\PermissionMeta;

use Livewire\Component;
use App\Models\Saas\PermissionGroup;
use App\Models\Saas\Permission;
use Flux;

class PermissionSync extends Component
{
    public PermissionGroup $permissionGroup;
    public array $selectedPermissions = [];
    public array $listsForFields = [];

    public function mount($permissionGroupId)
    {
        $this->permissionGroup = PermissionGroup::findOrFail($permissionGroupId);
        $this->selectedPermissions = $this->permissionGroup->permissions()->select('permissions.id')->pluck('id')->toArray();
        $this->initListsForFields();
    }

    public function save()
    {
        $this->permissionGroup->permissions()->sync($this->selectedPermissions);
        Flux::modal('permission-sync')->close();

        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: 'Permissions updated successfully!',
        );
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['permissionlist'] = Permission::where('is_inactive', false)
            ->get()
            ->mapWithKeys(function ($permission) {
                return [
                    $permission->id => $permission->name . ' (' . $permission->code . ')'
                ];
            })
            ->toArray();
    }

    public function render()
    {
        return view('livewire.saas.permission-meta.permission-sync');
    }
} 