<div class="space-y-6">
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-batch" class="flex justify-end">
            <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">
              New Assignment
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

    <!-- Add/Edit Batch Modal -->
    <flux:modal name="mdl-batch" @cancel="resetForm" position="right" class="max-w-6xl" variant="flyout">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Work Shift Assignment @else New Work Shift Assignment @endif
                    </flux:heading>
                    <flux:text class="text-gray-500">Assign work shifts to employees</flux:text>
                </div>

                <flux:separator/>

                <!-- Work Shift Selection -->
                <div class="grid grid-cols-1 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Work Shift</label>
                        <select 
                            wire:model.live="selectedWorkShift"
                            class="block w-full rounded-md border-gray-300 py-3 px-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option value="">Select Work Shift</option>
                            @foreach($workShifts as $shift)
                                <option value="{{ $shift->id }}">{{ $shift->shift_title }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if($selectedWorkShift && !$workShiftDays->isEmpty())
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-gray-700 mb-2">Shift Period</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <span class="text-sm text-gray-500">Start Date:</span>
                                    <span class="text-sm font-medium">{{ $workShiftDays->first()->work_date->format('j M Y') }}</span>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">End Date:</span>
                                    <span class="text-sm font-medium">{{ $workShiftDays->last()->work_date->format('j M Y') }}</span>
                                </div>
                            </div>
                            <div class="mt-2">
                                <span class="text-sm text-gray-500">Total Days:</span>
                                <span class="text-sm font-medium">{{ $workShiftDays->count() }}</span>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Employee Selection Section -->
                <div class="mt-6">
                    <div class="flex justify-between items-center mb-4">
                        <label class="block text-sm font-medium text-gray-700">Select Employees</label>
                        <div class="flex space-x-2">
                            <flux:button size="xs" variant="outline" wire:click="selectAllEmployeesGlobal">Select All</flux:button>
                            <flux:button size="xs" variant="ghost" wire:click="deselectAllEmployeesGlobal">Deselect</flux:button>
                        </div>
                    </div>

                    <!-- Employee Search and Filters -->
                    <div class="mb-4">
                        <!-- Filters Row -->
                        <div class="grid grid-cols-12 gap-4 lg:grid-cols-3 items-end">
                            <!-- Employee Search -->
                            <div class="col-span-6 relative">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Search Employees</label>
                                <flux:input
                                    type="search"
                                    placeholder="Search employees by name, email or phone..."
                                    wire:model.live="employeeSearch"
                                    class="w-full"
                                >
                                    <x-slot:prefix>
                                        <flux:icon name="magnifying-glass" class="w-5 h-5 text-gray-400"/>
                                    </x-slot:prefix>
                                    @if($employeeSearch)
                                        <x-slot:suffix>
                                            <flux:button
                                                wire:click="clearEmployeeSearch"
                                                variant="ghost"
                                                size="xs"
                                                icon="x-mark"
                                                class="text-gray-400 hover:text-gray-600"
                                            />
                                        </x-slot:suffix>
                                    @endif
                                </flux:input>
                            </div>

                            <!-- Employment Type Filter -->
                            @if(count($employmentTypes) > 0)
                            <div class="p-0.5" wire:key="employment-type-filter">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Employment Type</label>
                                <flux:select
                                    wire:model.live="selectedEmploymentType"
                                    placeholder="All Types"
                                    class="w-full"
                                >
                                    <flux:select.option value="">All Types</flux:select.option>
                                    @foreach($employmentTypes as $type)
                                        <flux:select.option value="{{ $type->id }}">
                                            {{ $type->title }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>
                            @endif

                            <!-- Work Shift Filter -->
                            <div class="p-0.5" wire:key="work-shift-filter">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Work Shift Status</label>
                                <flux:select
                                    wire:model.live="workShiftFilter"
                                    class="w-full"
                                >
                                    <flux:select.option value="">All Employees</flux:select.option>
                                    <flux:select.option value="with_shift">With Active Shift</flux:select.option>
                                    <flux:select.option value="without_shift">Without Active Shift</flux:select.option>
                                </flux:select>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 overflow-y-auto max-h-[60vh] pr-2">
                        <flux:accordion class="w-full">
                            @forelse($filteredDepartmentsWithEmployees as $department)
                                <flux:accordion.item>
                                    <flux:accordion.heading>
                                        <div class="flex justify-between items-center w-full">
                                            <span>{{ $department['title'] }}</span>
                                            <span class="text-sm text-gray-500">({{ count($department['employees']) }} employees)</span>
                                        </div>
                                    </flux:accordion.heading>
                                    <flux:accordion.content class="pl-4">
                                        <div class="flex justify-end space-x-2 mb-2">
                                            <flux:button size="xs" variant="outline" wire:click="selectAllEmployees('{{ $department['id'] }}')">
                                                Select All
                                            </flux:button>
                                            <flux:button size="xs" variant="ghost" wire:click="deselectAllEmployees('{{ $department['id'] }}')">
                                                Deselect
                                            </flux:button>
                                        </div>
                                        
                                        <flux:checkbox.group class="space-y-1">
                                            @foreach($department['employees'] as $employee)
                                                <div class="flex items-center justify-between space-x-2 mb-2">
                                                    <flux:checkbox
                                                        wire:model="selectedEmployees"
                                                        class="w-full truncate"
                                                        label="{{ $employee['fname'] }} {{ $employee['lname'] }}"
                                                        value="{{ $employee['id'] }}"
                                                        id="employee-{{ $employee['id'] }}"
                                                    />
                                                    <flux:tooltip toggleable>
                                                        <flux:button icon="information-circle" size="xs" variant="ghost" />
                                                        <flux:tooltip.content class="max-w-[20rem] space-y-2">
                                                            <p><strong>Email:</strong> {{ $employee['email'] }}</p>
                                                            <p><strong>Phone:</strong> {{ $employee['phone'] }}</p>
                                                            <p><strong>ID:</strong> {{ $employee['id'] }}</p>
                                                            <p><strong>Has Active Shift:</strong> {{ $employee['has_active_work_shift'] ? 'Yes' : 'No' }}</p>
                                                        </flux:tooltip.content>
                                                    </flux:tooltip>
                                                </div>
                                            @endforeach
                                        </flux:checkbox.group>
                                    </flux:accordion.content>
                                </flux:accordion.item>
                            @empty
                                <div class="text-center py-4 text-gray-500">
                                    @if($employeeSearch)
                                        No employees found matching "{{ $employeeSearch }}"
                                    @else
                                        No departments or employees available
                                    @endif
                                </div>
                            @endforelse
                        </flux:accordion>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end space-x-2 pt-4">
                    <flux:button x-on:click="$flux.modal('mdl-batch').close()">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        Save Assignment
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <!-- Data Table -->
    <flux:table :paginate="$this->list" class="w-full">
        <flux:table.columns>
            <flux:table.column>Title</flux:table.column>
            <flux:table.column>Created At</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->list as $batch)
                <flux:table.row :key="$batch->id">
                    <flux:table.cell>{{ $batch->title }}</flux:table.cell>
                    <flux:table.cell>{{ $batch->created_at->format('jS F Y h:i a') }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex space-x-2">
                            <flux:button
                                wire:click="showBatchItems({{ $batch->id }})"
                                variant="primary"
                                size="xs"
                                icon="eye"
                            >
                                View Details
                            </flux:button>
                            <flux:modal.trigger name="rollback-{{ $batch->id }}">
                                <flux:button variant="danger" size="xs" icon="arrow-uturn-left"/>
                            </flux:modal.trigger>
                        </div>

                        <!-- Rollback Confirmation Modal -->
                        <flux:modal name="rollback-{{ $batch->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Rollback Work Shift Assignment?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to rollback this work shift assignment. This action cannot be undone.</p>
                                        <p class="mt-2 text-red-500">Note: This will remove all work shift assignments created in this batch.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer/>
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button variant="danger" icon="arrow-uturn-left" wire:click="rollbackBatch({{ $batch->id }})"/>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <!-- Batch Items Modal -->
    <flux:modal name="batch-items-modal" wire:model="showItemsModal" title="Batch Items" class="max-w-6xl">
        @if($selectedBatchId)
            @livewire('hrms.leave.batch-items', [
                'batchId' => $selectedBatchId
            ], key('batch-items-'.$selectedBatchId))
        @endif
    </flux:modal>
</div>
