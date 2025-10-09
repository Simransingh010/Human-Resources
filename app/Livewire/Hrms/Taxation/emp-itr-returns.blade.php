<div class="space-y-6">
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-emp-itr-return" class="flex justify-end">
            <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">
                New
            </flux:button>
        </flux:modal.trigger>
    </div>
    <flux:separator class="mt-2 mb-2" />

    <flux:card>
        <div class="flex flex-wrap gap-3 items-end">
            <flux:input class="w-64" placeholder="Search ack/ITR type/status..." wire:model.live.debounce.200ms="search" />
            <flux:select class="w-28" wire:model.live="perPage" label="Per Page">
                @foreach([10,20,50,100] as $n)
                    <option value="{{ $n }}">{{ $n }}</option>
                @endforeach
            </flux:select>
        </div>
    </flux:card>

    <flux:modal name="mdl-emp-itr-return" @cancel="@this.resetForm()">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Add ITR Return</flux:heading>
                    <flux:subheading>Record ITR details and upload the PDF acknowledgement/return copy.</flux:subheading>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select label="Employee" wire:model.live="formData.emp_id">
                        <option value="">Select Employee</option>
                        @foreach(($lists['employees'] ?? []) as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select label="ITR Type" wire:model.live="formData.itr_type">
                        <option value="">Select ITR</option>
                        @foreach(($lists['itr_types'] ?? []) as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                    <flux:input type="date" label="Date Filed" wire:model.live="formData.date_filed" />
                    <flux:input label="Acknowledgement No" wire:model.live="formData.acknowledgement_no" />
                    <flux:select label="Status" wire:model.live="formData.status">
                        @foreach(($lists['status'] ?? []) as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                    <div></div>
                    <div class="md:col-span-2">
                        <flux:input type="file" accept="application/pdf" label="Acknowledged Document (PDF)" wire:model.live="document" />
                        @error('document') <div class="text-red-500 text-sm mt-1">{{ $message }}</div> @enderror
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
                <flux:table.column align="center" variant="strong" sortable :sorted="$sortBy === 'date_filed'" :direction="$sortDirection" wire:click="sort('date_filed')">Date Filed</flux:table.column>
                <flux:table.column align="left" variant="strong" sortable :sorted="$sortBy === 'itr_type'" :direction="$sortDirection" wire:click="sort('itr_type')">ITR Type</flux:table.column>
                <flux:table.column align="left" variant="strong" sortable :sorted="$sortBy === 'acknowledgement_no'" :direction="$sortDirection" wire:click="sort('acknowledgement_no')">Ack No</flux:table.column>
                <flux:table.column align="center" variant="strong" sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Status</flux:table.column>
                <flux:table.column align="center" variant="strong">Document</flux:table.column>
                <flux:table.column align="center" variant="strong" sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">Created</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($rows as $row)
                    <flux:table.row :key="$row->id">
                        <flux:table.cell align="center">{{ optional($row->date_filed)->format('d M Y') }}</flux:table.cell>
                        <flux:table.cell>{{ $row->itr_type }}</flux:table.cell>
                        <flux:table.cell>{{ $row->acknowledgement_no ?? '-' }}</flux:table.cell>
                        <flux:table.cell align="center">{{ ucfirst($row->status ?? '-') }}</flux:table.cell>
                        <flux:table.cell align="center">
                            @php($u = $docUrls[$row->id] ?? null)
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
                        <td colspan="6" class="text-center py-8 text-gray-400 text-lg">No ITR returns found.</td>
                    </tr>
                @endforelse
            </flux:table.rows>
        </flux:table>
        <div class="mt-3">
            {{ $rows->links() }}
        </div>
    </div>
</div>

