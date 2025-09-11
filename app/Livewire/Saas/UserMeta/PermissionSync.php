<?php

namespace App\Livewire\Saas\UserMeta;

use App\Models\Saas\Action;
use App\Models\Saas\App as AppModel;
use App\Models\User;
use Flux;
use Livewire\Component;

class PermissionSync extends Component
{
    public User $user;
    public array $selectedActions = [];
    public array $actionRecordScopes = [];
    private array $previousSelectedActions = [];
    public array $hierarchy = [];
    public $selectedAppId = null;
    public $selectedModuleId = null;
    public $selectedAppName = null;
    public $selectedComponentId = null;
    public array $groupedActions = [];
    public $userFirmsCollection;
    public ?int $firmId = null;

    public function mount($userId, $firmId = null)
    {
        $this->user = User::findOrFail($userId);
        $this->firmId = $firmId;

        // Initialize userFirmsCollection first
        $userFirms = $this->user->firms;
        $this->userFirmsCollection = $userFirms->keyBy('name');
        
        // Get actions for specific firm if firmId is provided
        if ($firmId) {
            $this->selectedActions = $this->user->actions()
                ->wherePivot('firm_id', $firmId)
                ->pluck('actions.id')
                ->map(fn($id) => "{$id}|{$firmId}")
                ->toArray();

            // Load record scopes
            $this->actionRecordScopes = $this->user->actions()
                ->wherePivot('firm_id', $firmId)
                ->get()
                ->mapWithKeys(fn($a) => ["{$a->id}|{$firmId}" => $a->pivot->records_scope ?? 'none'])
                ->toArray();
        } else {
            $this->selectedActions = $this->user->actions()
                ->pluck('actions.id')
                ->map(fn($id) => "{$id}|{$firmId}")
                ->toArray();
        }

        $this->previousSelectedActions = $this->selectedActions;
        $this->initListsForFields();
        $this->buildHierarchy();
        
        // Set default selected app to the first app if available
        if (empty($this->selectedAppName) && !empty($this->groupedActions)) {
            $firstFirm = array_key_first($this->groupedActions);
            if ($firstFirm && !empty($this->groupedActions[$firstFirm])) {
                $this->selectedAppName = array_key_first($this->groupedActions[$firstFirm]);
            }
        }
    }

    protected function buildHierarchy()
    {
        $apps = AppModel::with([
            'modules.components.actions' => function ($q) {
                $q->where('is_inactive', false);
            }
        ])->where('is_inactive', false)->orderBy('id', 'asc')->get();

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
        $ACTION_TYPE_LABELS = Action::ACTION_TYPE_MAIN_SELECT;
        $ACTION_TYPE_LABELS['INDEPENDENT'] = 'Independent with no Type';

        $apps = AppModel::with([
            'modules.components.actions.actioncluster' => fn($q) => $q->where('is_inactive', false),
            'modules.components.actions.parentAction',
            'modules.components.actions.childActions',
        ])->where('is_inactive', false)->orderBy('id')->get();

        // Remove firm-specific component filtering - show all components and actions
        $actionHierarchy = [];
        foreach ($apps as $app) {
            foreach ($app->modules as $module) {
                foreach ($module->components as $component) {
                    // Show all components regardless of firm assignment
                    $actionsByType = [];
                    foreach ($ACTION_TYPE_LABELS as $typeKey => $typeLabel) {
                        $actionsOfType = ($typeKey === 'INDEPENDENT')
                            ? $component->actions->whereNull('action_type')
                            : $component->actions->where('action_type', $typeKey);

                        if ($actionsOfType->isEmpty()) {
                            continue;
                        }

                        $actionsByCluster = $actionsOfType->groupBy(fn($action) => $action->actioncluster->name ?? 'Independent');
                        $clusters = [];
                        foreach ($actionsByCluster as $clusterName => $actions) {
                            $parentActions = $actions->whereNull('parent_action_id');
                            $childActions = $actions->whereNotNull('parent_action_id');
                            
                            $clusterGroups = [];
                            foreach($parentActions as $parent) {
                                $clusterGroups[] = [
                                    'parent' => [
                                        'id' => $parent->id,
                                        'name' => $parent->name,
                                        'code' => $parent->code,
                                        'type' => $typeLabel,
                                    ],
                                    'children' => $childActions->where('parent_action_id', $parent->id)
                                        ->map(function($child) use ($typeLabel) {
                                            return [
                                                'id' => $child->id,
                                                'name' => $child->name,
                                                'code' => $child->code,
                                                'type' => $typeLabel,
                                            ];
                                        })->values()->all(),
                                ];
                            }
                            $clusters[$clusterName] = $clusterGroups;
                        }
                        $actionsByType[$typeKey] = [
                            'type_label' => $typeLabel,
                            'type_bg' => $ACTION_TYPE_BG[$typeKey] ?? 'bg-gray-50',
                            'clusters' => $clusters,
                        ];
                    }
                    $actionHierarchy[$app->name][$module->name][$component->name] = $actionsByType;
                }
            }
        }

        $grouped = [];
        
        // If a specific firm is selected, only show that firm's data
        if ($this->firmId) {
            $firm = $this->userFirmsCollection->firstWhere('id', $this->firmId);
            if ($firm) {
                $grouped[$firm->name] = $actionHierarchy;
            }
        } else {
            // If no specific firm, show all firms (for multi-firm view)
            foreach ($this->userFirmsCollection as $firm) {
                $grouped[$firm->name] = $actionHierarchy;
            }
        }

        $this->groupedActions = $grouped;
    }

