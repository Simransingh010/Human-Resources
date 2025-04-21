<div>
    <!-- Modal trigger for both adding and editing -->
    <flux:modal.trigger name="mdl-version">
        <flux:button variant="primary" class="bg-blue-500 text-white px-4 py-2 mb-4 rounded-md">
            Add Version
        </flux:button>
    </flux:modal.trigger>

    <!-- Modal Start -->
    <flux:modal name="mdl-version" @cancel="resetForm" position="right" class="max-w-none">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Version @else Add Version @endif
                    </flux:heading>
                    <flux:subheading>
                        @if($isEditing) Update @else Add new @endif version details.
                    </flux:subheading>
                </div>

                <!-- Grid layout for form fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input
                        label="Name"
                        wire:model="formData.name"
                        placeholder="Version Name"
                    />
                    <flux:input
                        label="Code"
                        wire:model="formData.code"
                        placeholder="Version Code"
                    />
                    <flux:select
                            label="Device Type"
                            wire:model="formData.device_type"
                    >
                        <option value="">Select Device Type</option>
                        @foreach($this->listsForFields['device_type'] as $key => $value)
                            <option value="{{ $value }}">{{ $value }}</option>
                        @endforeach
                    </flux:select>
                    <flux:input
                        label="Major Version"
                        wire:model="formData.major_version"
                        placeholder="Major Version Number"
                    />
                    <flux:input
                        label="Minor Version"
                        wire:model="formData.minor_version"
                        placeholder="Minor Version Number"
                    />
                    <flux:textarea
                        label="Description"
                        wire:model="formData.description"
                        placeholder="Version Description"
                        class="col-span-2"
                    />
                    <flux:switch
                        wire:model.live="formData.is_active"
                        label="Mark as Active"
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
            <flux:table.column>Version</flux:table.column>
            <flux:table.column>Description</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->list as $version)
                <flux:table.row :key="$version->id" class="border-b">
                    <flux:table.cell class="table-cell-wrap">{{ $version->name }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $version->code }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">
                        <div class="flex items-center gap-1">
                            @if($version->major_version)
                                <flux:badge color="blue" size="sm">{{ $version->major_version }}</flux:badge>
                            @endif
                            @if($version->minor_version)
                                <flux:badge color="gray" size="sm">{{ $version->minor_version }}</flux:badge>
                            @endif
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $version->description }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:switch
                            wire:model="statuses.{{ $version->id }}"
                            wire:click="toggleStatus({{ $version->id }})"
                        />
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex space-x-2">
                            <flux:button
                                variant="primary"
                                size="sm"
                                icon="pencil"
                                wire:click="edit({{ $version->id }})"
                            />
                            <flux:modal.trigger name="delete-{{ $version->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"/>
                            </flux:modal.trigger>
                        </div>

                        <!-- Delete Modal -->
                        <flux:modal name="delete-{{ $version->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete Version?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this version. This action cannot be undone.</p>
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
                                        wire:click="delete({{ $version->id }})"
                                    />
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div> 