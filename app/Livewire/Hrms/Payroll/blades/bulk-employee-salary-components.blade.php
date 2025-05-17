<div>
    <div class="flex justify-between items-center mb-4">
        <div>
            <flux:heading size="lg">Bulk Employee Salary Components</flux:heading>
            <flux:subheading>Manage salary components for multiple employees</flux:subheading>
        </div>
    </div>

    <!-- Filters -->
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

                            @default
                                <flux:input
                                    placeholder="Search {{ $cfg['label'] }}"
                                    wire:model.live.debounce.500ms="filters.{{ $field }}"
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

    <!-- Matrix Table -->
    <flux:table class="w-full">
        <flux:table.columns>
            <flux:table.column class="sticky left-0 z-10">Employee</flux:table.column>
            @foreach($components as $componentData)
{{--            @dd($components);--}}
                <flux:table.column class="text-center min-w-[120px]">
                    {{ $componentData['title'] }}
                </flux:table.column>
            @endforeach
        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->list as $record)
                <flux:table.row :key="$record->employee_id">
                    <flux:table.cell class="sticky left-0 bg-white z-10">
                        {{ $record->fname }}
                    </flux:table.cell>
                    @foreach($components as $componentData)
                        @php
                            $componentId = $componentData['id'];
                            $employeeComponents = $this->employeeComponents[$record->employee_id] ?? [];
                            $matchingComponent = collect($employeeComponents)
                                ->firstWhere('component_id', $componentId);
                        @endphp
                        <flux:table.cell class="text-center">
                            @if($matchingComponent)
                                <div class="flex items-center justify-center space-x-2">
                                    <flux:input
                                        type="number"
                                        step="0.01"
                                        size="sm"
                                        class="w-24"
                                        wire:model.defer="bulkupdate.{{ $record->employee_id }}.{{ $componentId }}"
                                        wire:change="updateComponentAmount({{ $record->employee_id }}, {{ $componentId }})"
                                    />

                                </div>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </flux:table.cell>
                    @endforeach
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <!-- Pagination Links -->
    <div class="mt-4">
        {{ $this->list->links() }}
    </div>
</div>