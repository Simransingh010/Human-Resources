<div class="w-full p-0 m-0">
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-app" class="flex justify-end">
            <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">
                New
            </flux:button>
        </flux:modal.trigger>
    </div>
    <flux:separator class="mt-2 mb-2"/>
    <!-- Heading End -->

    <!-- Filters Start -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-4">
        <flux:input
                label="Search by Name"
                wire:model.live="filters.search_name"
                placeholder="Search by name..."
        />
        <flux:input
                label="Search by Code"
                wire:model.live="filters.search_code"
                placeholder="Search by code..."
        />
        <flux:input
                label="Search by Route"
                wire:model.live="filters.search_route"
                placeholder="Search by route..."
        />
        <div class="flex justify-between">
            <flux:select
                    label="Filter by Status"
                    wire:model.live="filters.is_active"
            >
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </flux:select>
            <div class="min-w-[100px]">
                <flux:button variant="filled" class="w-full px-2 mt-6" tooltip="Cancel Filter" icon="x-circle"
                             wire:click="clearFilters()"></flux:button>
            </div>
        </div>
    </div>
    <!-- Filters End -->

    <!-- Modal Start -->
    <flux:modal name="mdl-app" @cancel="resetForm" position="right" class="max-w-none">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit App @else Add App @endif
                    </flux:heading>
                    <flux:subheading>
                        @if($isEditing) Update @else Add new @endif app details.
                    </flux:subheading>
                </div>

                <!-- Grid layout for form fields -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:input
                            label="Name"
                            wire:model="formData.name"
                            placeholder="App Name"
                    />
                    <flux:input
                            label="Code"
                            wire:model="formData.code"
                            placeholder="App Code"
                    />
                    <flux:input
                            label="Icon"
                            wire:model="formData.icon"
                            placeholder="Icon Class"
                    />
                    <flux:input
                            label="Route"
                            wire:model="formData.route"
                            placeholder="Route Path"
                    />
                    <flux:input
                            label="Color"
                            wire:model="formData.color"
                            type="color"
                    />
                    <flux:input
                            label="Tooltip"
                            wire:model="formData.tooltip"
                            placeholder="Tooltip Text"
                    />
                    <flux:input
                            label="Order"
                            wire:model="formData.order"
                            type="number"
                    />
                    <flux:input
                            label="Badge"
                            wire:model="formData.badge"
                            placeholder="Badge Text"
                    />
                    <flux:textarea
                            label="Description"
                            wire:model="formData.description"
                            placeholder="App Description"
                            class="col-span-3"
                    />
                    <flux:textarea
                            label="Custom CSS"
                            wire:model="formData.custom_css"
                            placeholder="Custom CSS"
                            class="col-span-3"
                    />
                    <flux:switch
                            wire:model.live="formData.is_inactive"
                            label="Mark as Inactive"
                    />
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end pt-4">
                    <flux:button type="submit" variant="primary">
                        Save
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <!-- Table Start-->
    <flux:table :paginate="$this->list" class="w-full">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column>Name</flux:table.column>
            <flux:table.column>Code</flux:table.column>
            <flux:table.column>Icon</flux:table.column>
            <flux:table.column>Route</flux:table.column>
            <flux:table.column>Order</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->list as $rec)
                <flux:table.row :key="$rec->id" class="border-b">
                    <flux:table.cell class="table-cell-wrap">
                        <div class="flex items-center gap-2">
                            @if($rec->color)
                                <div class="w-3 h-3 rounded-full" style="background-color: {{ $rec->color }}"></div>
                            @endif
                            {{ $rec->name }}
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->code }}</flux:table.cell>
                    <flux:table.cell>
                        @if($rec->icon)
                            <flux:icon :name="$rec->icon" class="w-5 h-5"/>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->route }}</flux:table.cell>
                    <flux:table.cell>{{ $rec->order }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:switch
                                wire:model="statuses.{{ $rec->id }}"
                                wire:click="toggleStatus({{ $rec->id }})"
                        />
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex space-x-2">
                            <flux:button wire:click="showModuleSync({{ $rec->id }})" color="zinc" size="xs">Modules
                            </flux:button>
                            <flux:button
                                    variant="primary"
                                    size="sm"
                                    icon="pencil"
                                    wire:click="edit({{ $rec->id }})"
                            />

                            <flux:modal.trigger name="delete-{{ $rec->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"/>
                            </flux:modal.trigger>
                        </div>

                        <!-- Delete Modal -->
                        <flux:modal name="delete-{{ $rec->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete App?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this app. This action cannot be undone.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer/>
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button
                                            type="submit"
                                            variant="danger"
                                            icon="trash"
                                            wire:click="delete({{ $rec->id }})"
                                    />
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal name="module-sync" title="Manage Modules" class="p-10">
        @if($selectedAppId)
            <livewire:saas.apps-meta.apps-module-sync :appId="$selectedAppId"
                                                      :wire:key="'module-sync-'.$selectedAppId"/>
        @endif
    </flux:modal>

</div>
