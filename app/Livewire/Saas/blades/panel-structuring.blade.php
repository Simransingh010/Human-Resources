
<div>


<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="p-6 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-900">Platform Setup</h2>
            <div class="flex items-center space-x-2">
                <flux:button wire:click="showModuleClustersModal" size="sm">Module Clusters</flux:button>
                <flux:button wire:click="showComponentClustersModal" size="sm">Component Clusters</flux:button>
                <flux:button wire:click="showActionClustersModal" size="sm">Action Clusters</flux:button>
            </div>
        </div>
    </div>
    
    <div class="flex h-[70vh]">
        <!-- Applications Column -->
        <div class="w-1/4 border-r border-gray-200 flex flex-col">
            <div class="p-4 bg-blue-50 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-layer-group text-blue-600"></i>
                        <span class="font-semibold text-gray-700">Applications</span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <flux:button size="xs" variant="ghost" icon="bars-3-bottom-right" tooltip="Reorder"
                            wire:click="toggleEditMode('app')"
                            :class="$isEditingApp ? 'text-blue-600' : 'text-gray-500'"
                            class="hover:text-gray-700"/>
                        <flux:modal.trigger name="add-app-modal">
                            <flux:button size="xs" variant="ghost" icon="plus" tooltip="Add new" class="text-gray-500 hover:text-gray-700"/>
                        </flux:modal.trigger>
                    </div>
                </div>
            </div>
            
            <div class="flex-1 overflow-y-auto custom-scrollbar p-2 h-64">
                <ul id="apps-list" class="space-y-2">
                    @foreach($apps as $app)
                        <li class="p-3 bg-blue-50 rounded-lg hover:bg-blue-100 cursor-pointer transition-colors border-l-4 {{ $selectedApplication === $app->code ? 'border-blue-400 bg-blue-100' : 'border-transparent' }} card-hover"
                            wire:click="selectApplication('{{ $app->code }}')"
                            data-id="{{ $app->id }}">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    @if($isEditingApp)
                                        <i class="fas fa-grip-vertical text-gray-400"></i>
                                    @endif
                                    <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                                        <i class="{{ $app->icon ?? 'fas fa-layer-group' }} text-white text-sm"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900">{{ $app->name }}</div>
                                    </div>
                                </div>
                                <flux:modal.trigger name="item-details-modal" wire:click.stop="openItemDetailsModal('app', '{{ $app->code }}')">
                                    <flux:button size="xs" variant="ghost" icon="arrows-pointing-out" tooltip="View and Edit Details" class="text-gray-500 hover:text-gray-700"/>
                                </flux:modal.trigger>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <!-- Modules Column -->
        <div class="w-1/4 border-r border-gray-200 flex flex-col">
            <div class="p-4 bg-green-50 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-puzzle-piece text-green-600"></i>
                        <span class="font-semibold text-gray-700">Modules</span>
                    </div>
                    @if($selectedApplication)
                                            <div class="flex items-center space-x-1">
                        <flux:button size="xs" variant="ghost" icon="bars-3-bottom-right" tooltip="Reorder"
                            wire:click="toggleEditMode('module')"
                            :class="$isEditingModule ? 'text-green-600' : 'text-gray-500'"
                            class="hover:text-gray-700"/>
                        <flux:modal.trigger name="assign-existing-item-modal">
                            <flux:button size="xs" variant="ghost" icon="link" tooltip="Assign existing" class="text-gray-500 hover:text-gray-700" wire:click="openAssignModal('module')"/>
                        </flux:modal.trigger> 
                        <flux:modal.trigger name="add-module-modal">
                            <flux:button size="xs" variant="ghost" tooltip="Add new" icon="plus" class="text-gray-500 hover:text-gray-700"/>
                        </flux:modal.trigger>
                        <flux:modal.trigger name="add-modulecluster-modal">
                            <flux:button size="xs" variant="ghost" tooltip="Add cluster" icon="folder-plus" class="text-green-600 hover:text-green-700"/>
                        </flux:modal.trigger>
                    </div>
                    @endif
                </div>
            </div>
            
            <div class="flex-1 overflow-y-auto custom-scrollbar p-2 h-64">
                @if($selectedApplication && $modules && count($modules))
                    <ul id="modules-list" class="space-y-2">
                        @foreach($modules as $module)
                            @php
                                $moduleId = is_array($module) ? ($module['id'] ?? null) : ($module->id ?? null);
                                $moduleName = is_array($module) ? ($module['name'] ?? '') : ($module->name ?? '');
                                $moduleDesc = is_array($module) ? ($module['description'] ?? '') : ($module->description ?? '');
                            @endphp
                            <li class="p-3 bg-green-50 rounded-lg hover:bg-green-100 cursor-pointer transition-colors border-l-4 {{ $selectedModule == $moduleId ? 'border-green-400 bg-green-100' : 'border-transparent' }} card-hover"
                                wire:click="selectModule('{{ $moduleId }}')"
                                data-id="{{ $moduleId }}">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <i class="fas fa-grip-vertical text-gray-400"></i>
                                        <div class="w-8 h-8 bg-green-600 rounded-lg flex items-center justify-center">
                                            <i class="{{ 'fas fa-puzzle-piece' }} text-white text-sm"></i>
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900">{{ $moduleName }}</div>
                                            <div class="text-xs text-gray-500">{{ $moduleDesc }}</div>
                                        </div>
                                    </div>
                                    <flux:modal.trigger name="item-details-modal" wire:click.stop="openItemDetailsModal('module', '{{ $moduleId }}')">
                                        <flux:button size="xs" variant="ghost" icon="arrows-pointing-out" tooltip="View and Edit Details" class="text-gray-500 hover:text-gray-700"/>
                                    </flux:modal.trigger>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="text-center text-gray-400 mt-10">Select an application to view modules</div>
                @endif
            </div>
        </div>

        <!-- Components Column -->
        <div class="w-1/4 border-r border-gray-200 flex flex-col">
            <div class="p-4 bg-yellow-50 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-cubes text-yellow-600"></i>
                        <span class="font-semibold text-gray-700">Components</span>
                    </div>
                    @if($selectedModule)
                        <div class="flex items-center space-x-1">
                            <flux:button size="xs" variant="ghost" icon="bars-3-bottom-right" tooltip="Reorder"
                                wire:click="toggleEditMode('component')"
                                :class="$isEditingComponent ? 'text-yellow-600' : 'text-gray-500'"
                                class="hover:text-gray-700"/>
                            <flux:modal.trigger name="assign-existing-item-modal">
                                <flux:button size="xs" variant="ghost" icon="link" tooltip="Assign existing" class="text-gray-500 hover:text-gray-700" wire:click="openAssignModal('component')"/>
                            </flux:modal.trigger>
                            <flux:modal.trigger name="add-component-modal">
                                <flux:button size="xs" variant="ghost" tooltip="Add new" icon="plus" class="text-gray-500 hover:text-gray-700"/>
                            </flux:modal.trigger>
                            <flux:modal.trigger name="add-componentcluster-modal">
                                <flux:button size="xs" variant="ghost" tooltip="Add cluster" icon="folder-plus" class="text-yellow-600 hover:text-yellow-700"/>
                            </flux:modal.trigger>
                        </div>
                    @endif
                </div>
            </div>
            
            <div class="flex-1 overflow-y-auto custom-scrollbar p-2 h-64">
                @if($selectedModule && $componentsForModule && count($componentsForModule))
                    <ul id="components-list" class="space-y-2">
                        @foreach($componentsForModule as $component)
                            @php
                                $componentId = is_array($component) ? ($component['id'] ?? null) : ($component->id ?? null);
                                $componentName = is_array($component) ? ($component['name'] ?? '') : ($component->name ?? '');
                                $componentDesc = is_array($component) ? ($component['description'] ?? '') : ($component->description ?? '');
                            @endphp
                            <li class="p-3 bg-yellow-50 rounded-lg hover:bg-yellow-100 cursor-pointer transition-colors border-l-4 {{ $selectedComponent == $componentId ? 'border-yellow-400 bg-yellow-200' : 'border-transparent' }} card-hover"
                                wire:click="selectComponent('{{ $componentId }}')"
                                data-id="{{ $componentId }}">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <i class="fas fa-grip-vertical text-gray-400"></i>
                                        <div class="w-8 h-8 bg-yellow-600 rounded-lg flex items-center justify-center">
                                            <i class="{{ $component->icon ?? 'fas fa-cubes' }} text-white text-sm"></i>
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900">{{ $componentName }}</div>
                                            <div class="text-xs text-gray-500">{{ $componentDesc }}</div>
                                        </div>
                                    </div>
                                    <flux:modal.trigger name="item-details-modal" wire:click.stop="openItemDetailsModal('component', '{{ $componentId }}')">
                                        <flux:button size="xs" variant="ghost" icon="arrows-pointing-out" tooltip="View and Edit Details" class="text-gray-500 hover:text-gray-700"/>
                                    </flux:modal.trigger>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="text-center text-gray-400 mt-10">Select a module to view components</div>
                @endif
            </div>
        </div>

        <!-- Actions Column -->
        <div class="w-1/4 flex flex-col">
            <div class="p-4 bg-purple-50 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-bolt text-purple-600"></i>
                        <span class="font-semibold text-gray-700">Actions</span>
                    </div>
                    @if($selectedComponent)
                        <div class="flex items-center space-x-1">
                            <flux:button size="xs" variant="ghost" icon="bars-3-bottom-right"  tooltip="Reorder"
                                wire:click="toggleEditMode('action')"
                                :class="$isEditingAction ? 'text-purple-600' : 'text-gray-500'"
                                class="hover:text-gray-700"/>
                            <flux:modal.trigger name="add-action-modal">
                                <flux:button size="xs" variant="ghost" icon="plus" tooltip="Add new" class="text-gray-500 hover:text-gray-700"/>
                            </flux:modal.trigger>
                            <flux:modal.trigger name="add-actioncluster-modal">
                                <flux:button size="xs" variant="ghost" tooltip="Add cluster" icon="folder-plus" class="text-purple-600 hover:text-purple-700"/>
                            </flux:modal.trigger>
                        </div>
                    @endif
                </div>
            </div>
            
            <div class="flex-1 overflow-y-auto custom-scrollbar p-2 h-64">
                @if($selectedComponent)
                    <div class="space-y-4">
                        @if($this->organizedActions->isNotEmpty())
                            <div class="space-y-3">
                                @foreach($this->organizedActions as $clusterName => $actionsByType)
                                    @if($actionsByType->isNotEmpty())
                                        <!-- Cluster Header -->
                                        <div class="bg-gray-100 rounded-lg p-2 mb-2">
                                            <div class="text-xs font-semibold text-gray-600 uppercase tracking-wide">
                                                {{ $clusterName }}
                                            </div>
                                        </div>
                                        
                                        <!-- Actions by Type within Cluster -->
                                        @foreach($actionsByType as $actionType => $hierarchicalActions)
                                            @if($hierarchicalActions->isNotEmpty())
                                                <!-- Action Type Header -->
                                                <div class="ml-2 mb-1">
                                                    <div class="text-xs font-medium text-gray-500">
                                                        {{ $actionType ?: 'No Type' }}
                                                    </div>
                                                </div>
                                                
                                                <!-- Actions List -->
                                                <ul id="actions-list" class="space-y-1 ml-2">
                                                    @foreach($hierarchicalActions as $actionData)
                                                        @php
                                                            $action = $actionData['action'];
                                                            $level = $actionData['level'];
                                                            $isParent = $actionData['is_parent'];
                                                            $actionId = $action->id;
                                                            $actionName = $action->name;
                                                            $actionDesc = $action->description ?? '';
                                                            $actionIcon = $action->icon ?? 'fas fa-bolt';
                                                        @endphp
                                                        <li class="p-2 bg-purple-50 rounded-lg hover:bg-purple-100 cursor-pointer transition-colors border-l-4 border-purple-200 card-hover"
                                                            data-id="{{ $actionId }}"
                                                            style="margin-left: {{ $level * 16 }}px;">
                                                            <div class="flex items-center justify-between">
                                                                <div class="flex items-center space-x-3">
                                                                    <i class="fas fa-grip-vertical text-gray-400"></i>
                                                                    <div class="w-6 h-6 bg-purple-600 rounded-lg flex items-center justify-center">
                                                                        <i class="{{ $actionIcon }} text-white text-xs"></i>
                                                                    </div>
                                                                    <div class="flex-1 min-w-0">
                                                                        <div class="font-medium text-gray-900 text-sm">{{ $actionName }}</div>
                                                                        @if($actionDesc)
                                                                            <div class="text-xs text-gray-500 truncate">{{ $actionDesc }}</div>
                                                                        @endif
                                                                        @if($isParent)
                                                                            <div class="text-xs text-purple-600 font-medium">Parent Action</div>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                <flux:modal.trigger name="item-details-modal" wire:click.stop="openItemDetailsModal('action', '{{ $actionId }}')">
                                                                    <flux:button size="xs" variant="ghost" icon="arrows-pointing-out" tooltip="View and Edit Details" class="text-gray-500 hover:text-gray-700"/>
                                                                </flux:modal.trigger>
                                                            </div>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        @endforeach
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <div class="text-center text-gray-400 mt-10">No actions available for this component</div>
                        @endif
                    </div>
                @else
                    <div class="text-center text-gray-400 mt-10">Select a component to view actions</div>
                @endif
            </div>
        </div>
    </div>
