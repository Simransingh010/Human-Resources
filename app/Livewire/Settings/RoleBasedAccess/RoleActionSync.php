<?php

namespace App\Livewire\Settings\RoleBasedAccess;

use Livewire\Component;
use App\Models\Saas\Role;
use App\Models\Saas\ActionRole;
use App\Models\Saas\App as AppModel;
use Illuminate\Support\Facades\Session;
use Flux;

class RoleActionSync extends Component
{
    public Role $role;
    public array $selectedActions = [];
    public array $actionRecordScopes = [];
    private array $previousSelectedActions = [];
    public array $hierarchy = [];
    public $selectedAppId = null;
    public $selectedModuleId = null;
    public $selectedAppName = null;
    public $selectedComponentId = null;
    public array $groupedActions = [];
    public $firmId = null; // Add property to accept firm ID

    public function mount($roleId, $firmId = null)
    {
        $this->firmId = $firmId;
        $this->role = Role::findOrFail($roleId);
        $this->selectedActions = $this->role->actions()->select('actions.id')->pluck('id')->toArray();
        $this->previousSelectedActions = $this->selectedActions;
        
        // Load existing record scopes for selected actions
        $existingActionRoles = ActionRole::where('role_id', $this->role->id)
            ->whereIn('action_id', $this->selectedActions)
            ->get();
        
        foreach ($existingActionRoles as $actionRole) {
            $this->actionRecordScopes[$actionRole->action_id] = $actionRole->records_scope ?? 'all';
        }
        
        $this->initListsForFields();
        $this->buildHierarchy();
        // Set default selected app to the first app if available
        if (empty($this->selectedAppName) && !empty($this->groupedActions)) {
            $this->selectedAppName = array_key_first($this->groupedActions);
        }
    }

    // Helper method to get the current firm ID
    protected function getCurrentFirmId()
    {
        return $this->firmId ?? Session::get('firm_id');
    }
    
    protected function buildHierarchy()
    {
        $apps = AppModel::with([
            'modules.components.actions' => function ($q) {
                $q->where('is_inactive', false);
            }

        ])->where('is_inactive', false)->orderBy('id', 'asc')->get();

        // Get firm ID from the role
        $firmId = $this->getCurrentFirmId();
        
        // If this is a firm-specific role (firm_id is set), filter components by firm assignment
        if ($firmId !== null) {
            // Get all component IDs that are assigned to this firm
            $assignedComponentIds = \App\Models\Saas\ComponentPanel::where('firm_id', $firmId)
                ->pluck('component_id')
                ->toArray();
        }

        $hierarchy = [];
        foreach ($apps as $app) {
            $appArr = [
                'id' => $app->id,
                'name' => $app->name,
                'modules' => [],
            ];
            foreach ($app->modules as $module) {
                $moduleArr = [
                    'id' => $module->id,
                    'name' => $module->name,
                    'components' => [],
                ];
                foreach ($module->components as $component) {
                    // Skip components that are not assigned to the firm (for firm-specific roles)
                    if ($firmId !== null && !in_array($component->id, $assignedComponentIds)) {
                        continue;
                    }
                    
                    $componentArr = [
                        'id' => $component->id,
                        'name' => $component->name,
                        'actions' => [],
                    ];
                    foreach ($component->actions as $action) {
                        $componentArr['actions'][] = [
                            'id' => $action->id,
                            'name' => $action->name,
                            'code' => $action->code,
                        ];
                    }
                    $moduleArr['components'][] = $componentArr;
                }
                $appArr['modules'][] = $moduleArr;
            }
            $hierarchy[] = $appArr;
        }
        $this->hierarchy = $hierarchy;
        
    }

