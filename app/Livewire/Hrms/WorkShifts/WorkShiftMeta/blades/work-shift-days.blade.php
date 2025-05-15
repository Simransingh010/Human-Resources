<?php ?>
<div class="space-y-6">
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-day" class="flex justify-end">
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
                    <div class="w-1/4" wire:key="filter-field-{{ $field }}">
                        @switch($cfg['type'])
                            @case('select')
                                <flux:select
                                    placeholder="All {{ $cfg['label'] }}"
                                    wire:model.live="filters.{{ $field }}"
                                    wire:change="$refresh"
                                >
                                    <option value="">All {{ $cfg['label'] }}</option>
                                    @foreach($listsForFields[$cfg['listKey']] ?? [] as $id => $title)
                                        <option value="{{ $id }}">{{ $title }}</option>
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
                                    wire:change="$refresh"
                                />
                        @endswitch
                    </div>
                @endif
            @endforeach

            <flux:button.group>
                <flux:button variant="outline" wire:click="clearFilters" tooltip="Clear Filters"
                            icon="x-circle"></flux:button>
                <flux:modal.trigger name="mdl-show-hide-filters">
                    <flux:button variant="outline" tooltip="Set Filters" icon="funnel"></flux:button>
                </flux:modal.trigger>
                <flux:modal.trigger name="mdl-show-hide-columns">
                    <flux:button variant="outline" tooltip="Set Columns" icon="bars-3"></flux:button>
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

    <!-- Modal Start -->
    <flux:modal name="mdl-day" @cancel="resetForm" position="right" class="max-w-none" variant="flyout">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Work Shift Day @else Add Work Shift Day @endif
                    </flux:heading>
                    <flux:subheading>
                        @if($isEditing) Update @else Add new @endif work shift day details.
                    </flux:subheading>
                </div>

                <!-- Grid layout for form fields -->
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
                                        @foreach($listsForFields[$cfg['listKey']] ?? [] as $id => $title)
                                            <option value="{{ $id }}">{{ $title }}</option>
                                        @endforeach
                                    </flux:select>
                                    @break

                                @case('date')
                                    <flux:date-picker
                                        label="{{ $cfg['label'] }}"
                                        wire:model.live="formData.{{ $field }}"
                                        selectable-header
                                    />
                                    @break

                                @case('time')
                                    <flux:input
                                        type="time"
                                        label="{{ $cfg['label'] }}"
                                        wire:model.live="formData.{{ $field }}"
                                    />
                                    @break

                                @case('number')
                                    <flux:input
                                        type="number"
                                        label="{{ $cfg['label'] }}"
                                        wire:model.live="formData.{{ $field }}"
                                        min="0"
                                        max="100"
                                        step="1"
                                    />
                                    @break

                                @default
                                    <flux:input
                                        label="{{ $cfg['label'] }}"
                                        wire:model.live="formData.{{ $field }}"
                                        placeholder="{{ $cfg['label'] }}"
                                    />
                            @endswitch
                        </div>
                    @endforeach
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
    <flux:table :paginate="$this->list">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            @foreach($fieldConfig as $field => $cfg)
                @if(in_array($field, $visibleFields))
                    <flux:table.column wire:key="column-{{ $field }}">{{ $cfg['label'] }}</flux:table.column>
                @endif
            @endforeach
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->list as $rec)
                <flux:table.row :key="$rec->id" class="border-b">
                    @foreach($fieldConfig as $field => $cfg)
                        @if(in_array($field, $visibleFields))
                            <flux:table.cell class="table-cell-wrap">
                                @switch($field)
                                    @case('work_shift_id')
                                        {{ $rec->work_shift->shift_title ?? 'N/A' }}
                                        @break

                                    @case('work_shift_day_status_id')
                                        {{ $rec->day_status->day_status_label ?? 'N/A' }}
                                        @break

                                    @case('work_date')
                                        {{ $rec->work_date ? date('jS F Y', strtotime($rec->work_date)) : '-' }}
                                        @break

                                    @case('start_time')
                                    @case('end_time')
                                        {{ $rec->$field ? date('H:i', strtotime($rec->$field)) : '-' }}
                                        @break

                                    @case('paid_percent')
                                        {{ $rec->paid_percent }}%
                                        @break

                                    @case('day_status_main')
                                        {{ \App\Models\Hrms\WorkShiftDay::WORK_STATUS_SELECT[$rec->day_status_main] ?? '-' }}
                                        @break

                                    @default
                                        {{ $rec->$field }}
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
                                wire:click="edit({{ $rec->id }})"
                            />
                            <flux:modal.trigger name="delete-{{ $rec->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"/>
                            </flux:modal.trigger>
                        </div>

                        <!-- Delete Modal -->
                        <flux:modal name="delete-{{ $rec->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete Work Shift Day?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this work shift day. This action cannot be undone.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer/>
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                        wire:click="delete({{ $rec->id }})"/>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
    <!-- Table End-->
</div> 