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
            @if(in_array('lop_details', $visibleFields))
                <flux:table.column>LOP Details</flux:table.column>
            @endif
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
                    @if(in_array('lop_details', $visibleFields))
                        <flux:table.cell class="table-cell-wrap">{{ $item['lop_details'] }}</flux:table.cell>
                    @endif
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
        <div
            class="p-4"
            x-data="{
                voidDates: @entangle('editForm.void_dates').live,
                lopDates: @entangle('editForm.lop_dates').live,
                voidHalfDates: @entangle('editForm.void_half_dates').live,
                lopHalfDates: @entangle('editForm.lop_half_dates').live,
                init() {
                    this.cleanHalf('void');
                    this.cleanHalf('lop');
                    this.$watch('voidDates', () => this.cleanHalf('void'));
                    this.$watch('lopDates', () => this.cleanHalf('lop'));
                },
                cleanHalf(type) {
                    const datesKey = type === 'void' ? 'voidDates' : 'lopDates';
                    const halfKey = type === 'void' ? 'voidHalfDates' : 'lopHalfDates';
                    this[halfKey] = this[halfKey].filter(date => this[datesKey].includes(date));
                },
                toggleHalf(type, date) {
                    const halfKey = type === 'void' ? 'voidHalfDates' : 'lopHalfDates';
                    const datesKey = type === 'void' ? 'voidDates' : 'lopDates';
                    if (!this[datesKey].includes(date)) return;
                    if (this[halfKey].includes(date)) {
                        this[halfKey] = this[halfKey].filter(d => d !== date);
                    } else {
                        this[halfKey] = [...this[halfKey], date];
                    }
                },
                isHalf(type, date) {
                    const halfKey = type === 'void' ? 'voidHalfDates' : 'lopHalfDates';
                    return this[halfKey].includes(date);
                },
                selectedText(type) {
                    const datesKey = type === 'void' ? 'voidDates' : 'lopDates';
                    if (!this[datesKey].length) {
                        return 'None';
                    }
                    return this[datesKey]
                        .map(date => this.isHalf(type, date) ? `${date} (1/2)` : date)
                        .join(', ');
                }
            }"
            x-cloak
        >
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
                        <label class="block text-sm font-medium text-gray-700">Void Days (select dates)</label>
                        <flux:date-picker multiple wire:model="editForm.void_dates" :months="1" placeholder="Select void days..." />
                        <div class="text-xs text-gray-500 mt-1">Selected:
                            <span x-text="selectedText('void')"></span>
                        </div>
                        @error('editForm.void_dates') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        <div
                            class="mt-3 space-y-2 bg-gray-50 rounded p-3 border border-gray-100"
                            x-show="voidDates.length"
                        >
                            <div class="text-xs font-medium text-gray-600">Tap a date to toggle half day</div>
                            <template x-for="date in voidDates" :key="`void-${date}`">
                                <div class="flex items-center justify-between text-xs">
                                    <span>
                                        <span x-text="date"></span>
                                        <span class="text-gray-400" x-show="isHalf('void', date)"> (1/2)</span>
                                    </span>
                                    <button
                                        type="button"
                                        class="px-3 py-1 text-xs font-semibold rounded border transition"
                                        :class="isHalf('void', date) ? 'bg-orange-500 border-orange-500 text-white' : 'border-gray-300 text-gray-700 bg-white'"
                                        x-on:click="toggleHalf('void', date)"
                                    >
                                        <span x-text="isHalf('void', date) ? 'Half Day' : 'Full Day'"></span>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">LOP Days (select dates)</label>
                        <flux:date-picker multiple wire:model="editForm.lop_dates" :months="1" placeholder="Select LOP days..." />
                        <div class="text-xs text-gray-500 mt-1">Selected:
                            <span x-text="selectedText('lop')"></span>
                        </div>
                        @error('editForm.lop_dates') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        <div
                            class="mt-3 space-y-2 bg-gray-50 rounded p-3 border border-gray-100"
                            x-show="lopDates.length"
                        >
                            <div class="text-xs font-medium text-gray-600">Tap a date to toggle half day</div>
                            <template x-for="date in lopDates" :key="`lop-${date}`">
                                <div class="flex items-center justify-between text-xs">
                                    <span>
                                        <span x-text="date"></span>
                                        <span class="text-gray-400" x-show="isHalf('lop', date)"> (1/2)</span>
                                    </span>
                                    <button
                                        type="button"
                                        class="px-3 py-1 text-xs font-semibold rounded border transition"
                                        :class="isHalf('lop', date) ? 'bg-orange-500 border-orange-500 text-white' : 'border-gray-300 text-gray-700 bg-white'"
                                        x-on:click="toggleHalf('lop', date)"
                                    >
                                        <span x-text="isHalf('lop', date) ? 'Half Day' : 'Full Day'"></span>
                                    </button>
                                </div>
                            </template>
                        </div>
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