</div>


<flux:modal name="item-details-modal" :open="$isModalOpen" @cancel="closeModal" class="max-w-6xl w-full ">
    @if($modalItem)
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ ucfirst($modalItemType) }} Details</flux:heading>
                <flux:subheading>Manage {{ $modalItemType }} information and settings.</flux:subheading>
            </div>

            <!-- Table View -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column>Code</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column>Actions</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        <flux:table.row>
                            <flux:table.cell>
                                        <div class="text-sm font-medium text-gray-900 px-1.5">{{ $modalItem->name }}</div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="text-sm text-gray-900 px-1.5">{{ $modalItem->code }}</div>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($modalItem->is_inactive)
                                    <flux:badge color="red">Inactive</flux:badge>
                                @else
                                    <flux:badge color="green">Active</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center space-x-2">
                                    <flux:modal.trigger name="edit-item-modal">
                                        <flux:button  variant="primary" class="px-2"
                                                     icon="pencil" wire:click="openEditModal('{{ $modalItemType }}', '{{ $modalItem->id }}')"/>
                                    </flux:modal.trigger>
                                    <div class="px-1"></div>
                                    <flux:modal.trigger name="delete-modal-item">

                                            <flux:button variant="danger" icon="trash"/>

{{--                                        <flux:button size="xs" variant="ghost" icon="trash" class="text-red-600 hover:text-red-700"/>--}}
                                    </flux:modal.trigger>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    </flux:table.rows>
                </flux:table>
            </div>

            <!-- Edit Form (shown when isEditingModal is true) -->
            @if($isEditingModal)
                <form wire:submit.prevent="saveModalItem" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($modalFields as $field => $cfg)
                            <div class="@if($cfg['type'] === 'textarea') col-span-2 @endif">
                                @switch($cfg['type'])
                                    @case('select')
                                        @if($field === 'cluster_id')
                                            <div class="space-y-2">
                                                <label class="block text-sm font-medium text-gray-700">{{ $cfg['label'] }}</label>
                                                <flux:dropdown>
                                                    <flux:button variant="outline" class="w-full justify-between">
                                                        {{ is_array($modalItem->selectedClusters ?? null) && count($modalItem->selectedClusters) > 0 ? count($modalItem->selectedClusters) . ' clusters selected' : 'Select clusters' }}
                                                        <i class="fas fa-chevron-down ml-2"></i>
                                                    </flux:button>
                                                    <flux:menu keep-open>
                                                        @if($modalItemType === 'module')
                                                            @foreach($moduleClusters as $cluster)
                                                                <flux:menu.checkbox 
                                                                    wire:model.live="modalItem.selectedClusters" 
                                                                    value="{{ $cluster->id }}"
                                                                    :checked="in_array($cluster->id, is_array($modalItem->selectedClusters ?? null) ? $modalItem->selectedClusters : [])">
                                                                    {{ $cluster->name }}
                                                                </flux:menu.checkbox>
                                                            @endforeach
                                                        @elseif($modalItemType === 'component')
                                                            @foreach($componentClusters as $cluster)
                                                                <flux:menu.checkbox 
                                                                    wire:model.live="modalItem.selectedClusters" 
                                                                    value="{{ $cluster->id }}"
                                                                    :checked="in_array($cluster->id, is_array($modalItem->selectedClusters ?? null) ? $modalItem->selectedClusters : [])">
                                                                    {{ $cluster->name }}
                                                                </flux:menu.checkbox>
                                                            @endforeach
                                                        @elseif($modalItemType === 'action')
                                                            @foreach($actionClusters as $cluster)
                                                                <flux:menu.checkbox 
                                                                    wire:model.live="modalItem.selectedClusters" 
                                                                    value="{{ $cluster->id }}"
                                                                    :checked="in_array($cluster->id, is_array($modalItem->selectedClusters ?? null) ? $modalItem->selectedClusters : [])">
                                                                    {{ $cluster->name }}
                                                                </flux:menu.checkbox>
                                                            @endforeach
                                                        @endif
                                                    </flux:menu>
                                                </flux:dropdown>
                                            </div>
                                        @else
                                            <flux:select label="{{ $cfg['label'] }}" wire:model.live="modalItem.{{ $field }}">
                                                <option value="">Select {{ $cfg['label'] }}</option>
                                                @if($field === 'action_type')
                                                    @foreach($actionTypes as $key => $value)
                                                        <option value="{{ $key }}">{{ $value }}</option>
                                                    @endforeach
                                                @elseif($field === 'parent_action_id')
                                                    @foreach($this->availableParentActions as $action)
                                                        <option value="{{ $action->id }}">{{ $action->name }}</option>
                                                    @endforeach
                                                @endif
                                            </flux:select>
                                        @endif
                                        @break
                                    @case('switch')
                                        <flux:switch label="{{ $cfg['label'] }}" wire:model.live="modalItem.{{ $field }}" />
                                        @break
                                    @case('textarea')
                                        <flux:textarea label="{{ $cfg['label'] }}" wire:model.live="modalItem.{{ $field }}" rows="3" />
                                        @break
                                    @default
                                        <flux:input type="{{ $cfg['type'] }}" label="{{ $cfg['label'] }}" wire:model.live="modalItem.{{ $field }}" />
                                @endswitch
                            </div>
                        @endforeach
                    </div>

                    <div class="flex justify-end pt-4 space-x-2">
                        <flux:button type="button" variant="ghost" wire:click="toggleEditModal">Cancel</flux:button>
                        <flux:button type="submit" variant="primary">Save Changes</flux:button>
                    </div>
                </form>
            @endif
        </div>

        <!-- Delete Confirmation Modal -->
        <flux:modal name="delete-modal-item" class="min-w-[22rem]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Delete {{ ucfirst($modalItemType) }}?</flux:heading>
                    <flux:text class="mt-2">
                        <p>You're about to delete this {{ $modalItemType }}. This action cannot be undone.</p>
                        <p class="mt-2 text-red-500">Note: Items with related records may not be deleted.</p>
                    </flux:text>
                </div>
                <div class="flex gap-2">
                    <flux:spacer/>
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" icon="trash" wire:click="deleteModalItem"/>
                </div>
            </div>
        </flux:modal>
    @endif