    public function selectApp($firmName, $appName)
    {
        $ids = $this->getActionIdsFor($firmName, $appName);
        $this->selectedActions = array_values(array_unique(array_merge($this->selectedActions, $ids)));
        
        // Set default record scope for newly selected actions
        foreach ($ids as $actionId) {
            if (!isset($this->actionRecordScopes[$actionId])) {
                $this->actionRecordScopes[$actionId] = 'all';
            }
        }

        $this->save(false);
    }

    public function deselectApp($firmName, $appName)
    {
        $ids = $this->getActionIdsFor($firmName, $appName);
        $this->selectedActions = array_values(array_diff($this->selectedActions, $ids));
        
        // Set record scopes to none for deselected actions
        foreach ($ids as $actionId) {
            $this->actionRecordScopes[$actionId] = 'none';
        }

        $this->save(false);
    }

    public function toggleModule($firmName, $appName, $moduleName)
    {
        $ids = $this->getActionIdsFor($firmName, $appName, $moduleName);
        $allSelected = empty(array_diff($ids, $this->selectedActions));

        if ($allSelected) {
            // Deselect all actions in module
            $this->selectedActions = array_values(array_diff($this->selectedActions, $ids));
            
            // Set record scopes to none for deselected actions
            foreach ($ids as $actionId) {
                $this->actionRecordScopes[$actionId] = 'none';
            }
        } else {
            // Select all actions in module
            $this->selectedActions = array_values(array_unique(array_merge($this->selectedActions, $ids)));
            
            // Set record scopes to all for selected actions
            foreach ($ids as $actionId) {
                $this->actionRecordScopes[$actionId] = 'all';
            }
        }

        $this->save(false);
    }

