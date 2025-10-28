<div class="w-full p-0 m-0">
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-college" class="flex justify-end">
            <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">
                New College
            </flux:button>
        </flux:modal.trigger>
    </div>
    <flux:separator class="mt-2 mb-2"/>

    <form wire:submit.prevent="applyFilters">
        <flux:heading level="3" size="lg">Filter Records</flux:heading>
        <flux:card size="sm"
                   class="sm:p-2 !rounded-xl rounded-xl! mb-1 rounded-t-lg p-0 bg-zinc-50 hover:bg-zinc-50 dark:hover:bg-zinc-700">

            <div class="grid md:grid-cols-3 gap-2 items-end">
                <div class="">
                    <flux:input type="text" placeholder="College Name, Code, City..." wire:model.debounce.500ms="filters.colleges"
                                wire:change="applyFilters"/>
                </div>
                <div>
                    <flux:input type="text" placeholder="Phone" wire:model.debounce.500ms="filters.phone"
                                wire:change="applyFilters"/>
                </div>
                <div>
                    <flux:input type="text" placeholder="Email" wire:model.debounce.500ms="filters.email"
                                wire:change="applyFilters"/>
                </div>
                <div class="flex flex-wrap gap-2">
                    <flux:button variant="filled" class="w-full px-2" tooltip="Cancel Filter" icon="x-circle"
                                 wire:click="clearFilters()"></flux:button>
                    <flux:button
                            variant="{{ $this->viewMode === 'card' ? 'primary' : 'outline' }}"
                            wire:click="setViewMode('card')"
                            icon="table-cells"
                            class="mr-2"
                    ></flux:button>
                    <flux:button
                            variant="{{ $this->viewMode === 'table' ? 'primary' : 'outline' }}"
                            wire:click="setViewMode('table')"
                            icon="adjustments-horizontal"
                    ></flux:button>
                </div>
            </div>

        </flux:card>
    </form>

    {{-- College Grid --}}
    @if($this->viewMode === 'card')
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mt-6">
            @foreach ($this->collegeslist as $college)
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow p-3 flex flex-col gap-3 hover:shadow-lg transition-all border border-zinc-100 dark:border-zinc-700">
                    <div class="flex items-center gap-4">
                        <div>
                            <div class="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center border border-blue-300 shadow-sm">
                                <flux class="w-8 h-8 text-blue-600"/>
                            </div>
                        </div>

                        <div class="flex flex-col">
                            <span class="font-semibold text-lg">{{ $college->name }}</span>
                            <span class="text-xs text-gray-500">
                                {{ $college->firm->name ?? 'No Firm' }}
                                @if($college->established_year)
                                &bull; Est. {{ $college->established_year }}
                                @endif
                            </span>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1 mt-2">
                        <span class="text-sm text-gray-700 dark:text-gray-200"><b>Code:</b> {{ $college->code }}</span>
                        <span class="text-sm text-gray-700 dark:text-gray-200"><b>Phone:</b> {{ $college->phone }}</span>
                        <span class="text-sm text-gray-700 dark:text-gray-200"><b>Email:</b> {{ $college->email }}</span>
                        <span class="text-sm text-gray-700 dark:text-gray-200"><b>City:</b> {{ $college->city }}</span>
                    </div>
                    <div class="flex items-center justify-between mt-2">
                        <div class="flex items-center gap-2">
                            <flux:switch wire:click="toggleStatus({{ $college->id }})"
                                         :checked="!$college->is_inactive"/>
                            <span class="text-xs">{{ $college->is_inactive ? 'Inactive' : 'Active' }}</span>
                        </div>
                        <div class="flex gap-2">
                            <flux:button variant="primary" size="sm" icon="pencil"
                                         wire:click="edit({{ $college->id }})"></flux:button>
                            <flux:modal.trigger name="delete-college-{{ $college->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>
                    </div>
                    <flux:modal name="delete-college-{{ $college->id }}" class="min-w-[22rem]">
                        <div class="space-y-6">
                            <div>
                                <flux:heading size="lg">Delete college?</flux:heading>
                                <flux:text class="mt-2">
                                    <p>You're about to delete this college.</p>
                                    <p>This action cannot be reversed.</p>
                                </flux:text>
                            </div>
                            <div class="flex gap-2">
                                <flux:spacer/>
                                <flux:modal.close>
                                    <flux:button variant="ghost">Cancel</flux:button>
                                </flux:modal.close>
                                <flux:button type="submit" variant="danger" icon="trash"
                                             wire:click="delete({{ $college->id }})"></flux:button>
                            </div>
                        </div>
                    </flux:modal>
                </div>
            @endforeach
        </div>
    @else
        {{-- Table view --}}
        <flux:table :paginate="$this->collegeslist" class="w-full">
            <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
                <flux:table.column>College Name</flux:table.column>
                <flux:table.column>Code</flux:table.column>
                <flux:table.column>Firm</flux:table.column>
                <flux:table.column>Phone</flux:table.column>
                <flux:table.column>Email</flux:table.column>
                <flux:table.column>City</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->collegeslist as $college)
                    <flux:table.row :key="$college->id" class="border-b">
                        <flux:table.cell>
                            <div class="flex gap-2">
                                <div>
                                    <div class="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center border border-blue-300 shadow-sm">
                                        <flux class="w-8 h-8 text-blue-600"/>
                                    </div>
                                </div>
                                <div>
                                    <flux:text class="font-bold">
                                        {{ $college->name }}
                                    </flux:text>
                                    <flux:text>
                                        {{ $college->city }}, {{ $college->state }} <br>
                                        Est. {{ $college->established_year }}
                                    </flux:text>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="font-mono text-sm bg-gray-100 px-2 py-1 rounded">{{ $college->code }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $college->firm->name ?? '-' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $college->phone }}
                        </flux:table.cell>
                        <flux:table.cell class="table-cell-wrap">
                            {{ $college->email }}
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $college->city }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:switch wire:click="toggleStatus({{ $college->id }})"
                                             :checked="!$college->is_inactive"/>
                                <span class="text-xs px-2 py-1 rounded {{ $college->is_inactive ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                    {{ $college->is_inactive ? 'Inactive' : 'Active' }}
                                </span>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex space-x-2">
                                <flux:button variant="primary" size="sm" icon="pencil"
                                             wire:click="edit({{ $college->id }})"></flux:button>
                                <flux:modal.trigger name="delete-college-{{ $college->id }}">
                                    <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                                </flux:modal.trigger>
                            </div>
                            <flux:modal name="delete-college-{{ $college->id }}" class="min-w-[22rem]">
                                <div class="space-y-6">
                                    <div>
                                        <flux:heading size="lg">Delete college?</flux:heading>
                                        <flux:text class="mt-2">
                                            <p>You're about to delete this college.</p>
                                            <p>This action cannot be reversed.</p>
                                        </flux:text>
                                    </div>
                                    <div class="flex gap-2">
                                        <flux:spacer/>
                                        <flux:modal.close>
                                            <flux:button variant="ghost">Cancel</flux:button>
                                        </flux:modal.close>
                                        <flux:button type="submit" variant="danger" icon="trash"
                                                     wire:click="delete({{ $college->id }})"></flux:button>
                                    </div>
                                </div>
                            </flux:modal>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
    {{-- Pagination --}}
    <div class="mt-6 flex justify-center">
        {{ $this->collegeslist->links() }}
    </div>

    <flux:modal name="mdl-college" @cancel="@this.resetForm()">
        <form wire:submit.prevent="{{ $editingId ?? false ? 'update' : 'store' }}">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ $editingId ?? false ? 'Edit' : 'Add' }} College</flux:heading>
                    <flux:subheading>Enter college information and details.</flux:subheading>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input label="College Name" wire:model.live="formData.name" required />
                    <flux:input label="College Code" wire:model.live="formData.code" required />
                    <flux:input type="number" label="Established Year" wire:model.live="formData.established_year" required />
                    <div class="md:col-span-2">
                        <flux:input label="Address" wire:model.live="formData.address" required />
                    </div>
                    <flux:input label="City" wire:model.live="formData.city" required />
                    <flux:input label="State" wire:model.live="formData.state" required />
                    <flux:input label="Country" wire:model.live="formData.country" required />
                    <flux:input label="Phone" wire:model.live="formData.phone" required />
                    <flux:input type="email" label="Email" wire:model.live="formData.email" required />
                    <flux:input type="url" label="Website (optional)" wire:model.live="formData.website" />
                    <div class="md:col-span-2">
                        <flux:checkbox label="Inactive" wire:model.live="formData.is_inactive" />
                    </div>
                </div>
                <div class="flex justify-end pt-2">
                    <flux:button type="submit" variant="primary">
                        {{ $editingId ?? false ? 'Update' : 'Save' }}
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>