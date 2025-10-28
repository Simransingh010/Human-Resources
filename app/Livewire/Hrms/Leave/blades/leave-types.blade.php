<div class="space-y-6">
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-leave-type" class="flex justify-end">
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

                            @case('number')
                                <flux:input
                                    type="number"
                                    placeholder="Search {{ $cfg['label'] }}"
                                    wire:model.live.debounce.500ms="filters.{{ $field }}"
                                    wire:change="applyFilters"
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

    <!-- Add/Edit Leave Type Modal -->
    <flux:modal name="mdl-leave-type" @cancel="resetForm">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Leave Type @else Add Leave Type @endif
                    </flux:heading>
                    <flux:subheading>
                        @if($isEditing) Update @else Add new @endif leave type details.
                    </flux:subheading>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($fieldConfig as $field => $cfg)
                        <div class="@if($cfg['type'] === 'textarea') col-span-2 @endif">
                            @switch($cfg['type'])
                                @case('select')
                                    <flux:select
                                        label="{{ $cfg['label'] }}"
                                        wire:model.live="formData.{{ $field }}"
                                    >
                                        <option value="">Select {{ $cfg['label'] }}</option>
                                        @foreach($listsForFields[$cfg['listKey']] as $val => $lab)
                                            <option value="{{ $val }}">{{ $lab }}</option>
                                        @endforeach
                                    </flux:select>
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
                                        @if($field === 'leave_nature')
                                            {{ \App\Models\Hrms\LeaveType::LEAVE_NATURE_OPTIONS[$item->$field] ?? $item->$field }}
                                        @elseif($field === 'leave_type_main')
                                            {{ \App\Models\Hrms\LeaveType::LEAVE_TYPE_MAIN_OPTIONS[$item->$field] ?? $item->$field }}
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
                                    <flux:heading size="lg">Delete Leave Type?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this leave type. This action cannot be undone.</p>
                                        <p class="mt-2 text-red-500">Note: Leave types with related records cannot be deleted.</p>
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