<div class="space-y-6">
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-tax-payment" class="flex justify-end">
            <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">
                New
            </flux:button>
        </flux:modal.trigger>
    </div>
    <flux:separator class="mt-2 mb-2" />

    <flux:card>
        <div class="flex flex-wrap gap-3 items-end">
            <flux:input class="w-64" placeholder="Search challan no..." wire:model.live.debounce.200ms="search" />
            <flux:select class="w-28" wire:model.live="perPage" label="Per Page">
                @foreach([10,20,50,100] as $n)
                    <option value="{{ $n }}">{{ $n }}</option>
                @endforeach
            </flux:select>
        </div>
    </flux:card>

    <flux:modal name="mdl-tax-payment" @cancel="@this.resetForm()">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Add Tax Payment</flux:heading>
                    <flux:subheading>Record employee tax payments with optional challan receipt.</flux:subheading>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select label="Employee" wire:model.live="formData.emp_id">
                        <option value="">Select Employee</option>
                        @foreach(($lists['employees'] ?? []) as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:input type="number" step="0.01" label="Amount" wire:model.live="formData.amount" />
                    <flux:input type="date" label="Payment Date" wire:model.live="formData.payment_date" />
                    <flux:input label="Challan No" wire:model.live="formData.challan_no" />
                    <flux:input type="date" label="From Date" wire:model.live="formData.from_date" />
                    <flux:input type="date" label="To Date" wire:model.live="formData.to_date" />
                    <flux:select label="Payment Type" wire:model.live="formData.payment_type">
                        <option value="">Select Type</option>
                        @foreach(($lists['payment_types'] ?? []) as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select label="Paid By" wire:model.live="formData.paid_by">
                        <option value="">Select</option>
                        @foreach(($lists['paid_by'] ?? []) as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                    <div class="md:col-span-2">
                        <flux:input type="file" accept="application/pdf" label="Challan Receipt (PDF)" wire:model.live="challan" />
                        @error('challan') <div class="text-red-500 text-sm mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="flex justify-end pt-2">
                    <flux:button type="submit" variant="primary">Save</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <div class="relative">
        <div wire:loading.flex wire:target="search,perPage,sort" class="absolute inset-0 z-10 items-center justify-center bg-white/70 backdrop-blur hidden">
            <div class="flex items-center justify-center w-full h-full">
                <flux:icon.loading class="w-10 h-10 text-blue-500 animate-spin" />
            </div>
        </div>
        <flux:table>
            <flux:table.columns>
                <flux:table.column align="left" variant="strong" sortable :sorted="$sortBy === 'challan_no'" :direction="$sortDirection" wire:click="sort('challan_no')">Challan No</flux:table.column>
                <flux:table.column align="right" variant="strong" sortable :sorted="$sortBy === 'amount'" :direction="$sortDirection" wire:click="sort('amount')">Amount</flux:table.column>
                <flux:table.column align="center" variant="strong" sortable :sorted="$sortBy === 'payment_date'" :direction="$sortDirection" wire:click="sort('payment_date')">Payment Date</flux:table.column>
                <flux:table.column align="center" variant="strong" sortable :sorted="$sortBy === 'payment_type'" :direction="$sortDirection" wire:click="sort('payment_type')">Type</flux:table.column>
                <flux:table.column align="center" variant="strong" sortable :sorted="$sortBy === 'paid_by'" :direction="$sortDirection" wire:click="sort('paid_by')">Paid By</flux:table.column>
                <flux:table.column align="center" variant="strong">Challan Receipt</flux:table.column>
                <flux:table.column align="center" variant="strong" sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">Created</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($rows as $row)
                    <flux:table.row :key="$row->id">
                        <flux:table.cell>{{ $row->challan_no ?? '-' }}</flux:table.cell>
                        <flux:table.cell align="right">{{ number_format($row->amount ?? 0, 2) }}</flux:table.cell>
                        <flux:table.cell align="center">{{ optional($row->payment_date)->format('d M Y') }}</flux:table.cell>
                        <flux:table.cell align="center">{{ ucfirst($row->payment_type ?? '-') }}</flux:table.cell>
                        <flux:table.cell align="center">{{ ucfirst($row->paid_by ?? '-') }}</flux:table.cell>
                        <flux:table.cell align="center">
                            @php($u = $challanReceipts[$row->id] ?? null)
                            @if($u)
                                <a class="text-blue-600 underline" href="{{ $u }}" target="_blank" rel="noopener noreferrer">View</a>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell align="center">{{ optional($row->created_at)->format('d M Y') }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-8 text-gray-400 text-lg">No tax payments found.</td>
                    </tr>
                @endforelse
            </flux:table.rows>
        </flux:table>
        <div class="mt-3">
            {{ $rows->links() }}
        </div>
    </div>
</div>

