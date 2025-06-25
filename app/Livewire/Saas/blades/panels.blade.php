<div class="w-full p-0 m-0">
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-panel" class="flex justify-end">
            <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">
                New
            </flux:button>
        </flux:modal.trigger>
    </div>
    <flux:separator class="mt-2 mb-2" />
    <!-- Heading End -->

    <!-- Modal Start -->
    <flux:modal name="mdl-panel" @cancel="resetForm" position="right" class="max-w-none">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Panel @else Add Panel @endif
                    </flux:heading>
                    <flux:subheading>
                        @if($isEditing) Update @else Add new @endif panel details.
                    </flux:subheading>
                </div>

                <!-- Grid layout for form fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input
                            label="Name"
                            wire:model="formData.name"
                            placeholder="Panel Name"
                    />
                      <flux:input
                            label="Code"
                            wire:model="formData.code"
                            placeholder="Panel Code"
                    />
                    <flux:textarea
                            label="Description"
                            wire:model="formData.description"
                            placeholder="Panel Description"
                    />
                    <flux:select
                            label="Panel Type"
                            wire:model="formData.panel_type"
                    >
                        <option value="">Select Type</option>
                        @foreach($this->listsForFields['panel_type'] as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                    </flux:select>
                    <flux:switch
                            wire:model.live="formData.is_inactive"
                            label="Mark as Inactive"
                    />
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end pt-4">
                    <flux:button type="submit" variant="primary">
                        Save
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
    <!-- Modal End -->

    <!-- Table Start-->
    <flux:table :paginate="$this->list" class="">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column>Name</flux:table.column>
            <flux:table.column>Code</flux:table.column>
            <flux:table.column>Description</flux:table.column>
            <flux:table.column>Panel Type</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->list as $rec)
                <flux:table.row :key="$rec->id" class="border-b">
                    <flux:table.cell class="table-cell-wrap">{{ $rec->name }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->code }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->description }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ ucfirst($rec->panel_type_label) }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:switch
                                wire:model="statuses.{{ $rec->id }}"
                                wire:click="toggleStatus({{ $rec->id }})"
                        />
                    </flux:table.cell>
                    <!-- In your actions cell -->
                    <flux:table.cell>
                        <div class="flex space-x-2">
                            <flux:button wire:click="showComponentSync({{ $rec->id }})" color="zinc" size="xs">Components
                            </flux:button>
                            <flux:button wire:click="showAppSync({{ $rec->id }})" color="zinc" size="xs">App
                            </flux:button>

                            <flux:button wire:click="showModuleSync({{ $rec->id }})" color="zinc" size="xs">Module
                            </flux:button>
                            <flux:button wire:click="showPanelStructure({{ $rec->id }})" color="blue" size="xs">Structure</flux:button>

                            <flux:button
                                    variant="primary"
                                    size="sm"
                                    icon="pencil"
                                    wire:click="edit({{ $rec->id }})"
                            />
                                <flux:modal.trigger name="delete-{{ $rec->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"/>
                            </flux:modal.trigger>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
    <!-- Table End-->
    <!-- Shared Modal -->
    <flux:modal name="app-sync" title="Manage Panels" class="p-10">
        @if($selectedPanelId)
            <livewire:saas.panel-meta.app-sync :panelId="$selectedPanelId" :wire:key="'app-sync-'.$selectedPanelId"/>
        @endif
    </flux:modal>

    <flux:modal name="module-sync" title="Manage Modules" class="p-10">
        @if($selectedPanelId)
            <livewire:saas.panel-meta.module-sync :panelId="$selectedPanelId"
                                                  :wire:key="'module-sync-'.$selectedPanelId"/>
        @endif
    </flux:modal>

