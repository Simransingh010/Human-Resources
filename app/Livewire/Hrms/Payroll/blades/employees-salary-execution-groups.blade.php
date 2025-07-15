<div>
<div class="space-y-6">
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-employee-assignment" class="flex justify-end">
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

    <!-- Add/Edit Employee Assignment Modal -->
    <flux:modal name="mdl-employee-assignment" @cancel="resetForm" position="right" class="max-w-6xl" variant="flyout">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">New Employee Assignment</flux:heading>
                    <flux:text class="text-gray-500">Assign employees to salary execution group</flux:text>
                </div>

                <flux:separator/>

                <!-- Salary Execution Group Selection -->
                @if(!$this->groupId)
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Salary Execution Group</label>
                    <select 
                        wire:model.live="selectedGroup"
                        class="block w-full rounded-md border-gray-300 py-3 px-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        required
                    >
                        <option value="">Select Salary Execution Group</option>
                        @foreach($listsForFields['execution_groups'] as $id => $title)
                            <option value="{{ $id }}">{{ $title }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <!-- Employee Selection Section -->
                <div class="mt-6">
                    <div class="flex justify-between items-center mb-4">
                        <label class="block text-sm font-medium text-gray-700">Select Employees</label>
                        <div class="flex space-x-2">
                            <flux:button size="xs" variant="outline" wire:click="selectAllEmployeesGlobal">Select All</flux:button>
                            <flux:button size="xs" variant="ghost" wire:click="deselectAllEmployeesGlobal">Deselect</flux:button>
                        </div>
                    </div>

                    <!-- Employee Search -->
                    <div class="mb-4">
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
                                        wire:click="$set('employeeSearch', '')"
                                        variant="ghost"
                                        size="xs"
                                        icon="x-mark"
                                        class="text-gray-400 hover:text-gray-600"
                                    />
                                </x-slot:suffix>
                            @endif
                        </flux:input>
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
                    <flux:button x-on:click="$flux.modal('mdl-employee-assignment').close()">
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
                            <flux:table.cell class="table-cell-wrap">
                                @switch($cfg['type'])
                                    @case('select')
                                        @if($field === 'employee_id')
                                            {{ $item->employee->fname }} {{ $item->employee->lname }}
                                        @elseif($field === 'salary_execution_group_id')
                                            {{ $item->salary_execution_group->title }}
                                        @else
                                            {{ $listsForFields[$cfg['listKey']][$item->$field] ?? $item->$field }}
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
                                    size="sm"
                                    icon="pencil"
                                    wire:click="edit({{ $item->id }})"
                            ></flux:button>
                            <flux:modal.trigger name="delete-{{ $item->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"/>
                            </flux:modal.trigger>
                        </div>

                        <flux:modal name="delete-{{ $item->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Remove Employee Assignment?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to remove this employee from the salary execution group. This action cannot be undone.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer/>
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
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
</div>
</div> 