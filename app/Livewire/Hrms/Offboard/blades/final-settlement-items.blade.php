<div class="space-y-6">
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-final-settlement-item" class="flex justify-end">
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

                            @case('date')
                                <flux:date-picker
                                    placeholder="Search {{ $cfg['label'] }}"
                                    wire:model.live="filters.{{ $field }}"
                                    wire:change="$refresh"
                                    selectable-header
                                />
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

    <!-- Add/Edit Final Settlement Item Modal -->
    <flux:modal name="mdl-final-settlement-item" @cancel="resetForm">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Final Settlement Item @else Add Final Settlement Item @endif
                    </flux:heading>
                    <flux:subheading>
                        @if($isEditing) Update @else Add new @endif final settlement item details.
                    </flux:subheading>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($fieldConfig as $field => $cfg)
                        @php
                            // Skip single component/nature/amount fields; handled by repeater
                            if(in_array($field, ['salary_component_id','nature','amount'])) continue;
                        @endphp
                        <div class="@if($cfg['type'] === 'textarea') col-span-2 @endif">
                            @switch($cfg['type'])
                                @case('select')
                                    <flux:select
                                        label="{{ $cfg['label'] }}"
                                        wire:model.live="formData.{{ $field }}"
                                        required="{{ in_array($field, ['exit_id', 'final_settlement_id', 'employee_id']) }}"
                                    >
                                        <option value="">Select {{ $cfg['label'] }}</option>
                                        @foreach($listsForFields[$cfg['listKey']] as $val => $lab)
                                            <option value="{{ $val }}">{{ $lab }}</option>
                                        @endforeach
                                    </flux:select>
                                    @error("formData.{$field}")
                                        <flux:text color="red" size="sm">{{ $message }}</flux:text>
                                    @enderror
                                    @break

                                @case('switch')
                                    <flux:switch
                                        label="{{ $cfg['label'] }}"
                                        wire:model.live="formData.{{ $field }}"
                                    />
                                    @break

                                @case('textarea')
                                    <flux:textarea
                                        label="{{ $cfg['label'] }}"
                                        wire:model.live="formData.{{ $field }}"
                                        rows="3"
                                    />
                                    @break

                                @case('date')
                                    <flux:date-picker
                                        label="{{ $cfg['label'] }}"
                                        wire:model.live="formData.{{ $field }}"
                                        selectable-header
                                    />
                                    @break

                                @default
                                    <flux:input
                                        type="{{ $cfg['type'] }}"
                                        label="{{ $cfg['label'] }}"
                                        wire:model.live="formData.{{ $field }}"
                                    />
                            @endswitch
                        </div>
                    @endforeach
                </div>

                <!-- Multi-row entry for items -->
                <div class="col-span-2">
                    <flux:heading>Items</flux:heading>
                    <div class="space-y-3 mt-2">
                        @foreach($fsItems as $idx => $it)
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                                <div>
                                    <flux:select
                                        label="Salary Component"
                                        wire:model.live="fsItems.{{ $idx }}.salary_component_id"
                                    >
                                        <option value="">Select Component</option>
                                        @foreach($this->availableComponents($idx) as $id => $title)
                                            <option value="{{ $id }}">{{ $title }}</option>
                                        @endforeach
                                    </flux:select>
                                </div>
                                <div>
                                    <flux:input
                                        type="number"
                                        label="Amount"
                                        wire:model.live="fsItems.{{ $idx }}.amount"
                                        min="0"
                                        step="0.01"
                                    />
                                </div>
                                <div class="flex items-end gap-2">
                                    <flux:input type="text" label="Remarks" wire:model.live="fsItems.{{ $idx }}.remarks" />
                                    <flux:button size="sm" icon="minus" wire:click="removeItem({{ $idx }})" />
                                </div>
                            </div>
                        @endforeach
                        <div>
                            <flux:button icon="plus" wire:click="addItem">Add Item</flux:button>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-4">
                    <flux:button type="submit" variant="primary">
                        Save
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
                                    @case('switch')
                                        @if($item->$field)
                                            <flux:badge color="green">Yes</flux:badge>
                                        @else
                                            <flux:badge color="gray">No</flux:badge>
                                        @endif
                                        @break
                                    @case('select')
                                        @if($field === 'exit_id')
                                            @if($item->exit && $item->exit->employee)
                                                Exit #{{ $item->exit->id }} - {{ $item->exit->employee->first_name }} {{ $item->exit->employee->last_name }}
                                            @else
                                                Exit #{{ $item->exit_id }}
                                            @endif
                                        @elseif($field === 'final_settlement_id')
                                            @if($item->finalSettlement && $item->finalSettlement->employee)
                                                Settlement #{{ $item->finalSettlement->id }} - {{ $item->finalSettlement->employee->first_name }} {{ $item->finalSettlement->employee->last_name }}
                                            @else
                                                Settlement #{{ $item->final_settlement_id }}
                                            @endif
                                        @elseif($field === 'employee_id')
                                            @if($item->employee)
                                                {{ $item->employee->first_name }} {{ $item->employee->last_name }} ({{ $item->employee->employee_code }})
                                            @else
                                                N/A
                                            @endif
                                        @elseif($field === 'salary_component_id')
                                            {{ $item->salaryComponent->title ?? 'N/A' }}
                                        @elseif($field === 'nature')
                                            @switch($item->nature)
                                                @case('earning')
                                                    <flux:badge color="green">{{ ucfirst($item->nature) }}</flux:badge>
                                                    @break
                                                @case('deduction')
                                                    <flux:badge color="red">{{ ucfirst($item->nature) }}</flux:badge>
                                                    @break
                                                @case('no_impact')
                                                    <flux:badge color="gray">{{ ucfirst(str_replace('_', ' ', $item->nature)) }}</flux:badge>
                                                    @break
                                                @default
                                                    {{ $item->nature }}
                                            @endswitch
                                        @else
                                            {{ $item->$field }}
                                        @endif
                                        @break
                                    @case('date')
                                        {{ $item->$field ? date('jS F Y', strtotime($item->$field)) : '' }}
                                        @break
                                    @case('number')
                                        @if($field === 'amount')
                                            {{ number_format($item->$field, 2) }}
                                        @else
                                            {{ number_format($item->$field, 0) }}
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
                                    <flux:heading size="lg">Delete Final Settlement Item?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this final settlement item. This action cannot be undone.</p>
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
