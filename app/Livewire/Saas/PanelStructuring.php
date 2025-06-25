<?php

    namespace App\Livewire\Hrms\Leave;

    use Livewire\Component;
    use Illuminate\Support\Facades\Session;

    use App\Models\Saas\App as SaasApp;
    use App\Models\Saas\Module;
    use App\Models\Saas\Component as SaasComponent;
    use App\Models\Saas\Action as SaasAction;
    use App\Models\Saas\Modulecluster;
    use App\Models\Saas\Componentcluster;
    use App\Models\Saas\Actioncluster;

    class PanelStructuring extends Component
    {
        // All applications from DB
        public $apps = [];
        public $modules = [];
        public $componentsForModule = []; // New property for componentsaa
        public $actionsForComponent = []; // New property for actions

        // Current selection state
        public $selectedApplication = null;
        public $selectedModule = null;
        public $selectedSection = null;
        public $selectedComponent = null;

        // Collapsed state for columns
        public $collapsed = [1 => false, 2 => false, 3 => false, 4 => false];

        // Modal state properties
        public $isModalOpen = false;
        public $modalItem = null;
        public $modalItemType = null;
        public $modalFields = [];
        public $isEditingModal = false;

        // New properties for adding items
        public $newApp = [
            'name' => '',
            'code' => '',
            'wire' => '',
            'description' => '',
            'icon' => '',
            'route' => '',
            'color' => '',
            'tooltip' => '',
            'order' => 0,
            'badge' => '',
            'custom_css' => '',
            'is_inactive' => 0,
        ];

        public $newModule = [
            'name' => '',
            'code' => '',
            'wire' => '',
            'description' => '',
            'icon' => '',
            'route' => '',
            'color' => '',
            'tooltip' => '',
            'order' => 0,
            'badge' => '',
            'custom_css' => '',
            'is_inactive' => 0,
            'selectedClusters' => [],
        ];

        public $newComponent = [
            'name' => '',
            'code' => '',
            'wire' => '',
            'description' => '',
            'icon' => '',
            'route' => '',
            'color' => '',
            'tooltip' => '',
            'order' => 0,
            'badge' => '',
            'custom_css' => '',
            'is_inactive' => 0,
            'selectedClusters' => [],
        ];

        public $newAction = [
            'name' => '',
            'code' => '',
            'wire' => '',
            'description' => '',
            'icon' => '',
            'color' => '',
            'tooltip' => '',
            'order' => 0,
            'badge' => '',
            'custom_css' => '',
            'is_inactive' => 0,
            'action_type' => '',
            'parent_action_id' => null,
            'actioncluster_id' => null,
        ];

        // Add these properties after the existing properties
        public $isEditingApp = false;
        public $isEditingModule = false;
        public $isEditingComponent = false;
        public $isEditingAction = false;

        public $isAssignModalOpen = false;
        public $isAssignModalLoading = false;
        public $assignableItems = [];
        public $selectedItemsToAssign = [];
        public $assignType = null;
        public $searchTerm = '';
        public $selectAll = false;
        public $assignedItems = [];
        public $allItems = [];
        public $selectedItems = [];

        // Computed properties for modal titles
        public function getSelectedApplicationNameProperty()
        {
            if (!$this->selectedApplication) return '';
            $app = SaasApp::where('code', $this->selectedApplication)->first();
            return $app ? $app->name : '';
        }

        public function getSelectedModuleNameProperty()
        {
            if (!$this->selectedModule) return '';
            $module = Module::find($this->selectedModule);
            return $module ? $module->name : '';
        }

        public function getSelectedComponentNameProperty()
        {
            if (!$this->selectedComponent) return '';
            $component = SaasComponent::find($this->selectedComponent);
            return $component ? $component->name : '';
        }

        public function getAvailableParentActionsProperty()
        {
            if (!$this->selectedComponent) return collect();
            $component = SaasComponent::find($this->selectedComponent);
            return $component ? $component->actions()->where('is_inactive', 0)->orderBy('name')->get() : collect();
        }

        public function getOrganizedActionsProperty()
        {
            if (!$this->selectedComponent) return collect();
            
            $component = SaasComponent::find($this->selectedComponent);
            if (!$component) return collect();
            
            $actions = $component->actions()
                ->with(['actioncluster', 'parentAction'])
                ->where('is_inactive', 0)
                ->orderBy('order')
                ->get();
            
            // Group by cluster first, then by action type
            $organized = collect();
            
            // Get all clusters that have actions
            $clustersWithActions = $actions->filter(function($action) {
                return $action->actioncluster !== null;
            })->map(function($action) {
                return $action->actioncluster;
            })->unique('id')->sortBy('name');
            
            foreach ($clustersWithActions as $cluster) {
                $clusterActions = $actions->filter(function($action) use ($cluster) {
                    return $action->actioncluster && $action->actioncluster->id === $cluster->id;
                });
                
                // Group actions by action type within this cluster and organize with hierarchy
                $actionsByType = $clusterActions->groupBy('action_type')->map(function($typeActions) {
                    return $this->organizeActionsWithHierarchy($typeActions);
                });
                
                $organized->put($cluster->name, $actionsByType);
            }
            
            // Also handle actions without clusters
            $actionsWithoutClusters = $actions->filter(function($action) {
                return !$action->actioncluster;
            });
            
            if ($actionsWithoutClusters->isNotEmpty()) {
                $organized->put('No Cluster', $actionsWithoutClusters->groupBy('action_type')->map(function($typeActions) {
                    return $this->organizeActionsWithHierarchy($typeActions);
                }));
            }
            
            return $organized;
        }

        protected function organizeActionsWithHierarchy($actions)
        {
            $organized = collect();
            $parentActions = $actions->whereNull('parent_action_id');
            $childActions = $actions->whereNotNull('parent_action_id');
            
            foreach ($parentActions as $parentAction) {
                $organized->push([
                    'action' => $parentAction,
                    'level' => 0,
                    'is_parent' => true
                ]);
                
                // Add children of this parent
                $children = $childActions->where('parent_action_id', $parentAction->id);
                foreach ($children as $childAction) {
                    $organized->push([
                        'action' => $childAction,
                        'level' => 1,
                        'is_parent' => false
                    ]);
                }
            }
            
            // Add orphaned child actions (children without parents)
            $orphanedChildren = $childActions->filter(function($childAction) use ($actions) {
                return !$actions->contains('id', $childAction->parent_action_id);
            });
            
            foreach ($orphanedChildren as $orphanedChild) {
                $organized->push([
                    'action' => $orphanedChild,
                    'level' => 0,
                    'is_parent' => false
                ]);
            }
            
            return $organized;
        }

        public $moduleClusters = [];
        public $componentClusters = [];
        public $actionClusters = [];
        public $actionTypes = [];

        public $editItemType = null;
        public $editFields = [];

        public function mount()
        {
            // Fetch all apps from DB (active only)
            $this->apps = SaasApp::where('is_inactive', 0)->orderBy('order')->get();

            // Load clusters
            $this->loadClusters();
            
            // Load action types
            $this->actionTypes = SaasAction::ACTION_TYPE_MAIN_SELECT;
            
            $this->isEditingApp = true;
            $this->isEditingModule = true;
            $this->isEditingComponent = true;
            $this->isEditingAction = true;
        }

        protected function loadClusters()
        {
            $this->moduleClusters = Modulecluster::where('is_inactive', 0)
                ->orderBy('name')
                ->get();


            $this->componentClusters = Componentcluster::where('is_inactive', 0)
                ->orderBy('name')
                ->get();

            $this->actionClusters = Actioncluster::where('is_inactive', 0)
                ->orderBy('name')
                ->get();
        }

        // Select application
        public function selectApplication($code)
        {
            $this->selectedApplication = $code;
            $this->selectedModule = null;
            $this->selectedSection = null;
            $this->selectedComponent = null;
            $this->componentsForModule = []; // Clear components when application changes
            // Fetch modules for this app
            $app = SaasApp::where('code', $code)->first();
            $this->modules = $app ? $app->modules()->where('is_inactive', 0)->orderBy('order')->get() : collect();
            $this->dispatch('sortable:init');
        }

        // Select module
        public function selectModule($id)
        {
            $this->selectedModule = $id;
            $this->selectedSection = null; // No longer used for display, but reset for consistency
            $this->selectedComponent = null;
            // Fetch components for this module
            $module = Module::find($id);
            $this->componentsForModule = $module ? $module->components()->where('is_inactive', 0)->orderBy('order')->get() : collect();
            $this->dispatch('sortable:init');
        }

        // Select section (this method will become less relevant but keep for now if blade still references it)


        // Select component
        public function selectComponent($id)
        {
            $this->selectedComponent = $id;
            // Fetch actions for this component
            $component = SaasComponent::find($id);
            $this->actionsForComponent = $component ? $component->actions()->where('is_inactive', 0)->orderBy('order')->get() : collect();
            $this->dispatch('sortable:init');
        }

        // Toggle collapse for columns
        public function toggleCollapse($column)
        {
            $this->collapsed[$column] = !$this->collapsed[$column];
        }

        public function openItemDetailsModal($type, $id)
        {
            $this->reset(['modalItem', 'modalItemType', 'modalFields', 'isEditingModal']); // Clear previous data

            $this->modalItemType = $type;

            switch ($type) {
                case 'app':
                    $this->modalItem = SaasApp::where('code', $id)->first();
                    $this->modalFields = [
                        'name' => ['label' => 'Name', 'type' => 'text'],
                        'code' => ['label' => 'Code', 'type' => 'text'],
                        'description' => ['label' => 'Description', 'type' => 'textarea'],
                        'icon' => ['label' => 'Icon', 'type' => 'text'],
                        'color' => ['label' => 'Color', 'type' => 'text'],
                        'tooltip' => ['label' => 'Tooltip', 'type' => 'text'],
                        'badge' => ['label' => 'Badge', 'type' => 'text'],
                        'custom_css' => ['label' => 'Custom CSS', 'type' => 'text'],
                        'order' => ['label' => 'Order', 'type' => 'number'],
                        'is_inactive' =>     ['label' => 'Inactive', 'type' => 'switch']
                    ];
                    break;
                case 'module':
                    $this->modalItem = Module::find($id);
                    $this->modalItem->selectedClusters = ($this->modalItem->moduleclusters ?? collect())->pluck('id')->toArray();
                    $this->modalItem = $this->ensureSelectedClustersIsArray($this->modalItem, 'module');
                    $this->modalFields = [
                        'name' => ['label' => 'Name', 'type' => 'text'],
                        'code' => ['label' => 'Code', 'type' => 'text'],
                        'description' => ['label' => 'Description', 'type' => 'textarea'],
                        'icon' => ['label' => 'Icon', 'type' => 'text'],
                        'color' => ['label' => 'Color', 'type' => 'text'],
                        'tooltip' => ['label' => 'Tooltip', 'type' => 'text'],
                        'badge' => ['label' => 'Badge', 'type' => 'text'],
                        'custom_css' => ['label' => 'Custom CSS', 'type' => 'text'],
                        'order' => ['label' => 'Order', 'type' => 'number'],
                        'is_inactive' => ['label' => 'Inactive', 'type' => 'switch'],
                        'cluster_id' => ['label' => 'Module Clusters', 'type' => 'select']
                    ];
                    break;
                case 'component':
                    $this->modalItem = SaasComponent::find($id);
                    $this->modalItem->selectedClusters = ($this->modalItem->componentclusters ?? collect())->pluck('id')->toArray();
                    $this->modalItem = $this->ensureSelectedClustersIsArray($this->modalItem, 'component');
                    $this->modalFields = [
                        'name' => ['label' => 'Name', 'type' => 'text'],
                        'code' => ['label' => 'Code', 'type' => 'text'],
                        'description' => ['label' => 'Description', 'type' => 'textarea'],
                        'icon' => ['label' => 'Icon', 'type' => 'text'],
                        'color' => ['label' => 'Color', 'type' => 'text'],
                        'tooltip' => ['label' => 'Tooltip', 'type' => 'text'],
                        'badge' => ['label' => 'Badge', 'type' => 'text'],
                        'custom_css' => ['label' => 'Custom CSS', 'type' => 'text'],
                        'order' => ['label' => 'Order', 'type' => 'number'],
                        'is_inactive' => ['label' => 'Inactive', 'type' => 'switch'],
                        'cluster_id' => ['label' => 'Component Clusters', 'type' => 'select']
                    ];
                    break;
                case 'action':
                    $this->modalItem = SaasAction::find($id);
                    // Handle cluster relationship
                    if ($this->modalItem->actioncluster) {
                        $this->modalItem->selectedClusters = [$this->modalItem->actioncluster->id];
                    } else {
                        $this->modalItem->selectedClusters = [];
                    }
                    $this->modalItem = $this->ensureSelectedClustersIsArray($this->modalItem, 'action');
                    $this->modalFields = [
                        'name' => ['label' => 'Name', 'type' => 'text'],
                        'code' => ['label' => 'Code', 'type' => 'text'],
                        'description' => ['label' => 'Description', 'type' => 'textarea'],
                        'icon' => ['label' => 'Icon', 'type' => 'text'],
                        'color' => ['label' => 'Color', 'type' => 'text'],
                        'tooltip' => ['label' => 'Tooltip', 'type' => 'text'],
                        'badge' => ['label' => 'Badge', 'type' => 'text'],
                        'custom_css' => ['label' => 'Custom CSS', 'type' => 'text'],
                        'order' => ['label' => 'Order', 'type' => 'number'],
                        'is_inactive' => ['label' => 'Inactive', 'type' => 'switch'],
                        'action_type' => ['label' => 'Action Type', 'type' => 'select'],
                        'parent_action_id' => ['label' => 'Parent Action', 'type' => 'select'],
                        'cluster_id' => ['label' => 'Action Clusters', 'type' => 'select']
                    ];
                    break;
            }

            $this->isModalOpen = true;
        }

        public function closeModal()
        {
            $this->isModalOpen = false;
            $this->reset(['modalItem', 'modalItemType', 'modalFields', 'isEditingModal']);
        }

        public function toggleEditModal()
        {
            $this->isEditingModal = !$this->isEditingModal;

            // When entering edit mode, reload the item from DB to ensure fresh values
            if ($this->isEditingModal && $this->modalItemType && $this->modalItem) {
                switch ($this->modalItemType) {
                    case 'app':
                        $this->modalItem = \App\Models\Saas\App::find($this->modalItem->id);
                        break;
                    case 'module':
                        $this->modalItem = \App\Models\Saas\Module::find($this->modalItem->id);
                        $this->modalItem->selectedClusters = ($this->modalItem->moduleclusters ?? collect())->pluck('id')->toArray();
                        $this->modalItem = $this->ensureSelectedClustersIsArray($this->modalItem, 'module');
                        break;
                    case 'component':
                        $this->modalItem = \App\Models\Saas\Component::find($this->modalItem->id);
                        $this->modalItem->selectedClusters = ($this->modalItem->componentclusters ?? collect())->pluck('id')->toArray();
                        $this->modalItem = $this->ensureSelectedClustersIsArray($this->modalItem, 'component');
                        break;
                    case 'action':
                        $this->modalItem = \App\Models\Saas\Action::find($this->modalItem->id);
                        // Handle cluster relationship for actions
                        if ($this->modalItem->actioncluster) {
                            $this->modalItem->selectedClusters = [$this->modalItem->actioncluster->id];
                        } else {
                            $this->modalItem->selectedClusters = [];
                        }
                        $this->modalItem = $this->ensureSelectedClustersIsArray($this->modalItem, 'action');
                        break;
                }
            }
        }

        public function saveModalItem()
        {
            $this->validate([
                'modalItem.name' => 'required',
                'modalItem.code' => 'required',
                'modalItem.selectedClusters' => 'nullable|array'
            ]);

            $item = $this->modalItem;

            // Handle cluster relationships
            if (isset($item->selectedClusters)) {
                // Ensure selectedClusters is always an array
                $selectedClusters = is_array($item->selectedClusters) ? $item->selectedClusters : [];
                
                if ($this->modalItemType === 'action') {
                    // For actions, use the one-to-many relationship
                    $item->actioncluster_id = !empty($selectedClusters) ? $selectedClusters[0] : null;
                } else {
                    $clusterMethod = $this->getClusterMethod();
                    $item->$clusterMethod()->sync($selectedClusters);
                }
            }

            $item->save();

            $this->isModalOpen = false;
            $this->isEditingModal = false;

            // Refresh the data
            $this->loadClusters();
            $this->selectApplication($this->selectedApplication);
        }

        protected function getClusterTable()
        {
            switch ($this->modalItemType) {
                case 'module':
                    return 'moduleclusters';
                case 'component':
                    return 'componentclusters';
                case 'action':
                    return 'actionclusters';
                default:
                    return '';
            }
        }

        protected function getClusterMethod()
        {
            switch ($this->modalItemType) {
                case 'module':
                    return 'moduleclusters';
                case 'component':
                    return 'componentclusters';
                case 'action':
                    return 'actionclusters';
                default:
                    return '';
            }
        }

        public function deleteModalItem()
        {
            if ($this->modalItem) {
                try {
                    $this->modalItem->delete();
                    Session::flash('message', ucfirst($this->modalItemType) . ' deleted successfully.');
                    $this->closeModal();
                    // Refresh lists
                    $this->mount();
                    $this->selectedApplication = null;
                    $this->selectedModule = null;
                    $this->selectedComponent = null;
                    $this->modules = collect();
                    $this->componentsForModule = collect();
                    $this->actionsForComponent = collect();
                } catch (\Exception $e) {
                    Session::flash('error', 'Cannot delete ' . $this->modalItemType . ': ' . $e->getMessage());
                }
            }
        }

        // Methods for adding new items
        public function addNewApp()
        {
            $validatedData = $this->validate([
                'newApp.name' => 'required|string|max:255',
                'newApp.code' => 'nullable|string|max:255',
                'newApp.wire' => 'nullable|string|max:255',
                'newApp.description' => 'nullable|string',
                'newApp.icon' => 'nullable|string|max:255',
                'newApp.route' => 'nullable|string|max:255',
                'newApp.color' => 'nullable|string|max:255',
                'newApp.tooltip' => 'nullable|string|max:255',
                'newApp.order' => 'required|integer',
                'newApp.badge' => 'nullable|string|max:255',
                'newApp.custom_css' => 'nullable|string',
                'newApp.is_inactive' => 'boolean',
            ]);

            try {
                $appData = $validatedData['newApp'];
                
                $app = SaasApp::create($appData);
                
                $this->reset('newApp');
                $this->mount(); // Refresh the apps list
                $this->modal('add-app-modal')->close();
                Session::flash('message', 'Application added successfully.');
            } catch (\Exception $e) {
                Session::flash('error', 'Failed to add application: ' . $e->getMessage());
            }
            $this->dispatch('sortable:init');
        }

        public function addNewModule()
        {
            if (!$this->selectedApplication) {
                Session::flash('error', 'Please select an application first.');
                return;
            }

            $validatedData = $this->validate([
                'newModule.name' => 'required|string|max:255',
                'newModule.code' => 'nullable|string|max:255',
                'newModule.wire' => 'nullable|string|max:255',
                'newModule.description' => 'nullable|string',
                'newModule.icon' => 'nullable|string|max:255',
                'newModule.route' => 'nullable|string|max:255',
                'newModule.color' => 'nullable|string|max:255',
                'newModule.tooltip' => 'nullable|string|max:255',
                'newModule.order' => 'required|integer',
                'newModule.badge' => 'nullable|string|max:255',
                'newModule.custom_css' => 'nullable|string',
                'newModule.is_inactive' => 'boolean',
                'newModule.selectedClusters' => 'nullable|array',
            ]);

            try {
                $app = SaasApp::where('code', $this->selectedApplication)->first();
                $moduleData = $validatedData['newModule'];
                $selectedClusters = is_array($moduleData['selectedClusters'] ?? null) ? $moduleData['selectedClusters'] : [];
                unset($moduleData['selectedClusters']);
                
                $module = $app->modules()->create($moduleData);
                
                // Handle cluster relationships if any clusters are selected
                if (!empty($selectedClusters)) {
                    $module->moduleclusters()->attach($selectedClusters);
                }
                
                $this->reset('newModule');
                $this->selectApplication($this->selectedApplication); // Refresh modules list
                $this->modal('add-module-modal')->close();
                Session::flash('message', 'Module added successfully.');
            } catch (\Exception $e) {
                Session::flash('error', 'Failed to add module: ' . $e->getMessage());
            }
            $this->dispatch('sortable:init');
        }

        public function addNewComponent()
        {
            if (!$this->selectedModule) {
                Session::flash('error', 'Please select a module first.');
                return;
            }

            $validatedData = $this->validate([
                'newComponent.name' => 'required|string|max:255',
                'newComponent.code' => 'nullable|string|max:255',
                'newComponent.wire' => 'nullable|string|max:255',
                'newComponent.description' => 'nullable|string',
                'newComponent.icon' => 'nullable|string|max:255',
                'newComponent.route' => 'nullable|string|max:255',
                'newComponent.color' => 'nullable|string|max:255',
                'newComponent.tooltip' => 'nullable|string|max:255',
                'newComponent.order' => 'required|integer',
                'newComponent.badge' => 'nullable|string|max:255',
                'newComponent.custom_css' => 'nullable|string',
                'newComponent.is_inactive' => 'boolean',
                'newComponent.selectedClusters' => 'nullable|array',
            ]);

            try {
                $module = Module::find($this->selectedModule);
                $componentData = $validatedData['newComponent'];
                $selectedClusters = is_array($componentData['selectedClusters'] ?? null) ? $componentData['selectedClusters'] : [];
                unset($componentData['selectedClusters']);
                
                $component = $module->components()->create($componentData);
                
                // Handle cluster relationships if any clusters are selected
                if (!empty($selectedClusters)) {
                    $component->componentclusters()->attach($selectedClusters);
                }
                
                $this->reset('newComponent');
                $this->selectModule($this->selectedModule); // Refresh components list
                $this->modal('add-component-modal')->close();
                Session::flash('message', 'Component added successfully.');
            } catch (\Exception $e) {
                Session::flash('error', 'Failed to add component: ' . $e->getMessage());
            }
            $this->dispatch('sortable:init');
        }

        public function addNewAction()
        {
            if (!$this->selectedComponent) {
                Session::flash('error', 'Please select a component first.');
                return;
            }

            $validatedData = $this->validate([
                'newAction.name' => 'required|string|max:255',
                'newAction.code' => 'nullable|string|max:255',
                'newAction.wire' => 'nullable|string|max:255',
                'newAction.description' => 'nullable|string',
                'newAction.icon' => 'nullable|string|max:255',
                'newAction.color' => 'nullable|string|max:255',
                'newAction.tooltip' => 'nullable|string|max:255',
                'newAction.order' => 'required|integer',
                'newAction.badge' => 'nullable|string|max:255',
                'newAction.custom_css' => 'nullable|string',
                'newAction.is_inactive' => 'boolean',
                'newAction.action_type' => 'nullable|string',
                'newAction.parent_action_id' => 'nullable|integer',
                'newAction.actioncluster_id' => 'nullable|integer',
            ]);

            try {
                $component = SaasComponent::find($this->selectedComponent);
                $actionData = $validatedData['newAction'];

                $action = $component->actions()->create(array_merge($actionData, [
                    'component_id' => $this->selectedComponent
                ]));

                $this->reset('newAction');
                $this->modal('add-action-modal')->close();
                Session::flash('message', 'Action added successfully.');
                $this->refreshData('action');
            } catch (\Exception $e) {
                Session::flash('error', 'Failed to add action: ' . $e->getMessage());
            }
        }

        // Add these methods before the render method
        public function toggleEditMode($type)
        {
            switch ($type) {
                case 'app':
                    $this->isEditingApp = !$this->isEditingApp;
                    break;
                case 'module':
                    $this->isEditingModule = !$this->isEditingModule;
                    break;
                case 'component':
                    $this->isEditingComponent = !$this->isEditingComponent;
                    break;
                case 'action':
                    $this->isEditingAction = !$this->isEditingAction;
                    break;
            }

            $this->dispatch('toggleEditMode', isEditing: true);
        }

        public function updateOrder($type, $items)
        {
            try {
                switch ($type) {
                    case 'app':
                        foreach ($items as $item) {
                            SaasApp::where('id', $item['id'])->update(['order' => $item['order']]);
                        }
                        $this->mount(); // Refresh the apps list
                        break;

                    case 'module':
                        foreach ($items as $item) {
                            Module::where('id', $item['id'])->update(['order' => $item['order']]);
                        }
                        if ($this->selectedApplication) {
                            $this->selectApplication($this->selectedApplication); // Refresh modules list
                        }
                        break;

                    case 'component':
                        foreach ($items as $item) {
                            SaasComponent::where('id', $item['id'])->update(['order' => $item['order']]);
                        }
                        if ($this->selectedModule) {
                            $this->selectModule($this->selectedModule); // Refresh components list
                        }
                        break;

                    case 'action':
                        foreach ($items as $item) {
                            SaasAction::where('id', $item['id'])->update(['order' => $item['order']]);
                        }
                        if ($this->selectedComponent) {
                            $this->selectComponent($this->selectedComponent); // Refresh actions list
                        }
                        break;
                }

                Session::flash('message', 'Order updated successfully.');
            } catch (\Exception $e) {
                Session::flash('error', 'Failed to update order: ' . $e->getMessage());
            }
            $this->dispatch('sortable:init');
        }

        public $isEditModalOpen = false;
        public $editItem = null;

        public function openEditModal($type, $id)
        {
            $this->reset('editItem', 'editItemType', 'editFields', 'isEditModalOpen');
            $this->editItemType = $type;
            switch ($type) {
                case 'app':
                    $model = \App\Models\Saas\App::find($id);
                    break;
                case 'module':
                    $model = \App\Models\Saas\Module::find($id);
                    break;
                case 'component':
                    $model = \App\Models\Saas\Component::find($id);
                    break;
                case 'action':
                    $model = \App\Models\Saas\Action::find($id);
                    break;
                default:
                    $model = null;
            }
            if ($model) {
                $this->editItem = $model->toArray();
                
                // Handle cluster relationships for different types
                if ($type === 'module') {
                    $this->editItem['selectedClusters'] = ($model->moduleclusters ?? collect())->pluck('id')->toArray();
                    $this->editItem = $this->ensureEditItemSelectedClustersIsArray($this->editItem, 'module');
                } elseif ($type === 'component') {
                    $this->editItem['selectedClusters'] = ($model->componentclusters ?? collect())->pluck('id')->toArray();
                    $this->editItem = $this->ensureEditItemSelectedClustersIsArray($this->editItem, 'component');
                }
                
                $this->editFields = [
                    'name' => ['label' => 'Name', 'type' => 'text'],
                    'code' => ['label' => 'Code', 'type' => 'text'],
                    'wire' => ['label' => 'Wire', 'type' => 'text'],
                    'description' => ['label' => 'Description', 'type' => 'textarea'],
                    'icon' => ['label' => 'Icon', 'type' => 'text'],
                    'route' => ['label' => 'Route', 'type' => 'text'],
                    'color' => ['label' => 'Color', 'type' => 'text'],
                    'tooltip' => ['label' => 'Tooltip', 'type' => 'text'],
                    'order' => ['label' => 'Order', 'type' => 'number'],
                    'badge' => ['label' => 'Badge', 'type' => 'text'],
                    'custom_css' => ['label' => 'Custom CSS', 'type' => 'textarea'],
                    'is_inactive' => ['label' => 'Inactive', 'type' => 'switch'],
                ];
                
                // Add action-specific fields
                if ($type === 'action') {
                    $this->editFields['action_type'] = ['label' => 'Action Type', 'type' => 'select'];
                    $this->editFields['parent_action_id'] = ['label' => 'Parent Action', 'type' => 'select'];
                }
                
                $this->isEditModalOpen = true;
            }
        }

        public function closeEditModal()
        {
            $this->isEditModalOpen = false;
            $this->editItem = null;
            $this->editItemType = null;
            $this->editFields = [];
        }

        public function saveEditItem()
        {
            if ($this->editItem && $this->editItemType && isset($this->editItem['id'])) {
                switch ($this->editItemType) {
                    case 'app':
                        $model = \App\Models\Saas\App::find($this->editItem['id']);
                        break;
                    case 'module':
                        $model = \App\Models\Saas\Module::find($this->editItem['id']);
                        break;
                    case 'component':
                        $model = \App\Models\Saas\Component::find($this->editItem['id']);
                        break;
                    case 'action':
                        $model = \App\Models\Saas\Action::find($this->editItem['id']);
                        break;
                    default:
                        $model = null;
                }
                if ($model) {
                    // Handle cluster relationships before saving
                    if (in_array($this->editItemType, ['module', 'component']) && isset($this->editItem['selectedClusters'])) {
                        $selectedClusters = is_array($this->editItem['selectedClusters']) ? $this->editItem['selectedClusters'] : [];
                        $clusterMethod = $this->editItemType . 'clusters';
                        $model->$clusterMethod()->sync($selectedClusters);
                        unset($this->editItem['selectedClusters']);
                    }
                    
                    $model->fill($this->editItem);
                    $model->save();
                    Session::flash('message', ucfirst($this->editItemType) . ' updated successfully.');

                    $itemType = $this->editItemType;
                    $this->closeEditModal();
                    $this->closeModal();
                    $this->refreshData($itemType);
                }
            }
        }

        public function updatedSearchTerm()
        {
            $this->loadAssignableItems();
        }

        public function updatedSelectAll($value)
        {
            if ($value) {
                $this->selectedItemsToAssign = $this->assignableItems->pluck('id')->map(fn ($id) => (string) $id)->toArray();
            } else {
                $this->selectedItemsToAssign = [];
            }
        }

        public function loadAssignableItems()
        {
            $this->allItems = collect();
            $this->selectedItems = [];
            switch ($this->assignType) {
                case 'module':
                    if ($this->selectedApplication) {
                        $app = SaasApp::with('modules')->where('code', $this->selectedApplication)->first();
                        if ($app) {
                            $assigned = $app->modules()->orderBy('name');
                            if ($this->searchTerm) {
                                $assigned->where('name', 'like', '%' . $this->searchTerm . '%');
                            }
                            $assignedItems = $assigned->get(['modules.id', 'modules.name', 'modules.description']);
                            $this->selectedItems = $assignedItems->pluck('id')->map(fn($id) => (string)$id)->toArray();
                            $query = Module::query();
                            if ($this->searchTerm) {
                                $query->where('name', 'like', '%' . $this->searchTerm . '%');
                            }
                            $this->allItems = $query->orderBy('name')->get(['modules.id', 'modules.name', 'modules.description']);
                            $this->allItems = $this->allItems->sortBy(function ($item) {
                                return in_array((string)$item->id, $this->selectedItems) ? 0 : 1;
                            })->values();
                        }
                    }
                    break;
                case 'component':
                    if ($this->selectedModule) {
                        $module = Module::with('components')->find($this->selectedModule);
                        if ($module) {
                            $assigned = $module->components()->orderBy('name');
                            if ($this->searchTerm) {
                                $assigned->where('name', 'like', '%' . $this->searchTerm . '%');
                            }
                            $assignedItems = $assigned->get(['components.id', 'components.name', 'components.description']);
                            $this->selectedItems = $assignedItems->pluck('id')->map(fn($id) => (string)$id)->toArray();
                            $query = SaasComponent::query();
                            if ($this->searchTerm) {
                                $query->where('name', 'like', '%' . $this->searchTerm . '%');
                            }
                            $this->allItems = $query->orderBy('name')->get(['components.id', 'components.name', 'components.description']);
                            $this->allItems = $this->allItems->sortBy(function ($item) {
                                return in_array((string)$item->id, $this->selectedItems) ? 0 : 1;
                            })->values();
                        }
                    }
                    break;
            }
        }

        public function toggleAssignment($id)
        {
            $id = (string)$id;
            switch ($this->assignType) {
                case 'module':
                    if ($this->selectedApplication) {
                        $app = SaasApp::where('code', $this->selectedApplication)->first();
                        if (in_array($id, $this->selectedItems)) {
                            // Unlink
                            $app->modules()->detach($id);
                            $this->selectedItems = array_values(array_diff($this->selectedItems, [$id]));
                        } else {
                            // Link
                            $app->modules()->attach($id);
                            $this->selectedItems[] = $id;
                        }
                    }
                    break;
                case 'component':
                    if ($this->selectedModule) {
                        $module = Module::find($this->selectedModule);
                        if (in_array($id, $this->selectedItems)) {
                            $module->components()->detach($id);
                            $this->selectedItems = array_values(array_diff($this->selectedItems, [$id]));
                        } else {
                            $module->components()->attach($id);
                            $this->selectedItems[] = $id;
                        }
                    }
                    break;
            }
        }

        public function openAssignModal($type)
        {
            $this->isAssignModalLoading = true;
            $this->assignType = $type;
            $this->loadAssignableItems();
            $this->isAssignModalLoading = false;
            $this->isAssignModalOpen = true;
        }

        public function closeAssignModal()
        {
            $this->isAssignModalOpen = false;
            $this->isAssignModalLoading = false;
            $this->reset('assignableItems', 'selectedItemsToAssign', 'assignType', 'searchTerm', 'selectAll', 'allItems', 'selectedItems');
        }

        public function assignSelectedItems()
        {
            if (empty($this->selectedItemsToAssign)) {
                $this->closeAssignModal();
                return;
            }

            try {
                switch ($this->assignType) {
                    case 'module':
                        if ($this->selectedApplication) {
                            $app = SaasApp::where('code', $this->selectedApplication)->first();
                            $app->modules()->attach($this->selectedItemsToAssign);
                            $this->selectApplication($this->selectedApplication);
                        }
                        break;
                    case 'component':
                        if ($this->selectedModule) {
                            $module = Module::find($this->selectedModule);
                            $module->components()->attach($this->selectedItemsToAssign);
                            $this->selectModule($this->selectedModule);
                        }
                        break;
                    case 'action':
                        if ($this->selectedComponent) {
                            SaasAction::whereIn('id', $this->selectedItemsToAssign)
                                ->update(['component_id' => $this->selectedComponent]);
                            $this->selectComponent($this->selectedComponent);
                        }
                        break;
                }
                Session::flash('message', ucfirst($this->assignType) . '(s) assigned successfully.');
            } catch (\Exception $e) {
                Session::flash('error', 'Failed to assign items: ' . $e->getMessage());
            }

            $this->closeAssignModal();
        }

        public function unlinkAssignedItem($id)
        {
            switch ($this->assignType) {
                case 'module':
                    if ($this->selectedApplication) {
                        $app = SaasApp::where('code', $this->selectedApplication)->first();
                        $app->modules()->detach($id);
                        $this->loadAssignableItems();
                        $this->selectApplication($this->selectedApplication);
                    }
                    break;
                case 'component':
                    if ($this->selectedModule) {
                        $module = Module::find($this->selectedModule);
                        $module->components()->detach($id);
                        $this->loadAssignableItems();
                        $this->selectModule($this->selectedModule);
                    }
                    break;
            }
        }

        public function reset(...$properties)
        {
            $property = $properties[0] ?? null;
            if (empty($properties)) {
                // Reset all properties
                parent::reset();
                // Re-initialize selectedClusters arrays
                $this->newModule['selectedClusters'] = [];
                $this->newComponent['selectedClusters'] = [];
                $this->newAction['actioncluster_id'] = null;
            } else {
                // Reset specific properties
                parent::reset(...$properties);
                // Re-initialize selectedClusters arrays if they were reset
                foreach ($properties as $property) {
                    if ($property === 'newModule') {
                        $this->newModule['selectedClusters'] = [];
                    } elseif ($property === 'newComponent') {
                        $this->newComponent['selectedClusters'] = [];
                    } elseif ($property === 'newAction') {
                        $this->newAction['actioncluster_id'] = null;
                    }
                }
            }
        }

        protected function ensureSelectedClustersIsArray($item, $type)
        {
            if (!isset($item->selectedClusters) || !is_array($item->selectedClusters)) {
                $item->selectedClusters = [];
            }
            return $item;
        }

        protected function ensureEditItemSelectedClustersIsArray($item, $type)
        {
            if (!isset($item['selectedClusters']) || !is_array($item['selectedClusters'])) {
                $item['selectedClusters'] = [];
            }
            return $item;
        }

        public function updatedNewActionParentActionId($value)
        {
            if (!empty($value)) {
                $parentAction = SaasAction::find($value);
                if ($parentAction) {
                    $this->newAction['actioncluster_id'] = $parentAction->actioncluster_id;
                }
            }
        }

        public function updatedEditItemParentActionId($value)
        {
            if (!empty($value)) {
                $parentAction = SaasAction::find($value);
                if ($parentAction) {
                    $this->editItem['actioncluster_id'] = $parentAction->actioncluster_id;
                }
            }
        }

        private function refreshData(string $type)
        {
            switch ($type) {
                case 'app':
                    $this->mount();
                    break;
                case 'module':
                    if ($this->selectedApplication) {
                        $this->selectApplication($this->selectedApplication);
                    }
                    break;
                case 'component':
                    if ($this->selectedModule) {
                        $this->selectModule($this->selectedModule);
                    }
                    break;
                case 'action':
                    if ($this->selectedComponent) {
                        $this->selectComponent($this->selectedComponent);
                    }
                    break;
            }
            $this->dispatch('sortable:init');
        }

        public function render()
        {
            return \view()->file(\app_path('Livewire/Hrms/Leave/blades/panel-structuring.blade.php'));
        }
    }
