<div class="w-full p-0 m-0">
    <!-- Heading Start -->
    <div class="flex justify-between">
        <div class="p-0 m-0">
            <div class="mt-3">
                <flux:heading size="xl" class="mb-1">Actions</flux:heading>
                <flux:text class="mt-2">Manage system actions and their configurations</flux:text>
            </div>
        </div>
        <flux:modal.trigger name="mdl-action" class="flex justify-end">
            <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">
                New
            </flux:button>
        </flux:modal.trigger>
    </div>
    <flux:separator class="mt-2 mb-2"/>
    <!-- Heading End -->

    <!-- Filters Start -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
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
        <div class="flex justify-between">
            <flux:select searchable
                    label="Filter by Component"
                    wire:model.live="filters.search_component_id"
            >
                <option value="">All Components</option>
                @foreach($this->listsForFields['components'] as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </flux:select>
            <div class="min-w-[100px] flex justify-end">
                <flux:button variant="filled" class=" px-2 mt-6" tooltip="Cancel Filter" icon="x-circle"
                             wire:click="clearFilters()"></flux:button>
            </div>
        </div>
    </div>
    <!-- Filters End -->

    <!-- Modal Start -->
    <flux:modal name="mdl-action" @cancel="resetForm" position="right" class="max-w-none">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Action @else Add Action @endif
                    </flux:heading>
                    <flux:subheading>
                        @if($isEditing) Update @else Add new @endif action details.
                    </flux:subheading>
                </div>

                <!-- Grid layout for form fields -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:select
                            label="Component"
                            wire:model="formData.component_id"
                            required
                    >
                        <option value="">Select Component</option>
                        @foreach($this->listsForFields['components'] as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select
                        label="Action Type"
                        wire:model="formData.action_type"
                        required
                    >
                        <option value="">Select Action Type</option>
                        @foreach($actionTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                    <flux:input
                            label="Name"
                            wire:model="formData.name"
                            placeholder="Action Name"
                    />
                    <flux:input
                            label="Code"
                            wire:model="formData.code"
                            placeholder="Action Code"
                    />

                    <flux:input
                            label="Icon"
                            wire:model="formData.icon"
                            placeholder="Icon Name"
                    />
                    <flux:input
                            label="Color"
                            wire:model="formData.color"
                            placeholder="Color Code"
                    />
                    <flux:input
                            label="Tooltip"
                            wire:model="formData.tooltip"
                            placeholder="Tooltip Text"
                    />
                    <flux:input
                            type="number"
                            label="Order"
                            wire:model="formData.order"
                            placeholder="Display Order"
                    />
                    <flux:input
                            label="Badge"
                            wire:model="formData.badge"
                            placeholder="Badge Text"
                    />
                    <flux:input
                            type="number"
                            label="Action Cluster ID"
                            wire:model="formData.actioncluster_id"
                            placeholder="Action Cluster ID"
                    />
                    <flux:textarea
                            label="Description"
                            wire:model="formData.description"
                            placeholder="Action Description"
                    />
                    <flux:textarea
                            label="Custom CSS"
                            wire:model="formData.custom_css"
                            placeholder="Custom CSS"
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
    <!-- Modal End -->

    <!-- Table Start-->
    <flux:table :paginate="$this->list" class="">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column>Name</flux:table.column>
            <flux:table.column>Code</flux:table.column>
            <flux:table.column>Description</flux:table.column>
            <flux:table.column>Icon</flux:table.column>
            <flux:table.column>Component</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->list as $rec)
                <flux:table.row :key="$rec->id" class="border-b">
                    <flux:table.cell class="table-cell-wrap">{{ $rec->name }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->code }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->description }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->icon }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->component->name }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:switch
                                wire:model="statuses.{{ $rec->id }}"
                                wire:click="toggleStatus({{ $rec->id }})"
                        />
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex space-x-2">
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
                        <flux:modal name="delete-{{ $rec->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete Action ?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this module. This action cannot be undone.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer/>
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                                 wire:click="delete({{ $rec->id }})"/>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
    <!-- Table End-->
</div> 