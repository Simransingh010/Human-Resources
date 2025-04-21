<?php

namespace App\Livewire\Saas\UserMeta;

use Livewire\Component;
use App\Models\User;
use App\Models\Saas\Permission;
use Flux;

class PermissionSync extends Component
{
    public User $user;
    public array $selectedPermissions = [];
    public array $listsForFields = [];

    public function mount($userId)
    {
        $this->user = User::findOrFail($userId);
        $this->selectedPermissions = $this->user->permissions()
            ->select('permissions.id', 'user_permission.firm_id')
            ->get()
            ->map(fn($p) => "{$p->id}|{$p->pivot->firm_id}")
            ->toArray();
        $this->initListsForFields();
    }

    public function save()
    {
        // Detach all current permissions first
        $this->user->permissions()->detach();

        // Reattach with firm_id
        foreach ($this->selectedPermissions as $value) {
            [$permissionId, $firmId] = explode('|', $value);

            $this->user->permissions()->attach($permissionId, [
                'firm_id' => $firmId,
            ]);
        }

        Flux::modal('permission-sync')->close();

        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: 'Components updated successfully!',
        );
    }
    protected function initListsForFields(): void
    {
        $grouped = [];

        $permissions = \App\Models\Saas\Permission::with(['permission_group_permissions.permission_group.firm'])->get();

        foreach ($permissions as $permission) {
            foreach ($permission->permission_group_permissions as $pgp) {
                $group = $pgp->permission_group;
                $firmName = $group?->firm?->name ?? 'Others';
                $groupName = $group?->name ?? 'Others';

                $grouped[$firmName][$groupName][] = [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'firm_id' => $group?->firm_id,
                    'value' => "{$permission->id}|{$group?->firm_id}",
                ];
            }
        }

        $this->listsForFields['permissionHierarchy'] = $grouped;
    }

}