</flux:modal>

<!-- Add New App Modal -->
<flux:modal name="add-app-modal" class="w-full">
    <form wire:submit.prevent="addNewApp">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Add New Application</flux:heading>
                <flux:subheading>Create a new application in the system.</flux:subheading>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input label="Name" wire:model.live="newApp.name" required/>
                <flux:input label="Code" wire:model.live="newApp.code"/>
                <flux:input label="Wire" wire:model.live="newApp.wire"/>
                <flux:input label="Route" wire:model.live="newApp.route"/>
                <flux:input label="Icon" wire:model.live="newApp.icon"/>
                <flux:input label="Color" wire:model.live="newApp.color"/>
                <flux:input label="Tooltip" wire:model.live="newApp.tooltip"/>
                <flux:input label="Badge" wire:model.live="newApp.badge"/>
                <flux:input label="Custom CSS" wire:model.live="newApp.custom_css"/>
                <flux:input type="number" label="Order" wire:model.live="newApp.order" value="0"/>
                <flux:switch label="Inactive" wire:model.live="newApp.is_inactive"/>
                <div class="col-span-2">
                    <flux:textarea label="Description" wire:model.live="newApp.description" rows="3"/>
                </div>
            </div>

            <div class="flex justify-end pt-4 space-x-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Add Application</flux:button>
            </div>
        </div>
    </form>
