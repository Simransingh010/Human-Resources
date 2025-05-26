<div>
<div class="space-y-6">
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
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

    <!-- Data Table -->
    <flux:table class="w-full">
        <flux:table.columns>
            <flux:table.column>Employee</flux:table.column>
            <flux:table.column>Cycle Days</flux:table.column>
            <flux:table.column>Void Days</flux:table.column>
            <flux:table.column>LOP Days</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->list as $item)
                <flux:table.row :key="$item['id']">
                    <flux:table.cell class="table-cell-wrap">{{ $item['employee_name'] }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $item['cycle_days'] }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $item['void_days_count'] }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">
                        <flux:badge color="{{ $item['lop_days_count'] > 0 ? 'red' : 'green' }}">
                            {{ $item['lop_days_count'] }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">
                        <div class="flex space-x-2">
                            <flux:button
                                wire:click="editLopDays({{ $item['id'] }})"
                                tooltip="Edit LOP Days"
                                icon="pencil"
                            >Edit</flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
    <div class="mt-4">
        {{ $this->list->links() }}
    </div>
    <!-- Edit LOP Days Modal -->
    <flux:modal wire:model="showEditModal">
        <div class="p-4">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Edit LOP Days</h2>
            </div>
            @if($selectedRecord)
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Employee</label>
                        <p class="mt-1">{{ $selectedRecord['employee_name'] }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Cycle Days</label>
                        <p class="mt-1">{{ $selectedRecord['cycle_days'] }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Void Days</label>
                        <flux:input type="number" wire:model="editForm.void_days_count" min="0" :max="$selectedRecord['cycle_days']" />
                        @error('editForm.void_days_count') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">LOP Days</label>
                        <flux:input type="number" wire:model="editForm.lop_days_count" min="0" :max="$selectedRecord['cycle_days']" />
                        @error('editForm.lop_days_count') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mt-6 flex justify-end space-x-3">
                    <flux:button wire:click="updateLopDays" variant="primary">Save Changes</flux:button>
                    <flux:button wire:click="closeEditModal">Cancel</flux:button>
                </div>
            @endif
        </div>
    </flux:modal>

</div>
</div> 