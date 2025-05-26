<div class="space-y-6">
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-salary-template-component" class="flex justify-end">
            <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">
                New
            </flux:button>
        </flux:modal.trigger>
    </div>
    <flux:separator class="mt-2 mb-2" />
    <!-- Heading End -->

    <!-- Filters Start -->
    <flux:card>
        <flux:heading>Filters</flux:heading>
        <div class="flex flex-wrap gap-4">
            @foreach($filterFields as $field => $cfg)
                @if(in_array($field, $visibleFilterFields))
                    <div class="w-1/4">
                        @switch($cfg['type'])
                            @case('select')
                                <flux:select
                                    variant="listbox"
                                    searchable
                                    placeholder="All {{ $cfg['label'] }}"
                                    wire:model="filters.{{ $field }}"
                                    wire:change="applyFilters"
                                >
                                    <flux:select.option value="">All {{ $cfg['label'] }}</flux:select.option>
                                    @foreach($listsForFields[$cfg['listKey']] as $val => $lab)
                                        <flux:select.option value="{{ $val }}">{{ $lab }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                @break

                            @default
                                <flux:input
                                    placeholder="Search {{ $cfg['label'] }}"
                                    wire:model.live.debounce.500ms="filters.{{ $field }}"
                                    wire:change="applyFilters"
                                />
                        @endswitch
                    </div>
                @endif
            @endforeach

            <flux:button.group>
                <flux:button variant="outline" wire:click="clearFilters" tooltip="Clear Filters" icon="x-circle"></flux:button>
                <flux:modal.trigger name="mdl-show-hide-filters">
                    <flux:button variant="outline" tooltip="Set Filters" icon="bars-3"></flux:button>
                </flux:modal.trigger>
                <flux:modal.trigger name="mdl-show-hide-columns">
                    <flux:button variant="outline" tooltip="Set Columns" icon="table-cells"></flux:button>
                </flux:modal.trigger>
            </flux:button.group>
        </div>
    </flux:card>

    <!-- Filter Fields Show/Hide Modal -->
    <flux:modal name="mdl-show-hide-filters" variant="flyout">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Show/Hide Filters</flux:heading>
            </div>
            <div class="flex flex-wrap items-center gap-4">
                <flux:checkbox.group>
                    @foreach($filterFields as $field => $cfg)
                        <flux:checkbox 
                            :checked="in_array($field, $visibleFilterFields)" 
                            label="{{ $cfg['label'] }}" 
                            wire:click="toggleFilterColumn('{{ $field }}')" 
                        />
                    @endforeach
                </flux:checkbox.group>
            </div>
        </div>
    </flux:modal>

    <!-- Columns Show/Hide Modal -->
    <flux:modal name="mdl-show-hide-columns" variant="flyout" position="right">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Show/Hide Columns</flux:heading>
            </div>
            <div class="flex flex-wrap items-center gap-4">
                <flux:checkbox.group>
                    @foreach($fieldConfig as $field => $cfg)
                        <flux:checkbox 
                            :checked="in_array($field, $visibleFields)" 
                            label="{{ $cfg['label'] }}" 
                            wire:click="toggleColumn('{{ $field }}')" 
                        />
                    @endforeach
                </flux:checkbox.group>
            </div>
        </div>
    </flux:modal>

    <!-- Add/Edit Salary Template Component Modal -->
    <flux:modal name="mdl-salary-template-component" @cancel="resetForm" position="right" class="max-w-6xl" variant="flyout">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Template Component @else Add Template Component @endif
                    </flux:heading>
                    <flux:subheading>
                        @if($isEditing) Update @else Add new @endif salary template component details.
                    </flux:subheading>
                </div>

                <flux:separator/>

                <!-- Template and Group Selection -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <flux:select
                            label="Salary Template"
                            wire:model.live="formData.salary_template_id"
                        >
                            <option value="">Select Salary Template</option>
                            @foreach($listsForFields['templates'] as $val => $lab)
                                <option value="{{ $val }}">{{ $lab }}</option>
                            @endforeach
                        </flux:select>
                    </div>

                    <div>
                        <flux:select
                            label="Component Group"
                            wire:model.live="formData.salary_component_group_id"
                        >
                            <option value="">Select Component Group</option>
                            @foreach($listsForFields['component_groups'] as $val => $lab)
                                <option value="{{ $val }}">{{ $lab }}</option>
                            @endforeach
                        </flux:select>
                    </div>

                    <div>
                        <flux:input
                            type="number"
                            label="Starting Sequence"
                            wire:model.live="formData.sequence"
                        />
                    </div>
                </div>

                <!-- Component Selection Section -->
                <div class="mt-6">
                    <div class="flex justify-between items-center mb-4">
                        <label class="block text-sm font-medium text-gray-700">Select Components</label>
                        <div class="flex space-x-2">
                            <flux:button size="xs" variant="outline" wire:click="selectAllComponents">Select All</flux:button>
                            <flux:button size="xs" variant="ghost" wire:click="deselectAllComponents">Deselect</flux:button>
                        </div>
                    </div>

                    <!-- Component Search -->
                    <div class="mb-4">
                        <flux:input
                            type="search"
                            placeholder="Search components by name, description or group..."
                            wire:model.live="componentSearch"
                            class="w-full"
                        >
                            <x-slot:prefix>
                                <flux:icon name="magnifying-glass" class="w-5 h-5 text-gray-400"/>
                            </x-slot:prefix>
                            @if($componentSearch)
                                <x-slot:suffix>
                                    <flux:button
                                        wire:click="$set('componentSearch', '')"
                                        variant="ghost"
                                        size="xs"
                                        icon="x-mark"
                                        class="text-gray-400 hover:text-gray-600"
                                    />
                                </x-slot:suffix>
                            @endif
                        </flux:input>
                    </div>

                    <div class="space-y-4 overflow-y-auto max-h-[60vh] pr-2">
                        <div class="relative">
                            <div class="min-h-[200px] border border-gray-300 rounded-lg bg-white shadow-sm">
                                @if(empty($filteredComponents))
                                    <div class="flex items-center justify-center h-32 text-gray-500">
                                        No components available
                                    </div>
                                @else
                                    <div class="divide-y divide-gray-200">
                                        @foreach($filteredComponents as $component)
                                            <label class="flex items-center px-4 py-3 hover:bg-gray-50 cursor-pointer transition-colors duration-150">
                                                <input 
                                                    type="checkbox"
                                                    class="h-4 w-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500"
                                                    wire:model="selectedComponents"
                                                    value="{{ is_array($component) ? ($component['id'] ?? '') : (is_object($component) ? ($component->id ?? '') : '') }}"
                                                >
                                                <div class="ml-3 flex-1">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ data_get($component, 'title', 'Untitled Component') }}
                                                    </div>
                                                  
                                                    
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end space-x-2 pt-4">
                    <flux:button x-on:click="$flux.modal('mdl-salary-template-component').close()">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        Save Assignment
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <!-- Data Table -->
    <flux:table :paginate="$this->list" class="w-full">
        <flux:table.columns>
            @foreach($fieldConfig as $field => $cfg)
                @if(in_array($field, $visibleFields))
                    <flux:table.column>{{ $cfg['label'] }}</flux:table.column>
                @endif
            @endforeach
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->list as $item)
                <flux:table.row :key="$item->id">
                    @foreach($fieldConfig as $field => $cfg)
                        @if(in_array($field, $visibleFields))
                            <flux:table.cell>
                                @switch($cfg['type'])
                                    @case('select')
                                        @if($field === 'salary_template_id')
                                            {{ $listsForFields['templates'][$item->$field] ?? $item->$field }}
                                        @elseif($field === 'salary_component_id')
                                            {{ $listsForFields['components'][$item->$field] ?? $item->$field }}
                                        @elseif($field === 'salary_component_group_id')
                                            {{ $listsForFields['component_groups'][$item->$field] ?? $item->$field }}
                                        @else
                                            {{ $item->$field }}
                                        @endif
                                        @break
                                    @default
                                        {{ $item->$field }}
                                @endswitch
                            </flux:table.cell>
                        @endif
                    @endforeach
                    
                    <flux:table.cell>
                        <div class="flex space-x-2">
                            <flux:button
                                variant="primary"
                                size="sm"
                                icon="pencil"
                                wire:click="edit({{ $item->id }})"
                            />
                            <flux:modal.trigger name="delete-{{ $item->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"/>
                            </flux:modal.trigger>
                        </div>

                        <!-- Delete Confirmation Modal -->
                        <flux:modal name="delete-{{ $item->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete Template Component?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this template component. This action cannot be undone.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer/>
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button variant="danger" icon="trash" wire:click="delete({{ $item->id }})"/>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>
