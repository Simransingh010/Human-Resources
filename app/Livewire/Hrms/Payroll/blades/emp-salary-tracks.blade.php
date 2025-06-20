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
                                wire:click="showSalarySlip({{ $item['employee_id'] }}, '{{ \Carbon\Carbon::parse($item['from_date'])->format('Y-m-d') }}', '{{ \Carbon\Carbon::parse($item['to_date'])->format('Y-m-d') }}', {{ $item['payroll_slot_id'] ?? 'null' }})"
                                tooltip="View Salary Slip"
                            >View Salary Slip</flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <!-- Salary Slip Modal -->
    <flux:modal wire:model="showSalarySlipModal" class="max-w-3xl ">
        <div class="bg-white">
            <!-- Header with Logo -->
            <div class="flex justify-center mb-6">
                @if($firmSquareLogo)
                    <img src="{{ asset($firmSquareLogo) }}"
                         alt="Company Square Logo"
                         class="h-16 w-16 object-contain"/>
                @elseif ($firmWideLogo)
                    <img src="{{ asset($firmWideLogo) }}"
                         alt="Company Wide Logo"
                         class="h-16 w-48 object-contain mt-2"/>
                @endif
            </div>
            <div class="flex justify-center mb-6">
                @if($rawComponents && $rawComponents->count() > 0)
                    <h2 class="text-xl font-bold">PAYSLIP FOR THE MONTH OF {{ strtoupper(date('F Y', strtotime($rawComponents->first()->salary_period_from))) }}</h2>
                @else
                    <h2 class="text-xl font-bold">PAYSLIP</h2>
                @endif
            </div>

            @if($selectedEmployee)
                <!-- Single Table Structure -->
                <table class="w-full border border-black">
                    <!-- Employee Details Section -->
                    <tr>
                        <td class="p-1 pb-1 pt-1 bg-white w-48 font-semibold">EMPLOYEE CODE </td>
                        <td class="p-1 pb-1 pt-1 bg-white">: {{ $selectedEmployee->emp_job_profile->employee_code}}</td>
                        <td class="p-1 pb-1 pt-1 bg-white w-48 font-semibold">DATE OF JOINING </td>
                        <td class="p-1 pb-1 pt-1 bg-white">
                            : {{ optional($selectedEmployee->emp_job_profile)->doh?->format('jS M Y') }}
                        </td>
                    </tr>
                    <tr>
                        <td class="p-1 pb-1 pt-1 bg-white w-48 font-semibold">NAME </td>
                        <td class="p-1 pb-1 pt-1 bg-white">: {{ $selectedEmployee->fname }} {{ $selectedEmployee->lname }}</td>
                        <td class="p-1 pb-1 pt-1 bg-white w-48 font-semibold">MONTH </td>
                        <td class="p-1 pb-1 pt-1 bg-white">: {{ $rawComponents && $rawComponents->count() > 0 ? date('M-y', strtotime($rawComponents->first()->salary_period_from)) : '' }}</td>
                    </tr>
                    <tr>
                        <td class="p-1 pb-1 pt-1 bg-white w-48 font-semibold">DEPARTMENT </td>
                        <td class="p-1 pb-1 pt-1 bg-white">: {{ optional($selectedEmployee->emp_job_profile)->department?->title }}</td>
                        <td class="p-1 pb-1 pt-1 bg-white w-48 font-semibold">BANK ACCOUNT NO. </td>
                        <td class="p-1 pb-1 pt-1 bg-white">: {{ $selectedEmployee->bank_account->bankaccount ?? 'N/A' }}</td>

                    </tr>
                    <tr>
                        <td class="p-1 pb-1 pt-1 bg-white w-48 font-semibold">DESIGNATION </td>
                        <td class="p-1 pb-1 pt-1 bg-white">: {{ optional($selectedEmployee->emp_job_profile)->designation?->title }}</td>

                         <td class="p-1 pb-1 pt-1 bg-white w-48 font-semibold">PAY LEVEL</td>
                        <td class="p-1 pb-1 pt-1 bg-white">: {{ optional($selectedEmployee->emp_job_profile)->paylevel ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="p-1 pb-1 pt-1 bg-white w-48 font-semibold">PAN NUMBER </td>
                        <td class="p-1 pb-1 pt-1 bg-white">: {{ $selectedEmployee->emp_personal_detail->panno?? 'N/A' }}</td>
                        <td class="p-1 pb-1 pt-1 bg-white w-48 font-semibold">PRAN NUMBER </td>
                        <td class="p-1 pb-1 pt-1 bg-white">: {{ $selectedEmployee->emp_job_profile->pran_number ?? 'N/A' }}</td>
                    </tr>

                    <!-- Salary Components Headers -->
                    <tr>
                        <th class="p-1 border border-black pb-1 pt-1 bg-white text-left font-semibold">EARNINGS</th>
                        <th class="p-1 border border-black pb-1 pt-1 bg-white text-right font-semibold">AMOUNT (in Rs.)</th>
                        <th class="p-1 border border-black pb-1 pt-1 bg-white text-left font-semibold">DEDUCTIONS</th>
                        <th class="p-1 border border-black pb-1 pt-1 bg-white text-right font-semibold">AMOUNT (in Rs.)</th>
                    </tr>

                    <!-- Salary Components Data -->
                    @php 
                        $salaryComponentsCollection = collect($salaryComponents);
                        $maxRows = max(
                            $salaryComponentsCollection->where('nature', 'earning')->count(), 
                            $salaryComponentsCollection->where('nature', 'deduction')->count()
                        );
                        $earnings = $salaryComponentsCollection->where('nature', 'earning')->values();
                        $deductions = $salaryComponentsCollection->where('nature', 'deduction')->values();
                    @endphp

                    @for($i = 0; $i < $maxRows; $i++)
                        <tr>
                            <td class="p-1 border border-black pb-1 pt-1 bg-white">
                                {{ isset($earnings[$i]) ? strtoupper($earnings[$i]['title']) : '' }}
                            </td>
                            <td class="p-1 border border-black pb-1 pt-1 bg-white text-right">
                                {{ isset($earnings[$i]) ? number_format($earnings[$i]['amount'], 0) : '' }}
                            </td>
                            <td class="p-1 border border-black pb-1 pt-1 bg-white">
                                {{ isset($deductions[$i]) ? strtoupper($deductions[$i]['title']) : '' }}
                            </td>
                            <td class="p-1 border border-black pb-1 pt-1 bg-white text-right">
                                {{ isset($deductions[$i]) ? number_format($deductions[$i]['amount'], 0) : '' }}
                            </td>
                        </tr>
                    @endfor

                    <!-- Totals -->
                    <tr>
                        <td class="p-1 border border-black pb-1 pt-1 bg-white font-bold">GROSS SALARY</td>
                        <td class="p-1 border border-black pb-1 pt-1 bg-white text-right font-bold">{{ number_format($totalEarnings, 0) }}</td>
                        <td class="p-1 border border-black pb-1 pt-1 bg-white font-bold">TOTAL DEDUCTIONS</td>
                        <td class="p-1 border border-black pb-1 pt-1 bg-white text-right font-bold">{{ number_format($totalDeductions, 0) }}</td>
                    </tr>

                    <!-- Net Salary -->
                    <tr>
                        <td colspan="2" class="p-1 border border-black pb-1 pt-1 bg-white font-bold">NET SALARY</td>
                        <td colspan="2" class="p-1 border border-black pb-1 pt-1 bg-white text-right font-bold">{{ number_format($netSalary, 0) }}</td>
                    </tr>

                    <!-- Note -->
                    <tr>
                        <td colspan="4" class="p-1 border border-black pb-1 pt-1 bg-white text-sm">
                            Note:-This is a computer generated salary slip, hence does not require signature.
                        </td>
                    </tr>
                </table>

                <!-- Action Buttons -->
                <div class="mt-6 flex justify-end space-x-3">
                    <flux:button 
                        wire:click="downloadPDF({{ $selectedEmployee->id }}, '{{ $rawComponents->first()->salary_period_from }}', '{{ $rawComponents->first()->salary_period_to }}', {{ $rawComponents->first()->payroll_slot_id }})" 
                        variant="primary" 
                        icon="document-arrow-down"
                    >Download PDF</flux:button>
                    <flux:button wire:click="closeSalarySlipModal">Close</flux:button>
                </div>
            @endif
        </div>
    </flux:modal>

</div>
</div>
