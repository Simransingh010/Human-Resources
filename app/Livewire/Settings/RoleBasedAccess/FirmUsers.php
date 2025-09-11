<?php

namespace App\Livewire\Settings\RoleBasedAccess;

use App\Models\User;
use App\Models\Saas\Firm;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class FirmUsers extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'name';
    public $sortDirection = 'asc';
    public $isEditing = false;
    public $selectedUserId = null;
    public $showRoleModal = false;
    public $roleModalUserId = null;
    public $roleModalUserName = '';
    public $roleModalSelectedRoles = [];
    public $roleModalAvailableRoles = [];

    // --- Direct Action Assignment Modal State ---
    public $showActionModal = false;
    public $actionModalUserId = null;
    public $actionModalUserName = '';
    public $actionModalSelectedActions = [];
    public $actionModalActionScopes = [];
    public $actionModalAvailableActions = [];
    public $actionModalGroupedActions = [];
    public $actionModalAppList = [];
    public $actionModalSelectedApp = null;

    // Track previous selected actions for Livewire reactivity
    public array $previousActionModalSelectedActions = [];

    // Track previous selected roles for Livewire reactivity
    public array $previousRoleModalSelectedRoles = [];

    // Field configuration for form and table
    public array $fieldConfig = [
        'name' => ['label' => 'Name', 'type' => 'text'],
        'email' => ['label' => 'Email', 'type' => 'text'],
        'phone' => ['label' => 'Phone', 'type' => 'text'],
        'passcode' => ['label' => 'Passcode', 'type' => 'text'],
        'is_inactive' => ['label' => 'Inactive', 'type' => 'switch'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'name' => ['label' => 'Name', 'type' => 'text'],
        'email' => ['label' => 'Email', 'type' => 'text'],
        'phone' => ['label' => 'Phone', 'type' => 'text'],
        'is_inactive' => ['label' => 'Status', 'type' => 'select', 'listKey' => 'status'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
        public array $visibleFilterFields = [];

    public $formData = [
        'id' => null,
        'name' => '',
        'email' => '',
        'password' => '',
        'passcode' => '',
        'phone' => '',
        'is_inactive' => 0,
    ];

    public function mount()
    {
        $this->resetPage();
        $this->initListsForFields();
        $this->visibleFields = ['name', 'email', 'phone', 'is_inactive'];
        $this->visibleFilterFields = ['name', 'email', 'phone', 'is_inactive'];
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['status'] = [
            '0' => 'Active',
            '1' => 'Inactive'
        ];
    }

    public function applyFilters()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
        $this->resetPage();
    }

    public function toggleColumn(string $field)
    {
        if (in_array($field, $this->visibleFields)) {
            $this->visibleFields = array_filter(
                $this->visibleFields,
                fn($f) => $f !== $field
            );
        } else {
            $this->visibleFields[] = $field;
        }
    }

    public function toggleFilterColumn(string $field)
    {
        if (in_array($field, $this->visibleFilterFields)) {
            $this->visibleFilterFields = array_filter(
                $this->visibleFilterFields,
                fn($f) => $f !== $field
            );
        } else {
            $this->visibleFilterFields[] = $field;
        }
    }

    #[Computed]
    public function list()
    {
        $firmId = Session::get('firm_id');
        return User::whereHas('firms', function($q) use ($firmId) {
                $q->where('firms.id', $firmId);
            })
            ->whereIn('role_main', ['L0_emp', 'L1_firm'])
            ->when($this->filters['name'], fn($query, $value) => 
                $query->where('name', 'like', "%{$value}%"))
            ->when($this->filters['email'], fn($query, $value) => 
                $query->where('email', 'like', "%{$value}%"))
            ->when($this->filters['phone'], fn($query, $value) => 
                $query->where('phone', 'like', "%{$value}%"))
            ->when($this->filters['is_inactive'] !== '', fn($query, $value) => 
                $query->where('is_inactive', $value))
            ->orderBy('id', $this->sortDirection) // Sort by user id
            ->paginate($this->perPage);
    }

    protected function rules()
    {
        return [
            'formData.name' => 'required|string|max:255',
            'formData.email' => 'nullable|string|max:255',
            'formData.password' => 'nullable|string|max:255',
            'formData.passcode' => 'nullable|string|max:255',
            'formData.phone' => 'nullable|string|max:9999999999',
            'formData.is_inactive' => 'boolean',
        ];
    }

    public function store()
    {
        $validatedData = $this->validate();
        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();
        if ($this->isEditing) {
            $user = User::findOrFail($this->formData['id']);
            $user->update($validatedData['formData']);
            $toastMsg = 'User updated successfully';
        } else {
            $user = User::create($validatedData['formData']);
            $toastMsg = 'User added successfully';
        }
        // Sync firm relation
        $firmId = Session::get('firm_id');
        $user->firms()->sync([$firmId]);
        $this->resetForm();
        $this->modal('mdl-firm-user')->close();
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
        $this->isEditing = true;
        $user = User::findOrFail($id);
        $this->formData = $user->toArray();
        $this->modal('mdl-firm-user')->show();
    }

    public function delete($id)
    {
        $user = User::findOrFail($id);
        $user->firms()->detach(Session::get('firm_id'));
        $user->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'User has been deleted successfully',
        );
    }

    public function openRoleModal($userId)
    {
        $firmId = Session::get('firm_id');
        $user = User::findOrFail($userId);
        $this->roleModalUserId = $userId;
        $this->roleModalUserName = $user->name;
        $this->roleModalAvailableRoles = \App\Models\Saas\Role::where('firm_id', $firmId)->pluck('name', 'id')->toArray();
        $this->roleModalSelectedRoles = \App\Models\Saas\RoleUser::where('user_id', $userId)->where('firm_id', $firmId)->pluck('role_id')->map(fn($id) => (string)$id)->toArray();
        $this->previousRoleModalSelectedRoles = $this->roleModalSelectedRoles;

        $this->showRoleModal = true;
        Flux::modal('user-role-modal')->show();
    }

    public function closeRoleModal()
    {
        $this->showRoleModal = false;
        $this->roleModalUserId = null;
        $this->roleModalUserName = '';
        $this->roleModalSelectedRoles = [];
        $this->roleModalAvailableRoles = [];
    }

    public function saveUserRoles()
    {
        $firmId = Session::get('firm_id');
        $userId = $this->roleModalUserId;
        $selectedRoles = $this->roleModalSelectedRoles;
        // Sync RoleUser
        $roleUserIds = [];
        foreach ($selectedRoles as $roleId) {
            $roleUser = \App\Models\Saas\RoleUser::firstOrCreate([
                'user_id' => $userId,
                'role_id' => $roleId,
                'firm_id' => $firmId,
            ]);
            $roleUserIds[] = $roleUser->id;
        }
        // Remove unselected roles
        \App\Models\Saas\RoleUser::where('user_id', $userId)
            ->where('firm_id', $firmId)
            ->whereNotIn('role_id', $selectedRoles)
            ->delete();
        // Sync actions
        $this->syncUserActions($userId, $firmId);
        $this->closeRoleModal();

        Flux::toast(
            variant: 'success',
            heading: 'Roles updated.',
            text: 'Roles and permissions have been updated for the user.',
        );

        Flux::modal('user-role-modal')->close();
    }

    public function syncUserActions($userId, $firmId = null)
    {
        $firmId = $firmId ?: Session::get('firm_id');
        // Get all roles for this user/firm
        $roleIds = \App\Models\Saas\RoleUser::where('user_id', $userId)->where('firm_id', $firmId)->pluck('role_id')->toArray();
        // Get all ActionRole for these roles/firm
        $actionRoles = \App\Models\Saas\ActionRole::whereIn('role_id', $roleIds)->where('firm_id', $firmId)->get();
        $actionMap = [];
        foreach ($actionRoles as $ar) {
            $actionMap[$ar->action_id] = $ar->records_scope;
        }
        // Remove ActionUser not in this set
        \App\Models\Saas\ActionUser::where('user_id', $userId)->where('firm_id', $firmId)
            ->whereNotIn('action_id', array_keys($actionMap))->delete();
        // Add/update ActionUser for each action
        foreach ($actionMap as $actionId => $recordsScope) {
            \App\Models\Saas\ActionUser::updateOrCreate(
                [
                    'user_id' => $userId,
                    'firm_id' => $firmId,
                    'action_id' => $actionId,
                ],
                [
                    'records_scope' => $recordsScope,
                ]
            );
        }
    }

    public function syncUser($userId)
    {
        $firmId = Session::get('firm_id');
        $this->syncUserActions($userId, $firmId);
        Flux::toast(
            variant: 'success',
            heading: 'Permissions Synced.',
            text: 'User permissions have been synced with assigned roles.',
        );
    }

    public function openActionModal($userId)
    {
        $firmId = Session::get('firm_id');
        $user = User::findOrFail($userId);
        $this->actionModalUserId = $userId;
        $this->actionModalUserName = $user->name;
        $ACTION_TYPE_BG = [
            'G' => 'bg-blue-50',
            'RL' => 'bg-yellow-50',
            'BR' => 'bg-green-50',
            'PR' => 'bg-pink-50',
            'INDEPENDENT' => 'bg-gray-100',
        ];
        $ACTION_TYPE_LABELS = \App\Models\Saas\Action::ACTION_TYPE_MAIN_SELECT;
        $ACTION_TYPE_LABELS['INDEPENDENT'] = 'Independent with no Type';
        
        // Get all apps with relationships
        $apps = \App\Models\Saas\App::with([
            'modules.components.actions.actioncluster' => function ($q) {
                $q->where('is_inactive', false);
            }
        ])->where('is_inactive', false)->orderBy('id', 'asc')->get();
        
        // Get panels assigned to this firm
        $assignedPanelIds = \App\Models\Saas\FirmPanel::where('firm_id', $firmId)->pluck('panel_id')->toArray();
        
        // Get components assigned to this firm (from ComponentPanel) with their panel assignments
        $assignedComponentPanels = \App\Models\Saas\ComponentPanel::where('firm_id', $firmId)
            ->select('component_id', 'panel_id')
            ->get()
            ->groupBy('component_id');
        
        $assignedComponentIds = $assignedComponentPanels->keys()->toArray();
        
        $grouped = [];
        $processedComponents = []; // Track processed components to avoid duplication
        
        foreach ($apps as $app) {
            $appHasComponents = false;
            
            foreach ($app->modules as $module) {
                $moduleHasComponents = false;
                
                foreach ($module->components as $component) {
                    // Check if this component is assigned to the firm
                    if (!in_array($component->id, $assignedComponentIds)) {
                        continue;
                    }
                    
                    // Avoid component duplication
                    if (in_array($component->id, $processedComponents)) {
                        continue;
                    }
                    $processedComponents[] = $component->id;
                    
                    // Check if this component belongs to any panel assigned to the firm
                    $componentBelongsToAssignedPanel = false;
                    if (isset($assignedComponentPanels[$component->id])) {
                        $componentPanels = $assignedComponentPanels[$component->id]->pluck('panel_id')->toArray();
                        $componentBelongsToAssignedPanel = !empty(array_intersect($componentPanels, $assignedPanelIds));
                    }
                    
                    if (!$componentBelongsToAssignedPanel) {
                        continue;
                    }
                    
                    $grouped[$app->name][$module->name][$component->name] = [];
                    $moduleHasComponents = true;
                    $appHasComponents = true;
                    
                    foreach ($ACTION_TYPE_LABELS as $typeKey => $typeLabel) {
                        if ($typeKey === 'INDEPENDENT') {
                            $actionsOfType = collect($component->actions)->filter(function($a){ return empty($a->action_type); });
                        } else {
                            $actionsOfType = collect($component->actions)->where('action_type', $typeKey);
                        }
                        if ($actionsOfType->isEmpty()) continue;
                        
                        $actionsByCluster = $actionsOfType->groupBy(function($action) {
                            return $action->actioncluster ? $action->actioncluster->name : 'Independent';
                        });
                        
                        $clusters = [];
                        foreach ($actionsByCluster as $clusterName => $actionsInCluster) {
                            $parentActions = $actionsInCluster->where('parent_action_id', null);
                            $childActions = $actionsInCluster->where('parent_action_id', '!=', null);
                            $clusterGroups = [];
                            
                            foreach ($parentActions as $parent) {
                                $children = $childActions->where('parent_action_id', $parent->id)->values()->all();
                                $clusterGroups[] = [
                                    'parent' => [
                                        'id' => $parent->id,
                                        'name' => $parent->name,
                                        'code' => $parent->code,
                                        'type' => $typeLabel,
                                        'type_key' => $typeKey,
                                    ],
                                    'children' => collect($children)->map(function($child) use ($typeLabel, $typeKey) {
                                        return [
                                            'id' => $child->id,
                                            'name' => $child->name,
                                            'code' => $child->code,
                                            'type' => $typeLabel,
                                            'type_key' => $typeKey,
                                        ];
                                    })->values()->all(),
                                ];
                            }
                            
                            $orphaned = $childActions->filter(function($child) use ($parentActions) {
                                return !$parentActions->contains('id', $child->parent_action_id);
                            });
                            
                            foreach ($orphaned as $orphan) {
                                $clusterGroups[] = [
                                    'parent' => [
                                        'id' => $orphan->id,
                                        'name' => $orphan->name,
                                        'code' => $orphan->code,
                                        'type' => $typeLabel,
                                        'type_key' => $typeKey,
                                    ],
                                    'children' => [],
                                ];
                            }
                            $clusters[$clusterName] = $clusterGroups;
                        }
                        
                        $grouped[$app->name][$module->name][$component->name][$typeKey] = [
                            'type_label' => $typeLabel,
                            'type_bg' => $ACTION_TYPE_BG[$typeKey] ?? 'bg-gray-50',
                            'clusters' => $clusters
                        ];
                    }
                }
                
                // Remove empty modules
                if (!$moduleHasComponents) {
                    unset($grouped[$app->name][$module->name]);
                }
            }
            
            // Remove empty apps
            if (!$appHasComponents) {
                unset($grouped[$app->name]);
            }
        }
        
        $this->actionModalGroupedActions = $grouped;
        $this->actionModalAppList = array_keys($grouped);
        $this->actionModalSelectedApp = $this->actionModalAppList[0] ?? null;
        
        // Mount previous direct permissions from ActionUser
        $existing = \App\Models\Saas\ActionUser::where('user_id', $userId)->where('firm_id', $firmId)->get();

        $this->actionModalSelectedActions = $existing->pluck('action_id')->map(fn($id) => (string)$id)->toArray();
        $this->previousActionModalSelectedActions = $this->actionModalSelectedActions;

        $this->actionModalActionScopes = $existing->pluck('records_scope', 'action_id')->toArray();
        $this->showActionModal = true;
        Flux::modal('user-action-modal')->show();
    }

    // Toggle all actions in a module
    public function toggleModule($appName, $moduleName)
    {
        $ids = collect($this->actionModalGroupedActions[$appName][$moduleName] ?? [])
            ->flatten(2)
            ->pluck('parent.id')
            ->merge(collect($this->actionModalGroupedActions[$appName][$moduleName] ?? [])->flatten(2)->pluck('children.*.id')->flatten())
            ->unique()
            ->values()
            ->all();
        $allSelected = !array_diff($ids, $this->actionModalSelectedActions);
        if ($allSelected) {
            $this->actionModalSelectedActions = array_values(array_diff($this->actionModalSelectedActions, $ids));
        } else {
            $this->actionModalSelectedActions = array_unique(array_merge($this->actionModalSelectedActions, $ids));
        }
    }

    // Toggle all actions in a component
    public function toggleComponent($appName, $moduleName, $componentName)
    {
        $ids = collect($this->actionModalGroupedActions[$appName][$moduleName][$componentName] ?? [])
            ->flatten(2)
            ->pluck('parent.id')
            ->merge(collect($this->actionModalGroupedActions[$appName][$moduleName][$componentName] ?? [])->flatten(2)->pluck('children.*.id')->flatten())
            ->unique()
            ->values()
            ->all();
        $allSelected = !array_diff($ids, $this->actionModalSelectedActions);
        if ($allSelected) {
            $this->actionModalSelectedActions = array_values(array_diff($this->actionModalSelectedActions, $ids));
        } else {
            $this->actionModalSelectedActions = array_unique(array_merge($this->actionModalSelectedActions, $ids));
        }
    }

    // Set record scope for an action
    public function setActionScope($actionId, $scope)
    {
        $this->actionModalActionScopes[$actionId] = $scope;
    }

    public function closeActionModal()
    {
        $this->showActionModal = false;
        $this->actionModalUserId = null;
        $this->actionModalUserName = '';
        $this->actionModalSelectedActions = [];
        $this->actionModalActionScopes = [];
        $this->actionModalGroupedActions = [];
        $this->actionModalAppList = [];
        $this->actionModalSelectedApp = null;
    }

    public function saveUserActions()
    {
        $firmId = Session::get('firm_id');
        $userId = $this->actionModalUserId;
        $selectedActions = $this->actionModalSelectedActions;
        $scopes = $this->actionModalActionScopes;
        // Remove all ActionUser for this user/firm not in selected
        \App\Models\Saas\ActionUser::where('user_id', $userId)->where('firm_id', $firmId)
            ->whereNotIn('action_id', $selectedActions)->delete();
        // Add/update selected
        foreach ($selectedActions as $actionId) {
            \App\Models\Saas\ActionUser::updateOrCreate(
                [
                    'user_id' => $userId,
                    'firm_id' => $firmId,
                    'action_id' => $actionId,
                ],
                [
                    'records_scope' => $scopes[$actionId] ?? 'all',
                ]
            );
        }
        $this->closeActionModal();
        Flux::toast(
            variant: 'success',
            heading: 'Actions updated.',
            text: 'Direct actions have been updated for the user.',
        );
        Flux::modal('user-action-modal')->close();
    }

    // Track changes to actionModalSelectedActions for reactivity
    public function updatedActionModalSelectedActions()
    {
        $newlySelected = array_diff($this->actionModalSelectedActions, $this->previousActionModalSelectedActions);
        $deselected = array_diff($this->previousActionModalSelectedActions, $this->actionModalSelectedActions);
        // (Optional) Handle any side effects here, like updating scopes, etc.
        $this->previousActionModalSelectedActions = $this->actionModalSelectedActions;
    }

    // Toggle a single role in the modal (for checkbox wire:click)
    public function toggleRoleModalSelectedRole($roleId)
    {
        $roleId = (string) $roleId;
        if (in_array($roleId, $this->roleModalSelectedRoles)) {
            $this->roleModalSelectedRoles = array_values(array_diff($this->roleModalSelectedRoles, [$roleId]));
        } else {
            $this->roleModalSelectedRoles[] = $roleId;
        }
        $this->previousRoleModalSelectedRoles = $this->roleModalSelectedRoles;
    }

    // Bulk sync all users in the firm
    public function bulkSyncUsers()
    {
        $firmId = Session::get('firm_id');
        $userIds = User::whereHas('firms', function($q) use ($firmId) {
            $q->where('firms.id', $firmId);
        })
        ->whereIn('role_main', ['L0_emp', 'L1_firm'])
        ->pluck('id');
        foreach ($userIds as $userId) {
            $this->syncUserActions($userId, $firmId);
        }
        Flux::toast(
            variant: 'success',
            heading: 'Bulk Sync Complete.',
            text: 'All users in the firm have been synced with their assigned roles.',
        );
    }

    public function render()
    {
        $this->previousActionModalSelectedActions = $this->actionModalSelectedActions;
        $this->previousRoleModalSelectedRoles = $this->roleModalSelectedRoles;
        return view()->file(app_path('Livewire/Settings/RoleBasedAccess/blades/firm-users.blade.php'));
    }
} 