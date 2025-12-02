<div wire:init="loadData">
    <!-- Loading Skeleton -->
    @if(!$readyToLoad)
        <div class="flex items-center justify-center py-12">
            <div class="text-center">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-4 border-blue-600"></div>
                <p class="mt-4 text-lg font-medium text-gray-700">Loading TDS calculations...</p>
            </div>
        </div>
    @else
    <div class="space-y-6">
        <!-- Heading Start -->
        <div class="flex justify-between">
            @livewire('panel.component-heading')
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
                <flux:table.column>Tax Regime</flux:table.column>
                <flux:table.column>Effective From</flux:table.column>
                <flux:table.column>Effective To</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($this->list as $item)
                    <flux:table.row :key="$item['id']">
                        <flux:table.cell class="table-cell-wrap">{{ $item['employee_name'] }}</flux:table.cell>
                        <flux:table.cell class="table-cell-wrap">{{ $item['regime_name'] }}</flux:table.cell>
                        <flux:table.cell class="table-cell-wrap">{{ $item['effective_from'] }}</flux:table.cell>
                        <flux:table.cell class="table-cell-wrap">
                            <flux:badge color="{{ $item['effective_to'] ? 'yellow' : 'green' }}">
                                {{ $item['effective_to'] ?? 'Active' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="table-cell-wrap">
                            <div class="flex space-x-2">
                                <flux:button
                                        wire:click="editTaxRegime({{ $item['id'] }})"
                                        tooltip="Edit Tax Regime"
                                        icon="pencil"
                                >Edit
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
        <div class="mt-4">
            {{ $this->list->links() }}
        </div>
        <!-- Edit Tax Regime Modal -->
        <flux:modal wire:model="showEditModal">
            <div class="p-4">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold">Edit Tax Regime</h2>
                </div>
                @if($selectedRecord)
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Employee</label>
                            <p class="mt-1">{{ $selectedRecord['employee_name'] }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tax Regime</label>
                            <flux:select
                                    wire:model="editForm.regime_id"
                                    class="mt-1"
                            >
                                <option value="">Select Tax Regime</option>
                                @foreach($listsForFields['tax_regimes'] as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </flux:select>
                            @error('editForm.regime_id') <span
                                    class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Effective From</label>
                            <flux:input type="date" wire:model="editForm.effective_from"/>
                            @error('editForm.effective_from') <span
                                    class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Effective To</label>
                            <flux:input type="date" wire:model="editForm.effective_to"/>
                            @error('editForm.effective_to') <span
                                    class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-6 flex justify-end space-x-3">
                        <flux:button wire:click="updateTaxRegime" variant="primary">Save Changes</flux:button>
                        <flux:button wire:click="closeEditModal">Cancel</flux:button>
                    </div>
                @endif
            </div>
        </flux:modal>

    </div>
    @endif
</div> 