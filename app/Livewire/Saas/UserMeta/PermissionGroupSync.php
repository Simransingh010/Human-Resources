<?php

namespace App\Livewire\Saas\UserMeta;

use App\Models\User;
use App\Models\Saas\Role;
use App\Models\Saas\Firm;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Illuminate\Support\Facades\View;
use Flux;
use Livewire\Attributes\Computed;

class PermissionGroupSync extends Component
{
    public User $user;
    public array $selectedRoles = [];
    public array $listsForFields = [];
    public ?int $firmId;

    public function mount($userId, $firmId = null)
    {
        $this->user = User::findOrFail($userId);
        $this->firmId = $firmId;
        
        // Get roles for specific firm if firmId is provided
        if ($firmId) {
            $this->selectedRoles = $this->user->roles()
                ->wherePivot('firm_id', $firmId)
                ->pluck('roles.id')
                ->toArray();
        } else {
            $this->selectedRoles = $this->user->roles()
                ->pluck('roles.id')
                ->toArray();
        }
        
        $this->initListsForFields();
    }

    public function save()
    {
        $userId = DB::table('users')->where('id', $this->user->id)->value('id');

        if ($this->firmId) {
            // First remove old records that are not in selectedRoles
            DB::table('role_user')
                ->where('user_id', $userId)
                ->where('firm_id', $this->firmId)
                ->whereNotIn('role_id', $this->selectedRoles)
                ->delete();

            // Then update or insert new role records
            foreach ($this->selectedRoles as $roleId) {
                DB::table('role_user')->updateOrInsert(
                    [
                        'user_id' => $userId,
                        'role_id' => $roleId,
                        'firm_id' => $this->firmId
                    ]
                );
            }

            // Sync actions from ActionRole to ActionUser
            $actionRoles = DB::table('action_role')
                ->whereIn('role_id', $this->selectedRoles)
                ->where('firm_id', $this->firmId)
                ->get();

            // Create action mapping
            $actionMap = [];
            foreach ($actionRoles as $ar) {
                $actionMap[$ar->action_id] = $ar->records_scope;
            }

            // Remove ActionUser records not in this set
            DB::table('action_user')
                ->where('user_id', $userId)
                ->where('firm_id', $this->firmId)
                ->whereNotIn('action_id', array_keys($actionMap))
                ->delete();

            // Add/update ActionUser for each action
            foreach ($actionMap as $actionId => $recordsScope) {
                DB::table('action_user')->updateOrInsert(
                    [
                        'user_id' => $userId,
                        'firm_id' => $this->firmId,
                        'action_id' => $actionId,
                    ],
                    [
                        'records_scope' => $recordsScope,
                    ]
                );
            }
        } else {
            // For roles without firm_id, update or insert
            foreach ($this->selectedRoles as $roleId) {
                DB::table('role_user')->updateOrInsert(
                    [
                        'user_id' => $userId,
                        'role_id' => $roleId,
                        'firm_id' => null
                    ]
                );
            }
        }

        Flux::modal('firm-panel-sync')->close();

        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: 'Roles and permissions updated successfully!',
        );
    }

    protected function initListsForFields(): void
    {
        // Get roles using DB facade to avoid model method issues
        if ($this->firmId) {
            if ($this->user->role_main === 'L1_firm') {
                // For L1_firm users, show only firm-specific roles
                $roles = DB::table('roles')
                    ->where('firm_id', $this->firmId)
                    ->whereNull('deleted_at')
                    ->orderBy('name')
                    ->get();
            } else {
                // For other users, show both firm-specific and global roles
                $roles = DB::table('roles')
                    ->where(function($query) {
                        $query->where('firm_id', $this->firmId)
                              ->orWhereNull('firm_id');
                    })
                    ->whereNull('deleted_at')
                    ->orderBy('name')
                    ->get();
            }
        } else {
            // Get all roles that have no firm_id
            $roles = DB::table('roles')
                ->whereNull('firm_id')
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get();
        }

        $this->listsForFields['rolelist'] = collect($roles)->mapWithKeys(function ($role) {
            $firmLabel = $role->firm_id ? ' (Firm)' : ' (Global)';
            return [
                $role->id => $role->name . $firmLabel
            ];
        })->toArray();
    }

    public function render()
    {
        return View::make('livewire.saas.user-meta.permission-group-sync');
    }
}
