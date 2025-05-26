<div>
<div class="space-y-6">
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
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

    <!-- Data Table -->
    <flux:table :paginate="$this->list" class="w-full">
        <flux:table.columns>
            <flux:table.column>Employee</flux:table.column>
            <flux:table.column>Income</flux:table.column>
            <flux:table.column>Deduction</flux:table.column>
            <flux:table.column>Net Salary</flux:table.column>
            <flux:table.column>Period</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->list as $item)
                <flux:table.row :key="$item['id']">
                    <flux:table.cell class="table-cell-wrap">{{ $item['employee_name'] }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ number_format($item['income'], 2) }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ number_format($item['deduction'], 2) }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">
                        <flux:badge color="{{ $item['net_salary'] >= 0 ? 'green' : 'red' }}">
                            {{ number_format($item['net_salary'], 2) }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $item['period'] }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">
                        <div class="flex space-x-2">
                            <flux:button
                                wire:click="showSalarySlip({{ $item['employee_id'] }}, '{{ \Carbon\Carbon::parse($item['from_date'])->format('Y-m-d') }}', '{{ \Carbon\Carbon::parse($item['to_date'])->format('Y-m-d') }}')"
                                tooltip="View Salary Slip"
                            >View Salary Slip</flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <!-- Salary Slip Modal -->
    <flux:modal wire:model="showSalarySlipModal" class="max-w-3xl">
        <div class="p-4">
            <div class="flex justify-between">
            <h2 class="text-xl font-bold mb-4">Salary Slip</h2>
                <img class="ms-5"
                     src="https://www.iimsirmaur.ac.in/themes/sirmaur/images/logo.png"
                     alt="IIM Logo"
                     class="h-20 w-auto"
                />
            </div>
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
                            <p><strong>Bank Account:</strong> {{ $selectedEmployee->bank_account -> bankaccount?? 'N/A' }}</p>
                            <p><strong>PAN:</strong> {{ $selectedEmployee->pan ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Salary Components (Earnings | Deductions) -->
                <div class="flex flex-col md:flex-row gap-8">
                    <!-- Earnings -->
                    <div class="flex-1 bg-gray-50 rounded-lg p-4 shadow-sm">
                        <h3 class="font-bold mb-3 border-b pb-2">Earnings</h3>
                        <div class="space-y-2">
                            @php $anyEarning = false; @endphp
                            @foreach($salaryComponents as $component)
                                @if($component['nature'] === 'earning')
                                    @php $anyEarning = true; @endphp
                                    <div class="flex justify-between">
                                        <span>{{ $component['title'] }}</span>
                                        <span>{{ number_format($component['amount'], 2) }}</span>
                                    </div>
                                @endif
                            @endforeach
                            @if(!$anyEarning)
                                <div class="text-gray-400 text-sm">No earnings</div>
                            @endif
                            <div class="border-t pt-2 font-bold">
                                <div class="flex justify-between">
                                    <span>Total Earnings</span>
                                    <span>{{ number_format($totalEarnings, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Deductions -->
                    <div class="flex-1 bg-gray-50 rounded-lg p-4 shadow-sm">
                        <h3 class="font-bold mb-3 border-b pb-2">Deductions</h3>
                        <div class="space-y-2">
                            @php $anyDeduction = false; @endphp
                            @foreach($salaryComponents as $component)
                                @if($component['nature'] === 'deduction')
                                    @php $anyDeduction = true; @endphp
                                    <div class="flex justify-between">
                                        <span>{{ $component['title'] }}</span>
                                        <span>{{ number_format($component['amount'], 2) }}</span>
                                    </div>
                                @endif
                            @endforeach
                            @if(!$anyDeduction)
                                <div class="text-gray-400 text-sm">No deductions</div>
                            @endif
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
                <div class="mt-8 border-t pt-4">
                    <div class="flex flex-col md:flex-row justify-between items-center font-bold text-lg">
                        <span>Net Salary</span>
                        <span class="text-green-600">{{ number_format($netSalary, 2) }}</span>
                    </div>
                    @if($netSalaryInWords)
                        <p class="text-sm mt-2 italic text-gray-600">Amount in words: {{ $netSalaryInWords }}</p>
                    @endif
                    <p class="mt-2 font-bold">Note: This is a Computer generated salary slip, hence dose not require signature.</p>

                </div>

                <!-- Action Buttons -->
                <div class="mt-6 flex justify-end space-x-3">
                    <flux:button variant="primary" icon="document-arrow-down">Download PDF</flux:button>
                    <flux:button wire:click="closeSalarySlipModal">Close</flux:button>
                </div>
            @endif
        </div>
    </flux:modal>

</div>
</div>