</flux:modal>

<!-- Add New Module Modal -->
<flux:modal name="add-module-modal" class="w-full">
    <form wire:submit.prevent="addNewModule ">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Add New Module</flux:heading>
                <flux:subheading>Create a new module for {{ $this->selectedApplicationName }}.</flux:subheading>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input label="Name" wire:model.live="newModule.name" required/>
                <flux:input label="Code" wire:model.live="newModule.code"/>
                <flux:input label="Wire" wire:model.live="newModule.wire"/>
                <flux:input label="Route" wire:model.live="newModule.route"/>
                <flux:input label="Icon" wire:model.live="newModule.icon"/>
                <flux:input label="Color" wire:model.live="newModule.color"/>
                <flux:input label="Tooltip" wire:model.live="newModule.tooltip"/>
                <flux:input label="Badge" wire:model.live="newModule.badge"/>
                <flux:input label="Custom CSS" wire:model.live="newModule.custom_css"/>
                <flux:input type="number" label="Order" wire:model.live="newModule.order" value="0"/>
                <flux:switch label="Inactive" wire:model.live="newModule.is_inactive"/>
                <div class="col-span-2">
                    <flux:textarea label="Description" wire:model.live="newModule.description" rows="3"/>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Module Clusters</label>
                    <flux:dropdown>
                        <flux:button variant="outline" class="w-full justify-between">
                            {{ is_array($newModule['selectedClusters'] ?? null) && count($newModule['selectedClusters']) > 0 ? count($newModule['selectedClusters']) . ' clusters selected' : 'Select clusters' }}
                            <i class="fas fa-chevron-down ml-2"></i>
                        </flux:button>
                        <flux:menu keep-open>
                            @foreach($moduleClusters as $cluster)
                                <flux:menu.checkbox 
                                    wire:model.live="newModule.selectedClusters" 
                                    value="{{ $cluster->id }}"
                                    :checked="in_array($cluster->id, is_array($newModule['selectedClusters'] ?? null) ? $newModule['selectedClusters'] : [])">
                                    {{ $cluster->name }}
                                </flux:menu.checkbox>
                            @endforeach
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </div>

            <div class="flex justify-end pt-4 space-x-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Add Module</flux:button>
            </div>
        </div>
    </form>
