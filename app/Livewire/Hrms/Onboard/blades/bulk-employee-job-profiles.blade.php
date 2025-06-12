<div class="space-y-6">

    <div class="flex justify-between">
        @livewire('panel.component-heading')
    </div>
    <flux:separator class="mx-0 px-0 mb-2"/>
    {{-- ─── 1) FILTER BAR ─────────────────────────────────────── --}}

        <flux:card>
            <flux:heading>Filters</flux:heading>
            <div class="flex flex-wrap gap-4">

                @foreach($filterFields  as $field => $cfg)
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
                                placeholder="Date {{ $cfg['label'] }}"
                                wire:model="filters.{{ $field }}"
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

    {{-- ─── 2) Modal Filter Fields Show/ Hide ─────────────────────────────────── --}}
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

    {{-- ─── 2) Modal Columns Show/ Hide ─────────────────────────────────── --}}

    <flux:modal name="mdl-show-hide-columns" variant="flyout" position="right">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Show/Hide Columns</flux:heading>
            </div>
            <div class="flex flex-wrap items-center gap-4">

                <flux:checkbox.group>
                    @foreach($fieldConfig as $field => $cfg)
                        <flux:checkbox :checked="in_array($field, $visibleFields)" label="{{ $cfg['label'] }}" wire:click="toggleColumn('{{ $field }}')" />
                    @endforeach
                </flux:checkbox.group>

            </div>

        </div>
    </flux:modal>



    {{-- ─── 3) DATA TABLE ─────────────────────────────────────── --}}
    <flux:table :paginate="$this->list" class="w-full table-auto border border-gray-200 text-sm">
        <flux:table.columns>
            <flux:table.column class="p-2 text-left"> {{ $labelHeader }} </flux:table.column>
            @foreach($fieldConfig as $field => $cfg)
                @if(in_array($field, $visibleFields))
                    <flux:table.column class="p-2 text-left">
                        {{ $cfg['label'] }}
                    </flux:table.column>
                @endif
            @endforeach

        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->list as $emp)
                <flux:table.row :key="$emp->id" class="border-t">
                    <flux:table.cell class="p-2">
                        {{ collect($labelFields)
                            ->map(fn($f) => data_get($emp, $f))
                            ->filter()
                            ->implode(' ')
                        }}
                    </flux:table.cell>

                    @foreach($fieldConfig as $field => $cfg)
                        @if(in_array($field, $visibleFields))
                            <flux:table.cell class="p-2">
                                @switch($cfg['type'])
                                    @case('select')
                                    <flux:select
                                        variant="listbox"
                                        searchable
                                        placeholder="Select…"
                                        wire:model.defer="bulkupdate.{{ $emp->id }}.{{ $field }}"
                                        wire:change="triggerUpdate({{ $emp->id }}, '{{ $field }}')"
                                    >
                                        @foreach($listsForFields[$cfg['listKey']] as $val => $lab)
                                            <flux:select.option value="{{ $val }}">{{ $lab }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    @break

                                    @case('date')
                                    <flux:date-picker selectable-header
                                                      wire:model.defer="bulkupdate.{{ $emp->id }}.{{ $field }}"
                                                      wire:change="triggerUpdate({{ $emp->id }}, '{{ $field }}')"
                                    />
                                    @break

                                    @default
                                    <flux:input
                                        wire:model.defer="bulkupdate.{{ $emp->id }}.{{ $field }}"
                                        wire:change="triggerUpdate({{ $emp->id }}, '{{ $field }}')"
                                    />
                                @endswitch
                            </flux:table.cell>
                        @endif
                    @endforeach
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>
