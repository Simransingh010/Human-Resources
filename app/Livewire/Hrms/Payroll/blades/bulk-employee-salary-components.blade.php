<div wire:init="load">
    <div class="flex justify-between items-center mb-4">
        <div>
            <flux:heading size="lg">Bulk Employee Salary Components</flux:heading>
            <flux:subheading>Manage salary components for multiple employees</flux:subheading>
        </div>
        <div>
            <flux:button
                wire:click="syncAllCalculations"
                wire:loading.attr="disabled"
                wire:target="syncAllCalculations"
            >
                <span wire:loading.remove wire:target="syncAllCalculations">
                    Sync All Calculations
                </span>
                <span wire:loading wire:target="syncAllCalculations">
                    Syncing...
                </span>
            </flux:button>
        </div>
    </div>

    <!-- Filters -->
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

                                    placeholder="{{ $cfg['label'] }}"
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
    </flux:card>

    @if($readyToLoad)
        <!-- Matrix Table -->
        <flux:table class="w-full">
            <flux:table.columns>
                <flux:table.column class="sticky left-0 z-10">Employee</flux:table.column>
                <flux:table.column class="sticky left-[200px] z-10">Actions</flux:table.column>
                @foreach($this->Salarycomponents as $cmpnts)
                    {{dd($this->Salarycomponents)}}
                    <flux:table.column class="table-cell-wrap text-center min-w-[120px]">
                        @if(is_array($cmpnts))
                            {{ $cmpnts['title'] }}
                        @elseif(is_object($cmpnts))
                            {{ $cmpnts->title }}
                        @else
                            {{ $cmpnts }}
                        @endif
                    </flux:table.column>
                @endforeach
            </flux:table.columns>

            <flux:table.rows>
                @foreach($this->list as $record)
                    <flux:table.row :key="$record['employee_id']">
                        <flux:table.cell class="table-cell-wrap sticky left-0 bg-white z-10">
                            <div class="space-y-1">
                                <div class="font-medium">
                                    {{ $record['fname'] }}{{ $record['lname'] }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    <div>Employee Code: {{ $record['employee_code'] }}</div>
                                    <div>Email: {{ $record['email'] }}</div>
                                    <div>Phone: {{ $record['phone'] }}</div>
                                    <div>Department: {{ $record['department_title'] }}</div>
                                    <div>Designation: {{ $record['designation_title'] }}</div>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell class="table-cell-wrap sticky left-[200px] bg-white z-10">
                            @if($this->hasCalculatedComponents($record['employee_id']))
                                <flux:button
                                    size="sm"
                                    wire:click="syncCalculations({{ $record['employee_id'] }})" 
                                    wire:loading.attr="disabled"
                                    wire:target="syncCalculations({{ $record['employee_id'] }})"
                                >
                                    <span wire:loading.remove wire:target="syncCalculations({{ $record['employee_id'] }})">
                                        Sync
                                    </span>
                                    <span wire:loading wire:target="syncCalculations({{ $record['employee_id'] }})">
                                        ...
                                    </span>
                                </flux:button>
                            @endif
                            <flux:button
                                    size="sm"
                                    wire:click="showSalarySlip({{ $record['employee_id'] }})"
                            >
                                View Slip
                            </flux:button>
                            <flux:modal.trigger name="mdl-salary-component-employees-{{ $record['employee_id'] }}">
                                <flux:button class="p-1" variant="primary" size="sm">
                                    Configure Heads
                                </flux:button>
                            </flux:modal.trigger>

                            <flux:modal name="mdl-salary-component-employees-{{ $record['employee_id'] }}" class="max-w-7xl">
                                @livewire('hrms.payroll.salary-component-employees', ['employeeId' => $record['employee_id']], key('salary-component-employees-'.$record['employee_id']))
                            </flux:modal>
                        </flux:table.cell>
                        @foreach($this->components as $cmpnts)
                            <flux:table.cell class="text-center">
                                <flux:input
                                    type="number"
                                    value="{{ $record['component_amounts'][$cmpnts['id']] ?? 0 }}"
                                    wire:change="updateComponentAmount({{ $record['employee_id'] }}, {{ $cmpnts['id'] }}, $event.target.value)"
                                    class="w-24"
                                />
                            </flux:table.cell>
                        @endforeach
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <!-- Pagination Links -->
        <div class="mt-4">
            {{ $this->list->links() }}
        </div>
    @else
        <div wire:loading.flex class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 backdrop-blur">
            <flux:icon.loading class="w-10 h-10  animate-spin" />
        </div>
    @endif

    <!-- Salary Slip Modal -->
    <flux:modal wire:model="showSalarySlipModal" class="max-w-3xl">
        <div class="p-4">
            <h2 class="text-xl font-bold mb-4">Salary Slip</h2>
            
            @if($selectedEmployee)
                <!-- Employee Details -->
                <div class="mb-6 border-b pb-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p><strong>Employee Name:</strong> {{ $selectedEmployee['fname'] }} {{ $selectedEmployee['lname'] }}</p>
                            <p><strong>Employee Code:</strong> {{ $selectedEmployee['id'] }}</p>
                            <p><strong>Department:</strong> {{ optional($selectedEmployee['emp_job_profile'])->department?->title }}</p>
                        </div>
                        <div>
                            <p><strong>Designation:</strong> {{ optional($selectedEmployee['emp_job_profile'])->designation?->title }}</p>
                            <p><strong>Bank Account:</strong> {{ $selectedEmployee['bank_account']['bankaccount'] ?? 'N/A' }}</p>
                            <p><strong>PAN:</strong> {{ $selectedEmployee['pan'] ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Debug Info -->
                <div class="mb-4 p-3 bg-gray-100 rounded text-sm">
                    <p><strong>Debug Info:</strong></p>
                    <p>Total Components: {{ count($cmpnts) }}</p>
                    <p>Total Earnings: {{ $totalEarnings }}</p>
                    <p>Total Deductions: {{ $totalDeductions }}</p>
                    <p>Net Salary: {{ $netSalary }}</p>
                </div>

                <!-- Salary Components -->
                <div class="grid grid-cols-2 gap-8">
                    <!-- Earnings -->
                    <div>
                        <h3 class="font-bold mb-3">Earnings</h3>
                        <div class="space-y-2">
                            @php
                                $earningCount = 0;
                            @endphp
                            @foreach($cmpnts as $component)
                                @if(is_array($component) && isset($component['nature']) && $component['nature'] === 'earning')
                                    @php $earningCount++; @endphp
                                    <div class="flex justify-between">
                                        <span class="pr-3">
                                            {{ $component['title'] }}
                                            @if(isset($component['effective_from']) && \Carbon\Carbon::parse($component['effective_from'])->isFuture())
                                                <span class="block text-xs text-blue-500">
                                                    w.e.f = {{ \Carbon\Carbon::parse($component['effective_from'])->format('d M Y') }}
                                                </span>
                                            @endif
                                        </span>
                                        <span>{{ number_format($component['amount'], 2) }}</span>
                                    </div>
                                @endif
                            @endforeach
                            <div class="border-t pt-2 font-bold">
                                <div class="flex justify-between">
                                    <span>Total Earnings ({{ $earningCount }} items)</span>
                                    <span>{{ number_format($totalEarnings, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Deductions -->
                    <div>
                        <h3 class="font-bold mb-3">Deductions</h3>
                        <div class="space-y-2">
                            @php
                                $deductionCount = 0;
                            @endphp
                            @foreach($cmpnts as $component)
                                @if(is_array($component) && isset($component['nature']) && $component['nature'] === 'deduction')
                                    @php $deductionCount++; @endphp
                                    <div class="flex justify-between">
                                        <span>
                                            {{ $component['title'] }}
                                            @if(isset($component['effective_from']) && \Carbon\Carbon::parse($component['effective_from'])->isFuture())
                                                <span class="block text-xs text-blue-500">
                                                    w.e.f = {{ \Carbon\Carbon::parse($component['effective_from'])->format('d M Y') }}
                                                </span>
                                            @endif
                                        </span>
                                        <span>{{ number_format($component['amount'], 2) }}</span>
                                    </div>
                                @endif
                            @endforeach
                            <div class="border-t pt-2 font-bold">
                                <div class="flex justify-between">
                                    <span>Total Deductions ({{ $deductionCount }} items)</span>
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
                    <flux:button wire:click="debugSalarySlip({{ $selectedEmployee['id'] }})" >Debug</flux:button>
                    <flux:button wire:click="closeSalarySlipModal">Close</flux:button>
{{--                    <flux:button wire:click="downloadSalarySlip">Download PDF</flux:button>--}}
                </div>

                <!-- JavaScript Debug -->
                <script>
                    console.log('=== Salary Slip Debug Info ===');
                    console.log('Selected Employee:', @json($selectedEmployee));
                    console.log('Components Array:', @json($cmpnts));
                    console.log('Total Components Count:', {{ count($cmpnts) }});
                    console.log('Total Earnings:', {{ $totalEarnings }});
                    console.log('Total Deductions:', {{ $totalDeductions }});
                    console.log('Net Salary:', {{ $netSalary }});
                    
                    // Log each component individually
                    @foreach($cmpnts as $index => $component)
                        console.log('Component {{ $index }}:', @json($component));
                    @endforeach
                    
                    // Count earnings and deductions
                    let earnings = @json($cmpnts).filter(c => c.nature === 'earning');
                    let deductions = @json($cmpnts).filter(c => c.nature === 'deduction');
                    console.log('Earnings count:', earnings.length);
                    console.log('Deductions count:', deductions.length);
                    console.log('Earnings:', earnings);
                    console.log('Deductions:', deductions);
                </script>
            @endif
        </div>
    </flux:modal>
</div>
