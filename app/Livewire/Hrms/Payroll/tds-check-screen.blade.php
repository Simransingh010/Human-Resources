<div class="w-full p-0 m-0">
    <div class="flex justify-between">
        @livewire('panel.component-heading')
    </div>
    <flux:separator class="mt-2 mb-2"/>

    <form wire:submit.prevent>
        <flux:heading level="3" size="lg">TDS Overview</flux:heading>
        <flux:card size="sm" class="sm:p-2 !rounded-xl rounded-xl! mb-1 rounded-t-lg p-0 bg-zinc-50 hover:bg-zinc-50 dark:hover:bg-zinc-700">
            <div class="grid md:grid-cols-3 gap-2 items-end">
                <div>
                    <flux:input type="text" placeholder="Employee Name" wire:model.debounce.400ms="filters.employees" />
                </div>
                <div>
                    <flux:input type="text" placeholder="Email" wire:model.debounce.400ms="filters.email" />
                </div>
                <div>
                    <flux:input type="text" placeholder="Phone" wire:model.debounce.400ms="filters.phone" />
                </div>
            </div>
        </flux:card>
    </form>

    <flux:card class="mt-3">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
                        <th class="text-left p-3 font-semibold">Employee</th>
                        @if($this->employeesPage->count() > 0)
                            @php($firstEmp = $this->employeesPage->first())
                            @php($firstSummary = $this->getEmployeeTdsSummary($firstEmp->id))
                            @foreach($firstSummary['months'] as $month)
                                <th class="text-center p-3 font-semibold min-w-[80px]">{{ $month['label'] }}</th>
                            @endforeach
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->employeesPage as $emp)
                        @php($summary = $this->getEmployeeTdsSummary($emp->id))
                        <tr class="border-b hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <td class="p-3 align-top">
                                <div class="flex flex-col">
                                    <span class="font-semibold">{{ $emp->fname }} {{ $emp->mname }} {{ $emp->lname }}</span>
                                    <span class="text-xs text-zinc-500">{{ $emp->email }} • {{ $emp->phone }}</span>
                                    <div class="mt-2 text-xs space-y-1">
                                        <table class="w-full text-xs">
                                            <tr>
                                                <td class="text-zinc-600 pr-2">Annual Salary:</td>
                                                <td class="font-semibold text-blue-700 text-right">₹ {{ number_format($summary['annual_salary'], 0) }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-zinc-600 pr-2">Annual TDS:</td>
                                                <td class="font-semibold text-right">₹ {{ number_format($summary['annual_applicable'], 0) }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-zinc-600 pr-2">Deducted (YTD):</td>
                                                <td class="text-green-700 text-right">₹ {{ number_format($summary['ytd_deducted'], 0) }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-zinc-600 pr-2">Remaining:</td>
                                                <td class="text-amber-700 text-right">₹ {{ number_format($summary['remaining'], 0) }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-zinc-600 pr-2">Plan/Month:</td>
                                                <td class="text-right">₹ {{ number_format($summary['per_month_plan'], 0) }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </td>
                            @foreach($summary['months'] as $month)
                                <td class="p-3 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-3 h-3 rounded-full mb-1 {{ $month['actual'] > 0 ? 'bg-amber-400' : 'bg-cyan-400' }}"></div>
                                        @if($month['actual'] > 0)
                                            <div class="text-sm font-semibold">₹ {{ number_format($month['actual'], 0) }}</div>
                                        @elseif($month['planned'] > 0)
                                            <div class="text-sm text-zinc-600">₹ {{ number_format($month['planned'], 0) }}</div>
                                        @else
                                            <div class="text-sm text-zinc-400">0</div>
                                        @endif
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </flux:card>

    <div class="mt-4 flex items-center justify-between">
        <div class="text-sm text-zinc-600">
            @php($start = ($this->page - 1) * $this->perPage + 1)
            @php($end = min($this->totalEmployeesCount, $this->page * $this->perPage))
            Showing {{ $this->totalEmployeesCount > 0 ? $start : 0 }} - {{ $end }} of {{ $this->totalEmployeesCount }}
        </div>
        <div class="space-x-2">
             </div>
    </div>
</div>


