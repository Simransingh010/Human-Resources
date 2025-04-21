<div>
    <!-- Modal trigger for both adding and editing -->
    <flux:modal.trigger name="mdl-app-module">
        <flux:button variant="primary" class="bg-blue-500 text-white px-4 py-2 mb-4 rounded-md">
            Add App Module
        </flux:button>
    </flux:modal.trigger>

    <!-- Modal Start -->
    <flux:modal name="mdl-app-module" @cancel="resetForm" position="right" class="max-w-none">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit App Module @else Add App Module @endif
                    </flux:heading>
                    <flux:subheading>
                        @if($isEditing) Update @else Add new @endif app module details.
                    </flux:subheading>
                </div>

                <!-- Grid layout for form fields -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:select label="App" wire:model="formData.app_id">
                        <flux:select.option value="">-- Select App --</flux:select.option>
                        @foreach($this->listsForFields['apps'] as $key => $value)
                            <flux:select.option value="{{ $key }}">{{$value}}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select label="Module Group" wire:model="formData.module_group_id">
                        <flux:select.option value="">-- Select Module Group --</flux:select.option>
                        @foreach($this->listsForFields['moduleGroups'] as $key => $value)
                            <flux:select.option value="{{ $key }}">{{$value}}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input
                        label="Name"
                        wire:model="formData.name"
                        placeholder="App Module Name"
                    />
                    <flux:input
                        label="Code"
                        wire:model="formData.code"
                        placeholder="App Module Code"
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
                </div>
                <flux:textarea
                    label="Description"
                    wire:model="formData.description"
                    placeholder="App Module Description"
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
            <flux:table.column>App</flux:table.column>
            <flux:table.column>Module Group</flux:table.column>
            <flux:table.column>Order</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->list as $appModule)
                <flux:table.row :key="$appModule->id" class="border-b">
                    <flux:table.cell class="table-cell-wrap">
                        <div class="flex items-center gap-2">
                            @if($appModule->color)
                                <div class="w-3 h-3 rounded-full" style="background-color: {{ $appModule->color }}"></div>
                            @endif
                            {{ $appModule->name }}
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>{{ $appModule->code }}</flux:table.cell>
                    <flux:table.cell>{{ $appModule->app->name }}</flux:table.cell>
                    <flux:table.cell>{{ $appModule->module_group?->name }}</flux:table.cell>
                    <flux:table.cell>{{ $appModule->order }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:switch
                            wire:model="statuses.{{ $appModule->id }}"
                            wire:click="toggleStatus({{ $appModule->id }})"
                        />
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex space-x-2">
                            <flux:button
                                variant="primary"
                                size="sm"
                                icon="pencil"
                                wire:click="edit({{ $appModule->id }})"
                            />

                            <flux:modal.trigger name="delete-{{ $appModule->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"/>
                            </flux:modal.trigger>
                        </div>

                        <!-- Delete Modal -->
                        <flux:modal name="delete-{{ $appModule->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete App Module?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this app module. This action cannot be undone.</p>
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
                                        wire:click="delete({{ $appModule->id }})"
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