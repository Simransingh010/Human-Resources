<div class="space-y-6">
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-batch" class="flex justify-end">
            <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">
                New Allocation
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
        </div>
    </flux:card>

    <!-- Add/Edit Batch Modal -->
    <flux:modal name="mdl-batch" @cancel="resetForm" position="right" class="max-w-6xl" variant="flyout">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                @if(!empty($allocationWarnings))
                    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4">
                        <div class="font-bold mb-1">Some allocations were skipped:</div>
                        <ul class="list-disc pl-5">
                            @foreach($allocationWarnings as $warning)
                                <li>{{ $warning }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div>
                    <flux:heading size="lg">New Template Allocation</flux:heading>
                    <flux:text class="text-gray-500">Allocate salary components to employees</flux:text>
                </div>

                <flux:separator/>

                <!-- Allocation Type Selection -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Allocation Type</label>
                    <select 
                        wire:model.live="allocationType"
                        class="block w-full rounded-md border-gray-300 py-3 px-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    >
                        <option value="">Select Type</option>
                        <option value="template">Template Based</option>
                        <option value="direct">Direct Components</option>
                    </select>
                </div>

                @if($allocationType)
                    @if($allocationType === 'template')
                        <!-- Template Selection -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Salary Template</label>
                            <select 
                                wire:model.live="formData.template_id"
                                class="block w-full rounded-md border-gray-300 py-3 px-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            >
                                <option value="">Select Template</option>
                                @foreach($listsForFields['templates'] as $id => $title)
                                    <option value="{{ $id }}">{{ $title }}</option>
                                @endforeach
                            </select>

                            @if($selectedTemplate)
                                <div class="mt-4 space-y-4">
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <h3 class="text-sm font-medium text-gray-900">Template Details</h3>
                                        <div class="mt-2 space-y-2">
                                            <p class="text-sm text-gray-500">
                                                Effective Period: {{ $selectedTemplate->effective_from->format('d M Y') }} to 
                                                {{ $selectedTemplate->effective_to ? $selectedTemplate->effective_to->format('d M Y') : 'No End Date' }}
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                Components: {{ $templateComponents->count() }}
                                            </p>
                                        </div>
                                    </div>

                                    @if($templateComponents->isNotEmpty())
                                        <div class="bg-white shadow overflow-hidden sm:rounded-md">
                                            <ul role="list" class="divide-y divide-gray-200">
                                                @foreach($templateComponents as $component)
                                                    @php
                                                        $componentId = is_array($component) ? $component['salary_component_id'] : $component->salary_component_id;
                                                        $componentTitle = is_array($component) ? $component['salary_component']['title'] : $component->salary_component->title;
                                                        $componentGroup = is_array($component) ? ($component['salary_component_group']['title'] ?? null) : ($component->salary_component_group ? $component->salary_component_group->title : null);
                                                        $sequence = is_array($component) ? $component['sequence'] : $component->sequence;
                                                    @endphp
                                                    <li class="px-4 py-4 sm:px-6">
                                                        <div class="flex items-center justify-between">
                                                            <p class="text-sm font-medium text-indigo-600 truncate">
                                                                {{ $componentTitle }}
                                                            </p>
                                                            <div class="ml-2 flex-shrink-0 flex">
                                                                <p class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                                    Sequence: {{ $sequence }}
                                                                </p>
                                                            </div>
                                                        </div>
                                                        <div class="mt-2 grid grid-cols-2 gap-4">
                                                            <div>
                                                                <label class="block text-xs font-medium text-gray-500 mb-1">Effective From</label>
                                                                <flux:date-picker
                                                                    wire:model.live="templateComponentDates.{{ $componentId }}.effective_from"
                                                                    placeholder="Effective From"
                                                                />
                                                            </div>
                                                            <div>
                                                                <label class="block text-xs font-medium text-gray-500 mb-1">Effective To</label>
                                                                <flux:date-picker
                                                                    wire:model.live="templateComponentDates.{{ $componentId }}.effective_to"
                                                                    placeholder="Effective To"
                                                                />
                                                            </div>
                                                        </div>
                                                        @if($componentGroup)
                                                            <div class="mt-2 sm:flex sm:justify-between">
                                                                <div class="sm:flex">
                                                                    <p class="flex items-center text-sm text-gray-500">
                                                                        Group: {{ $componentGroup }}
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @else
                        <!-- Direct Component Selection -->
                        <div class="mb-4">
                            <!-- Salary Cycle Selection -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Salary Cycle</label>
                                <select 
                                    wire:model.live="selectedSalaryCycleId"
                                    class="block w-full rounded-md border-gray-300 py-3 px-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    required
                                >
                                    <option value="">Select Salary Cycle</option>
                                    @foreach($salaryCycles as $cycle)
                                        <option value="{{ $cycle->id }}">{{ $cycle->title }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Effective Dates Row -->
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Effective From</label>
                                    <flux:date-picker selectable-header
                                        wire:model.live="effectiveFrom"
                                        selectable-header
                                        placeholder="Select start date"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Effective To</label>
                                    <flux:date-picker selectable-header
                                        wire:model.live="effectiveTo"
                                        selectable-header
                                        placeholder="Select end date"
                                        min="{{ $effectiveFrom }}"
                                    />
                                </div>
                            </div>

                            <!-- Components Selection -->
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Select Components</label>
                                <flux:select
                                    variant="listbox"
                                    wire:model="selectedComponents"
                                    multiple
                                    searchable
                                    placeholder="Select components to allocate"
                                    class="w-full"
                                >
                                    @foreach($availableComponents as $component)
                                        @php
                                            $componentId = is_array($component) ? $component['id'] : $component->id;
                                            $componentTitle = is_array($component) ? $component['title'] : $component->title;
                                        @endphp
                                        <flux:select.option value="{{ $componentId }}">{{ $componentTitle }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>

                            @if(count($selectedComponents) > 0)
                                <div class="mt-4">
                                    <div class="bg-white shadow overflow-hidden sm:rounded-md">
                                        <ul role="list" class="divide-y divide-gray-200">
                                            @foreach($selectedComponents as $index => $componentId)
                                                @php
                                                    $component = collect($availableComponents)->first(function($item) use ($componentId) {
                                                        return (is_array($item) ? $item['id'] : $item->id) == $componentId;
                                                    });
                                                @endphp
                                                @if($component)
                                                    <li class="px-4 py-4 sm:px-6">
                                                        <div class="flex items-center justify-between">
                                                            <p class="text-sm font-medium text-indigo-600 truncate">
                                                                {{ is_array($component) ? $component['title'] : $component->title }}
                                                            </p>
                                                            <div class="ml-2 flex-shrink-0 flex">
                                                                <p class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                                    Sequence: {{ $index + 1 }}
                                                                </p>
                                                            </div>
                                                        </div>
                                                        @php
                                                            $group = is_array($component) 
                                                                ? ($component['salary_component_group'] ?? null) 
                                                                : $component->salary_component_group;
                                                            $groupTitle = is_array($component) && isset($component['salary_component_group']) 
                                                                ? $component['salary_component_group']['title'] 
                                                                : ($group ? $group->title : null);
                                                        @endphp
                                                        @if($group)
                                                            <div class="mt-2 sm:flex sm:justify-between">
                                                                <div class="sm:flex">
                                                                    <p class="flex items-center text-sm text-gray-500">
                                                                        Group: {{ $groupTitle }}
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </li>
                                                @endif
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endif
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
                                @forelse($filteredDepartmentsWithEmployees as $group)
                                    <flux:accordion.item>
                                        <flux:accordion.heading>
                                            <div class="flex justify-between items-center w-full">
                                                <span>{{ $group['title'] }}</span>
                                                <span class="text-sm text-gray-500">({{ count($group['employees']) }} employees)</span>
                                            </div>
                                        </flux:accordion.heading>
                                        <flux:accordion.content class="pl-4">
                                            <div class="flex justify-end space-x-2 mb-2">
                                                <flux:button size="xs" variant="outline" wire:click="selectAllEmployees('{{ $group['id'] }}')">
                                                    Select All
                                                </flux:button>
                                                <flux:button size="xs" variant="ghost" wire:click="deselectAllEmployees('{{ $group['id'] }}')">
                                                    Deselect
                                                </flux:button>
                                            </div>
                                            
                                            <flux:checkbox.group class="space-y-1">
                                                @foreach($group['employees'] as $employee)
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
                                            No salary execution groups or employees available
                                        @endif
                                    </div>
                                @endforelse
                            </flux:accordion>
                        </div>
                    </div>
                @endif

                <!-- Submit Button -->
                <div class="flex justify-end space-x-2 pt-4">
                    <flux:button x-on:click="$flux.modal('mdl-batch').close()">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        Save Allocation
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
{{--            <flux:table.column>Items</flux:table.column>--}}
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->list as $batch)
                <flux:table.row :key="$batch->id" class="table-cell-wrap">
                    <flux:table.cell>{{ $batch->title }}</flux:table.cell>
                    <flux:table.cell>{{ $batch->created_at->format('jS F Y h:i a') }}</flux:table.cell>
{{--                    <flux:table.cell>{{ $batch->items_count }}</flux:table.cell>--}}
                    <flux:table.cell>
                        <div class="flex space-x-2">
                            <flux:button
                                wire:click="viewDetails({{ $batch->id }})"
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
                                    <flux:heading size="lg">Rollback Template Allocation?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to rollback this template allocation. This action cannot be undone.</p>
                                        <p class="mt-2 text-red-500">Note: This will permanently delete all salary components created in this batch.</p>
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
    <flux:modal name="batch-items-modal" wire:model="showItemsModal" title="Salary Component Allocation Details" class="max-w-6xl">
        @if($selectedBatchId)
            <div class="space-y-6">
                <!-- Filters Section -->
                <flux:card>
                    <flux:heading>Filters</flux:heading>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Search -->
                        <div>
                            <flux:input
                                type="search"
                                placeholder="Search by employee or component..."
                                wire:model.live="batchItemSearch"
                                class="w-full"
                            >
                                <x-slot:prefix>
                                    <flux:icon name="magnifying-glass" class="w-5 h-5 text-gray-400"/>
                                </x-slot:prefix>
                                @if($batchItemSearch)
                                    <x-slot:suffix>
                                        <flux:button
                                            wire:click="$set('batchItemSearch', '')"
                                            variant="ghost"
                                            size="xs"
                                            icon="x-mark"
                                            class="text-gray-400 hover:text-gray-600"
                                        />
                                    </x-slot:suffix>
                                @endif
                            </flux:input>
                        </div>

                        <!-- Date Range Filter -->
                        <div>
                            <flux:date-picker
                                wire:model.live="effectiveFromFilter"
                                placeholder="Effective From"
                                class="w-full"
                            />
                        </div>
                        <div>
                            <flux:date-picker
                                wire:model.live="effectiveToFilter"
                                placeholder="Effective To"
                                class="w-full"
                            />
                        </div>
                    </div>

                    <!-- Clear Filters Button -->
                    <div class="mt-4 flex justify-end">
                        <flux:button
                            wire:click="clearBatchItemFilters"
                            variant="outline"
                            size="sm"
                            icon="x-circle"
                        >
                            Clear Filters
                        </flux:button>
                    </div>
                </flux:card>

                <!-- Results Table -->
                <flux:table :items="$this->filteredBatchItems->groupBy('salaryComponentEmployee.employee_id')" class="w-full">
                    <flux:table.columns>
                        <flux:table.column class="table-cell-wrap">Employee</flux:table.column>
                        <flux:table.column  class="table-cell-wrap">Components</flux:table.column>
                        <flux:table.column class="table-cell-wrap">Template</flux:table.column>
                        <flux:table.column class="table-cell-wrap">Effective Period</flux:table.column>
                        <flux:table.column class="table-cell-wrap">Operation</flux:table.column>
                        <flux:table.column class="table-cell-wrap">Actions</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse($this->filteredBatchItems->groupBy('salaryComponentEmployee.employee_id') as $employeeId => $items)
                            @php
                                $firstItem = $items->first();
                                $employee = $firstItem->salaryComponentEmployee->employee;
                            @endphp
                            <flux:table.row :key="$employeeId">
                                <flux:table.cell class="table-cell-wrap sticky left-0  z-10">
                                    <div class="space-y-1">
                                        <div class="font-medium">
                                            {{ $employee->fname }} {{ $employee->lname }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <div><strong>Email:</strong> {{ $employee['email'] }}</div>
                                            <div><strong>Phone:</strong> {{ $employee['phone'] }}</div>
                                        </div>
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell class="table-cell-wrap">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($items as $item)
                                            <flux:badge variant="outline">
                                                {{ $item->salaryComponentEmployee->salary_component->title }}
                                            </flux:badge>
                                        @endforeach
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell class="table-cell-wrap">
                                    {{ $firstItem->salaryComponentEmployee->salary_template ? 
                                       $firstItem->salaryComponentEmployee->salary_template->title : 
                                       'Direct Allocation' }}
                                </flux:table.cell>
                                <flux:table.cell  class="table-cell-wrap">
                                    {{ Carbon\Carbon::parse($firstItem->salaryComponentEmployee->effective_from)->format('j M Y') }} - 
                                    {{ Carbon\Carbon::parse($firstItem->salaryComponentEmployee->effective_to)->format('j M Y') }}
                                </flux:table.cell>
                                <flux:table.cell  class="table-cell-wrap">
                                    <flux:badge variant="{{ $firstItem->operation === 'insert' ? 'success' : 'info' }}">
                                        {{ ucfirst($firstItem->operation) }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell class="table-cell-wrap">
                                    <flux:modal.trigger name="rollback-employee-{{ $employeeId }}">
                                        <flux:button 
                                            variant="danger" 
                                            size="xs" 
                                            icon="arrow-uturn-left"
                                            title="Rollback allocation for this employee"
                                        />
                                    </flux:modal.trigger>

                                    <!-- Individual Employee Rollback Confirmation Modal -->
                                    <flux:modal name="rollback-employee-{{ $employeeId }}" class="min-w-[22rem]">
                                        <div class="space-y-6">
                                            <div>
                                                <flux:heading size="lg">Rollback Employee Allocation?</flux:heading>
                                                <flux:text class="mt-2">
                                                    <p>You're about to rollback the allocation for <span class="font-bold">{{ $employee->fname }} {{ $employee->lname }}</span>.</p>
                                                    <p>This action cannot be undone.</p>
                                                    <p class="mt-2 text-red-500">Note: This will permanently delete all salary components for this employee in this batch.</p>
                                                </flux:text>
                                            </div>
                                            <div class="flex gap-2">
                                                <flux:spacer/>
                                                <flux:modal.close>
                                                    <flux:button variant="ghost">Cancel</flux:button>
                                                </flux:modal.close>
                                                <flux:button 
                                                    variant="danger" 
                                                    icon="arrow-uturn-left" 
                                                    wire:click="rollbackEmployeeAllocation({{ $employeeId }})"
                                                />
                                            </div>
                                        </div>
                                    </flux:modal>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="6" class="table-cell-wrap text-center py-4 text-gray-500">
                                    @if($batchItemSearch || $effectiveFromFilter || $effectiveToFilter)
                                        No records match the selected filters
                                    @else
                                        No salary component allocations found in this batch
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif
    </flux:modal>
</div>
