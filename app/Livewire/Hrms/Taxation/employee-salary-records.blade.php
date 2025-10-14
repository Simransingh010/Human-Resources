<div class="w-full p-0 m-0">
    <div class="flex justify-between">
        @livewire('panel.component-heading')
    </div>
    <flux:separator class="mt-2 mb-2"/>

    <form wire:submit.prevent>
        <flux:heading level="3" size="xl">Employee Salary Records</flux:heading>
        <flux:card size="sm" class="sm:p-2 !rounded-xl mb-2 bg-zinc-50 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-all">
            <div class="grid md:grid-cols-3 gap-2 items-end">
                <div>
                    <flux:input type="text" placeholder="Employee Name" wire:model.live.debounce.250ms="filters.employees" />
                </div>
                <div>
                    <flux:input type="text" placeholder="Email" wire:model.live.debounce.250ms="filters.email" />
                </div>
                <div>
                    <flux:input type="text" placeholder="Phone" wire:model.live.debounce.250ms="filters.phone" />
                </div>
            </div>
        </flux:card>
    </form>

    <flux:card class="shadow-lg">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
                        <th class="text-left p-3 font-semibold">Employee</th>
                        @foreach(($tableRows['labels'] ?? []) as $label)
                            <th class="text-center p-3 font-semibold min-w-[90px]">{{ $label }}</th>
                        @endforeach
                        <th class="text-right p-3 font-semibold">YTD Paid</th>
                        <th class="text-right p-3 font-semibold">Remaining</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(($tableRows['rows'] ?? []) as $r)
                        <tr class="border-b hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <td class="p-3 align-top">
                                <div class="flex flex-col">
                                    <span class="font-semibold">{{ $r['emp']->fname }} {{ $r['emp']->mname }} {{ $r['emp']->lname }}</span>
                                    <span class="text-xs text-zinc-500">{{ $r['emp']->email }} • {{ $r['emp']->phone }}</span>
                                </div>
                            </td>
                            @foreach($r['cells'] as $cell)
                                <td class="p-2 text-center">
                                    <div class="flex flex-col items-center animate-fade-in">
                                        <div class="w-2.5 h-2.5 rounded-full mb-1 {{ $cell['actual'] > 0 ? 'bg-green-500' : ($cell['planned'] > 0 ? 'bg-amber-400' : 'bg-zinc-300') }}"></div>
                                        <div class="text-[11px] {{ $cell['actual']>0 ? 'font-semibold' : 'text-zinc-600' }}">₹ {{ number_format($cell['actual']>0 ? $cell['actual'] : $cell['planned'], 0) }}</div>
                                        @if(!empty($cell['breakup']))
                                            <div class="text-[10px] text-zinc-500 mt-0.5">Taxable: ₹ {{ number_format($cell['breakup']['taxable_earnings'] ?? 0, 0) }}</div>
                                        @endif
                                    </div>
                                </td>
                            @endforeach
                            <td class="p-3 text-right font-semibold text-green-700">₹ {{ number_format($r['ytd_paid'], 0) }}</td>
                            <td class="p-3 text-right font-semibold text-amber-700">₹ {{ number_format($r['remaining_planned'], 0) }}</td>
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
            <flux:button size="sm" variant="primary" wire:click="prevPage">Prev</flux:button>
            <flux:button size="sm" variant="primary" wire:click="nextPage">Next</flux:button>
        </div>
    </div>

    <style>
        @keyframes fade-in { from { opacity: 0; transform: translateY(2px);} to { opacity: 1; transform: translateY(0);} }
        .animate-fade-in { animation: fade-in .3s ease-in-out; }
    </style>
</div>

