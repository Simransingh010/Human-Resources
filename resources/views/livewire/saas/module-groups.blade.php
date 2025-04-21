<div>
    <!-- Modal trigger for both adding and editing -->
    <flux:modal.trigger name="mdl-module-group">
        <flux:button variant="primary" class="bg-blue-500 text-white px-4 py-2 mb-4 rounded-md">
            Add Module Group
        </flux:button>
    </flux:modal.trigger>

    <!-- Modal Start -->
    <flux:modal name="mdl-module-group" @cancel="resetForm" position="right" class="max-w-none">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Module Group @else Add Module Group @endif
                    </flux:heading>
                    <flux:subheading>
                        @if($isEditing) Update @else Add new @endif module group details.
                    </flux:subheading>
                </div>

                <!-- Grid layout for form fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select label="App" wire:model="formData.app_id" >
                        <flux:select.option value="">-- Select App --</flux:select.option>
                        <!-- static placeholder -->
                        @foreach($this->listsForFields['apps'] as $key => $value)
                            <flux:select.option value="{{ $key }}">{{$value}}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input
                        label="Name"
                        wire:model="formData.name"
                        placeholder="Module Group Name"
                    />

                </div>
                <flux:textarea
                        label="Description"
                        wire:model="formData.description"
                        placeholder="Module Group Description"
                />
                <flux:switch
                        wire:model.live="formData.is_inactive"
                        label="Mark as Inactive"
                />
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
            <flux:table.column>App</flux:table.column>
            <flux:table.column>Description</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->list as $moduleGroup)
                <flux:table.row :key="$moduleGroup->id" class="border-b">
                    <flux:table.cell class="table-cell-wrap">{{ $moduleGroup->name }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $moduleGroup->app->name }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $moduleGroup->description }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:switch
                            wire:model="statuses.{{ $moduleGroup->id }}"
                            wire:click="toggleStatus({{ $moduleGroup->id }})"
                        />
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex space-x-2">
                            <flux:button
                                variant="primary"
                                size="sm"
                                icon="pencil"
                                wire:click="edit({{ $moduleGroup->id }})"
                            />

                            <flux:modal.trigger name="delete-{{ $moduleGroup->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"/>
                            </flux:modal.trigger>
                        </div>

                        <!-- Delete Modal -->
                        <flux:modal name="delete-{{ $moduleGroup->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete Module Group?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this module group. This action cannot be undone.</p>
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
                                        wire:click="delete({{ $moduleGroup->id }})"
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