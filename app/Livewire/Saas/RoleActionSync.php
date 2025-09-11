<?php

namespace App\Livewire\Saas;

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

    public function mount($roleId, $firmId = null)
    {
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
    
    protected function buildHierarchy()
    {
        $apps = AppModel::with([
            'modules.components.actions' => function ($q) {
                $q->where('is_inactive', false);
            }

        ])->where('is_inactive', false)->orderBy('id', 'asc')->get();

        // Get firm ID from the role
        $firmId = $this->role->firm_id;
        
        // If this is a global role (firm_id is null), show all components
        if ($firmId === null) {
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
            return;
        }
        
        // For firm-specific roles, filter by firm assignments
        // Get panels assigned to this firm
        $assignedPanelIds = \App\Models\Saas\FirmPanel::where('firm_id', $firmId)->pluck('panel_id')->toArray();
        
        // Get components assigned to this firm (from ComponentPanel) with their panel assignments
        $assignedComponentPanels = \App\Models\Saas\ComponentPanel::where('firm_id', $firmId)
            ->select('component_id', 'panel_id')
            ->get()
            ->groupBy('component_id');
        
        $assignedComponentIds = $assignedComponentPanels->keys()->toArray();
        
        $hierarchy = [];
        $processedComponents = []; // Track processed components to avoid duplication
        
        foreach ($apps as $app) {
            $appHasComponents = false;
            $appArr = [
                'id' => $app->id,
                'name' => $app->name,
                'modules' => [],
            ];
            
            foreach ($app->modules as $module) {
                $moduleHasComponents = false;
                $moduleArr = [
                    'id' => $module->id,
                    'name' => $module->name,
                    'components' => [],
                ];
                
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
                    $moduleHasComponents = true;
                    $appHasComponents = true;
                }
                
                // Only add modules that have components
                if ($moduleHasComponents) {
                    $appArr['modules'][] = $moduleArr;
                }
            }
            
            // Only add apps that have components
            if ($appHasComponents) {
                $hierarchy[] = $appArr;
            }
        }
        
        $this->hierarchy = $hierarchy;
    }

    public function save($closeModal = true)
    {
        // For global roles (where firm_id is null), we don't set firm_id in action_role
        $firmId = $this->role->firm_id;  // This will be null for global roles
        $existingActionIds = $this->role->actions()->pluck('actions.id')->toArray();
        $actionsToAdd = array_diff($this->selectedActions, $existingActionIds);
        $actionsToRemove = array_diff($existingActionIds, $this->selectedActions);
        
        foreach ($actionsToAdd as $actionId) {
            ActionRole::create([
                'firm_id' => $firmId,  // Will be null for global roles
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
            text: $this->role->firm_id ? 'Role actions updated successfully!' : 'Global role actions updated successfully!',
        );
    }

    public function render()
    {
        $this->previousSelectedActions = $this->selectedActions;
        return view()->file(app_path('Livewire/Saas/blades/role-action-sync.blade.php'));
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
        $firmId = $this->role->firm_id;
        
        // If this is a global role (firm_id is null), show all actions
        if ($firmId === null) {
        $grouped = [];
        foreach ($apps as $app) {
            foreach ($app->modules as $module) {
                foreach ($module->components as $component) {
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
            return;
        }
        
        // For firm-specific roles, filter by firm assignments
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
        
        $this->groupedActions = $grouped;
    }

    public function selectApp($appName)
    {
        $ids = collect($this->groupedActions[$appName] ?? [])
            ->flatten(3)
            ->pluck('id')
            ->unique()
            ->values()
            ->all();
        $this->selectedActions = array_values(array_diff($this->selectedActions, $ids));
    }

    public function deselectApp($appName)
    {
        $ids = collect($this->groupedActions[$appName] ?? [])
            ->flatten(3)
            ->pluck('id')
            ->unique()
            ->values()
            ->all();
        $this->selectedActions = array_values(array_diff($this->selectedActions, $ids));
    }

    public function toggleModule($appName, $moduleName)
    {
        $ids = collect($this->groupedActions[$appName][$moduleName] ?? [])
            ->flatten(2)
            ->pluck('id')
            ->unique()
            ->values()
            ->all();
        $allSelected = !array_diff($ids, $this->selectedActions);
        if ($allSelected) {
            $this->selectedActions = array_values(array_diff($this->selectedActions, $ids));
        } else {
            $this->selectedActions = array_unique(array_merge($this->selectedActions, $ids));
        }
    }

    public function toggleComponent($appName, $moduleName, $componentName)
    {
        $ids = collect($this->groupedActions[$appName][$moduleName][$componentName] ?? [])
            ->pluck('id')
            ->unique()
            ->values()
            ->all();
        $allSelected = !array_diff($ids, $this->selectedActions);
        if ($allSelected) {
            $this->selectedActions = array_values(array_diff($this->selectedActions, $ids));
        } else {
            $this->selectedActions = array_unique(array_merge($this->selectedActions, $ids));
        }
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

