<div>
    <!-- Loading Overlay for Entire Page -->
    @if($isLoading)
        <div class="fixed inset-0 bg-white dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 z-50 flex items-center justify-center">
            <div class="text-center">
                <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-600 mx-auto mb-4"></div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Loading Page</h3>
                <p class="text-gray-600 dark:text-gray-400">Please wait while we load the data...</p>
            </div>
        </div>
    @endif

    <!-- Header Section -->
    <div class="flex justify-between items-center mb-4">
        <div>
            <flux:heading size="lg">Bulk Employee Salary Components</flux:heading>
            <flux:subheading>Manage salary components for multiple employees</flux:subheading>
        </div>
        <div class="flex gap-2">
            <flux:button
                wire:click="refresh"
                wire:loading.attr="disabled"
                wire:target="refresh"
                variant="outline"
                :disabled="$isLoading"
            >
                <span wire:loading.remove wire:target="refresh">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Refresh
                </span>
                <span wire:loading wire:target="refresh">
                    <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Refreshing...
                </span>
            </flux:button>
            <flux:button
                wire:click="syncAllCalculations"
                wire:loading.attr="disabled"
                wire:target="syncAllCalculations"
                :disabled="$isLoading"
            >
                <span wire:loading.remove wire:target="syncAllCalculations">
                    Sync All Calculations
                </span>
                <span wire:loading wire:target="syncAllCalculations">
                    <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Syncing...
                </span>
            </flux:button>
            <flux:modal.trigger name="mdl-performance-optimization">
                <flux:button variant="outline" size="sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    Performance
                </flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    <!-- Filters Section -->
    <flux:card>
        <flux:heading>Filters</flux:heading>
        
        @if($isLoadingFilters)
            <!-- Skeleton for Filters -->
            <div class="flex flex-wrap gap-4">
                @for($i = 0; $i < 4; $i++)
                    <div class="w-1/4">
                        <div class="h-10 bg-gray-200 dark:bg-gray-700 rounded-md animate-pulse"></div>
                    </div>
                @endfor
                <div class="flex gap-2">
                    @for($i = 0; $i < 3; $i++)
                        <div class="h-10 w-10 bg-gray-200 dark:bg-gray-700 rounded-md animate-pulse"></div>
                    @endfor
                </div>
            </div>
            <div class="mt-2 text-sm text-gray-500 dark:text-gray-400 animate-pulse">
                Loading filter options...
            </div>
        @else
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
                                        wire:model.live="filters.{{ $field }}"
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
        @endif
    </flux:card>

    <!-- Matrix Table Section -->
    @if($isLoading || $isLoadingComponents)
        <!-- Skeleton for Table Header -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mt-4">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider sticky left-0 z-10">
                                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider sticky left-[200px] z-10">
                                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                            </th>
                            @for($i = 0; $i < 5; $i++)
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider min-w-[120px]">
                                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                                </th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @for($row = 0; $row < 5; $row++)
                            <tr>
                                <td class="px-4 py-4 whitespace-nowrap sticky left-0 bg-white dark:bg-gray-800 z-10">
                                    <div class="space-y-2">
                                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse w-3/4"></div>
                                        <div class="space-y-1">
                                            @for($i = 0; $i < 5; $i++)
                                                <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded animate-pulse w-1/2"></div>
                                            @endfor
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap sticky left-[200px] bg-white dark:bg-gray-800 z-10">
                                    <div class="flex gap-2">
                                        <div class="h-8 w-16 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                                        <div class="h-8 w-20 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                                        <div class="h-8 w-24 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                                    </div>
                                </td>
                                @for($col = 0; $col < 5; $col++)
                                    <td class="px-4 py-4 text-center">
                                        <div class="h-8 w-20 bg-gray-200 dark:bg-gray-700 rounded animate-pulse mx-auto"></div>
                                    </td>
                                @endfor
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
            <div class="p-4 text-center">
                <div class="text-sm text-gray-500 dark:text-gray-400 animate-pulse">
                    Loading salary components and employee data...
                </div>
            </div>
        </div>
    @else
        <!-- Actual Table -->
        <flux:table class="w-full">
            <flux:table.columns>
                <flux:table.column class="sticky left-0 z-10">Employee</flux:table.column>
                <flux:table.column class="sticky left-[200px] z-10">Actions</flux:table.column>
                @foreach($components as $componentData)
                    <flux:table.column class="table-cell-wrap text-center min-w-[120px]">
                        {{ $componentData['title'] }}
                    </flux:table.column>
                @endforeach
            </flux:table.columns>

            <flux:table.rows>
                @if($isLoadingEmployees)
                    <!-- Skeleton for Table Rows -->
                    @for($row = 0; $row < 5; $row++)
                        <flux:table.row>
                            <flux:table.cell class="table-cell-wrap sticky left-0 bg-white z-10">
                                <div class="space-y-2">
                                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse w-3/4"></div>
                                    <div class="space-y-1">
                                        @for($i = 0; $i < 5; $i++)
                                            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded animate-pulse w-1/2"></div>
                                        @endfor
                                    </div>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell class="table-cell-wrap sticky left-[200px] bg-white z-10">
                                <div class="flex gap-2">
                                    <div class="h-8 w-16 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                                    <div class="h-8 w-20 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                                    <div class="h-8 w-24 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                                </div>
                            </flux:table.cell>
                            @foreach($components as $componentData)
                                <flux:table.cell class="text-center">
                                    <div class="h-8 w-20 bg-gray-200 dark:bg-gray-700 rounded animate-pulse mx-auto"></div>
                                </flux:table.cell>
                            @endforeach
                        </flux:table.row>
                    @endfor
                    <div class="p-4 text-center">
                        <div class="text-sm text-gray-500 dark:text-gray-400 animate-pulse">
                            Loading employee data...
                        </div>
                    </div>
                @else
                    @foreach($this->list as $record)
                        <flux:table.row :key="$record->employee_id">
                            <flux:table.cell class="table-cell-wrap sticky left-0 bg-white z-10">
                                <div class="space-y-1">
                                    <div class="font-medium">
                                        {{ $record->fname }} {{ $record->mname ? $record->mname . ' ' : '' }}{{ $record->lname }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <div>Employee Code: {{ $record->employee_code }}</div>
                                        <div>Email: {{ $record->email }}</div>
                                        <div>Phone: {{ $record->phone }}</div>
                                        <div>Department: {{ $record->department_title }}</div>
                                        <div>Designation: {{ $record->designation_title }}</div>
                                    </div>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell class="table-cell-wrap sticky left-[200px] bg-white z-10">
                                @if($this->hasCalculatedComponents($record->employee_id))
                                    <flux:button
                                        size="sm"
                                        wire:click="syncCalculations({{ $record->employee_id }})" 
                                        wire:loading.attr="disabled"
                                        wire:target="syncCalculations({{ $record->employee_id }})"
                                    >
                                        <span wire:loading.remove wire:target="syncCalculations({{ $record->employee_id }})">
                                            Sync
                                        </span>
                                        <span wire:loading wire:target="syncCalculations({{ $record->employee_id }})">
                                            ...
                                        </span>
                                    </flux:button>
                                @endif
                                <flux:button
                                        size="sm"
                                        wire:click="showSalarySlip({{ $record->employee_id }})"
                                >
                                    View Slip
                                </flux:button>
                                <flux:modal.trigger name="mdl-salary-component-employees-{{ $record->employee_id }}">
                                    <flux:button class="p-1" variant="primary" size="sm">
                                        Configure Heads
                                    </flux:button>
                                </flux:modal.trigger>

                                <flux:modal name="mdl-salary-component-employees-{{ $record->employee_id }}" class="max-w-7xl">
                                    @livewire('hrms.payroll.salary-component-employees', ['employeeId' => $record->employee_id], key('salary-component-employees-'.$record->employee_id))
                                </flux:modal>
                            </flux:table.cell>
                            @foreach($components as $componentData)
                                @php
                                    $componentId = $componentData['id'];
                                    $employeeComponents = $this->employeeComponents[$record->employee_id] ?? [];
                                    $matchingComponent = collect($employeeComponents)
                                        ->firstWhere('component_id', $componentId);
                                @endphp
                                <flux:table.cell class="text-center">
                                    @if($matchingComponent)
                                        <div class="flex items-center justify-center space-x-2">
                                            <flux:input
                                                type="number"
                                                step="0.01"
                                                size="sm"
                                                class="w-24"
                                                wire:model.defer="bulkupdate.{{ $record->employee_id }}.{{ $componentId }}"
                                                wire:change="updateComponentAmount({{ $record->employee_id }}, {{ $componentId }})"
                                            />

                                        </div>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </flux:table.cell>
                            @endforeach
                        </flux:table.row>
                    @endforeach
                @endif
            </flux:table.rows>
        </flux:table>
    @endif

    <!-- Pagination Links -->
    <div class="mt-4">
        @if($isLoading || $isLoadingEmployees)
            <!-- Skeleton for Pagination -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="h-8 w-20 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                    <div class="h-8 w-32 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                </div>
                <div class="flex items-center space-x-2">
                    @for($i = 0; $i < 5; $i++)
                        <div class="h-8 w-8 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                    @endfor
                </div>
            </div>
        @else
            {{ $this->list->links() }}
        @endif
    </div>

    <!-- Salary Slip Modal -->
    <flux:modal wire:model="showSalarySlipModal" class="max-w-3xl">
        <div class="p-4">
            <h2 class="text-xl font-bold mb-4">Salary Slip</h2>
            
            @if($selectedEmployee)
                <!-- Employee Details -->
                <div class="mb-6 border-b pb-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p><strong>Employee Name:</strong> {{ $selectedEmployee->fname }} {{ $selectedEmployee->lname }}</p>
                            <p><strong>Employee Code:</strong> {{ $selectedEmployee->id }}</p>
                            <p><strong>Department:</strong> {{ optional($selectedEmployee->emp_job_profile)->department?->title }}</p>
                        </div>
                        <div>
                            <p><strong>Designation:</strong> {{ optional($selectedEmployee->emp_job_profile)->designation?->title }}</p>
                            <p><strong>Bank Account:</strong> {{ $selectedEmployee->bank_account->bankaccount ?? 'N/A' }}</p>
                            <p><strong>PAN:</strong> {{ $selectedEmployee->pan ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Salary Components -->
                <div class="grid grid-cols-2 gap-8">
                    <!-- Earnings -->
                    <div>
                        <h3 class="font-bold mb-3">Earnings</h3>
                        <div class="space-y-2">
                            @foreach($salaryComponents as $component)
                                @if($component['nature'] === 'earning')
                                    <div class="flex justify-between">
                                        <span>{{ $component['title'] }}</span>
                                        <span>{{ number_format($component['amount'], 2) }}</span>
                                    </div>
                                @endif
                            @endforeach
                            <div class="border-t pt-2 font-bold">
                                <div class="flex justify-between">
                                    <span>Total Earnings</span>
                                    <span>{{ number_format($totalEarnings, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Deductions -->
                    <div>
                        <h3 class="font-bold mb-3">Deductions</h3>
                        <div class="space-y-2">
                            @foreach($salaryComponents as $component)
                                @if($component['nature'] === 'deduction')
                                    <div class="flex justify-between">
                                        <span>{{ $component['title'] }}</span>
                                        <span>{{ number_format($component['amount'], 2) }}</span>
                                    </div>
                                @endif
                            @endforeach
                            <div class="border-t pt-2 font-bold">
                                <div class="flex justify-between">
                                    <span>Total Deductions</span>
                                    <span>{{ number_format($totalDeductions, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Net Salary -->
                <div class="mt-6 border-t pt-4">
                    <div class="flex justify-between font-bold text-lg">
                        <span>Net Salary</span>
                        <span>{{ number_format($netSalary, 2) }}</span>
                    </div>
                    @if($netSalaryInWords)
                        <p class="text-sm mt-2">Amount in words: {{ $netSalaryInWords }}</p>
                    @endif
                </div>

                <!-- Action Buttons -->
                <div class="mt-6 flex justify-end space-x-3">
                    <flux:button wire:click="closeSalarySlipModal">Close</flux:button>
{{--                    <flux:button wire:click="downloadSalarySlip">Download PDF</flux:button>--}}
                </div>
            @endif
        </div>
    </flux:modal>

    <!-- Performance Optimization Modal -->
    <flux:modal name="mdl-performance-optimization" class="max-w-4xl">
        <div class="p-6">
            <h2 class="text-xl font-bold mb-4">Performance Optimization</h2>
            
            <div class="space-y-6">
                <!-- Database Indexes Section -->
                <div>
                    <h3 class="text-lg font-semibold mb-3">Suggested Database Indexes</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Run these SQL commands in your database to improve query performance significantly:
                    </p>
                    
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 max-h-96 overflow-y-auto">
                        @foreach($this->getSuggestedIndexes() as $index)
                            <div class="font-mono text-sm mb-2 p-2 bg-white dark:bg-gray-700 rounded border">
                                {{ $index }};
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <p class="text-sm text-blue-800 dark:text-blue-200">
                            <strong>Note:</strong> These indexes will significantly improve query performance. 
                            Run them during low-traffic periods and monitor the results.
                        </p>
                    </div>
                </div>

                <!-- Performance Tips -->
                <div>
                    <h3 class="text-lg font-semibold mb-3">Performance Tips</h3>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li class="flex items-start">
                            <span class="text-green-500 mr-2">✓</span>
                            Query results are now cached for 5 minutes to reduce database load
                        </li>
                        <li class="flex items-start">
                            <span class="text-green-500 mr-2">✓</span>
                            Unnecessary GROUP BY clause has been removed
                        </li>
                        <li class="flex items-start">
                            <span class="text-green-500 mr-2">✓</span>
                            Optimized JOINs with proper indexing hints
                        </li>
                        <li class="flex items-start">
                            <span class="text-green-500 mr-2">✓</span>
                            Reduced N+1 queries with eager loading
                        </li>
                        <li class="flex items-start">
                            <span class="text-green-500 mr-2">✓</span>
                            Added DISTINCT to prevent duplicate rows
                        </li>
                    </ul>
                </div>

                <!-- Cache Management -->
                <div>
                    <h3 class="text-lg font-semibold mb-3">Cache Management</h3>
                    <div class="flex gap-2">
                        <flux:button
                            wire:click="clearCaches"
                            variant="outline"
                            size="sm"
                        >
                            Clear All Caches
                        </flux:button>
                        <flux:button
                            wire:click="refresh"
                            variant="outline"
                            size="sm"
                        >
                            Refresh Data
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    </flux:modal>
</div>