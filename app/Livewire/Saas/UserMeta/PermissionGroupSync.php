<?php

namespace App\Livewire\Saas\UserMeta;

use Livewire\Component;
use App\Models\User;
use App\Models\Saas\PermissionGroup;
use Flux;
use Illuminate\Support\Facades\DB;

class PermissionGroupSync extends Component
{
    public User $user;
    public array $selectedPermissionGroups = [];
    public array $listsForFields = [];

    public function mount($userId)
    {
        $this->user = User::findOrFail($userId);
        $this->selectedPermissionGroups = $this->user->permissionGroups()->select('permission_groups.id')->pluck('id')->toArray();
        $this->initListsForFields();
    }

//    public function save()
//    {
//        $this->user->permissionGroups()->sync($this->selectedPermissionGroups);
//        Flux::modal('permission-group-sync')->close();
//
//        Flux::toast(
//            variant: 'success',
//            heading: 'Changes saved.',
//            text: 'Roles updated successfully!',
//        );
//    }
    public function save()
    {
        $existingGroupIds = $this->user->permissionGroups()->pluck('permission_groups.id')->toArray();

        // Sync groups
        $this->user->permissionGroups()->sync($this->selectedPermissionGroups);

        $selectedGroups = PermissionGroup::with('permission_group_permissions')->whereIn('id', $this->selectedPermissionGroups)->get();

        // STEP 1: Attach permissions from selected groups with correct firm_id
        foreach ($selectedGroups as $group) {
            foreach ($group->permission_group_permissions as $pgp) {
                $permissionId = $pgp->permission_id;

                // Check if user already has this permission for this firm
                $alreadyHas = DB::table('user_permission')
                    ->where('user_id', $this->user->id)
                    ->where('permission_id', $permissionId)
                    ->where('firm_id', $group->firm_id)
                    ->exists();

                if (!$alreadyHas) {
                    $this->user->permissions()->attach([
                        $permissionId => ['firm_id' => $group->firm_id],
                    ]);
                }
            }
        }

        // STEP 2: Clean up permissions from deselected groups (scoped to firm)
        $removedGroupIds = array_diff($existingGroupIds, $this->selectedPermissionGroups);

        $removedGroups = PermissionGroup::with('permission_group_permissions')->whereIn('id', $removedGroupIds)->get();

        foreach ($removedGroups as $group) {
            $permissionIds = $group->permission_group_permissions->pluck('permission_id')->toArray();

            // Detach only entries for this firm's permission ids
            $this->user->permissions()
                ->wherePivot('firm_id', $group->firm_id)
                ->whereIn('permissions.id', $permissionIds)
                ->detach();
        }

        // Done
        Flux::modal('permission-group-sync')->close();

        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: 'Roles and permissions updated successfully!',
        );
    }


    protected function initListsForFields(): void
    {
        $firmIds = $this->user->firms()->pluck('firms.id')->toArray();

        $groups = PermissionGroup::with('firm')
            ->whereIn('firm_id', $firmIds)
            ->get()
            ->groupBy(fn ($group) => optional($group->firm)->name ?? 'Unknown Firm');

        // Convert Eloquent groups to a clean array
        $this->listsForFields['permissiongrouplist'] = $groups->map(function ($groupList) {
            return $groupList->map(function ($group) {
                return [
                    'id' => $group->id,
                    'name' => $group->name,
                ];
            })->toArray();
        })->toArray();

    }


}
