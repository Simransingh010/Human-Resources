<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <flux:heading size="lg">Student Profiles</flux:heading>
            <flux:subheading>Inline edit every student attribute except name.</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <flux:button variant="outline" icon="arrow-path" wire:click="$refresh">
                Refresh
            </flux:button>
        </div>
    </div>

    <flux:separator class="mb-2" />

    <flux:card>
        <flux:heading>Filters</flux:heading>
        <div class="flex flex-wrap gap-4">
            @foreach($filterFields as $field => $cfg)
                @if(in_array($field, $visibleFilterFields))
                    <div class="w-full md:w-1/4">
                        @switch($cfg['type'])
                            @case('select')
                                @if(isset($cfg['options']))
                                    <flux:select wire:model="filters.{{ $field }}" wire:change="applyFilters" placeholder="{{ $cfg['label'] }}">
                                        @foreach($cfg['options'] as $val => $label)
                                            <flux:select.option value="{{ $val }}">{{ $label }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                @else
                                    <flux:select
                                        variant="listbox"
                                        searchable
                                        placeholder="All {{ $cfg['label'] }}"
                                        wire:model="filters.{{ $field }}"
                                        wire:change="applyFilters"
                                    >
                                        <flux:select.option value="">All {{ $cfg['label'] }}</flux:select.option>
                                        @foreach($listsForFields[$cfg['listKey']] ?? [] as $val => $label)
                                            <flux:select.option value="{{ $val }}">{{ $label }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                @endif
                                @break
                            @case('date')
                                <flux:date-picker placeholder="{{ $cfg['label'] }}" wire:model="filters.{{ $field }}" wire:change="applyFilters"/>
                                @break
                            @default
                                <flux:input placeholder="Search {{ $cfg['label'] }}"
                                    wire:model.live.debounce.500ms="filters.{{ $field }}"
                                    wire:change="applyFilters"
                                />
                        @endswitch
                    </div>
                @endif
            @endforeach

            <flux:button.group>
                <flux:button variant="outline" icon="x-circle" tooltip="Clear Filters" wire:click="clearFilters"></flux:button>
                <flux:modal.trigger name="mdl-show-hide-filters">
                    <flux:button variant="outline" icon="funnel" tooltip="Show/Hide Filters"></flux:button>
                </flux:modal.trigger>
                <flux:modal.trigger name="mdl-show-hide-columns">
                    <flux:button variant="outline" icon="table-cells" tooltip="Show/Hide Columns"></flux:button>
                </flux:modal.trigger>
            </flux:button.group>
        </div>
    </flux:card>

    <flux:modal name="mdl-show-hide-filters" variant="flyout">
        <div class="space-y-4">
            <flux:heading size="lg">Show / Hide Filters</flux:heading>
            <flux:checkbox.group>
                @foreach($filterFields as $field => $cfg)
                    <flux:checkbox :checked="in_array($field, $visibleFilterFields)"
                                   label="{{ $cfg['label'] }}"
                                   wire:click="toggleFilterColumn('{{ $field }}')" />
                @endforeach
            </flux:checkbox.group>
        </div>
    </flux:modal>

    <flux:modal name="mdl-show-hide-columns" variant="flyout" position="right">
        <div class="space-y-4">
            <flux:heading size="lg">Show / Hide Columns</flux:heading>
            <flux:checkbox.group>
                @foreach($fieldConfig as $field => $cfg)
                    <flux:checkbox :checked="in_array($field, $visibleFields)"
                                   label="{{ $cfg['label'] }}"
                                   wire:click="toggleColumn('{{ $field }}')" />
                @endforeach
            </flux:checkbox.group>
        </div>
    </flux:modal>

    <flux:table :paginate="$this->list" class="w-full table-auto border text-sm">
        <flux:table.columns>
            <flux:table.column>{{ $labelHeader }}</flux:table.column>
            @foreach($fieldConfig as $field => $cfg)
                @if(in_array($field, $visibleFields))
                    <flux:table.column>{{ $cfg['label'] }}</flux:table.column>
                @endif
            @endforeach
        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->list as $student)
                <flux:table.row :key="$student->id">
                    <flux:table.cell>
                        {{ collect($labelFields)->map(fn($field) => data_get($student, $field))->filter()->implode(' ') }}
                        <span class="block text-xs text-gray-500">{{ $student->email ?? 'N/A' }}</span>
                    </flux:table.cell>

                    @foreach($fieldConfig as $field => $cfg)
                        @if(in_array($field, $visibleFields))
                            <flux:table.cell>
                                @switch($cfg['type'])
                                    @case('select')
                                        @if(isset($cfg['options']))
                                            <flux:select wire:model.defer="bulkupdate.{{ $student->id }}.{{ $field }}"
                                                         wire:change="triggerUpdate({{ $student->id }}, '{{ $field }}')">
                                                @foreach($cfg['options'] as $val => $label)
                                                    <flux:select.option value="{{ $val }}">{{ $label }}</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                        @else
                                            <flux:select
                                                variant="listbox"
                                                searchable
                                                placeholder="Select..."
                                                wire:model.defer="bulkupdate.{{ $student->id }}.{{ $field }}"
                                                wire:change="triggerUpdate({{ $student->id }}, '{{ $field }}')"
                                            >
                                                <flux:select.option value="">Select…</flux:select.option>
                                                @foreach($listsForFields[$cfg['listKey']] ?? [] as $val => $label)
                                                    <flux:select.option value="{{ $val }}">{{ $label }}</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                        @endif
                                        @break

                                    @case('date')
                                        <flux:date-picker
                                            wire:model.defer="bulkupdate.{{ $student->id }}.{{ $field }}"
                                            wire:change="triggerUpdate({{ $student->id }}, '{{ $field }}')"
                                        />
                                        @break

                                    @case('multiselect')
                                        <select multiple
                                                class="w-full border border-gray-300 dark:border-gray-600 rounded-md p-2 bg-white dark:bg-zinc-900"
                                                wire:model.defer="bulkupdate.{{ $student->id }}.{{ $field }}"
                                                wire:change="triggerUpdate({{ $student->id }}, '{{ $field }}')">
                                            @foreach($listsForFields[$cfg['listKey']] ?? [] as $val => $label)
                                                <option value="{{ $val }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @break

                                    @default
                                        <flux:input
                                            wire:model.defer="bulkupdate.{{ $student->id }}.{{ $field }}"
                                            wire:change="triggerUpdate({{ $student->id }}, '{{ $field }}')"
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