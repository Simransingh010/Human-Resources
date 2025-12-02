<div wire:init="loadData" class="space-y-6">
    <!-- Initial Loading -->
    @if(!$readyToLoad)
        <div class="flex items-center justify-center py-20">
            <div class="text-center">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-4 border-blue-600"></div>
                <p class="mt-4 text-gray-600">Loading approval rules...</p>
            </div>
        </div>
    @else
        <!-- Header -->
        <div class="flex justify-between items-center">
            @livewire('panel.component-heading')
            <flux:button variant="primary" icon="plus" wire:click="create">New Rule</flux:button>
        </div>
        <flux:separator />

        <!-- Filters -->
        <flux:card>
            <div class="flex flex-wrap gap-4 items-end">
                @foreach($filterFields as $field => $cfg)
                    @if(in_array($field, $visibleFilterFields))
                        <div class="w-48">
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
                        </div>
                    @endif
                @endforeach
                <flux:button variant="outline" wire:click="clearFilters" icon="x-circle">Clear</flux:button>
            </div>
        </flux:card>

        <!-- Rules Grouped by Leave Type - Client-side Accordion with Filters -->
        <div class="space-y-3" x-data="{ expanded: {} }">
            @forelse($this->groupedRules as $leaveTypeId => $group)
                @php
                    $colors = $this->getLeaveTypeColor($group['leave_title']);
                    $rulesJson = json_encode($group['rules']);
                    $approversJson = json_encode($group['approvers']);
                @endphp

                <div 
                    class="rounded-xl border {{ $colors['border'] }} overflow-hidden shadow-sm"
                    x-data="{
                        rules: {{ $rulesJson }},
                        approvers: {{ $approversJson }},
                        searchQuery: '',
                        selectedApprover: '',
                        get filteredRules() {
                            return this.rules.filter(rule => {
                                const matchesSearch = !this.searchQuery || 
                                    (rule.approver_name && rule.approver_name.toLowerCase().includes(this.searchQuery.toLowerCase())) ||
                                    (rule.employee_names && rule.employee_names.toLowerCase().includes(this.searchQuery.toLowerCase()));
                                const matchesApprover = !this.selectedApprover || 
                                    String(rule.approver_id) === String(this.selectedApprover) ||
                                    (this.selectedApprover === 'auto' && rule.auto_approve);
                                return matchesSearch && matchesApprover;
                            });
                        },
                        clearFilters() {
                            this.searchQuery = '';
                            this.selectedApprover = '';
                        }
                    }"
                >
                    <!-- Header - Click handled by Alpine (no server request) -->
                    <button
                        type="button"
                        @click="expanded[{{ $leaveTypeId }}] = !expanded[{{ $leaveTypeId }}]"
                        class="w-full px-5 py-4 flex items-center justify-between {{ $colors['bg'] }} hover:brightness-95 transition-all cursor-pointer text-left"
                    >
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 rounded-xl bg-white shadow-sm">
                                @switch($colors['icon'])
                                    @case('heart') <flux:icon.heart class="size-6 text-{{ $colors['badge'] }}-600" /> @break
                                    @case('sun') <flux:icon.sun class="size-6 text-{{ $colors['badge'] }}-600" /> @break
                                    @case('calendar') <flux:icon.calendar class="size-6 text-{{ $colors['badge'] }}-600" /> @break
                                    @case('user') <flux:icon.user class="size-6 text-{{ $colors['badge'] }}-600" /> @break
                                    @case('academic-cap') <flux:icon.academic-cap class="size-6 text-{{ $colors['badge'] }}-600" /> @break
                                    @case('banknotes') <flux:icon.banknotes class="size-6 text-{{ $colors['badge'] }}-600" /> @break
                                    @default <flux:icon.document-text class="size-6 text-{{ $colors['badge'] }}-600" />
                                @endswitch
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 text-lg">{{ $group['leave_title'] }}</h3>
                                <div class="flex items-center gap-3 mt-0.5 text-sm text-gray-600">
                                    <span>{{ $group['total_rules'] }} {{ Str::plural('rule', $group['total_rules']) }}</span>
                                    @if($group['inactive_count'] > 0)
                                        <span class="text-amber-600">• {{ $group['inactive_count'] }} inactive</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <flux:badge color="{{ $colors['badge'] }}" size="lg">{{ $group['active_count'] }} Active</flux:badge>
                            <span x-show="!expanded[{{ $leaveTypeId }}]"><flux:icon.chevron-down class="size-5 text-gray-500" /></span>
                            <span x-show="expanded[{{ $leaveTypeId }}]" x-cloak><flux:icon.chevron-up class="size-5 text-gray-500" /></span>
                        </div>
                    </button>

                    <!-- Content - Shown/Hidden by Alpine (no server request) -->
                    <div
                        x-show="expanded[{{ $leaveTypeId }}]"
                        x-collapse
                        class="bg-white border-t {{ $colors['border'] }}"
                    >
                        <!-- Inline Filters (Client-side - no server request) -->
                        <div class="px-4 py-3 bg-gray-50 border-b border-gray-100 flex flex-wrap items-center gap-3">
                            <div class="flex-1 min-w-[200px] max-w-xs">
                                <flux:input 
                                    type="search" 
                                    x-model.debounce.200ms="searchQuery"
                                    placeholder="Search approver or employee..."
                                    icon="magnifying-glass"
                                    size="sm"
                                />
                            </div>
                            <div class="w-48">
                                <flux:select x-model="selectedApprover" size="sm" placeholder="All Approvers">
                                    <flux:select.option value="">All Approvers</flux:select.option>
                                    <flux:select.option value="auto">Auto-Approve</flux:select.option>
                                    @foreach($group['approvers'] as $approverId => $approverName)
                                        <flux:select.option value="{{ $approverId }}">{{ $approverName }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>
                            <flux:button 
                                x-show="searchQuery || selectedApprover"
                                @click="clearFilters()"
                                variant="ghost"
                                size="sm"
                                icon="x-circle"
                            >Clear</flux:button>
                            <flux:badge color="zinc" size="sm" class="ml-auto" x-text="filteredRules.length + ' of ' + rules.length + ' rules'"></flux:badge>
                        </div>

                        <!-- Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                                    <tr>
                                        <th class="px-4 py-3 text-left">Level</th>
                                        <th class="px-4 py-3 text-left">Approver</th>
                                        <th class="px-4 py-3 text-left">Mode</th>
                                        <th class="px-4 py-3 text-left">Days</th>
                                        <th class="px-4 py-3 text-left">Period</th>
                                        <th class="px-4 py-3 text-center">Employees</th>
                                        <th class="px-4 py-3 text-center">Status</th>
                                        <th class="px-4 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <template x-for="rule in filteredRules" :key="rule.id">
                                        <tr class="hover:bg-gray-50" :class="{ 'opacity-50': rule.is_inactive }">
                                            <td class="px-4 py-3">
                                                <span 
                                                    x-show="rule.approval_level"
                                                    class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-{{ $colors['badge'] }}-100 text-{{ $colors['badge'] }}-700 font-semibold text-sm"
                                                    x-text="rule.approval_level"
                                                ></span>
                                                <span x-show="!rule.approval_level" class="text-gray-400">-</span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span x-show="rule.auto_approve" class="inline-flex items-center gap-1.5 text-green-700">
                                                    <flux:icon.check-circle class="size-4" />
                                                    Auto-Approve
                                                </span>
                                                <span x-show="!rule.auto_approve" class="text-gray-900" x-text="rule.approver_name || 'Not Set'"></span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span 
                                                    x-show="rule.approval_mode"
                                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                                    :class="{
                                                        'bg-emerald-100 text-emerald-700': rule.approval_mode === 'sequential',
                                                        'bg-blue-100 text-blue-700': rule.approval_mode === 'parallel',
                                                        'bg-violet-100 text-violet-700': rule.approval_mode === 'any',
                                                        'bg-zinc-100 text-zinc-700': rule.approval_mode === 'view_only'
                                                    }"
                                                    x-text="{'sequential':'Sequential','parallel':'Parallel','any':'Any','view_only':'View Only'}[rule.approval_mode] || rule.approval_mode"
                                                ></span>
                                                <span x-show="!rule.approval_mode" class="text-gray-400">-</span>
                                            </td>
                                            <td class="px-4 py-3 text-gray-600">
                                                <span x-text="(rule.min_days ?? 0) + ' - ' + (rule.max_days ?? '∞')"></span>
                                            </td>
                                            <td class="px-4 py-3 text-gray-600 text-xs">
                                                <span x-text="rule.period_start + ' → ' + rule.period_end"></span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <button
                                                    x-show="rule.employees_count > 0"
                                                    x-data="{ loading: false }"
                                                    @click="loading = true; $wire.showEmployeeList(rule.id).then(() => loading = false)"
                                                    :disabled="loading"
                                                    class="inline-flex items-center gap-1 px-2 py-1 rounded bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm transition disabled:opacity-50"
                                                >
                                                    <template x-if="!loading">
                                                        <flux:icon.users class="size-4" />
                                                    </template>
                                                    <template x-if="loading">
                                                        <flux:icon.loading class="size-4" />
                                                    </template>
                                                    <span x-text="rule.employees_count"></span>
                                                </button>
                                                <span x-show="!rule.employees_count || rule.employees_count === 0" class="text-gray-400">-</span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <flux:switch
                                                    x-bind:wire:model.live="'statuses.' + rule.id"
                                                    @change="$wire.toggleStatus(rule.id)"
                                                />
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <div class="flex items-center justify-end gap-1">
                                                    <button 
                                                        x-data="{ loading: false }"
                                                        @click="loading = true; $wire.edit(rule.id).then(() => loading = false)"
                                                        :disabled="loading"
                                                        class="p-1 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded disabled:opacity-50"
                                                    >
                                                        <template x-if="!loading">
                                                            <flux:icon.pencil-square class="size-4" />
                                                        </template>
                                                        <template x-if="loading">
                                                            <flux:icon.loading class="size-4" />
                                                        </template>
                                                    </button>
                                                    <button 
                                                        x-data="{ loading: false }"
                                                        @click="loading = true; $wire.confirmDelete(rule.id).then(() => loading = false)"
                                                        :disabled="loading"
                                                        class="p-1 text-red-500 hover:text-red-700 hover:bg-red-50 rounded disabled:opacity-50"
                                                    >
                                                        <template x-if="!loading">
                                                            <flux:icon.trash class="size-4" />
                                                        </template>
                                                        <template x-if="loading">
                                                            <flux:icon.loading class="size-4" />
                                                        </template>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <!-- No results message -->
                                    <tr x-show="filteredRules.length === 0">
                                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                            No rules match your filters
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-16 bg-gray-50 rounded-xl border-2 border-dashed border-gray-200">
                    <flux:icon.document-text class="size-12 mx-auto text-gray-300" />
                    <p class="mt-4 text-gray-600 font-medium">No approval rules yet</p>
                    <p class="text-gray-500 text-sm">Create your first rule to get started</p>
                    <flux:button variant="primary" icon="plus" wire:click="create" class="mt-4">Create Rule</flux:button>
                </div>
            @endforelse
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    <flux:modal name="mdl-confirm-delete" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">Delete Rule?</flux:heading>
            <p class="text-gray-600">This action cannot be undone.</p>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="executeDelete">Delete</flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Add/Edit Rule Modal -->
    <flux:modal name="mdl-approval-rule" @cancel="$wire.resetForm()" position="right" class="max-w-4xl" variant="flyout">
        <form wire:submit.prevent="store" class="space-y-6">
            <flux:heading size="lg">{{ $isEditing ? 'Edit' : 'New' }} Approval Rule</flux:heading>
            <flux:separator />

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2 flex items-center gap-2">
                    <flux:switch wire:model.live="formData.auto_approve" />
                    <span class="text-sm font-medium">Auto Approve</span>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Leave Type *</label>
                    <select wire:model="formData.leave_type_id" class="w-full rounded-md border-gray-300 text-sm">
                        <option value="">Select...</option>
                        @foreach($listsForFields['leave_types_list'] as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('formData.leave_type_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                @if(!$formData['auto_approve'])
                    <div>
                        <label class="block text-sm font-medium mb-1">Approver</label>
                        <select wire:model="formData.approver_id" class="w-full rounded-md border-gray-300 text-sm">
                            <option value="">Select...</option>
                            @foreach($listsForFields['approvers_list'] as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Mode</label>
                        <select wire:model="formData.approval_mode" class="w-full rounded-md border-gray-300 text-sm">
                            <option value="">Select...</option>
                            @foreach($listsForFields['approval_modes'] as $k => $v)
                                <option value="{{ $k }}">{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Level</label>
                        <flux:input type="number" wire:model="formData.approval_level" min="1" />
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-medium mb-1">Min Days</label>
                    <flux:input type="number" wire:model="formData.min_days" min="0" step="0.5" />
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Max Days</label>
                    <flux:input type="number" wire:model="formData.max_days" min="0" step="0.5" />
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Period Start *</label>
                    <flux:input type="date" wire:model="formData.period_start" />
                    @error('formData.period_start') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Period End *</label>
                    <flux:input type="date" wire:model="formData.period_end" />
                    @error('formData.period_end') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Employee Selection -->
            <div>
                <div class="flex justify-between items-center mb-2">
                    <label class="text-sm font-medium">
                        Employees
                        @if(count($selectedEmployees) > 0)
                            <flux:badge color="blue" size="sm" class="ml-2">{{ count($selectedEmployees) }} selected</flux:badge>
                        @endif
                    </label>
                    <div class="flex gap-2">
                        <flux:button size="xs" variant="outline" wire:click="selectAllEmployeesGlobal">All</flux:button>
                        <flux:button size="xs" variant="ghost" wire:click="deselectAllEmployeesGlobal">None</flux:button>
                    </div>
                </div>

                <flux:input type="search" wire:model.live="employeeSearch" placeholder="Search employees..." class="mb-2" />

                <div class="max-h-60 overflow-y-auto border rounded-lg p-2 space-y-2">
                    @forelse($filteredDepartmentsWithEmployees as $dept)
                        <div x-data="{ open: false }" class="border rounded">
                            <button type="button" @click="open = !open" class="w-full px-3 py-2 flex justify-between items-center bg-gray-50 hover:bg-gray-100 text-sm">
                                <span>{{ $dept['title'] }} ({{ count($dept['employees']) }})</span>
                                <span x-show="!open"><flux:icon.chevron-down class="size-4" /></span>
                                <span x-show="open" x-cloak><flux:icon.chevron-up class="size-4" /></span>
                            </button>
                            <div x-show="open" x-collapse class="p-2 space-y-1">
                                <div class="flex gap-2 mb-2">
                                    <flux:button size="xs" variant="outline" wire:click="selectAllEmployees({{ $dept['id'] }})">Select All</flux:button>
                                    <flux:button size="xs" variant="ghost" wire:click="deselectAllEmployees({{ $dept['id'] }})">Deselect</flux:button>
                                </div>
                                @foreach($dept['employees'] as $emp)
                                    <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-gray-50 p-1 rounded">
                                        <input type="checkbox" wire:model="selectedEmployees" value="{{ $emp['id'] }}" class="rounded border-gray-300">
                                        {{ $emp['fname'] }} {{ $emp['lname'] }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 text-sm text-center py-4">No employees found</p>
                    @endforelse
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-4">
                <flux:button type="button" variant="ghost" x-on:click="$flux.modal('mdl-approval-rule').close()">Cancel</flux:button>
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Employee List Modal -->
    <flux:modal name="mdl-employee-list" class="max-w-4xl">
        <div class="space-y-4">
            <flux:heading size="lg">Assigned Employees</flux:heading>

            @if($selectedRuleForEmployees)
                <div class="flex gap-4">
                    <flux:input type="search" wire:model.live.debounce.300ms="employeeSearch" placeholder="Search..." class="flex-1" />
                    @foreach($employeeFilterFields as $field => $cfg)
                        @if($cfg['type'] === 'select')
                            <flux:select wire:model.live="employeeFilters.{{ $field }}" placeholder="All {{ $cfg['label'] }}" class="w-48">
                                <flux:select.option value="">All</flux:select.option>
                                @foreach($listsForFields[$cfg['listKey']] as $v => $l)
                                    <flux:select.option value="{{ $v }}">{{ $l }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        @endif
                    @endforeach
                    <flux:button variant="outline" wire:click="clearEmployeeFilters" icon="x-circle" />
                </div>

                <div class="max-h-96 overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-4 py-2 text-left">Name</th>
                                <th class="px-4 py-2 text-left">Email</th>
                                <th class="px-4 py-2 text-left">Department</th>
                                <th class="px-4 py-2 text-left">Designation</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse($this->employeeList as $emp)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2">{{ $emp->fname }} {{ $emp->lname }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ $emp->email }}</td>
                                    <td class="px-4 py-2">{{ $emp->emp_job_profile->department->title ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $emp->emp_job_profile->designation->title ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">No employees</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-2">
                    {{ $this->employeeList->links() }}
                </div>
            @endif
        </div>
    </flux:modal>
</div>