    public function save($closeModal = true)
    {
        $firmId = $this->getCurrentFirmId();
        $existingActionIds = $this->role->actions()->pluck('actions.id')->toArray();
        $actionsToAdd = array_diff($this->selectedActions, $existingActionIds);
        $actionsToRemove = array_diff($existingActionIds, $this->selectedActions);
        
        foreach ($actionsToAdd as $actionId) {
            ActionRole::create([
                'firm_id' => $firmId,
                'role_id' => $this->role->id,
                'action_id' => $actionId,
                'records_scope' => $this->actionRecordScopes[$actionId] ?? 'all',
            ]);
        }
        
        // Update existing action roles with new record scopes
        foreach ($this->selectedActions as $actionId) {
            if (in_array($actionId, $existingActionIds)) {
                ActionRole::where('role_id', $this->role->id)
                    ->where('action_id', $actionId)
                    ->update([
                        'records_scope' => $this->actionRecordScopes[$actionId] ?? 'all',
            ]);
        }
        }
        
        if (!empty($actionsToRemove)) {
            ActionRole::where('role_id', $this->role->id)
                ->whereIn('action_id', $actionsToRemove)
                ->delete();
        }
        
        if ($closeModal) {
            Flux::modal('role-action-sync')->close();
        }
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: 'Role actions updated successfully!',
        );
    }

    public function render()
    {
        $this->previousSelectedActions = $this->selectedActions;
        return view()->file(app_path('Livewire/Settings/RoleBasedAccess/blades/role-action-sync.blade.php'));
    }

    public function updatedSelectedAppId()
    {
        $this->selectedModuleId = null;
        $this->selectedComponentId = null;
    }

    public function updatedSelectedModuleId()
    {
        $this->selectedComponentId = null;
    }

    protected function initListsForFields(): void
    {
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
        $apps = AppModel::with([
            'modules.components.actions.actioncluster' => function ($q) {
                $q->where('is_inactive', false);
            }
        ])->where('is_inactive', false)->orderBy('id', 'asc')->get();

        // Get firm ID from the role
        $firmId = $this->getCurrentFirmId();
        
        // If this is a firm-specific role (firm_id is set), filter components by firm assignment
        if ($firmId !== null) {
            // Get all component IDs that are assigned to this firm
            $assignedComponentIds = \App\Models\Saas\ComponentPanel::where('firm_id', $firmId)
                ->pluck('component_id')
                ->toArray();
        }

        $grouped = [];
        foreach ($apps as $app) {
            foreach ($app->modules as $module) {
                foreach ($module->components as $component) {
                    // Skip components that are not assigned to the firm (for firm-specific roles)
                    if ($firmId !== null && !in_array($component->id, $assignedComponentIds)) {
                        continue;
                    }
                    
                    $grouped[$app->name][$module->name][$component->name] = [];
                    // For each action type
                    foreach ($ACTION_TYPE_LABELS as $typeKey => $typeLabel) {
                        if ($typeKey === 'INDEPENDENT') {
                            $actionsOfType = collect($component->actions)->filter(function($a){ return empty($a->action_type); });
                        } else {
                            $actionsOfType = collect($component->actions)->where('action_type', $typeKey);
                        }
                        if ($actionsOfType->isEmpty()) continue;
                        // Group by cluster
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
                                    ],
                                    'children' => collect($children)->map(function($child) use ($typeLabel) {
                                        return [
                                            'id' => $child->id,
                                            'name' => $child->name,
                                            'code' => $child->code,
                                            'type' => $typeLabel,
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
            }
        }
        $this->groupedActions = $grouped;
    }

    public function selectApp($appName)
    {
        $ids = [];
        foreach ($this->groupedActions[$appName] ?? [] as $modules) {
            foreach ($modules as $componentName => $actionTypes) {
                foreach ($actionTypes as $typeKey => $typeData) {
                    if (!empty($typeData['clusters'])) {
                        foreach ($typeData['clusters'] as $clusters) {
                            foreach ($clusters as $group) {
                                $ids[] = $group['parent']['id'];
                                // Set record scope for parent if it's a RL or BR type
                                if (in_array($typeKey, ['RL', 'BR'])) {
                                    $this->actionRecordScopes[$group['parent']['id']] = 'all';
                                }
                                foreach ($group['children'] as $child) {
                                    $ids[] = $child['id'];
                                    // Set record scope for child if it's a RL or BR type
                                    if (in_array($typeKey, ['RL', 'BR'])) {
                                        $this->actionRecordScopes[$child['id']] = 'all';
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        $ids = array_unique($ids);
        $this->selectedActions = array_unique(array_merge($this->selectedActions, $ids));
        $this->save(false);
    }

    public function deselectApp($appName)
    {
        $ids = [];
        foreach ($this->groupedActions[$appName] ?? [] as $modules) {
            foreach ($modules as $componentName => $actionTypes) {
                foreach ($actionTypes as $typeKey => $typeData) {
                    if (!empty($typeData['clusters'])) {
                        foreach ($typeData['clusters'] as $clusters) {
                            foreach ($clusters as $group) {
                                $ids[] = $group['parent']['id'];
                                // Set record scope to none for parent
                                if (in_array($typeKey, ['RL', 'BR'])) {
                                    $this->actionRecordScopes[$group['parent']['id']] = 'none';
                                }
                                foreach ($group['children'] as $child) {
                                    $ids[] = $child['id'];
                                    // Set record scope to none for child
                                    if (in_array($typeKey, ['RL', 'BR'])) {
                                        $this->actionRecordScopes[$child['id']] = 'none';
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        $ids = array_unique($ids);
        $this->selectedActions = array_values(array_diff($this->selectedActions, $ids));
        $this->save(false);
    }

    public function toggleModule($appName, $moduleName)
    {
        $ids = [];
        $typeKeys = [];
        foreach ($this->groupedActions[$appName][$moduleName] ?? [] as $components) {
            foreach ($components as $typeKey => $typeData) {
                if (!empty($typeData['clusters'])) {
                    foreach ($typeData['clusters'] as $clusters) {
                        foreach ($clusters as $group) {
                            $ids[] = $group['parent']['id'];
                            $typeKeys[$group['parent']['id']] = $typeKey;
                            foreach ($group['children'] as $child) {
                                $ids[] = $child['id'];
                                $typeKeys[$child['id']] = $typeKey;
                            }
                        }
                    }
                }
            }
        }
        $ids = array_unique($ids);
        $allSelected = !array_diff($ids, $this->selectedActions);
        
        if ($allSelected) {
            $this->selectedActions = array_values(array_diff($this->selectedActions, $ids));
            // Set record scopes to none for deselected actions
            foreach ($ids as $id) {
                if (in_array($typeKeys[$id], ['RL', 'BR'])) {
                    $this->actionRecordScopes[$id] = 'none';
                }
            }
        } else {
            $this->selectedActions = array_unique(array_merge($this->selectedActions, $ids));
            // Set record scopes to all for selected actions
            foreach ($ids as $id) {
                if (in_array($typeKeys[$id], ['RL', 'BR'])) {
                    $this->actionRecordScopes[$id] = 'all';
                }
            }
        }
        $this->save(false);
    }

    public function toggleComponent($appName, $moduleName, $componentName)
    {
        $ids = [];
        $typeKeys = [];
        foreach ($this->groupedActions[$appName][$moduleName][$componentName] ?? [] as $typeKey => $typeData) {
            if (!empty($typeData['clusters'])) {
                foreach ($typeData['clusters'] as $clusters) {
                    foreach ($clusters as $group) {
                        $ids[] = $group['parent']['id'];
                        $typeKeys[$group['parent']['id']] = $typeKey;
                        foreach ($group['children'] as $child) {
                            $ids[] = $child['id'];
                            $typeKeys[$child['id']] = $typeKey;
                        }
                    }
                }
            }
        }
        $ids = array_unique($ids);
        $allSelected = !array_diff($ids, $this->selectedActions);
        
        if ($allSelected) {
            $this->selectedActions = array_values(array_diff($this->selectedActions, $ids));
            // Set record scopes to none for deselected actions
            foreach ($ids as $id) {
                if (in_array($typeKeys[$id], ['RL', 'BR'])) {
                    $this->actionRecordScopes[$id] = 'none';
                }
            }
        } else {
            $this->selectedActions = array_unique(array_merge($this->selectedActions, $ids));
            // Set record scopes to all for selected actions
            foreach ($ids as $id) {
                if (in_array($typeKeys[$id], ['RL', 'BR'])) {
                    $this->actionRecordScopes[$id] = 'all';
                }
            }
        }
        $this->save(false);
    }

    public function toggleAction($actionId)
    {
        if (in_array($actionId, $this->selectedActions)) {
            $this->selectedActions = array_values(array_diff($this->selectedActions, [$actionId]));
        } else {
            $this->selectedActions[] = $actionId;
        }
    }

    public function selectRecordScope($actionId, $scope)
    {
        $this->actionRecordScopes[$actionId] = $scope;

        if ($scope === 'none') {
            if (($key = array_search($actionId, $this->selectedActions)) !== false) {
                unset($this->selectedActions[$key]);
            }
        } else {
            if (!in_array($actionId, $this->selectedActions)) {
                $this->selectedActions[] = $actionId;
            }
        }

        $this->save(false);
    }

    public function updatedSelectedActions()
    {
        $newlySelected = array_diff($this->selectedActions, $this->previousSelectedActions);
        $deselected = array_diff($this->previousSelectedActions, $this->selectedActions);

        foreach ($newlySelected as $actionId) {
            if (($this->actionRecordScopes[$actionId] ?? 'none') === 'none') {
                $this->actionRecordScopes[$actionId] = 'all';
            }
        }

        foreach ($deselected as $actionId) {
            $this->actionRecordScopes[$actionId] = 'none';
        }

        $this->save(false);
    }

    public function updatedSelectedAppName($appName)
    {
        $this->selectedModuleId = null;
        $this->selectedComponentId = null;
        if (isset($this->groupedActions[$appName])) {
            $this->selectedAppId = array_key_first($this->groupedActions[$appName]);
        } else {
            $this->selectedAppId = null;
        }
    }
} 

