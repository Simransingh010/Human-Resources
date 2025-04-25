<div>
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-document-type" class="flex justify-end">
            <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">
                New
            </flux:button>
        </flux:modal.trigger>
    </div>
    <flux:separator class="mt-2 mb-2" />
    <!-- Heading End -->

    <!-- Filters Start -->
    <div class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:input
                label="Search by Title"
                wire:model.live="filters.search_title"
                placeholder="Search by title..."
            />
            <flux:input
                label="Search by Code"
                wire:model.live="filters.search_code"
                placeholder="Search by code..."
            />
            <div class="min-w-[100px] flex justify-end">
                <flux:button variant="filled" class="px-2 mt-6" tooltip="Cancel Filter" icon="x-circle"
                             wire:click="clearFilters()"></flux:button>
            </div>
        </div>
    </div>
    <!-- Filters End -->

    <!-- Modal Start -->
    <flux:modal name="mdl-document-type" @cancel="resetForm" position="right" class="max-w-none" variant="flyout">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Document Type @else Add Document Type @endif
                    </flux:heading>
                    <flux:subheading>
                        @if($isEditing) Update @else Add new @endif document type details.
                    </flux:subheading>
                </div>

                <!-- Grid layout for form fields -->
                <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
                    <flux:input 
                        label="Title" 
                        wire:model="formData.title" 
                        placeholder="Document Type Title"
                    />

                    <flux:input 
                        label="Code" 
                        wire:model="formData.code" 
                        placeholder="Document Type Code"
                    />

                    <flux:textarea 
                        label="Description" 
                        wire:model="formData.description" 
                        placeholder="Document Type Description"
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
    <flux:table :paginate="$this->list" class="w-full">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column>Title</flux:table.column>
            <flux:table.column>Code</flux:table.column>
            <flux:table.column>Description</flux:table.column>
            <flux:table.column>Firm</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->list as $docType)
                <flux:table.row :key="$docType->id" class="border-b">
                    <flux:table.cell class="table-cell-wrap">{{ $docType->title }}</flux:table.cell>
                    <flux:table.cell>{{ $docType->code }}</flux:table.cell>
                    <flux:table.cell>{{ Str::limit($docType->description, 50) }}</flux:table.cell>
                    <flux:table.cell>{{ $docType->firm?->name ?? '-' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:switch
                            wire:model="statuses.{{ $docType->id }}"
                            wire:click="toggleStatus({{ $docType->id }})"
                        />
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex space-x-2">
                            <flux:button variant="primary" size="sm" icon="pencil"
                                wire:click="edit({{ $docType->id }})"/>
                            <flux:modal.trigger name="delete-document-type-{{ $docType->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"/>
                            </flux:modal.trigger>
                        </div>

                        <!-- Delete Modal -->
                        <flux:modal name="delete-document-type-{{ $docType->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete Document Type?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this document type. This action cannot be undone.</p>
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
                                        wire:click="delete({{ $docType->id }})"/>
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
