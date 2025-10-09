<div class="space-y-6">
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-loss-cf" class="flex justify-end">
            <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">
                New
            </flux:button>
        </flux:modal.trigger>
    </div>
    <flux:separator class="mt-2 mb-2" />

    <flux:card>
        <div class="flex flex-wrap gap-3 items-end">
            <flux:input class="w-64" placeholder="Search employee/remarks..." wire:model.live.debounce.200ms="search" />
            <flux:select class="w-28" wire:model.live="perPage" label="Per Page">
                @foreach([10,20,50,100] as $n)
                    <option value="{{ $n }}">{{ $n }}</option>
                @endforeach
            </flux:select>
        </div>
    </flux:card>

    <flux:modal name="mdl-loss-cf" @cancel="@this.resetForm()">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Add Loss Carry Forward</flux:heading>
                    <flux:subheading>Track business losses and setoff/carry forward details for an employee.</flux:subheading>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select label="Employee" wire:model.live="formData.emp_id">
                        <option value="">Select Employee</option>
                        @foreach(($lists['employees'] ?? []) as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:input type="number" step="0.01" label="Original Loss Amount" wire:model.live="formData.original_loss_amount" />
                    <flux:input type="number" step="0.01" label="Setoff in Current Year" wire:model.live="formData.setoff_in_current_year" />
                    <flux:input type="number" step="0.01" label="Carry Forward Amount" wire:model.live="formData.carry_forward_amount" />
                    <flux:input type="number" label="Forward Upto Year" wire:model.live="formData.forward_upto_year" />
                    <flux:input type="number" label="Declaration ID (optional)" wire:model.live="formData.declaration_id" />
                    <flux:input type="number" label="ITR ID (optional)" wire:model.live="formData.itr_id" />
                    <div class="md:col-span-2">
                        <flux:textarea rows="3" label="Remarks (optional)" wire:model.live="formData.remarks" />
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
                <flux:table.column align="left" variant="strong" sortable :sorted="$sortBy === 'employee_name'" :direction="$sortDirection" wire:click="sort('employee_name')">Employee</flux:table.column>
                <flux:table.column align="right" variant="strong" sortable :sorted="$sortBy === 'original_loss_amount'" :direction="$sortDirection" wire:click="sort('original_loss_amount')">Original Loss</flux:table.column>
                <flux:table.column align="right" variant="strong" sortable :sorted="$sortBy === 'setoff_in_current_year'" :direction="$sortDirection" wire:click="sort('setoff_in_current_year')">Setoff (Current)</flux:table.column>
                <flux:table.column align="right" variant="strong" sortable :sorted="$sortBy === 'carry_forward_amount'" :direction="$sortDirection" wire:click="sort('carry_forward_amount')">Carry Forward</flux:table.column>
                <flux:table.column align="center" variant="strong" sortable :sorted="$sortBy === 'forward_upto_year'" :direction="$sortDirection" wire:click="sort('forward_upto_year')">Forward Upto</flux:table.column>
                <flux:table.column align="left" variant="strong">Remarks</flux:table.column>
                <flux:table.column align="center" variant="strong" sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">Created</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($rows as $row)
                    <flux:table.row :key="$row->id">
                        <flux:table.cell>{{ $row->employee_name }}</flux:table.cell>
                        <flux:table.cell align="right">{{ number_format($row->original_loss_amount ?? 0, 2) }}</flux:table.cell>
                        <flux:table.cell align="right">{{ number_format($row->setoff_in_current_year ?? 0, 2) }}</flux:table.cell>
                        <flux:table.cell align="right">{{ number_format($row->carry_forward_amount ?? 0, 2) }}</flux:table.cell>
                        <flux:table.cell align="center">{{ $row->forward_upto_year }}</flux:table.cell>
                        <flux:table.cell>{{ \Illuminate\Support\Str::limit($row->remarks, 80) }}</flux:table.cell>
                        <flux:table.cell align="center">{{ optional($row->created_at)->format('d M Y') }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-8 text-gray-400 text-lg">No loss carry forward records found.</td>
                    </tr>
                @endforelse
            </flux:table.rows>
        </flux:table>
        <div class="mt-3">
            {{ $rows->links() }}
        </div>
    </div>
</div>

