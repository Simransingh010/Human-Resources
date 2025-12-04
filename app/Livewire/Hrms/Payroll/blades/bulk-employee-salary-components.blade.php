<div wire:init="loadData">
    <!-- Loading Skeleton -->
    @if(!$readyToLoad)
        <div class="fixed inset-0 bg-white z-50 flex items-center justify-center">
            <div class="text-center">
                <div class="inline-block animate-spin rounded-full h-16 w-16 border-b-4 border-blue-600"></div>
                <p class="mt-4 text-lg font-medium text-gray-700">Loading salary components...</p>
                <p class="mt-2 text-sm text-gray-500">Please wait</p>
            </div>
        </div>
    @endif
    
    <div wire:loading.flex wire:target="list,applyFilters,clearFilters" class="fixed inset-0 bg-white/80 z-50 items-center justify-center">
        <div class="text-center">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-gray-900"></div>
            <p class="mt-4 text-gray-600">Loading...</p>
        </div>
    </div>

    <div class="flex justify-between items-center mb-4">
        <div>
            <flux:heading size="lg">Bulk Employee Salary Components</flux:heading>
            <flux:subheading>Manage salary components for multiple employees</flux:subheading>
        </div>
    </div>

    <!-- Filters -->
    <flux:card>
        <flux:heading>Filters</flux:heading>
        <div class="flex flex-wrap gap-4">
            <!-- Explicit Employee search field -->
            <div class="w-1/4">
                <flux:input
                    placeholder="Search Employee"
                    wire:model.live.debounce.500ms="filters.employee_search"
                />
            </div>
            @foreach($filterFields as $field => $cfg)
                @if($field !== 'employee_search' && in_array($field, $visibleFilterFields))
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

            <div class="flex items-center gap-2">
                <flux:select wire:model.live="perPage" placeholder="Per page">
                    <flux:select.option value="5">5 per page</flux:select.option>
                    <flux:select.option value="10">10 per page</flux:select.option>
                    <flux:select.option value="20">20 per page</flux:select.option>
                    <flux:select.option value="50">50 per page</flux:select.option>
                </flux:select>
            </div>
            
            <flux:button.group>
                <flux:button variant="outline" wire:click="clearFilters" tooltip="Clear Filters" icon="x-circle"></flux:button>
                <flux:modal.trigger name="mdl-show-hide-filters">
                    <flux:button variant="outline" tooltip="Set Filters" icon="bars-3"></flux:button>
                </flux:modal.trigger>
                <flux:modal.trigger name="mdl-show-hide-columns">
                    <flux:button variant="outline" tooltip="Set Columns" icon="table-cells"></flux:button>
                </flux:modal.trigger>
                <flux:button
                    variant="primary"
                    wire:click="syncAllCalculations"
                    wire:loading.attr="disabled"
                    wire:target="syncAllCalculations"
                >
                    <span wire:loading.remove wire:target="syncAllCalculations">
                        Bulk Sync
                    </span>
                    <span wire:loading wire:target="syncAllCalculations">
                        Syncing...
                    </span>
                </flux:button>
            </flux:button.group>
        </div>
    </flux:card>

    <!-- Matrix Table with Flux -->
    <flux:table :paginate="$this->list">
        <flux:table.columns sticky class="bg-white dark:bg-zinc-900">
            <flux:table.column sticky class="bg-white dark:bg-zinc-900">Employee</flux:table.column>
            <flux:table.column sticky class="bg-white dark:bg-zinc-900">Actions</flux:table.column>
            @foreach($components as $componentData)
                <flux:table.column align="center" class="min-w-[120px]">
                    {{ $componentData['title'] }}
                </flux:table.column>
            @endforeach
        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->list as $record)
                <flux:table.row :key="$record->employee_id">
                    <flux:table.cell sticky class="bg-white dark:bg-zinc-900">
                        <div class="text-sm font-medium">{{ $record->fname }} {{ $record->lname }}</div>
                        <div class="text-xs text-gray-500">{{ $record->employee_code }}</div>
                    </flux:table.cell>
                    
                    <flux:table.cell sticky class="bg-white dark:bg-zinc-900">
                        <div class="flex gap-1">
                            @if($this->hasCalculatedComponents($record->employee_id))
                                <flux:button
                                    size="sm"
                                    wire:click="syncCalculations({{ $record->employee_id }})" 
                                    wire:loading.attr="disabled"
                                >
                                    Sync
                                </flux:button>
                            @endif
                            <flux:button
                                size="sm"
                                wire:click="showSalarySlip({{ $record->employee_id }})"
                            >
                                View
                            </flux:button>
                            <flux:button 
                                size="sm" 
                                variant="primary"
                                wire:click="openConfigModal({{ $record->employee_id }})"
                            >
                                Config
                            </flux:button>
                        </div>
                    </flux:table.cell>
                    
                    @foreach($components as $componentData)
                        @php
                            $componentId = $componentData['id'];
                            $employeeComponents = $this->employeeComponents[$record->employee_id] ?? [];
                            $matchingComponent = null;
                            foreach ($employeeComponents as $ec) {
                                if ($ec['component_id'] == $componentId) {
                                    $matchingComponent = $ec;
                                    break;
                                }
                            }
                        @endphp
                        <flux:table.cell align="center">
                            @if($matchingComponent)
                                <flux:input
                                    type="number"
                                    step="0.01"
                                    size="sm"
                                    class="w-24"
                                    wire:model.defer="bulkupdate.{{ $record->employee_id }}.{{ $componentId }}"
                                    wire:change="updateComponentAmount({{ $record->employee_id }}, {{ $componentId }})"
                                />
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </flux:table.cell>
                    @endforeach
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <!-- Single Config Modal -->
    <flux:modal name="mdl-salary-component-config" class="max-w-7xl" x-on:open-config-modal.window="$flux.modal('mdl-salary-component-config').show()">
        @if($selectedEmployeeForConfig)
            <div wire:key="config-modal-{{ $selectedEmployeeForConfig }}">
                @livewire('hrms.payroll.salary-component-employees', ['employeeId' => $selectedEmployeeForConfig], key('salary-component-config-'.$selectedEmployeeForConfig))
            </div>
        @endif
    </flux:modal>
    
    <!-- Performance Monitor (Remove in production) -->
    @if(config('app.debug'))
      
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const updateStats = () => {
                    document.getElementById('dom-count').textContent = document.getElementsByTagName('*').length;
                    document.getElementById('load-time').textContent = Math.round(performance.now());
                };
                updateStats();
                document.addEventListener('livewire:navigated', updateStats);
                Livewire.hook('morph.updated', updateStats);
            });
        </script>
    @endif

    <!-- Salary Slip Modal - Lazy Loaded with Flux -->
    @if($showSalarySlipModal)
        <flux:modal wire:model.self="showSalarySlipModal" class="max-w-3xl" wire:key="salary-slip-modal">
            @if($selectedEmployee)
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">Salary Slip</flux:heading>
                    </div>

                    <!-- Employee Details -->
                    <div class="pb-4 border-b grid grid-cols-2 gap-4">
                        <div>
                            <p><strong>Name:</strong> {{ $selectedEmployee->fname }} {{ $selectedEmployee->lname }}</p>
                            <p><strong>Code:</strong> {{ $selectedEmployee->id }}</p>
                        </div>
                        <div>
                            <p><strong>Department:</strong> {{ optional($selectedEmployee->emp_job_profile)->department?->title }}</p>
                            <p><strong>Designation:</strong> {{ optional($selectedEmployee->emp_job_profile)->designation?->title }}</p>
                        </div>
                    </div>

                    <!-- Salary Components -->
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <flux:subheading>Earnings</flux:subheading>
                            @foreach($salaryComponents as $item)
                                @if($item['nature'] === 'earning')
                                    <div class="flex justify-between py-1">
                                        <span>{{ $item['title'] }}</span>
                                        <span>{{ number_format($item['amount'], 2) }}</span>
                                    </div>
                                @endif
                            @endforeach
                            <div class="border-t pt-2 font-bold flex justify-between">
                                <span>Total</span>
                                <span>{{ number_format($totalEarnings, 2) }}</span>
                            </div>
                        </div>

                        <div>
                            <flux:subheading>Deductions</flux:subheading>
                            @foreach($salaryComponents as $item)
                                @if($item['nature'] === 'deduction')
                                    <div class="flex justify-between py-1">
                                        <span>{{ $item['title'] }}</span>
                                        <span>{{ number_format($item['amount'], 2) }}</span>
                                    </div>
                                @endif
                            @endforeach
                            <div class="border-t pt-2 font-bold flex justify-between">
                                <span>Total</span>
                                <span>{{ number_format($totalDeductions, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Net Salary -->
                    <div class="pt-4 border-t">
                        <div class="flex justify-between font-bold text-lg">
                            <span>Net Salary</span>
                            <span>{{ number_format($netSalary, 2) }}</span>
                        </div>
                    </div>

                    <div class="flex">
                        <flux:spacer />
                        <flux:button wire:click="closeSalarySlipModal">Close</flux:button>
                    </div>
                </div>
            @endif
        </flux:modal>
    @endif
</div>