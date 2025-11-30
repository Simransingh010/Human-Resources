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
                <flux:button variant="danger" wire:click="clearFilters" tooltip="Clear Filters" icon="x-circle"></flux:button>
                <flux:modal.trigger name="mdl-show-hide-filters">
                    <flux:button variant="danger" tooltip="Set Filters" icon="funnel"></flux:button>
                </flux:modal.trigger>
                <flux:modal.trigger name="mdl-show-hide-columns">
                    <flux:button variant="danger" tooltip="Set Columns" icon="bars-3"></flux:button>
                </flux:modal.trigger>
            </flux:button.group>
        </div>
    </flux:card>

    <!-- View Mode Toggle -->
    <flux:card>
        <div class="flex items-center justify-between">
            <flux:heading >View Mode</flux:heading>
            <flux:button.group>
                <flux:button
                        variant="{{ $viewMode === 'accordion' ? 'primary' : 'danger' }}"
                        wire:click="toggleViewMode"

                >
                    Accordion View
                </flux:button>
                <flux:button
                        variant="{{ $viewMode === 'table' ? 'primary' : 'danger' }}"
                        wire:click="toggleViewMode"

                >
                    Table View
                </flux:button>
              
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

    <!-- Add/Edit Leave Balance Modal -->
    <flux:modal name="mdl-leave-balance" @cancel="resetForm">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Leave Balance @else Add Leave Balance @endif
                    </flux:heading>
                    <flux:subheading>
                        @if($isEditing) Update @else Add new @endif leave balance details.
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

                                @case('date')
                                    <flux:input
                                            type="date"
                                            label="{{ $cfg['label'] }}"
                                            wire:model.live="formData.{{ $field }}"
                                    />
                                    @break

                                @case('number')
                                    <flux:input
                                            type="number"
                                            label="{{ $cfg['label'] }}"
                                            wire:model.live="formData.{{ $field }}"
                                            step="0.01"
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
    @if($viewMode === 'table')
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
                                        @case('date')
                                            {{ $item->$field?->format('jS F Y') }}
                                            @break
                                        @case('number')
                                            {{ number_format($item->$field, 2) }}
                                            @break
                                        @case('select')
                                            @if($field === 'employee_id')
                                                {{ $item->employee->fname ?? 'N/A' }}
                                            @elseif($field === 'leave_type_id')
                                                {{ $item->leave_type->leave_title ?? 'N/A' }}
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
                                        class="p-1"
                                        icon="pencil"
                                        wire:click="edit({{ $item->id }})"
                                />
                                <flux:button
                                        wire:click="showLeaveTransactions({{ $item->id }})"
                                        color="green"
                                        size="sm"
                                        icon="information-circle"
                                >
                                    Transactions
                                </flux:button>
                            </div>

                            <!-- Delete Confirmation Modal -->
                            <flux:modal name="delete-{{ $item->id }}" class="min-w-[22rem]">
                                <div class="space-y-6">
                                    <div>
                                        <flux:heading size="lg">Delete Leave Balance?</flux:heading>
                                        <flux:text class="mt-2">
                                            <p>You're about to delete this leave balance. This action cannot be undone.</p>
                                            <p class="mt-2 text-red-500">Note: Leave balances with related records cannot be deleted.</p>
                                        </flux:text>
                                    </div>
                                    <div class="flex gap-2">
                                        <flux:spacer/>
                                        <flux:modal.close>
                                            <flux:button variant="danger">Cancel</flux:button>
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
    @endif

    <!-- Accordion View -->
    @if($viewMode === 'accordion')
        <div class="space-y-6">
            @foreach($this->groupedLeaveData as $employeeId => $employeeData)
                <flux:accordion exclusive>
                    <flux:accordion.item>
                        <flux:accordion.heading>
                            <div class="flex items-center justify-between w-full">
                                <div class="flex items-center space-x-3">
                                    <flux:avatar :src="$employeeData['employee']->profile_photo_url ?? null">
                                        {{ substr($employeeData['employee']->fname ?? 'N/A', 0, 1) }}
                                    </flux:avatar>
                                    <div>
                                        <div class="font-bold text-lg text-gray-900 dark:text-white">
                                            {{ $employeeData['employee']->fname ?? 'N/A' }} {{ $employeeData['employee']->lname ?? '' }}
                                        </div>

                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <flux:badge variant="solid" color="blue">
                                        {{ count($employeeData['leave_types']) }} Leave Types
                                    </flux:badge></div>
                            </div>
                        </flux:accordion.heading>

                        <flux:accordion.content>
                            <div class="space-y-4 pt-4">

                                <!-- Leave Types Grid -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @foreach($employeeData['leave_types'] as $leaveTypeId => $leaveTypeData)
                                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-800">
                                            <div class="flex items-center justify-between mb-3">
                                                <div class="flex items-center space-x-2">
                                                    <flux:icon name="calendar" class="w-5 h-5 text-blue-500" />
                                                    <h4 class="font-semibold text-gray-900 dark:text-white">
                                                        {{ $leaveTypeData['leave_type']->leave_title ?? 'N/A' }}
                                                    </h4>
                                                    @if(count($leaveTypeData['periods']) > 1)
                                                        <flux:badge variant="filled" color="purple">
                                                            {{ count($leaveTypeData['periods']) }} Periods
                                                        </flux:badge>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-1 gap-4">
                                                @foreach($leaveTypeData['periods'] as $balance)
                                                    <flux:card class="hover:shadow-md transition-shadow">
                                                        <div class="p-4">
                                                            <div class="flex items-center justify-between mb-3">
                                                                <flux:badge variant="filled" color="gray">
                                                                    {{ $this->getPeriodLabel($balance) }}
                                                                </flux:badge>
                                                                <div class="flex space-x-1">
                                                                    <flux:button
                                                                            variant="danger"
                                                                            icon="pencil"
                                                                            wire:click="edit({{ $balance->id }})"
                                                                            class="p-1"
                                                                    />
                                                                    <flux:button
                                                                            variant="danger"
                                                                            icon="information-circle"
                                                                            wire:click="showLeaveTransactions({{ $balance->id }})"
                                                                            class="text-green-600 hover:text-green-700"
                                                                    ></flux:button>
                                                                </div>
                                                            </div>

                                                            <div class="space-y-2">
                                                                <div class="flex justify-between text-sm">
                                                                    <span class="text-gray-600 dark:text-gray-400">Allocated:</span>
                                                                    <span class="font-medium">{{ number_format($balance->allocated_days, 1) }}</span>
                                                                </div>
                                                                <div class="flex justify-between text-sm">
                                                                    <span class="text-gray-600 dark:text-gray-400">Consumed:</span>
                                                                    <span class="font-medium text-red-600">{{ number_format($balance->consumed_days, 1) }}</span>
                                                                </div>
                                                                <div class="flex justify-between text-sm">
                                                                    <span class="text-gray-600 dark:text-gray-400">Carry Forward:</span>
                                                                    <span class="font-medium text-blue-600">{{ number_format($balance->carry_forwarded_days, 1) }}</span>
                                                                </div>
                                                                <div class="flex justify-between text-sm">
                                                                    <span class="text-gray-600 dark:text-gray-400">Lapsed:</span>
                                                                    <span class="font-medium text-gray-600">{{ number_format($balance->lapsed_days, 1) }}</span>
                                                                </div>
                                                                <div class="border-t pt-2">
                                                                    <div class="flex justify-between items-center">
                                                                        <span class="font-semibold text-gray-900 dark:text-white">Balance:</span>
                                                                        <div class="text-right">
                                                                            <span class="font-bold text-lg {{ $this->getBalanceColor($balance) }}">
                                                                                {{ number_format($balance->balance, 1) }}
                                                                            </span>

                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </flux:card>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </flux:accordion.content>
                    </flux:accordion.item>
                </flux:accordion>
            @endforeach

            @if(empty($this->groupedLeaveData))
                <flux:card class="text-center py-12">
                    <flux:icon name="inbox" class="w-12 h-12 text-gray-400 mx-auto mb-4" />
                    <flux:heading size="lg" class="text-gray-900 dark:text-white mb-2">No Leave Balances Found</flux:heading>
                    <flux:text class="text-gray-500 mb-4">
                        No leave balances match your current filters. Try adjusting your search criteria.
                    </flux:text>
                    <flux:button variant="outline" wire:click="clearFilters">
                        Clear Filters
                    </flux:button>
                </flux:card>
            @endif
        </div>
    @endif
    <flux:modal name="leave-transactions" title="Leave Transactions" class="max-w-6xl">
        @if($selectedId)
            <livewire:hrms.leave.EmpLeaveBalance.emp-leave-transactions :bala-id="$selectedId"
                                                                        :wire:key="'emp-leave-transactions-'.$selectedId"/>
        @endif
    </flux:modal>
</div>