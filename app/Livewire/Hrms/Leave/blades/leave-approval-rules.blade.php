<div class="space-y-6">
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-approval-rule" class="flex justify-end">
            <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">
                New Rule
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

    <!-- Add/Edit Rule Modal -->
    <flux:modal name="mdl-approval-rule" @cancel="resetForm" position="right" class="max-w-6xl" variant="flyout">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Leave Approval Rule @else New Leave Approval Rule @endif
                    </flux:heading>
                    <flux:text class="text-gray-500">Configure leave approval rules and assign employees</flux:text>
                </div>

                <flux:separator/>

                <!-- Rule Configuration -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="flex items-center space-x-2">
                        <flux:switch wire:model.live="formData.auto_approve" />
                        <label class="text-sm font-medium text-gray-700">Auto Approve</label>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Leave Type</label>
                        <select 
                            wire:model="formData.leave_type_id"
                            class="block w-full rounded-md border-gray-300 py-2 px-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option value="">Select Leave Type</option>
                            @foreach($listsForFields['leave_types_list'] as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('formData.leave_type_id')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    @if(!$formData['auto_approve'])
                        <flux:select variant="listbox"  wire:model="formData.approver_id" searchable placeholder="Choose Approver..">
                            @foreach($listsForFields['approvers_list'] as $id => $name)
                                <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
                            @endforeach
                        </flux:select>
{{--                    <div>--}}
{{--                        <label class="block text-sm font-medium text-gray-700 mb-1">Approver</label>--}}
{{--                        <select --}}
{{--                            wire:model="formData.approver_id"--}}
{{--                            class="block w-full rounded-md border-gray-300 py-2 px-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"--}}
{{--                        >--}}
{{--                            <option value="">Select Approver</option>--}}
{{--                            @foreach($listsForFields['approvers_list'] as $id => $name)--}}
{{--                                <option value="{{ $id }}">{{ $name }}</option>--}}
{{--                            @endforeach--}}
{{--                        </select>--}}
{{--                        @error('formData.approver_id')--}}
{{--                            <span class="text-red-500 text-sm">{{ $message }}</span>--}}
{{--                        @enderror--}}
{{--                    </div>--}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Approval Mode</label>
                        <select 
                            wire:model="formData.approval_mode"
                            class="block w-full rounded-md border-gray-300 py-2 px-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option value="">Select Mode</option>
                            @foreach($listsForFields['approval_modes'] as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                        @error('formData.approval_mode')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Approval Level</label>
                        <flux:input type="number" wire:model.live="formData.approval_level" min="1" />
                    </div>
                    @endif
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Min Days</label>
                        <flux:input type="number" wire:model.live="formData.min_days" min="0" step="0.5" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Max Days</label>
                        <flux:input type="number" wire:model.live="formData.max_days" min="0" step="0.5" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Period Start</label>
                        <flux:input 
                            type="date" 
                            wire:model.live="formData.period_start"
                            placeholder="Select start date"
                            class="w-full"
                        />
                        @error('formData.period_start')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Period End</label>
                        <flux:input 
                            type="date" 
                            wire:model.live="formData.period_end"
                            placeholder="Select end date"
                            class="w-full"
                            min="{{ $formData['period_start'] ?? '' }}"
                        />
                        @error('formData.period_end')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
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
                                                        value="{{ (string) $employee['id'] }}"
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
                    <flux:button x-on:click="$flux.modal('mdl-approval-rule').close()">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        Save Rule
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <!-- Data Table -->
    <flux:table :paginate="$this->list" class="w-full">
        <flux:table.columns>
            <flux:table.column>Leave Type</flux:table.column>
            <flux:table.column>Approver</flux:table.column>
            <flux:table.column>Mode</flux:table.column>
            <flux:table.column>Level</flux:table.column>
            <flux:table.column>Period</flux:table.column>
            <flux:table.column>Days Range</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>

            @foreach($this->list as $rule)
                <flux:table.row :key="$rule->id">
                    <flux:table.cell>
                        <flux:badge color="blue" inset="top bottom">
                            {{ $rule->leave_type->leave_title }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $rule->user->name ?? 'Auto-Approval' }}</flux:table.cell>
                    <flux:table.cell>{{ ucfirst($rule->approval_mode) }}</flux:table.cell>
                    <flux:table.cell>{{ $rule->approval_level }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">
                        {{ \Carbon\Carbon::parse($rule->period_start)->format('Y-m-d') }} to
                        {{ \Carbon\Carbon::parse($rule->period_end)->format('Y-m-d') }}
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $rule->min_days ?? 0 }} - {{ $rule->max_days ?? 'No Limit' }}
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:switch wire:model.live="statuses.{{ $rule->id }}"
                            wire:change="toggleStatus({{ $rule->id }})" />
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex space-x-2">

                            <flux:button variant="primary" size="xs" icon="pencil" wire:click="edit({{ $rule->id }})" />
                            <flux:modal.trigger name="delete-{{ $rule->id }}">
                                <flux:button variant="danger" size="xs" icon="trash" />
                            </flux:modal.trigger>
                        </div>

                        <!-- Delete Modal -->
                        <flux:modal name="delete-{{ $rule->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete Rule?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this leave approval rule. This action cannot be undone.</p>
                                        <p class="mt-2 text-red-500">Note: Rules with related records cannot be deleted.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button variant="danger" icon="trash" wire:click="delete({{ $rule->id }})" />
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <!-- Rule Details Modal -->
    <flux:modal name="rule-details-modal" wire:model="showDetailsModal" title="Rule Details" class="max-w-6xl">
        @if($selectedRuleId)
            @livewire('hrms.leave.rule-details', [
                'ruleId' => $selectedRuleId
            ], key('rule-details-'.$selectedRuleId))
        @endif
    </flux:modal>
</div>