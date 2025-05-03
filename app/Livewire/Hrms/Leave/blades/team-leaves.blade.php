<div class="space-y-6"
     x-data
     x-on:leave-status-updated.window="$wire.$refresh()"
>
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        {{--        <flux:modal.trigger name="mdl-leave-request" class="flex justify-end">--}}
        {{--            <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">--}}
        {{--                New--}}
        {{--            </flux:button>--}}
        {{--        </flux:modal.trigger>--}}
    </div>
    <flux:separator class="mt-2 mb-2"/>
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
                                <flux:input
                                        type="date"
                                        placeholder="Search {{ $cfg['label'] }}"
                                        wire:model.live.debounce.500ms="filters.{{ $field }}"
                                        wire:change="applyFilters"
                                />
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

    <!-- Add/Edit Leave Request Modal -->
    <flux:modal name="mdl-leave-request" @cancel="resetForm">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Leave Request @else Add Leave Request @endif
                    </flux:heading>
                    <flux:subheading>
                        @if($isEditing) Update @else Add new @endif leave request details.
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
                                            :disabled="$field !== 'status'"
                                    >
                                        <option value="">Select {{ $cfg['label'] }}</option>
                                        @foreach($listsForFields[$cfg['listKey']] as $val => $lab)
                                            <option value="{{ $val }}">{{ $lab }}</option>
                                        @endforeach
                                    </flux:select>
                                    @break

                                @case('date')
                                    <flux:input
                                            type="date"
                                            label="{{ $cfg['label'] }}"
                                            wire:model.live="formData.{{ $field }}"
                                            :disabled="$field !== 'acted_at'"
                                    />
                                    @break

                                @case('number')
                                    <flux:input
                                            type="number"
                                            label="{{ $cfg['label'] }}"
                                            wire:model.live="formData.{{ $field }}"
                                            step="0.01"
                                            :disabled="$field !== 'approval_level'"
                                    />
                                    @break

                                @default
                                    <flux:input
                                            type="{{ $cfg['type'] }}"
                                            label="{{ $cfg['label'] }}"
                                            wire:model.live="formData.{{ $field }}"
                                            disabled
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
                    @if($field !== 'status') {{-- Skip status in the regular columns --}}
                        <flux:table.column>{{ $cfg['label'] }}</flux:table.column>
                    @endif
                @endif
            @endforeach
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->list as $item)
                <flux:table.row :key="$item->id">
                    @foreach($fieldConfig as $field => $cfg)
                        @if(in_array($field, $visibleFields))
                            @if($field !== 'status') {{-- Skip status in the regular cells --}}
                                <flux:table.cell>
                                    @switch($field)
                                        @case('employee_id')
                                            {{ $item->employee->fname ?? 'N/A' }}
                                            @break
                                        @case('leave_type_id')
                                            {{ $item->leave_type->leave_title ?? 'N/A' }}
                                            @break
                                        @case('apply_from')
                                            {{ $item->apply_from ? $item->apply_from->format('Y-m-d') : 'N/A' }}
                                            @break
                                        @case('apply_to')
                                            {{ $item->apply_to ? $item->apply_to->format('Y-m-d') : 'N/A' }}
                                            @break
                                        @default
                                            {{ $item->$field }}
                                    @endswitch
                                </flux:table.cell>
                            @endif
                        @endif
                    @endforeach
                    <flux:table.cell>
                        @php
                            $statusColor = match($item->status) {
                                'applied' => 'blue',
                                'reviewed' => 'cyan',
                                'approved' => 'green',
                                'approved_further' => 'emerald',
                                'partially_approved' => 'lime',
                                'rejected' => 'red',
                                'cancelled_employee', 'cancelled_hr' => 'zinc',
                                'modified' => 'orange',
                                'escalated' => 'yellow',
                                'delegated' => 'purple',
                                'hold' => 'amber',
                                'expired' => 'rose',
                                'withdrawn' => 'gray',
                                'auto_approved' => 'teal',
                                default => 'zinc'
                            };
                        @endphp
                        <flux:badge color="{{ $statusColor }}" variant="solid">
                            {{ ucfirst(str_replace('_', ' ', $item->status)) }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex mx-1">
                            <flux:button
                                wire:click="showLeaveRequestEvents({{ $item->id }})"
                                color="blue"
                                size="sm"
                                tooltip="View Leave Request Events"
                            >
                                Events
                            </flux:button>  
                            @if($this->canApproveLeave($item))
                                @if(!in_array($item->status, ['approved', 'rejected', 'cancelled_employee', 'cancelled_hr']))
                                    <flux:button 
                                        variant="primary"
                                        size="sm"
                                        icon="pencil-square"
                                        wire:click="edit({{ $item->id }})"
                                        class="ml-2"
                                    />
                                @endif
                            @else
                                <flux:badge color="zinc" variant="solid" class="ml-2">
                                    Not authorized
                                </flux:badge>
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <!-- Leave Action Modal -->
    <flux:modal name="mdl-leave-action" wire:model="showActionModal" @cancel="resetForm">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Leave Request Action</flux:heading>
                <flux:subheading>
                    Add remarks and choose an action for this leave request.
                </flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:textarea
                    label="Remarks"
                    wire:model="formData.remarks"
                    placeholder="Enter your remarks here..."
                    rows="4"
                />

                <div class="flex justify-end space-x-4">
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>

                    <flux:button
                        variant="danger"
                        type="button"
                        wire:click="handleAction('reject')"
                    >
                        Reject
                    </flux:button>

                    <flux:button
                        variant="primary"
                        type="button"
                        wire:click="handleAction('approve')"
                    >
                        Approve
                    </flux:button>
                </div>
            </div>
        </div>
    </flux:modal>

    <!-- Leave Request Events Modal -->
    <flux:modal name="leave-request-events-modal" wire:model="showEventsModal" title="Leave Request Events" class="max-w-6xl">
        @if($selectedId)
            <livewire:hrms.leave.emp-leave-requests.leave-request-events 
                :emp-leave-request-id="$selectedId"
                :wire:key="'leave-request-events-'.$selectedId"/>
        @endif
    </flux:modal>
</div>