</flux:modal>

<!-- Add New Component Modal -->
<flux:modal name="add-component-modal" class="w-full">
    <form wire:submit.prevent="addNewComponent">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Add New Component</flux:heading>
                <flux:subheading>Create a new component for {{ $this->selectedModuleName }}.</flux:subheading>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input label="Name" wire:model.live="newComponent.name" required/>
                <flux:input label="Code" wire:model.live="newComponent.code"/>
                <flux:input label="Wire" wire:model.live="newComponent.wire"/>
                <flux:input label="Route" wire:model.live="newComponent.route"/>
                <flux:input label="Icon" wire:model.live="newComponent.icon"/>
                <flux:input label="Color" wire:model.live="newComponent.color"/>
                <flux:input label="Tooltip" wire:model.live="newComponent.tooltip"/>
                <flux:input label="Badge" wire:model.live="newComponent.badge"/>
                <flux:input label="Custom CSS" wire:model.live="newComponent.custom_css"/>
                <flux:input type="number" label="Order" wire:model.live="newComponent.order" value="0"/>
                <flux:switch label="Inactive" wire:model.live="newComponent.is_inactive"/>
                <div class="col-span-2">
                    <flux:textarea label="Description" wire:model.live="newComponent.description" rows="3"/>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Component Clusters</label>
                    <flux:dropdown>
                        <flux:button variant="outline" class="w-full justify-between">
                            {{ is_array($newComponent['selectedClusters'] ?? null) && count($newComponent['selectedClusters']) > 0 ? count($newComponent['selectedClusters']) . ' clusters selected' : 'Select clusters' }}
                            <i class="fas fa-chevron-down ml-2"></i>
                        </flux:button>
                        <flux:menu keep-open>
                            @foreach($componentClusters as $cluster)
                                <flux:menu.checkbox 
                                    wire:model.live="newComponent.selectedClusters" 
                                    value="{{ $cluster->id }}"
                                    :checked="in_array($cluster->id, is_array($newComponent['selectedClusters'] ?? null) ? $newComponent['selectedClusters'] : [])">
                                    {{ $cluster->name }}
                                </flux:menu.checkbox>
                            @endforeach
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </div>

            <div class="flex justify-end pt-4 space-x-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Add Component</flux:button>
            </div>
        </div>
    </form>
</flux:modal>

