<div>
    <div class="space-y-1">
        <!-- Heading Start -->
        <div class="justify-between">
            @livewire('panel.component-heading')
        </div>

        <!-- Heading End -->

        <!-- Tabs -->
        <flux:tab.group>
            <flux:tabs wire:model="tab">
                <flux:tab name="single">Single Employee Increment</flux:tab>
                <flux:tab name="bulk">Bulk Increments</flux:tab>
                <flux:tab name="logs">Change Logs</flux:tab>
            </flux:tabs>

            <!-- Single Employee Increment Panel -->
            <flux:tab.panel name="single">
                <flux:card>
                    <div class="space-y-6">
                        <!-- Search Input - Always Visible -->
                        <div class="max-w-2xl">
                            <flux:heading size="lg" class="mb-4">{{ $selectedEmployee ? 'Change Employee' : 'Select Employee to Begin' }}</flux:heading>
                            
                            <!-- Search Input with Dropdown -->
                            <div class="relative">
                                <flux:input
                                    wire:model.live.debounce.300ms="search"
                                    placeholder="👤 Type to search by Employee Name..."
                                    class="w-full"
                                />

                                <!-- Search Results Dropdown -->
                                @if(!empty($search) && $employees->isNotEmpty())
                                    <div class="absolute z-50 w-full mt-1 bg-white rounded-md shadow-lg border border-gray-200">
                                        <ul class="py-1 max-h-60 overflow-auto">
                                            @foreach($employees as $employee)
                                                <li>
                                                    <button
                                                        wire:click="selectEmployee({{ $employee->id }})"
                                                        class="w-full px-4 py-2 text-left hover:bg-gray-100 focus:bg-gray-100 focus:outline-none"
                                                    >
                                                        <div class="flex justify-between items-center">
                                                            <div>
                                                                <span class="font-medium">
                                                                    {{ $employee->fname }} {{ $employee->lname }}
                                                                </span>
                                                            </div>
                                                            @if(optional($employee->emp_job_profile)->designation)
                                                                <span class="text-sm text-gray-500">
                                                                    {{ optional($employee->emp_job_profile->designation)->title }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </button>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if($selectedEmployee)
                            <!-- Selected Employee Details -->
                            <div class="space-y-4">
                                <flux:separator />
                                <flux:heading>Employee Details</flux:heading>
                                
                                <!-- Employee details content will go here -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Name</label>
                                        <div class="mt-1">
                                            {{ $selectedEmployee->fname }} {{ $selectedEmployee->lname }}
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Employee Code</label>
                                        <div class="mt-1 flex items-center justify-between">
                                            {{ optional($selectedEmployee->emp_job_profile)->employee_code }}
                                            <!-- Assign New Components Button -->
                                            <flux:modal.trigger name="assign-components-modal">
                                                <flux:button variant="primary" size="sm" class="ml-4">Assign New Components</flux:button>
                                            </flux:modal.trigger>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Designation</label>
                                        <div class="mt-1">
                                            {{ optional($selectedEmployee->emp_job_profile->designation)->title }}
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Department</label>
                                        <div class="mt-1">
                                            {{ optional($selectedEmployee->emp_job_profile->department)->title }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Structure Periods + Components Layout -->
                                <div class="mt-6 flex gap-6 min-h-[300px]">
                                    <!-- Vertical Structure Periods List (like slots) -->
                                    <div class="w-64 border rounded bg-white p-2 flex flex-col gap-2">
                                        <div class="font-semibold mb-2">Salary Structure Periods</div>
                                        <div class="flex justify-end mb-2">
                                            <flux:button  wire:click="resyncFromChangeLogs">Sync Old Changes</flux:button>
                                        </div>
                                        @if($structurePeriods && $structurePeriods->count())
                                            @foreach($structurePeriods as $period)
                                                <button type="button"
                                                    wire:click="selectStructure('{{ $period['key'] }}')"
                                                    class="w-full text-left px-3 py-2 rounded border @if($selectedStructureKey === $period['key']) bg-blue-100 border-blue-500 @elseif($period['is_active']) bg-green-400/20 @else bg-gray-200 @endif">
                                                    <div class="font-medium">
                                                        {{ $period['from']->format('d M Y') }} - {{ $period['to'] ? $period['to']->format('d M Y') : 'Present' }}
                                                    </div>
                                                    <div class="text-xs mt-1">
                                                        <span class="inline-block px-2 py-0.5 rounded text-white text-xs @if($period['is_active']) bg-green-500 @else bg-zinc-500 @endif">
                                                            {{ $period['is_active'] ? 'Active' : 'Inactive' }}
                                                        </span>
                                                        <span class="ml-2 text-gray-600">{{ $period['components_count'] }} components</span>
                                                    </div>
                                                </button>
                                            @endforeach
                                        @else
                                            <div class="text-gray-400">No structures</div>
                                        @endif
                                    </div>

                                    <!-- Components Table for selected period -->
                                    <div class="flex-1">
                                        <flux:heading size="lg">Components in Selected Period</flux:heading>
                                        <flux:table class="mt-4">
                                            <flux:table.columns>
                                                <flux:table.column 
                                                    sortable 
                                                    :sorted="$sortBy === 'sequence'" 
                                                    :direction="$sortDirection" 
                                                    wire:click="sort('sequence')"
                                                >Component</flux:table.column>
                                                <flux:table.column 
                                                    sortable 
                                                    :sorted="$sortBy === 'amount'" 
                                                    :direction="$sortDirection" 
                                                    wire:click="sort('amount')"
                                                >Amount</flux:table.column>
                                                <flux:table.column>Type</flux:table.column>
                                                <flux:table.column>Effective From</flux:table.column>
                                                <flux:table.column>Effective To</flux:table.column>
                                                <flux:table.column>Actions</flux:table.column>
                                            </flux:table.columns>

                                            <flux:table.rows>
                                                @foreach(($structureComponents && $structureComponents->count() ? $structureComponents : $salaryComponents) as $salaryItem)
                                                    <flux:table.row :key="$salaryItem['id']">
                                                        <flux:table.cell>
                                                            <div>
                                                                <div class="font-medium">{{ $salaryItem['title'] }}</div>
                                                                @php
                                                                    $today = \Carbon\Carbon::today();
                                                                    $from = $salaryItem['effective_from'] ? \Carbon\Carbon::createFromFormat('d M Y', $salaryItem['effective_from']) : null;
                                                                    $to = $salaryItem['effective_to'] ? \Carbon\Carbon::createFromFormat('d M Y', $salaryItem['effective_to']) : null;
                                                                    if ($from && $from->gt($today)) {
                                                                        $status = 'Scheduled';
                                                                        $badgeColor = 'blue';
                                                                    } elseif ($to && $to->lt($today)) {
                                                                        $status = 'Expired';
                                                                        $badgeColor = 'zinc';
                                                                    } else {
                                                                        $status = 'Active';
                                                                        $badgeColor = 'green';
                                                                    }
                                                                @endphp
                                                                <div class="mt-1">
                                                                    <flux:badge color="{{ $badgeColor }}">{{ $status }}</flux:badge>
                                                                </div>
                                                                @if($salaryItem['group'])
                                                                    <div class="text-sm text-gray-500">{{ $salaryItem['group'] }}</div>
                                                                @endif
                                                            </div>
                                                        </flux:table.cell>
                                                        <flux:table.cell class="font-medium">
                                                            <flux:badge 
                                                                :color="$salaryItem['nature'] === 'earning' ? 'green' : ($salaryItem['nature'] === 'deduction' ? 'red' : 'gray')"
                                                            >
                                                                ₹{{ number_format((float) $salaryItem['amount'], 2) }}
                                                            </flux:badge>
                                                        </flux:table.cell>
                                                        <flux:table.cell>
                                                            <div class="text-sm">
                                                                {{ ucfirst($salaryItem['component_type']) }}
                                                                @if($salaryItem['amount_type'])
                                                                    <span class="text-gray-500">({{ str_replace('_', ' ', $salaryItem['amount_type']) }})</span>
                                                                @endif
                                                            </div>
                                                        </flux:table.cell>
                                                        <flux:table.cell>
                                                            {{ $salaryItem['effective_from'] }}
                                                        </flux:table.cell>
                                                        <flux:table.cell>
                                                            {{ $salaryItem['effective_to'] }}
                                                        </flux:table.cell>
                                                        <flux:table.cell>
                                                            <div class="flex space-x-2">
                                                                <flux:dropdown>
                                                                <flux:button icon="ellipsis-horizontal" />

                                                                    <flux:menu>
                                                                        @if($salaryItem['amount_type'] === 'static_known')
                                                                            <flux:menu.item 
                                                                                wire:click="editComponent({{ $salaryItem['id'] }})"
                                                                                icon="pencil"
                                                                            >
                                                                                Increment/Decrement
                                                                            </flux:menu.item>
                                                                        @elseif($salaryItem['amount_type'] === 'calculated_known')
                                                                            <flux:menu.item 
                                                                                wire:click="openCalculationRule({{ $salaryItem['id'] }})"
                                                                                icon="calculator"
                                                                            >
                                                                                Configure Formula
                                                                            </flux:menu.item>
                                                                        @endif
                                                                        <flux:menu.separator />
                                                                        <flux:modal.trigger name="delete-{{ $salaryItem['id'] }}">
                                                                            <flux:menu.item icon="trash" class="text-red-600">
                                                                                Delete
                                                                            </flux:menu.item>
                                                                        </flux:modal.trigger>
                                                                    </flux:menu>
                                                                </flux:dropdown>

                                                                <!-- Delete Confirmation Modal -->
                                                                <flux:modal name="delete-{{ $salaryItem['id'] }}" class="min-w-[22rem]">
                                                                    <div class="space-y-6">
                                                                        <div>
                                                                            <flux:heading size="lg">Delete Salary Component?</flux:heading>
                                                                            <flux:text class="mt-2">
                                                                                <p>You're about to delete this salary component. This action cannot be undone.</p>
                                                                                <p class="mt-2 text-red-500">Note: This will affect employee's salary calculation.</p>
                                                                            </flux:text>
                                                                        </div>
                                                                        <div class="flex gap-2">
                                                                            <flux:spacer/>
                                                                            <flux:modal.close>
                                                                                <flux:button variant="ghost">Cancel</flux:button>
                                                                            </flux:modal.close>
                                                                            <flux:button 
                                                                                variant="danger" 
                                                                                icon="trash" 
                                                                                wire:click="deleteComponent({{ $salaryItem['id'] }})"
                                                                            />
                                                                        </div>
                                                                    </div>
                                                                </flux:modal>

                                                                <!-- Increment/Decrement Modal -->
                                                                <flux:modal name="increment-{{ $salaryItem['id'] }}" class="md:w-[40rem]">
                                                                    <div class="space-y-6">
                                                                        <div>
                                                                            <flux:heading size="lg">Modify Salary Component</flux:heading>
                                                                            <flux:text class="mt-2 text-lg">
                                                                                Current Amount: <span class="font-semibold text-green-600">₹{{ number_format((float) $salaryItem['amount'], 2) }}</span>
                                                                            </flux:text>
                                                                        </div>

                                                                        <!-- Date Range Fields -->
                                                                        <div class="grid grid-cols-2 gap-4">
                                                                            <div>
                                                                            <div>DEBUG: {{ $start_date }}</div>
                                                                                <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                                                                                <flux:date-picker wire:model.live="start_date" placeholder="Start Date" />
                                                                            </div>
                                                                            <div>
                                                                                <label class="block text-sm font-medium text-gray-700 mb-1">End Date (Optional)</label>
                                                                                <flux:date-picker wire:model.live="end_date" placeholder="End Date (Optional)" />
                                                                            </div>
                                                                        </div>

                                                                        <!-- Grid Layout for Controls -->
                                                                        <div class="grid grid-cols-2 gap-4">
                                                                            <!-- Modification Type -->
                                                                            <div>
                                                                                <flux:select 
                                                                                    wire:model.live="incrementType"
                                                                                    label="Modification Type"
                                                                                >
                                                                                    <flux:select.option value="fixed_amount">Fixed Amount</flux:select.option>
                                                                                    <flux:select.option value="percentage">Percentage</flux:select.option>
                                                                                    <flux:select.option value="new_amount">New Fixed Amount</flux:select.option>
                                                                                </flux:select>
                                                                            </div>

                                                                            <!-- Operation Type -->
                                                                            <div>
                                                                                <flux:select 
                                                                                    wire:model.live="operation"
                                                                                    label="Operation"
                                                                                >
                                                                                    <flux:select.option value="increase">Increase</flux:select.option>
                                                                                    <flux:select.option value="decrease">Decrease</flux:select.option>
                                                                                </flux:select>
                                                                            </div>

                                                                            <!-- Value Input -->
                                                                            <div class="col-span-2">
                                                                                <flux:input
                                                                                    type="number"
                                                                                    wire:model.live="modificationValue"
                                                                                    :label="$incrementType === 'percentage' ? 'Enter Percentage (%)' : ($incrementType === 'new_amount' ? 'Enter New Amount (₹)' : 'Enter Amount (₹)')"
                                                                                    :placeholder="$incrementType === 'percentage' ? 'Enter percentage (0-100)' : ($incrementType === 'new_amount' ? 'Enter new fixed amount' : 'Enter amount to add/subtract')"
                                                                                    :max="$incrementType === 'percentage' ? 100 : null"
                                                                                    min="0"
                                                                                />
                                                                            </div>

                                                                            <!-- Remarks Field -->
                                                                            <div class="col-span-2 mt-4">
                                                                                <flux:input
                                                                                    wire:model="remarks"
                                                                                    label="Remarks"
                                                                                    description="Please provide a reason for this salary modification."
                                                                                    placeholder="e.g., Annual increment, Performance bonus, etc."
                                                                                />
                                                                            </div>

                                                                        </div>

                                                                        <!-- Final Amount Preview -->
                                                                        <div class="p-6 bg-gray-50 rounded-lg border border-gray-200">
                                                                            <div class="flex justify-between items-center">
                                                                                <flux:heading>Final Amount</flux:heading>
                                                                                <div class="text-2xl font-bold text-green-600">
                                                                                    ₹{{ number_format((float) $calculatedFinalAmount, 2) }}
                                                                                </div>
                                                                            </div>
                                                                            <div class="mt-2 text-gray-600 text-sm">
                                                                                @if($modificationValue)
                                                                                    @if($incrementType === 'fixed_amount')
                                                                                        ₹{{ number_format((float) $currentAmount, 2) }} {{ $operation === 'increase' ? '+' : '-' }} ₹{{ number_format((float) $modificationValue, 2) }}
                                                                                    @elseif($incrementType === 'percentage')
                                                                                        ₹{{ number_format((float) $currentAmount, 2) }} {{ $operation === 'increase' ? '+' : '-' }} {{ $modificationValue }}% 
                                                                                        (₹{{ number_format((float) ($currentAmount * $modificationValue / 100), 2) }})
                                                                                    @else
                                                                                        New fixed amount (Previous: ₹{{ number_format((float) $currentAmount, 2) }})
                                                                                    @endif
                                                                                @endif
                                                                            </div>
                                                                        </div>

                                                                        <!-- Action Buttons -->
                                                                        <div class="flex gap-2">
                                                                            <flux:spacer/>
                                                                            <flux:button 
                                                                                wire:click="cancelModification"
                                                                            >Cancel</flux:button>
                                                                            <flux:button
                                                                                variant="primary"
                                                                                wire:click="saveModification({{ $salaryItem['id'] }})"
                                                                                :disabled="!$modificationValue || $modificationValue <= 0"
                                                                            >
                                                                                Apply Changes
                                                                            </flux:button>
                                                                        </div>
                                                                    </div>
                                                                </flux:modal>
                                                            </div>
                                                        </flux:table.cell>
                                                    </flux:table.row>
                                                @endforeach
                                            </flux:table.rows>
                                        </flux:table>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </flux:card>
            </flux:tab.panel>

            <!-- Bulk Increment Panel -->
            <flux:tab.panel name="bulk">
                <flux:card>
                    <flux:heading>Bulk Salary Increments</flux:heading>
                    <div class="space-y-4">
                        <!-- Bulk Filters UI -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Employee Name Filter -->
                            <flux:input
                                wire:model.live="bulkFilter.name"
                                label="Employee Name"
                                placeholder="Type employee name..."
                            />
                            <!-- Department Filter -->
                            <flux:select
                                wire:model.live="bulkFilter.department_id"
                                label="Department"
                                placeholder="Select department"
                            >
                                <flux:select.option value="">All</flux:select.option>
                                @foreach($this->allDepartments as $id => $title)
                                    <flux:select.option value="{{ $id }}">{{ $title }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <!-- Execution Group Filter -->
                            <flux:select
                                wire:model.live="bulkFilter.execution_group_id"
                                label="Execution Group"
                                placeholder="Select execution group"
                            >
                                <flux:select.option value="">All</flux:select.option>
                                @foreach($this->allExecutionGroups as $id => $title)
                                    <flux:select.option value="{{ $id }}">{{ $title }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Date of Joining From -->
                            <flux:date-picker wire:model.live="bulkFilter.doh_from" label="Date of Joining (From)">
                                <x-slot name="trigger">
                                    <flux:date-picker.input />
                                </x-slot>
                            </flux:date-picker>
                            <!-- Date of Joining To -->
                            <flux:date-picker wire:model.live="bulkFilter.doh_to" label="Date of Joining (To)">
                                <x-slot name="trigger">
                                    <flux:date-picker.input />
                                </x-slot>
                            </flux:date-picker>
                            <!-- Component Amount Filter -->
                            <div class="flex gap-2 items-end">
                                <flux:select
                                    wire:model.live="bulkFilter.component_id"
                                    label="Component"
                                    placeholder="Component"
                                    class="w-1/2"
                                >
                                    <flux:select.option value="">All</flux:select.option>
                                    @foreach($this->allSalaryComponents as $id => $title)
                                        <flux:select.option value="{{ $id }}">{{ $title }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:input
                                    type="number"
                                    wire:model.live="bulkFilter.amount_min"
                                    label="Min Amount"
                                    placeholder="Min"
                                    class="w-1/4"
                                />
                                <flux:input
                                    type="number"
                                    wire:model.live="bulkFilter.amount_max"
                                    label="Max Amount"
                                    placeholder="Max"
                                    class="w-1/4"
                                />
                            </div>
                        </div>
                        <!-- Filtered Employee List -->
                        <div class="mt-6 relative">
                            <flux:heading >Filtered Employees ({{ count($this->bulkEmployees) }})</flux:heading>
                            <!-- Action Bar for bulk actions -->
                            <div class="flex items-center justify-between gap-2 mb-2 py-2 px-3 bg-gray-50 border border-gray-200 rounded-lg">
                                <div class="w-1/2">
                                    <flux:select
                                        wire:model="selectedBulkComponentId"
                                        placeholder="Select Common Component"
                                    >
                                        <flux:select.option value="" disabled selected>Select Common Component</flux:select.option>
                                        @if(count($this->commonSalaryComponents))
                                            @foreach($this->commonSalaryComponents as $id => $title)
                                                <flux:select.option value="{{ $id }}">{{ $title }}</flux:select.option>
                                            @endforeach
                                        @else
                                            <flux:select.option value="" disabled>No common components</flux:select.option>
                                        @endif
                                    </flux:select>
                                </div>
                                <flux:button size="sm" variant="primary" wire:click="openBulkUpdateModal">Bulk Updation</flux:button>
                                <!-- Future actions can be added here -->
                            </div>
                            <!-- Loader overlay -->
                            <div wire:loading.flex wire:target="bulkFilter" class="absolute inset-0 z-10 flex  justify-center bg-white/70 backdrop-blur">
                                <flux:icon.loading class="w-10 h-10 text-blue-500 animate-spin" />
                            </div>
                            <flux:table class="mt-2">
                                <flux:table.columns>
                                    <flux:table.column>
                                        <flux:checkbox
                                            wire:model="selectAllBulkEmployees"
                                            :checked="count($this->selectedBulkEmployeeIds) === count($this->bulkEmployees) && count($this->bulkEmployees) > 0"
                                            wire:click="toggleSelectAllBulkEmployees"
                                        />
                                    </flux:table.column>
                                    <flux:table.column>Name</flux:table.column>
                                    <flux:table.column>Employee Code</flux:table.column>
                                    <flux:table.column>Department</flux:table.column>
                                    <flux:table.column>Execution Group</flux:table.column>
                                    <flux:table.column>Date of Joining</flux:table.column>
                                </flux:table.columns>
                                <flux:table.rows>
                                    @foreach($this->bulkEmployees as $employee)
                                        <flux:table.row :key="$employee->id">
                                            <flux:table.cell>
                                                <flux:checkbox.group wire:model="selectedBulkEmployeeIds">
                                                    <flux:checkbox value="{{ $employee->id }}" />
                                                </flux:checkbox.group>
                                            </flux:table.cell>
                                            <flux:table.cell>{{ $employee->fname }} {{ $employee->lname }}</flux:table.cell>
                                            <flux:table.cell>{{ optional($employee->emp_job_profile)->employee_code }}</flux:table.cell>
                                            <flux:table.cell>{{ $employee->emp_job_profile?->department?->title }}</flux:table.cell>
                                            <flux:table.cell>
                                                @if($employee->salary_execution_groups && count($employee->salary_execution_groups))
                                                    @foreach($employee->salary_execution_groups as $group)
                                                        {{ $group->title ?? '' }}@if(!$loop->last), @endif
                                                    @endforeach
                                                @else
                                                    <span class="text-gray-400">-</span>
                                                @endif
                                            </flux:table.cell>
                                            <flux:table.cell>{{ optional($employee->emp_job_profile)->doh ? optional($employee->emp_job_profile)->doh->format('d M Y') : '' }}</flux:table.cell>
                                        </flux:table.row>
                                    @endforeach
                                </flux:table.rows>
                            </flux:table>
                        </div>
                    </div>
                </flux:card>
            </flux:tab.panel>

            <!-- Change Logs Panel -->
            <flux:tab.panel name="logs">
                <flux:card>
                    <flux:heading>Salary Change Logs</flux:heading>
                    <div class="mt-4">
                        <div class="bg-white rounded shadow p-4">
                            <flux:table class="w-full">
                                <flux:table.columns>
                                    <flux:table.column>Date</flux:table.column>
                                    <flux:table.column>Employee</flux:table.column>
                                    <flux:table.column>Type</flux:table.column>
                                    <flux:table.column>Details</flux:table.column>
                                    <flux:table.column>Actions</flux:table.column>
                                </flux:table.columns>
                                <flux:table.rows>
                                    @forelse($this->changeLogs as $log)
                                        <flux:table.row :key="$log->id">
                                            <flux:table.cell>{{ \Carbon\Carbon::parse($log->created_at)->format('d M Y H:i') }}</flux:table.cell>
                                            <flux:table.cell>{{ $log->employee ? $log->employee->fname . ' ' . $log->employee->lname : '-' }}</flux:table.cell>
                                            <flux:table.cell>
                                                @if($log->batch_id)
                                                    <flux:badge variant="info">Batch</flux:badge>
                                                @else
                                                    <flux:badge variant="outline">Individual</flux:badge>
                                                @endif
                                            </flux:table.cell>
                                            <flux:table.cell>
                                                <div class="text-xs">
                                                    <div><b>Old:</b> {{ $log->old_salary_components ? ($log->old_salary_components->title ?? $log->old_salary_components->id) : '-' }}</div>
                                                    <div><b>New:</b> {{ $log->new_salary_components ? ($log->new_salary_components->title ?? $log->new_salary_components->id) : '-' }}</div>
                                                    <div><b>Change:</b> {{ $log->change_type ?? '-' }}</div>
                                                </div>
                                            </flux:table.cell>
                                            <flux:table.cell>
                                                <flux:modal.trigger name="log-details-{{ $log->id }}">
                                                    <flux:button size="xs" variant="primary" icon="eye">View</flux:button>
                                                </flux:modal.trigger>
                                                <!-- Details Modal -->
                                                <flux:modal name="log-details-{{ $log->id }}" class="min-w-[22rem]">
                                                    <div class="space-y-4">
                                                        <flux:heading size="lg">Change Details</flux:heading>
                                                        <pre class="bg-gray-100 p-2 rounded text-xs overflow-x-auto">{{ json_encode($log->changes_details_json, JSON_PRETTY_PRINT) }}</pre>
                                                        <div class="flex justify-end">
                                                            <flux:modal.close>
                                                                <flux:button variant="ghost">Close</flux:button>
                                                            </flux:modal.close>
                                                        </div>
                                                    </div>
                                                </flux:modal>
                                            </flux:table.cell>
                                        </flux:table.row>
                                    @empty
                                        <flux:table.row>
                                            <flux:table.cell colspan="5" class="text-center text-gray-500">No change logs found.</flux:table.cell>
                                        </flux:table.row>
                                    @endforelse
                                </flux:table.rows>
                            </flux:table>
                        </div>
                    </div>
                </flux:card>
            </flux:tab.panel>
        </flux:tab.group>

        <!-- Calculation Rule Builder Modal -->
        <flux:modal name="calculation-rule-modal" class="max-w-lg">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-semibold">Calculation Rule Builder</h3>
                </div>

                <!-- Date Range Fields -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <div>DEBUG: {{ $start_date }}</div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                        <flux:date-picker wire:model.live="start_date" placeholder="Start Date" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Date (Optional)</label>
                        <flux:date-picker wire:model.live="end_date" placeholder="End Date (Optional)" />
                    </div>
                </div>

                <div class="grid gap-6">
                    <!-- Left Side: Builder Form -->
                    <div class="space-y-6 border rounded-lg p-6 bg-white">
                        <!-- Root Type Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Root Type</label>
                            <flux:select wire:model.live="rule.type">
                                <flux:select.option value="conditional">Conditional</flux:select.option>
                                <flux:select.option value="operation">Operation (+, -, ×, ÷)</flux:select.option>
                                <flux:select.option value="component">Salary Component</flux:select.option>
                                <flux:select.option value="constant">Fixed Value</flux:select.option>
                            </flux:select>
                        </div>

                        @if($rule['type'] === 'operation')
                            <div class="space-y-4">
                                <!-- Operator Selection -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Operator</label>
                                    <flux:select wire:model.live="rule.operator">
                                        <flux:select.option value="+">Add (+)</flux:select.option>
                                        <flux:select.option value="-">Subtract (-)</flux:select.option>
                                        <flux:select.option value="*">Multiply (×)</flux:select.option>
                                        <flux:select.option value="/">Divide (÷)</flux:select.option>
                                    </flux:select>
                                </div>

                                <!-- Operands -->
                                <div>
                                    <div class="flex justify-between items-center mb-4">
                                        <label class="block text-sm font-medium text-gray-700">Operands</label>
                                        <flux:button size="sm" wire:click="addOperand" class="text-sm">
                                            Add Operand
                                        </flux:button>
                                    </div>

                                    <div class="space-y-4">
                                        @foreach($rule['operands'] ?? [] as $i => $operand)
                                            <div class="p-4 border rounded-lg bg-gray-50">
                                                <div class="flex items-center gap-4 mb-4">
                                                    <div class="flex-1">
                                                        <flux:select wire:model.live="rule.operands.{{ $i }}.type">
                                                            <flux:select.option value="component">Salary Component</flux:select.option>
                                                            <flux:select.option value="constant">Fixed Value</flux:select.option>
                                                        </flux:select>
                                                    </div>
                                                    <flux:button variant="danger" size="sm" wire:click="removeOperand({{ $i }})" class="text-sm">
                                                        Remove
                                                    </flux:button>
                                                </div>

                                                @if($operand['type'] === 'component')
                                                    <div class="ml-4">
                                                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Component</label>
                                                        <flux:select wire:model.live="rule.operands.{{ $i }}.key">
                                                            @foreach($this->salaryComponentsList as $id => $salaryitem)
                                                                <flux:select.option value="{{ $id }}">{{ $salaryitem['title'] }}</flux:select.option>
                                                            @endforeach
                                                        </flux:select>
                                                    </div>
                                                @else
                                                    <div class="ml-4">
                                                        <label class="block text-sm font-medium text-gray-700 mb-2">Enter Value</label>
                                                        <flux:input 
                                                            type="number" 
                                                            step="0.01" 
                                                            wire:model.live="rule.operands.{{ $i }}.value" 
                                                            placeholder="Enter value" 
                                                        />
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @elseif($rule['type'] === 'component')
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Select Component</label>
                                <flux:select wire:model.live="rule.key">
                                    @foreach($this->salaryComponentsList as $id => $salaryitem)
                                        <flux:select.option value="{{ $id }}">{{ $salaryitem['title'] }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>
                        @elseif($rule['type'] === 'constant')
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Enter Value</label>
                                <flux:input type="number" step="0.01" wire:model.live="rule.value" placeholder="Enter value" />
                            </div>
                        @endif
                    </div>

                    <!-- Right Side: JSON Preview -->
                    <div class="border rounded-lg p-6 bg-gray-50">
                        <flux:heading class="mb-4">Live JSON Preview</flux:heading>
                        <pre class="bg-white p-4 rounded-lg overflow-auto max-h-[600px] text-sm">{{ json_encode($rule, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>

                <!-- Remarks Field -->
                <div class="mt-4">
                    <flux:input
                        wire:model="remarks"
                        label="Remarks"
                        description="Please provide a reason for modifying the calculation rule."
                        placeholder="e.g., Updated formula for overtime calculation, Added new component to calculation, etc."
                    />
                </div>

                <div class="flex justify-end gap-4 mt-6">
                    <flux:button wire:click="$dispatch('close-modal', { id: 'calculation-rule-modal' })">Cancel</flux:button>
                    <flux:button wire:click="saveRule">Save Rule</flux:button>
                </div>
            </div>
        </flux:modal>

        <!-- Assign New Components Modal -->
        <flux:modal name="assign-components-modal" class="max-w-lg">
            <div class="p-6 space-y-6">
                <flux:heading size="lg">Assign New Components</flux:heading>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Select Components to Assign</label>
                    <flux:select
                        variant="listbox"
                        wire:model="assignComponentIds"
                        multiple
                        searchable
                        placeholder="Select components to assign"
                        class="w-full"
                    >
                        @foreach($this->availableComponentsForAssignment as $availablecomponents)
                            <flux:select.option value="{{ $availablecomponents['id'] }}">{{ $availablecomponents['title'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Effective From</label>
                        <flux:date-picker wire:model.live="assignEffectiveFrom" placeholder="Effective From" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Effective To (Optional)</label>
                        <flux:date-picker wire:model.live="assignEffectiveTo" placeholder="Effective To (Optional)" />
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <flux:button wire:click="$dispatch('close-modal', { id: 'assign-components-modal' })">Cancel</flux:button>
                    <flux:button
                            variant="primary"
                            wire:click="assignNewComponents"
                            :disabled="empty($assignComponentIds)"
                    >
                        Assign
                    </flux:button>
                </div>
            </div>
        </flux:modal>

        <!-- Bulk Increment/Decrement Modal -->
        <flux:modal name="bulk-increment-modal" class="md:w-[40rem]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Bulk Increment/Decrement</flux:heading>
                    <flux:text class="mt-2 text-lg">
                        Apply increment or decrement to the selected component for all selected employees.
                    </flux:text>
                </div>
                <!-- Date Range Fields -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                        <flux:date-picker wire:model.live="bulk_start_date" placeholder="Start Date" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Date (Optional)</label>
                        <flux:date-picker wire:model.live="bulk_end_date" placeholder="End Date (Optional)" />
                    </div>
                </div>
                <!-- Controls -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <flux:select 
                            wire:model.live="bulk_incrementType"
                            label="Modification Type"
                        >
                            <flux:select.option value="fixed_amount">Fixed Amount</flux:select.option>
                            <flux:select.option value="percentage">Percentage</flux:select.option>
                            <flux:select.option value="new_amount">New Fixed Amount</flux:select.option>
                        </flux:select>
                    </div>
                    <div>
                        <flux:select 
                            wire:model.live="bulk_operation"
                            label="Operation"
                        >
                            <flux:select.option value="increase">Increase</flux:select.option>
                            <flux:select.option value="decrease">Decrease</flux:select.option>
                        </flux:select>
                    </div>
                    <div class="col-span-2">
                        <flux:input
                            type="number"
                            wire:model.live="bulk_modificationValue"
                            :label="$bulk_incrementType === 'percentage' ? 'Enter Percentage (%)' : ($bulk_incrementType === 'new_amount' ? 'Enter New Amount (₹)' : 'Enter Amount (₹)')"
                            :placeholder="$bulk_incrementType === 'percentage' ? 'Enter percentage (0-100)' : ($bulk_incrementType === 'new_amount' ? 'Enter new fixed amount' : 'Enter amount to add/subtract')"
                            :max="$bulk_incrementType === 'percentage' ? 100 : null"
                            min="0"
                        />
                    </div>
                    <div class="col-span-2 mt-4">
                        <flux:input
                            wire:model="bulk_remarks"
                            label="Remarks"
                            description="Please provide a reason for this bulk salary modification."
                            placeholder="e.g., Annual increment, Performance bonus, etc."
                        />
                    </div>
                </div>
                <div class="flex gap-2">
                    <flux:spacer/>
                    <flux:button wire:click="$dispatch('close-modal', { id: 'bulk-increment-modal' })">Cancel</flux:button>
                    <flux:button variant="primary" :disabled="!$bulk_modificationValue || $bulk_modificationValue <= 0" wire:click="saveBulkModification">Apply Changes</flux:button>
                </div>
            </div>
        </flux:modal>

        <!-- Bulk Calculation Rule Modal -->
        <flux:modal name="bulk-calculation-rule-modal" class="max-w-lg">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-semibold">Bulk Calculation Rule Builder</h3>
                </div>
                <!-- Date Range Fields -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                        <flux:date-picker selectable-header wire:model.live="bulk_start_date" placeholder="Start Date" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Date (Optional)</label>
                        <flux:date-picker selectable-header wire:model.live="bulk_end_date" placeholder="End Date (Optional)" />
                    </div>
                </div>
                <div class="grid gap-6">
                    <div class="space-y-6 border rounded-lg p-6 bg-white">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Root Type</label>
                            <flux:select wire:model.live="bulk_rule.type">
                                <flux:select.option value="conditional">Conditional</flux:select.option>
                                <flux:select.option value="operation">Operation (+, -, ×, ÷)</flux:select.option>
                                <flux:select.option value="component">Salary Component</flux:select.option>
                                <flux:select.option value="constant">Fixed Value</flux:select.option>
                            </flux:select>
                        </div>
                        @if($bulk_rule['type'] === 'operation')
                            <div class="space-y-4">
                                <!-- Operator Selection -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Operator</label>
                                    <flux:select wire:model.live="bulk_rule.operator">
                                        <flux:select.option value="+">Add (+)</flux:select.option>
                                        <flux:select.option value="-">Subtract (-)</flux:select.option>
                                        <flux:select.option value="*">Multiply (×)</flux:select.option>
                                        <flux:select.option value="/">Divide (÷)</flux:select.option>
                                    </flux:select>
                                </div>
                                <!-- Operands -->
                                <div>
                                    <div class="flex justify-between items-center mb-4">
                                        <label class="block text-sm font-medium text-gray-700">Operands</label>
                                        <flux:button size="sm" wire:click="addOperand" class="text-sm">
                                            Add Operand
                                        </flux:button>
                                    </div>
                                    <div class="space-y-4">
                                        @foreach($bulk_rule['operands'] ?? [] as $i => $operand)
                                            <div class="p-4 border rounded-lg bg-gray-50">
                                                <div class="flex items-center gap-4 mb-4">
                                                    <div class="flex-1">
                                                        <flux:select wire:model.live="bulk_rule.operands.{{ $i }}.type">
                                                            <flux:select.option value="component">Salary Component</flux:select.option>
                                                            <flux:select.option value="constant">Fixed Value</flux:select.option>
                                                        </flux:select>
                                                    </div>
                                                    <flux:button variant="danger" size="sm" wire:click="removeOperand({{ $i }})" class="text-sm">
                                                        Remove
                                                    </flux:button>
                                                </div>
                                                @if($operand['type'] === 'component')
                                                    <div class="ml-4">
                                                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Component</label>
                                                        <flux:select wire:model.live="bulk_rule.operands.{{ $i }}.key">
                                                            @foreach($this->salaryComponentsList as $id => $salaryitem)
                                                                <flux:select.option value="{{ $id }}">{{ $salaryitem['title'] }}</flux:select.option>
                                                            @endforeach
                                                        </flux:select>
                                                    </div>
                                                @else
                                                    <div class="ml-4">
                                                        <label class="block text-sm font-medium text-gray-700 mb-2">Enter Value</label>
                                                        <flux:input 
                                                            type="number" 
                                                            step="0.01" 
                                                            wire:model.live="bulk_rule.operands.{{ $i }}.value" 
                                                            placeholder="Enter value" 
                                                        />
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @elseif($bulk_rule['type'] === 'component')
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Select Component</label>
                                <flux:select wire:model.live="bulk_rule.key">
                                    @foreach($this->salaryComponentsList as $id => $salaryitem)
                                        <flux:select.option value="{{ $id }}">{{ $salaryitem['title'] }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>
                        @elseif($bulk_rule['type'] === 'constant')
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Enter Value</label>
                                <flux:input type="number" step="0.01" wire:model.live="bulk_rule.value" placeholder="Enter value" />
                            </div>
                        @endif
                    </div>
                    <div class="border rounded-lg p-6 bg-gray-50">
                        <flux:heading class="mb-4">Live JSON Preview</flux:heading>
                        <pre class="bg-white p-4 rounded-lg overflow-auto max-h-[600px] text-sm">{{ json_encode($bulk_rule, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>
                <div class="mt-4">
                    <flux:input
                        wire:model="bulk_remarks"
                        label="Remarks"
                        description="Please provide a reason for modifying the calculation rule."
                        placeholder="e.g., Updated formula for overtime calculation, Added new component to calculation, etc."
                    />
                </div>
                <div class="flex justify-end gap-4 mt-6">
                    <flux:button wire:click="$dispatch('close-modal', { id: 'bulk-calculation-rule-modal' })">Cancel</flux:button>
                    <flux:button variant="primary" wire:click="saveBulkRule">Save Rule</flux:button>
                </div>
            </div>
        </flux:modal>
    </div>
</div>