{{--    <flux:modal name="component-sync" variant="flyout" title="Manage Components" class="p-10" class="min-h-[60vh] max-h-[90vh] overflow-y-auto">--}}
    <flux:modal name="component-sync"  variant="flyout" class="max-w-5xl min-h-[70vh] max-h-[85vh] overflow-y-auto">
        @if($selectedPanelId)
            <livewire:saas.panel-meta.component-sync :panelId="$selectedPanelId"
                                                  :wire:key="'component-sync-'.$selectedPanelId"/>
            <div class="bg-white rounded-lg shadow p-6 max-w-4xl mx-auto my-8">
                <h2 class="text-2xl font-bold mb-4 flex items-center">
                    <svg class="w-6 h-6 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Panel Structure
                </h2>
                <div>
                    @foreach($this->panelTreeHierarchy as $app)
                        <div class="mb-2">
                            <div class="flex items-center cursor-pointer" wire:click="$toggle('appOpen.{{ $app['id'] }}')">
                                <svg class="w-4 h-4 mr-2 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 01.993.883L11 3v2h2a1 1 0 01.117 1.993L13 7h-2v2a1 1 0 01-1.993.117L9 9V7H7a1 1 0 01-.117-1.993L7 5h2V3a1 1 0 01.883-.993L10 2z" /></svg>
                                <span class="font-semibold text-blue-700">{{ $app['name'] }}</span>
                                <span class="ml-2 px-2 py-0.5 rounded bg-blue-100 text-blue-600 text-xs">App</span>
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                            @if(isset($appOpen[$app['id']]) && $appOpen[$app['id']])
                            <div class="ml-6 border-l-2 border-blue-100 pl-4 mt-1">
                                @foreach($app['modules'] as $module)
                                    <div class="mb-1">
                                        <div class="flex items-center cursor-pointer" wire:click="$toggle('moduleOpen.{{ $module['id'] }}')">
                                            <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 01.993.883L11 3v2h2a1 1 0 01.117 1.993L13 7h-2v2a1 1 0 01-1.993.117L9 9V7H7a1 1 0 01-.117-1.993L7 5h2V3a1 1 0 01.883-.993L10 2z" /></svg>
                                            <span class="font-semibold text-green-700">{{ $module['name'] }}</span>
                                            <span class="ml-2 px-2 py-0.5 rounded bg-green-100 text-green-600 text-xs">Module</span>
                                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                        </div>
                                        @if(isset($moduleOpen[$module['id']]) && $moduleOpen[$module['id']])
                                        <div class="ml-6 border-l-2 border-green-100 pl-4 mt-1">
                                            @foreach($module['components'] as $component)
                                                <div class="mb-1">
                                                    <div class="flex items-center cursor-pointer" wire:click="$toggle('componentOpen.{{ $component['id'] }}')">
                                                        <svg class="w-4 h-4 mr-2 text-purple-500" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="8" /></svg>
                                                        <span class="font-semibold text-purple-700">{{ $component['name'] }}</span>
                                                        <span class="ml-2 px-2 py-0.5 rounded bg-purple-100 text-purple-600 text-xs">Component</span>
                                                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                                    </div>
                                                    @if(isset($componentOpen[$component['id']]) && $componentOpen[$component['id']])
                                                    <div class="ml-6 border-l-2 border-purple-100 pl-4 mt-1">
                                                        @foreach($component['actions'] as $action)
                                                            <div class="flex items-center mb-1">
                                                                <svg class="w-4 h-4 mr-2 text-pink-500" fill="currentColor" viewBox="0 0 20 20"><rect width="16" height="4" x="2" y="8" rx="2" /></svg>
                                                                <span class="text-pink-700">{{ $action['name'] }}</span>
                                                                <span class="ml-2 px-2 py-0.5 rounded bg-pink-100 text-pink-600 text-xs">Action</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </flux:modal>   
    <!-- Panel Structure Modal -->
    <flux:modal name="panel-structure" class="max-w-5xl min-h-[70vh] max-h-[85vh] overflow-y-auto">
        @if($selectedPanelId)
            <div class="bg-white p-6">
                <!-- Header -->
                <div class="mb-6 border-b border-gray-200 pb-4">
                    <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 text-gray-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                        </svg>
                        Panel Structure
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">Hierarchical view of panel components and relationships</p>
                </div>

                <!-- Tree Structure -->
                <div class="tree-container" x-data="{ expandedNodes: {} }">
                    <!-- Root Panel -->
                    <div class="tree-node root-node">
                        <div class="node-content">
                            <div class="node-icon">
                                <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                                </svg>
                            </div>
                            <span class="node-label">{{ $selectedPanel->name ?? 'Panel' }}</span>
                            <span class="node-type panel">Panel</span>
                        </div>
                        
                        <!-- Applications Level -->
                        <div class="tree-children">
                            @foreach($this->panelTreeHierarchy as $app)
                                <div class="tree-node" data-level="1" x-data="{ isExpanded: true }">
                                    <div class="node-content expandable" 
                                         @click="isExpanded = !isExpanded"
                                         :class="{ 'expanded': isExpanded }">
                                        @if(count($app['modules']) > 0)
                                            <div class="expand-icon">
                                                <svg class="w-3 h-3 text-gray-500 transition-transform duration-200" 
                                                     :class="{ 'rotate-90': isExpanded }"
                                                     fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                        @else
                                            <div class="expand-icon"></div>
                                        @endif
                                        <div class="node-icon">
                                            <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
                                            </svg>
                                        </div>
                                        <span class="node-label">{{ $app['name'] }}</span>
                                        <span class="node-type app">Application</span>
                                    </div>
                                    
                                    @if(count($app['modules']) > 0)
                                        <!-- Modules Level -->
                                        <div class="tree-children" x-show="isExpanded" x-transition>
                                            @foreach($app['modules'] as $module)
                                                <div class="tree-node" data-level="2" x-data="{ isExpanded: false }">
                                                    <div class="node-content expandable"
                                                         @click="isExpanded = !isExpanded"
                                                         :class="{ 'expanded': isExpanded }">
                                                        @if(count($module['components']) > 0)
                                                            <div class="expand-icon">
                                                                <svg class="w-3 h-3 text-gray-500 transition-transform duration-200"
                                                                     :class="{ 'rotate-90': isExpanded }"
                                                                     fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                                                </svg>
                                                            </div>
                                                        @else
                                                            <div class="expand-icon"></div>
                                                        @endif
                                                        <div class="node-icon">
                                                            <svg class="w-4 h-4 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                                                <path d="M3 7v10a2 2 0 002 2h10a2 2 0 002-2V9a2 2 0 00-2-2h-1V5a2 2 0 00-2-2H8a2 2 0 00-2 2v2H5a2 2 0 00-2 2z"/>
                                                            </svg>
                                                        </div>
                                                        <span class="node-label">{{ $module['name'] }}</span>
                                                        <span class="node-type module">Module</span>
                                                    </div>
                                                    
                                                    @if(count($module['components']) > 0)
                                                        <!-- Components Level -->
                                                        <div class="tree-children" x-show="isExpanded" x-transition>
                                                            @foreach($module['components'] as $component)
                                                                <div class="tree-node" data-level="3" x-data="{ isExpanded: false }">
                                                                    <div class="node-content expandable"
                                                                         @click="isExpanded = !isExpanded"
                                                                         :class="{ 'expanded': isExpanded }">
                                                                        @if(count($component['actions']) > 0)
                                                                            <div class="expand-icon">
                                                                                <svg class="w-3 h-3 text-gray-500 transition-transform duration-200"
                                                                                     :class="{ 'rotate-90': isExpanded }"
                                                                                     fill="currentColor" viewBox="0 0 20 20">
                                                                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                                                                </svg>
                                                                            </div>
                                                                        @else
                                                                            <div class="expand-icon"></div>
                                                                        @endif
                                                                        <div class="node-icon">
                                                                            <svg class="w-4 h-4 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                                                                <path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd"/>
                                                                            </svg>
                                                                        </div>
                                                                        <span class="node-label">{{ $component['name'] }}</span>
                                                                        <span class="node-type component">Component</span>
                                                                    </div>
                                                                    
                                                                    @if(count($component['actions']) > 0)
                                                                        <!-- Actions Level -->
                                                                        <div class="tree-children" x-show="isExpanded" x-transition>
                                                                            @foreach($component['actions'] as $action)
                                                                                <div class="tree-node" data-level="4">
                                                                                    <div class="node-content">
                                                                                        <div class="expand-icon"></div>
                                                                                        <div class="node-icon">
                                                                                            <svg class="w-4 h-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                                                            </svg>
                                                                                        </div>
                                                                                        <span class="node-label">{{ $action['name'] }}</span>
                                                                                        <span class="node-type action">Action</span>
                                                                                    </div>
                                                                                </div>
                                                                            @endforeach
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Custom Styles -->
            <style>
                .tree-container {
                    font-size: 14px;
                    line-height: 1.5;
                    font-family: 'Inter', system-ui, sans-serif;
                }

                .tree-node {
                    position: relative;
                }

                .tree-node:not(.root-node)::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: -16px;
                    width: 16px;
                    height: 20px;
                    border-left: 1px solid #e5e7eb;
                    border-bottom: 1px solid #e5e7eb;
                }

                .tree-node:not(.root-node):last-child::before {
                    border-left: 1px solid #e5e7eb;
                    border-bottom: 1px solid #e5e7eb;
                    height: 20px;
                }

                .tree-children {
                    margin-left: 24px;
                    position: relative;
                }

                .tree-children::before {
                    content: '';
                    position: absolute;
                    top: -20px;
                    left: -24px;
                    bottom: 20px;
                    width: 1px;
                    background: #e5e7eb;
                }

                .tree-children:not(.collapsed) .tree-node:last-child::after {
                    content: '';
                    position: absolute;
                    top: 20px;
                    left: -16px;
                    bottom: 0;
                    width: 1px;
                    background: #ffffff;
                    z-index: 1;
                }

                .collapsed {
                    display: none;
                }

                .node-content {
                    display: flex;
                    align-items: center;
                    padding: 8px 12px;
                    margin: 2px 0;
                    border-radius: 6px;
                    transition: all 0.2s ease;
                    cursor: default;
                }

                .node-content:hover {
                    background-color: #f8fafc;
                }

                .expandable {
                    cursor: pointer;
                }

                .expandable:hover {
                    background-color: #f1f5f9;
                }

                .expand-icon {
                    width: 16px;
                    height: 16px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin-right: 8px;
                }

                .expanded .expand-icon svg {
                    transform: rotate(90deg);
                }

                .node-icon {
                    width: 20px;
                    height: 20px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin-right: 12px;
                }

                .node-label {
                    font-weight: 500;
                    color: #1f2937;
                    flex: 1;
                }

                .node-type {
                    font-size: 11px;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    padding: 2px 8px;
                    border-radius: 12px;
                    margin-left: 12px;
                }

                .node-type.panel {
                    background-color: #dbeafe;
                    color: #1d4ed8;
                }

                .node-type.app {
                    background-color: #d1fae5;
                    color: #047857;
                }

                .node-type.module {
                    background-color: #e9d5ff;
                    color: #7c3aed;
                }

                .node-type.component {
                    background-color: #fed7aa;
                    color: #c2410c;
                }

                .node-type.action {
                    background-color: #f3f4f6;
                    color: #6b7280;
                }

                .root-node .node-content {
                    background-color: #f8fafc;
                    border: 1px solid #e2e8f0;
                    font-weight: 600;
                }

                .rotate-90 {
                    transform: rotate(90deg);
                }
            </style>
        @endif
    </flux:modal>   
</div>
    