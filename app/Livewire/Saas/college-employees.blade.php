<div class="space-y-6">
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-assignment" class="flex justify-end">
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

    <!-- Assignment Modal -->
    <flux:modal name="mdl-assignment" @cancel="resetForm" position="right" class="max-w-6xl" variant="flyout">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">New College Employee Assignment</flux:heading>
                    <flux:text class="text-gray-500">Assign employees to a college</flux:text>
                </div>

                <flux:separator/>

                <!-- College Selection -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Select College</label>
                    <flux:select
                        variant="listbox"
                        searchable
                        placeholder="Select College"
                        wire:model.live="selectedCollegeId"
                        class="w-full"
                    >
                        <flux:select.option value="">Select College</flux:select.option>
                        @foreach($colleges as $college)
                            <flux:select.option value="{{ $college->id }}">
                                {{ $college->name }} ({{ $college->code }}) - {{ $college->city }}
                                @if($college->is_inactive)
                                     [Inactive]
                                @endif
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                @if($selectedCollegeId)
                    @php
                        $selectedCollege = $colleges->firstWhere('id', $selectedCollegeId);
                    @endphp

                    <!-- Selected College Info -->
                    <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-semibold text-blue-900">{{ $selectedCollege->name ?? 'Unknown' }}</div>
                                <div class="text-sm text-blue-700">{{ $selectedCollege->code ?? '' }} - {{ $selectedCollege->city ?? '' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Employee Selection Section -->
                    <div class="mt-6">
                        <div class="flex justify-between items-center mb-4">
                            <label class="block text-sm font-medium text-gray-700">Select Employees</label>
                            <div class="flex space-x-2">
                                <flux:button size="xs" variant="outline" wire:click="selectAllEmployees">Select All</flux:button>
                                <flux:button size="xs" variant="ghost" wire:click="deselectAllEmployees">Deselect</flux:button>
                            </div>
                        </div>

                        <!-- Employee Search -->
                        <div class="mb-4">
                            <flux:input
                                type="search"
                                placeholder="Search employees by name, email or phone..."
                                wire:model.live.debounce.500ms="employeeSearch"
                                class="w-full"
                                icon="magnifying-glass"
                            />
                        </div>

                        <!-- Employee List -->
                        <div class="space-y-2 max-h-[60vh] overflow-y-auto border rounded-lg p-3">
                            @if($employees->count() > 0)
                                <flux:checkbox.group class="space-y-2">
                                    @foreach($employees as $employee)
                                        <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded">
                                            <flux:checkbox
                                                wire:model="selectedEmployeeIds"
                                                label="{{ $employee->fname }} {{ $employee->lname }}"
                                                value="{{ $employee->id }}"
                                                id="employee-{{ $employee->id }}"
                                            />
                                            <flux:tooltip toggleable>
                                                <flux:button icon="information-circle" size="xs" variant="ghost" />
                                                <flux:tooltip.content class="max-w-[20rem] space-y-2">
                                                    <p><strong>Email:</strong> {{ $employee->email }}</p>
                                                    <p><strong>Phone:</strong> {{ $employee->phone }}</p>
                                                    <p><strong>ID:</strong> {{ $employee->id }}</p>
                                                </flux:tooltip.content>
                                            </flux:tooltip>
                                        </div>
                                    @endforeach
                                </flux:checkbox.group>
                            @else
                                <div class="text-center py-8 text-gray-500">
                                    <flux:icon.user-group class="mx-auto h-12 w-12 text-gray-400" />
                                    <p class="mt-2">
                                        @if($employeeSearch)
                                            No employees found matching "{{ $employeeSearch }}"
                                        @else
                                            No employees available
                                        @endif
                                    </p>
                                </div>
                            @endif
                        </div>

                        @if(count($selectedEmployeeIds) > 0)
                            <div class="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                                <div class="text-sm text-green-700">
                                    <strong>{{ count($selectedEmployeeIds) }}</strong> employee(s) selected
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end space-x-2 pt-4 border-t">
                        <flux:button x-on:click="$flux.modal('mdl-assignment').close()">
                            Cancel
                        </flux:button>
                        <flux:button type="submit" variant="primary" :disabled="empty($selectedEmployeeIds)">
                            Save Assignment
                        </flux:button>
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500">
                        <flux:icon.building-office class="mx-auto h-12 w-12 text-gray-400" />
                        <p class="mt-2">Please select a college to continue</p>
                    </div>
                @endif
            </div>
        </form>
    </flux:modal>

    <!-- Data Table -->
    <flux:table :paginate="$this->list" class="w-full">
        <flux:table.columns>
            <flux:table.column>College</flux:table.column>
            <flux:table.column>Created At</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->list as $batch)
                <flux:table.row :key="$batch->id">
                    <flux:table.cell class="table-cell-wrap">{{ $batch->title }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $batch->created_at->format('jS F Y h:i a') }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">
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
                                    <flux:heading size="lg">Rollback Assignment?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to rollback this college employee assignment. This action cannot be undone.</p>
                                        <p class="mt-2 text-red-500">Note: This will remove all assignments created in this batch.</p>
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
            @php
                $batch = \App\Models\Batch::with(['items', 'user'])->find($selectedBatchId);
            @endphp
            @if($batch)
                <div class="space-y-6">
                    <div>
                        <flux:heading class="text-lg font-semibold">{{ $batch->title }}</flux:heading>
                        <flux:text class="text-gray-500">
                            Created by {{ $batch->user->name ?? 'Unknown' }} on {{ $batch->created_at->format('jS F Y h:i a') }}
                        </flux:text>
                    </div>
                    
                    <flux:separator/>
                    
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <flux:heading >Assignment Details</flux:heading>
                            <flux:badge color="blue">{{ $batch->items->count() }} items</flux:badge>
                        </div>
                        
                        <div class="max-h-96 overflow-y-auto">
                            <flux:table class="w-full">
                                <flux:table.columns>
                                    <flux:table.column>Employee</flux:table.column>
                                    <flux:table.column>College</flux:table.column>
                                    <flux:table.column>Operation</flux:table.column>
                                    <flux:table.column>Date</flux:table.column>
                                </flux:table.columns>
                                
                                <flux:table.rows class="table-cell-wrap">
                                    @foreach($batch->items as $item)
                                        @php
                                            $data = json_decode($item->new_data, true);
                                            $employee = $data ? \App\Models\Hrms\Employee::find($data['employee_id'] ?? null) : null;
                                            $college = $data ? \App\Models\Saas\College::find($data['college_id'] ?? null) : null;
                                        @endphp
                                        <flux:table.row>
                                            <flux:table.cell class="table-cell-wrap">
                                                {{ $employee ? $employee->fname . ' ' . $employee->lname : 'Unknown' }}
                                            </flux:table.cell>
                                            <flux:table.cell class="table-cell-wrap">
                                                {{ $college ? $college->name : 'Unknown' }}
                                            </flux:table.cell>
                                            <flux:table.cell class="table-cell-wrap">
                                                <flux:badge color="{{ $item->operation === 'insert' ? 'green' : ($item->operation === 'update' ? 'blue' : 'gray') }}">
                                                    {{ ucfirst($item->operation) }}
                                                </flux:badge>
                                            </flux:table.cell>
                                            <flux:table.cell class="table-cell-wrap">
                                                {{ $item->created_at->format('jS F Y h:i a') }}
                                            </flux:table.cell>
                                        </flux:table.row>
                                    @endforeach
                                </flux:table.rows>
                            </flux:table>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </flux:modal>
</div>