    public function toggleComponent($firmName, $appName, $moduleName, $componentName)
    {
        $ids = $this->getActionIdsFor($firmName, $appName, $moduleName, $componentName);
        $allSelected = empty(array_diff($ids, $this->selectedActions));
        
        if ($allSelected) {
            // Deselect all actions in component
            $this->selectedActions = array_values(array_diff($this->selectedActions, $ids));
            
            // Set record scopes to none for deselected actions
            foreach ($ids as $actionId) {
                $this->actionRecordScopes[$actionId] = 'none';
            }
        } else {
            // Select all actions in component
            $this->selectedActions = array_values(array_unique(array_merge($this->selectedActions, $ids)));
            
            // Set record scopes to all for selected actions
            foreach ($ids as $actionId) {
                $this->actionRecordScopes[$actionId] = 'all';
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

    public function selectRecordScope($actionId, $firmId, $scope)
    {
        $value = "{$actionId}|{$firmId}";
        $this->actionRecordScopes[$value] = $scope;

        if ($scope === 'none') {
            if (($key = array_search($value, $this->selectedActions)) !== false) {
                unset($this->selectedActions[$key]);
                $this->selectedActions = array_values($this->selectedActions);
            }
        } else {
            if (!in_array($value, $this->selectedActions)) {
                $this->selectedActions[] = $value;
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

    public function save($closeModal = true)
    {
        if ($this->firmId) {
            // First remove all actions for this firm
            $this->user->actions()->wherePivot('firm_id', $this->firmId)->detach();
            
            // Then attach new actions with firm_id and record scope
            foreach ($this->selectedActions as $actionValue) {
                [$actionId, $firmId] = explode('|', $actionValue);
                if ((int)$firmId === $this->firmId) {
                    $this->user->actions()->attach($actionId, [
                        'firm_id' => $this->firmId,
                        'records_scope' => $this->actionRecordScopes[$actionValue] ?? 'none'
                    ]);
                }
            }
        }

        if ($closeModal) {
            Flux::modal('permission-sync')->close();
            
            Flux::toast(
                variant: 'success',
                heading: 'Changes saved.',
                text: 'Actions updated successfully!',
            );
        }
    }

    public function render()
    {
        $this->previousSelectedActions = $this->selectedActions;
        return view('livewire.saas.user-meta.permission-sync');
    }

    private function getActionIdsFor($firmName, $appName, $moduleName = null, $componentName = null)
    {
        // Check if firm exists in collection
        if (!isset($this->userFirmsCollection[$firmName])) {
            return [];
        }

        $firmId = $this->userFirmsCollection[$firmName]->id;

        // Check if firm exists in grouped actions
        if (!isset($this->groupedActions[$firmName])) {
            return [];
        }

        // Get the firm's data
        $firmData = $this->groupedActions[$firmName];
        
        // Check if app exists in firm data
        if (!isset($firmData[$appName])) {
            return [];
        }
        
        // Get the app's data from the firm
        $appData = $firmData[$appName];
        
        if ($moduleName && isset($appData[$moduleName])) {
            $data = $appData[$moduleName];
            if ($componentName && isset($data[$componentName])) {
                $data = $data[$componentName];
            }
        } else {
            $data = $appData;
        }

        // Collect all action IDs recursively
        $actionIds = [];
        
        // Helper function to extract action IDs from clusters
        $extractActionIds = function($typeData) use (&$actionIds) {
            if (!empty($typeData['clusters'])) {
                foreach ($typeData['clusters'] as $clusters) {
                    foreach ($clusters as $group) {
                        if (isset($group['parent']['id'])) {
                            $actionIds[] = $group['parent']['id'];
                        }
                        if (!empty($group['children'])) {
                            foreach ($group['children'] as $child) {
                                if (isset($child['id'])) {
                                    $actionIds[] = $child['id'];
                                }
                            }
                        }
                    }
                }
            }
        };

        // If we're looking at a specific component
        if ($componentName) {
            foreach ($data as $typeData) {
                $extractActionIds($typeData);
            }
        }
        // If we're looking at a module
        else if ($moduleName) {
            foreach ($data as $component) {
                foreach ($component as $typeData) {
                    $extractActionIds($typeData);
                }
            }
        }
        // If we're looking at an app
        else {
            foreach ($data as $modules) {
                foreach ($modules as $components) {
                    foreach ($components as $typeData) {
                        $extractActionIds($typeData);
                    }
                }
            }
        }

        $actionIds = array_unique($actionIds);
        return array_map(fn($id) => "{$id}|{$firmId}", $actionIds);
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
