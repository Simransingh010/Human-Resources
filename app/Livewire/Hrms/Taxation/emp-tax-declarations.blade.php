<div class="space-y-6">
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-emp-tax-declaration" class="flex justify-end">
            <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">
                New
            </flux:button>
        </flux:modal.trigger>
    </div>
    <flux:separator class="mt-2 mb-2" />


    <flux:card>
        <div class="flex flex-wrap gap-3 items-end">
            <flux:input class="w-64" placeholder="Search employee name..." wire:model.live.debounce.200ms="search" />
            <flux:select class="w-52" wire:model.live="filterDeclarationTypeId" label="Declaration Type">
                <option value="">All</option>
                @foreach(($lists['declaration_types'] ?? []) as $id => $label)
                    <option value="{{ $id }}">{{ $label }}</option>
                @endforeach
            </flux:select>
            <flux:select class="w-44" wire:model.live="filterStatus" label="Status">
                <option value="">All</option>
                @foreach(($lists['status'] ?? []) as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </flux:select>
            <flux:select class="w-56" wire:model.live="filterSource" label="Source">
                <option value="">All</option>
                @foreach(($lists['source'] ?? []) as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </flux:select>
            <flux:select class="w-28" wire:model.live="perPage" label="Per Page">
                @foreach([10,20,50,100] as $n)
                    <option value="{{ $n }}">{{ $n }}</option>
                @endforeach
            </flux:select>
        </div>
    </flux:card>

    <flux:modal name="mdl-emp-tax-declaration" @cancel="@this.resetForm()">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Add Declaration</flux:heading>
                    <flux:subheading>Upload supporting PDF and declared amount. Status will be Pending.</flux:subheading>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select label="Employee" wire:model.live="formData.emp_id">
                        <option value="">Select Employee</option>
                        @foreach(($lists['employees'] ?? []) as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select label="Declaration Type" wire:model.live="formData.declaration_type_id">
                        <option value="">Select Type</option>
                        @foreach(($lists['declaration_types'] ?? []) as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                    <flux:input type="number" step="0.01" label="Declared Amount" wire:model.live="formData.declared_amount" />
                    <div class="md:col-span-2">
                        <flux:input type="file" accept="application/pdf" label="Supporting Document (PDF)" wire:model.live="document" />
                        @error('document') <div class="text-red-500 text-sm mt-1">{{ $message }}</div> @enderror
                    </div>
                    <flux:select label="Source (optional)" wire:model.live="formData.source">
                        <option value="">Select Source</option>
                        @foreach(($lists['source'] ?? []) as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                    <div></div>
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
        <div wire:loading.flex wire:target="search,filterDeclarationTypeId,filterStatus,filterSource,perPage,sort,document" class="absolute inset-0 z-10 items-center justify-center bg-white/70 backdrop-blur hidden">
            <div class="flex items-center justify-center w-full h-full">
                <flux:icon.loading class="w-10 h-10 text-blue-500 animate-spin" />
            </div>
        </div>
        <flux:table>
            <flux:table.columns>
                <flux:table.column align="left" variant="strong" sortable :sorted="$sortBy === 'employee_name'" :direction="$sortDirection" wire:click="sort('employee_name')">Employee</flux:table.column>
                <flux:table.column align="left" variant="strong" sortable :sorted="$sortBy === 'declaration_type_name'" :direction="$sortDirection" wire:click="sort('declaration_type_name')">Declaration Type</flux:table.column>
                <flux:table.column align="right" variant="strong" sortable :sorted="$sortBy === 'declared_amount'" :direction="$sortDirection" wire:click="sort('declared_amount')">Declared</flux:table.column>
                <flux:table.column align="center" variant="strong">Document</flux:table.column>
                <flux:table.column align="center" variant="strong" sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Status</flux:table.column>
                <flux:table.column align="center" variant="strong" sortable :sorted="$sortBy === 'source'" :direction="$sortDirection" wire:click="sort('source')">Source</flux:table.column>
                <flux:table.column align="left" variant="strong">Remarks</flux:table.column>
                <flux:table.column align="center" variant="strong" sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">Created</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($rows as $row)
                    <flux:table.row :key="$row->id">
                        <flux:table.cell>
                            {{ $row->employee_name }}
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $row->declaration_type_name }}
                        </flux:table.cell>
                        <flux:table.cell align="right">
                            {{ number_format($row->declared_amount, 2) }}
                        </flux:table.cell>
                        <flux:table.cell align="center">
                            @if($row->supporting_doc)
                                <a class="text-blue-600 underline" href="{{ Storage::disk('public')->url($row->supporting_doc) }}" target="_blank" rel="noopener noreferrer">View</a>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell align="center">
                            <span class="inline-block px-2 py-0.5 rounded text-white text-xs
                                @if($row->status === 'approved') bg-green-500
                                @elseif($row->status === 'rejected') bg-rose-500
                                @else bg-yellow-500 @endif">
                                {{ \App\Models\Hrms\EmpTaxDeclaration::STATUS_SELECT[$row->status] ?? $row->status }}
                            </span>
                        </flux:table.cell>
                        <flux:table.cell align="center">
                            {{ \App\Models\Hrms\EmpTaxDeclaration::SOURCE_SELECT[$row->source] ?? ($row->source ?? '-') }}
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ \Illuminate\Support\Str::limit($row->remarks, 60) }}
                        </flux:table.cell>
                        <flux:table.cell align="center">
                            {{ optional($row->created_at)->format('d M Y') }}
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-8 text-gray-400 text-lg">No declarations found.</td>
                    </tr>
                @endforelse
            </flux:table.rows>
        </flux:table>
        <div class="mt-3">
            {{ $rows->links() }}
        </div>
    </div>
</div>
