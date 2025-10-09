<div class="space-y-6">
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-declaration-group" class="flex justify-end">
            <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">
                New
            </flux:button>
        </flux:modal.trigger>
    </div>
    <flux:separator class="mt-2 mb-2" />

    <flux:card>
        <div class="flex flex-wrap gap-3 items-end">
            <flux:input class="w-64" placeholder="Search name/code..." wire:model.live.debounce.200ms="search" />
            <flux:select class="w-28" wire:model.live="perPage" label="Per Page">
                @foreach([10,20,50,100] as $n)
                    <option value="{{ $n }}">{{ $n }}</option>
                @endforeach
            </flux:select>
        </div>
    </flux:card>

    <flux:modal name="mdl-declaration-group" @cancel="@this.resetForm()">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Add Declaration Group</flux:heading>
                    <flux:subheading>Define a new declaration group and limits.</flux:subheading>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input label="Name" wire:model.live="formData.name" />
                    <flux:input label="Code" wire:model.live="formData.code" />
                    <flux:input type="number" label="Section Code" wire:model.live="formData.section_code" />
                    <flux:input type="number" step="0.01" label="Max Cap" wire:model.live="formData.max_cap" />
                    <flux:input label="Regime ID (optional)" wire:model.live="formData.regime_id" />
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
                <flux:table.column align="left" variant="strong" sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Name</flux:table.column>
                <flux:table.column align="left" variant="strong" sortable :sorted="$sortBy === 'code'" :direction="$sortDirection" wire:click="sort('code')">Code</flux:table.column>
                <flux:table.column align="right" variant="strong" sortable :sorted="$sortBy === 'section_code'" :direction="$sortDirection" wire:click="sort('section_code')">Section</flux:table.column>
                <flux:table.column align="right" variant="strong" sortable :sorted="$sortBy === 'max_cap'" :direction="$sortDirection" wire:click="sort('max_cap')">Max Cap</flux:table.column>
                <flux:table.column align="center" variant="strong" sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">Created</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($rows as $row)
                    <flux:table.row :key="$row->id">
                        <flux:table.cell>{{ $row->name }}</flux:table.cell>
                        <flux:table.cell>{{ $row->code }}</flux:table.cell>
                        <flux:table.cell align="right">{{ $row->section_code ?? '-' }}</flux:table.cell>
                        <flux:table.cell align="right">{{ $row->max_cap !== null ? number_format($row->max_cap, 2) : '-' }}</flux:table.cell>
                        <flux:table.cell align="center">{{ optional($row->created_at)->format('d M Y') }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-8 text-gray-400 text-lg">No declaration groups found.</td>
                    </tr>
                @endforelse
            </flux:table.rows>
        </flux:table>
        <div class="mt-3">
            {{ $rows->links() }}
        </div>
    </div>
</div>