<!-- Add New Action Modal -->
<flux:modal name="add-action-modal" class="w-full">
    <form wire:submit.prevent="addNewAction">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Add New Action</flux:heading>
                <flux:subheading>Create a new action for {{ $this->selectedComponentName }}.</flux:subheading>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input label="Name" wire:model.live="newAction.name" required/>
                <flux:input label="Code" wire:model.live="newAction.code"/>
                <flux:input label="Wire" wire:model.live="newAction.wire"/>
                <flux:input label="Icon" wire:model.live="newAction.icon"/>
                <flux:input label="Color" wire:model.live="newAction.color"/>
                <flux:input label="Tooltip" wire:model.live="newAction.tooltip"/>
                <flux:input label="Badge" wire:model.live="newAction.badge"/>
                <flux:input label="Custom CSS" wire:model.live="newAction.custom_css"/>
                <flux:input type="number" label="Order" wire:model.live="newAction.order" value="0"/>
                <flux:switch label="Inactive" wire:model.live="newAction.is_inactive"/>
                <flux:select label="Action Type" wire:model.live="newAction.action_type">
                    <option value="">Select Action Type</option>
                    @foreach($actionTypes as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </flux:select>
                <flux:select label="Parent Action" wire:model.live="newAction.parent_action_id">
                    <option value="">Select Parent Action (Optional)</option>
                    @foreach($this->availableParentActions as $action)
                        <option value="{{ $action->id }}">{{ $action->name }}</option>
                    @endforeach
                </flux:select>
                <div class="col-span-2">
                    <flux:textarea label="Description" wire:model.live="newAction.description" rows="3"/>
                </div>
                <div class="col-span-2">
                    <flux:select variant="listbox" label="Action Cluster" wire:model.live="newAction.actioncluster_id" :disabled="!empty($newAction['parent_action_id'])">
                        <option value="">No Cluster</option>
                        @foreach($actionClusters as $cluster)
                            <option value="{{ $cluster->id }}">{{ $cluster->name }}</option>
                        @endforeach
                    </flux:select>
                </div>
            </div>

            <div class="flex justify-end pt-4 space-x-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Add Action</flux:button>
            </div>
        </div>
    </form>
</flux:modal>

<!-- Add New Module Cluster Modal -->
<flux:modal name="add-modulecluster-modal" class="w-full">
    <form wire:submit.prevent="addNewModulecluster">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Add New Module Cluster</flux:heading>
                <flux:subheading>Create a new module cluster for organizing modules.</flux:subheading>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input label="Name" wire:model.live="newModulecluster.name" required/>
                <flux:input label="Code" wire:model.live="newModulecluster.code"/>
                <flux:input label="Icon" wire:model.live="newModulecluster.icon"/>
                <flux:input label="Color" wire:model.live="newModulecluster.color"/>
                <flux:input label="Tooltip" wire:model.live="newModulecluster.tooltip"/>
                <flux:input label="Badge" wire:model.live="newModulecluster.badge"/>
                <flux:input label="Custom CSS" wire:model.live="newModulecluster.custom_css"/>
                <flux:input type="number" label="Order" wire:model.live="newModulecluster.order" value="0"/>
                <flux:switch label="Inactive" wire:model.live="newModulecluster.is_inactive"/>
                <flux:select label="Parent Module Cluster" wire:model.live="newModulecluster.parent_modulecluster_id">
                    <option value="">Select Parent Cluster (Optional)</option>
                    @foreach($moduleClusters as $cluster)
                        <option value="{{ $cluster->id }}">{{ $cluster->name }}</option>
                    @endforeach
                </flux:select>
                <div class="col-span-2">
                    <flux:textarea label="Description" wire:model.live="newModulecluster.description" rows="3"/>
                </div>
            </div>

            <div class="flex justify-end pt-4 space-x-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Add Module Cluster</flux:button>
            </div>
        </div>
    </form>
</flux:modal>

<!-- Add New Component Cluster Modal -->
<flux:modal name="add-componentcluster-modal" class="w-full">
    <form wire:submit.prevent="addNewComponentcluster">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Add New Component Cluster</flux:heading>
                <flux:subheading>Create a new component cluster for organizing components.</flux:subheading>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input label="Name" wire:model.live="newComponentcluster.name" required/>
                <flux:input label="Code" wire:model.live="newComponentcluster.code"/>
                <flux:input label="Icon" wire:model.live="newComponentcluster.icon"/>
                <flux:input label="Color" wire:model.live="newComponentcluster.color"/>
                <flux:input label="Tooltip" wire:model.live="newComponentcluster.tooltip"/>
                <flux:input label="Badge" wire:model.live="newComponentcluster.badge"/>
                <flux:input label="Custom CSS" wire:model.live="newComponentcluster.custom_css"/>
                <flux:input type="number" label="Order" wire:model.live="newComponentcluster.order" value="0"/>
                <flux:switch label="Inactive" wire:model.live="newComponentcluster.is_inactive"/>
                <flux:select label="Parent Component Cluster" wire:model.live="newComponentcluster.parent_componentcluster_id">
                    <option value="">Select Parent Cluster (Optional)</option>
                    @foreach($componentClusters as $cluster)
                        <option value="{{ $cluster->id }}">{{ $cluster->name }}</option>
                    @endforeach
                </flux:select>
                <div class="col-span-2">
                    <flux:textarea label="Description" wire:model.live="newComponentcluster.description" rows="3"/>
                </div>
            </div>

            <div class="flex justify-end pt-4 space-x-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Add Component Cluster</flux:button>
            </div>
        </div>
    </form>
</flux:modal>

<!-- Add New Action Cluster Modal -->
<flux:modal name="add-actioncluster-modal" class="w-full">
    <form wire:submit.prevent="addNewActioncluster">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Add New Action Cluster</flux:heading>
                <flux:subheading>Create a new action cluster for organizing actions.</flux:subheading>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input label="Name" wire:model.live="newActioncluster.name" required/>
                <flux:input label="Code" wire:model.live="newActioncluster.code"/>
                <flux:input label="Icon" wire:model.live="newActioncluster.icon"/>
                <flux:input label="Color" wire:model.live="newActioncluster.color"/>
                <flux:input label="Tooltip" wire:model.live="newActioncluster.tooltip"/>
                <flux:input label="Badge" wire:model.live="newActioncluster.badge"/>
                <flux:input label="Custom CSS" wire:model.live="newActioncluster.custom_css"/>
                <flux:input type="number" label="Order" wire:model.live="newActioncluster.order" value="0"/>
                <flux:switch label="Inactive" wire:model.live="newActioncluster.is_inactive"/>
                <flux:select label="Parent Action Cluster" wire:model.live="newActioncluster.parent_actioncluster_id">
                    <option value="">Select Parent Cluster (Optional)</option>
                    @foreach($actionClusters as $cluster)
                        <option value="{{ $cluster->id }}">{{ $cluster->name }}</option>
                    @endforeach
                </flux:select>
                <div class="col-span-2">
                    <flux:textarea label="Description" wire:model.live="newActioncluster.description" rows="3"/>
                </div>
            </div>

            <div class="flex justify-end pt-4 space-x-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Add Action Cluster</flux:button>
            </div>
        </div>
    </form>
</flux:modal>

<!-- Edit Item Modal -->
<flux:modal name="edit-item-modal" :open="$isEditModalOpen" @cancel="closeEditModal" class="max-w-3xl w-full" >
    @if($editItem)
    <form wire:submit.prevent="saveEditItem">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Edit {{ ucfirst($editItemType) }}</flux:heading>
                <flux:subheading>Update {{ $editItemType }} details below.</flux:subheading>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
               
                
                @if($editItemType === 'module')
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Module Clusters</label>
                        <flux:dropdown>
                            <flux:button variant="outline" class="w-full justify-between">
                                {{ is_array($editItem['selectedClusters'] ?? null) && count($editItem['selectedClusters']) > 0 ? count($editItem['selectedClusters']) . ' clusters selected' : 'Select clusters' }}
                                <i class="fas fa-chevron-down ml-2"></i>
                            </flux:button>
                            <flux:menu keep-open>
                                @foreach($moduleClusters as $cluster)
                                    <flux:menu.checkbox 
                                        wire:model.live="editItem.selectedClusters" 
                                        value="{{ $cluster->id }}"
                                        :checked="in_array($cluster->id, is_array($editItem['selectedClusters'] ?? null) ? $editItem['selectedClusters'] : [])">
                                        {{ $cluster->name }}
                                    </flux:menu.checkbox>
                                @endforeach
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                @endif
                @if($editItemType === 'component')
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Component Clusters</label>
                        <flux:dropdown>
                            <flux:button variant="outline" class="w-full justify-between">
                                {{ is_array($editItem['selectedClusters'] ?? null) && count($editItem['selectedClusters']) > 0 ? count($editItem['selectedClusters']) . ' clusters selected' : 'Select clusters' }}
                                <i class="fas fa-chevron-down ml-2"></i>
                            </flux:button>
                            <flux:menu keep-open>
                                @foreach($componentClusters as $cluster)
                                    <flux:menu.checkbox 
                                        wire:model.live="editItem.selectedClusters" 
                                        value="{{ $cluster->id }}"
                                        :checked="in_array($cluster->id, is_array($editItem['selectedClusters'] ?? null) ? $editItem['selectedClusters'] : [])">
                                        {{ $cluster->name }}
                                    </flux:menu.checkbox>
                                @endforeach
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                @endif
                @if($editItemType === 'action')
                    <div class="col-span-2">
                        <flux:select label="Action Cluster" variant="listbox" wire:model.live="editItem.actioncluster_id" placeholder="Select a cluster..." :disabled="!empty($editItem['parent_action_id'])">
                            <flux:select.option value="">No Cluster</flux:select.option>
                            @foreach($actionClusters as $cluster)
                                <flux:select.option value="{{ $cluster->id }}">{{ $cluster->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                @endif
                @foreach($editFields as $field => $cfg)
                    <div class="@if($cfg['type'] === 'textarea') col-span-2 @endif">
                        @switch($cfg['type'])
                            @case('select')
                            
                                <flux:select  label="{{ $cfg['label'] }}" wire:model.live="editItem.{{ $field }}">
                                    <option value="">Select {{ $cfg['label'] }}</option>
                                    @if($field === 'action_type')
                                        @foreach($actionTypes as $key => $value)
                                            <option value="{{ $key }}">{{ $value }}</option>
                                        @endforeach
                                    @elseif($field === 'parent_action_id')
                                        @foreach($this->availableParentActions as $action)
                                            <option value="{{ $action->id }}">{{ $action->name }}</option>
                                        @endforeach
                                    @endif
                                </flux:select>
                                @break
                            @case('switch')
                                <flux:switch label="{{ $cfg['label'] }}" wire:model.live="editItem.{{ $field }}" />
                                @break
                            @case('textarea')
                                <flux:textarea label="{{ $cfg['label'] }}" wire:model.live="editItem.{{ $field }}" rows="3" />
                                @break
                            @default
                                <flux:input type="{{ $cfg['type'] }}" label="{{ $cfg['label'] }}" wire:model.live="editItem.{{ $field }}" />
                        @endswitch
                    </div>
                @endforeach
            </div>
            <div class="flex justify-end pt-4 space-x-2">
                <flux:button type="button" variant="ghost" wire:click="closeEditModal">Cancel</flux:button>
                <flux:button type="submit" variant="primary">Save Changes</flux:button>
            </div>
        </div>
    </form>
    @else
        <div class="flex justify-center items-center p-8">
            <i class="fas fa-spinner fa-spin text-4xl text-gray-400"></i>
        </div>
    @endif
</flux:modal>

<!-- Assign Existing Item Modal -->
<flux:modal name="assign-existing-item-modal" :open="$isAssignModalOpen" @cancel="closeAssignModal" class="w-full max-w-2xl" >
    @if(!$isAssignModalLoading)
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Assign Existing {{ ucfirst($assignType ?? '') }}</flux:heading>
        </div>
        <flux:separator />
        <div class="flex justify-between items-center">
            <div class="w-full">
                <flux:input wire:model.live.debounce.300ms="searchTerm" placeholder="Search items..." icon="magnifying-glass" />
            </div>
            <div class="ml-4 flex-shrink-0">
                <flux:checkbox wire:model.live="selectAll" label="Select All" />
            </div>
        </div>
        <div class="space-y-4 overflow-y-auto max-h-[60vh] pr-2 h-64">
            <flux:checkbox.group class="ml-2 mt-2 space-y-1">
                @forelse($allItems as $item)
                    <div class="flex items-start hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md p-1">
                        <flux:checkbox 
                            :checked="in_array($item->id, $selectedItems)"
                            wire:change="toggleAssignment({{ $item->id }})"
                            value="{{ $item->id }}"
                            label="{{ $item->name }}"
                        />
                        <div class="ml-3 text-xs text-gray-500">
                            @if(!empty($item->description))
                                {{ $item->description }}
                            @else
                                (No description available)
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4">No items found.</p>
                @endforelse
            </flux:checkbox.group>
        </div>
        <div class="mt-4 flex justify-end items-center">
            <div class="space-x-2">
                <flux:button wire:click="closeAssignModal">Close</flux:button>
            </div>
        </div>
    </div>
    @else
    <div class="flex justify-center items-center p-12">
        <i class="fas fa-spinner fa-spin text-4xl text-gray-400"></i>
    </div>
    @endif
</flux:modal>

<div id="sortable-loading-overlay" style="position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:1000;background:rgba(255,255,255,0.7);display:flex;align-items:center;justify-content:center;font-size:2rem;display:none;">
  <div>
    <i class="fas fa-spinner fa-spin"></i> Initializing drag-and-drop...
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4" defer></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    
    body {
        font-family: 'Inter', sans-serif;
    }
    
    .card-hover {
        transition: all 0.2s ease-in-out;
    }
    
    .card-hover:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    
    .gradient-bg {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .custom-scrollbar {
        scrollbar-width: thin;
        scrollbar-color: #e5e7eb #f3f4f6;
    }
    
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f3f4f6;
        border-radius: 3px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #e5e7eb;
        border-radius: 3px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #d1d5db;
    }
    
    .status-indicator {
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
</style>
<script>
  let sortableInitialized = false;
  function showSortableOverlay() {
    document.getElementById('sortable-loading-overlay').style.display = 'flex';
  }
  function hideSortableOverlay() {
    document.getElementById('sortable-loading-overlay').style.display = 'none';
  }
  function initSortableAll() {
    showSortableOverlay();
    const lists = [
      { selector: '#apps-list',       type: 'app'       },
      { selector: '#modules-list',    type: 'module'    },
      { selector: '#components-list', type: 'component' },
      { selector: '#actions-list',    type: 'action'    },
    ];
    lists.forEach(({ selector, type }) => {
      const el = document.querySelector(selector);
      if (!el) return;
      // destroy old instance
      if (el._sortable) {
        el._sortable.destroy();
        el._sortable = null;
      }
      // re-init Sortable (handle option temporarily removed for testing)
      el._sortable = new Sortable(el, {
        animation: 150,
        ghostClass: 'bg-gray-100',
        onEnd(evt) {
          const items = Array.from(el.children).map((li, i) => ({
            id: li.dataset.id,
            order: i,
          }));
          console.log('onEnd fired', items);
          @this.call('updateOrder', type, items);
        },
      });
      el.classList.add('cursor-move');
      console.log(`Sortable initialized for ${selector}`);
    });
    if (!sortableInitialized) {
      hideSortableOverlay();
      sortableInitialized = true;
    }
  }
  // run once on page load
  document.addEventListener('DOMContentLoaded', function() {
    showSortableOverlay();
    initSortableAll();
  });
  // re-run whenever Livewire tells us to
  window.addEventListener('sortable:init', function() {
    showSortableOverlay();
    setTimeout(() => {
      initSortableAll();
      hideSortableOverlay();
    }, 0);
  });
</script>



<!-- Module Clusters Modal -->
<flux:modal name="module-clusters-modal" title="Manage Module Clusters" class="p-10 max-w-7xl">
    <livewire:saas.moduleclusters />
</flux:modal>

<!-- Component Clusters Modal -->
<flux:modal name="component-clusters-modal" title="Manage Component Clusters" class="p-10 max-w-7xl">
    <livewire:saas.componentclusters />
</flux:modal>

<!-- Action Clusters Modal -->
<flux:modal name="action-clusters-modal" title="Manage Action Clusters" class="p-10 max-w-7xl">
    <livewire:saas.actionclusters />
</flux:modal>

</div>