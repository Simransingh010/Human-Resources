<div class="space-y-6">
    <!-- Header -->
    <div>
        <flux:heading size="lg">
            Leave Balance Changes
            @if($batch)
                <span class="text-sm font-normal text-gray-500 ml-2">
                    (Batch #{{ $batch->id }} | {{ $batch->modulecomponent }} | {{ $batch->user->name }})
                </span>
            @endif
        </flux:heading>
    </div>

    <!-- Filter Section -->
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
                                    wire:model.live="filters.{{ $field }}"
                                >
                                    <flux:select.option value="">All {{ $cfg['label'] }}</flux:select.option>
                                    @foreach($listsForFields[$cfg['listKey']] as $val => $lab)
                                        <flux:select.option value="{{ $val }}">{{ $lab }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                @break

                            @case('date')
                                <flux:date-picker selectable-header
                                    placeholder="Date {{ $cfg['label'] }}"
                                    wire:model.live="filters.{{ $field }}"
                                />
                                @break

                            @default
                                <flux:input
                                    placeholder="Search {{ $cfg['label'] }}"
                                    wire:model.live.debounce.300ms="filters.{{ $field }}"
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
                        <flux:checkbox :checked="in_array($field, $visibleFilterFields)" label="{{ $cfg['label'] }}" wire:click="toggleFilterColumn('{{ $field }}')" />
                    @endforeach
                </flux:checkbox.group>
            </div>
        </div>
    </flux:modal>

    <!-- Table -->
    <flux:table :paginate="$this->batchItems" class="w-full">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column sortable :sorted="$sortBy === 'id'" :direction="$sortDirection" wire:click="sort('id')"
                class="w-12">
                ID
            </flux:table.column>
            <flux:table.column>Employee</flux:table.column>
            <flux:table.column>Leave Type</flux:table.column>
            <flux:table.column>Period</flux:table.column>
            <flux:table.column>Days</flux:table.column>
            <flux:table.column>Operation</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->batchItems as $item)
                @php
                    $leaveData = $this->getLeaveData($item->new_data);
                @endphp
                <flux:table.row :key="$item->id" class="border-b">
                    <flux:table.cell>{{ $item->id }}</flux:table.cell>
                    <flux:table.cell>
                        @if($leaveData)
                            {{ $leaveData['employee_name'] }}
                        @else
                            <span class="text-gray-400">N/A</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($leaveData)
                            {{ $leaveData['leave_type'] }}
                        @else
                            <span class="text-gray-400">N/A</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($leaveData)
                            <div class="text-sm">
                                <div>From: {{ \Carbon\Carbon::parse($leaveData['period_start'])->format('jS F Y') }}</div>
                                <div>To: {{ \Carbon\Carbon::parse($leaveData['period_end'])->format('jS F Y') }}</div>
                            </div>

                        @else
                            <span class="text-gray-400">N/A</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($leaveData)
                            <div class="text-sm">
                                <div>Allocated: {{ $leaveData['allocated_days'] }}</div>
                                <div>Balance: {{ $leaveData['balance'] }}</div>
                            </div>
                        @else
                            <span class="text-gray-400">N/A</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$item->operation === 'insert' ? 'green' : ($item->operation === 'update' ? 'blue' : 'red')">
                            {{ $item->operation }}
                        </flux:badge>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>