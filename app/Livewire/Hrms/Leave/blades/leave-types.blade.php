<div>
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-leave-type" class="flex justify-end">
            <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">
                New
            </flux:button>
        </flux:modal.trigger>
    </div>
    <flux:separator class="mt-2 mb-2" />
    <!-- Heading End -->

    <!-- Filters Start -->
    <div class="grid lg:grid-cols-4 gap-4 mb-4">
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
        <div class="relative w-48">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 py-0.5 mb-1">Filter by Nature</label>
            <select
                wire:model.live="filters.search_nature"
                class="block w-full rounded-md border-gray-300 px-2 py-2 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
            >
                <option value="">All Nature</option>
                @foreach($listsForFields['leave_nature'] as $key => $value)
                    <option value="{{ $key }}">{{ $value }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[100px] flex justify-end">
            <flux:button variant="filled" class="px-2 mt-6" tooltip="Cancel Filter" icon="x-circle"
                         wire:click="clearFilters"></flux:button>
        </div>
    </div>
    <!-- Filters End -->

    <!-- Modal Start -->
    <flux:modal name="mdl-leave-type" @cancel="resetForm" class="max-w-none">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Leave Type @else Add Leave Type @endif
                    </flux:heading>
                    <flux:subheading>
                        @if($isEditing) Update @else Add new @endif leave type details.
                    </flux:subheading>
                </div>

                <!-- Grid layout for form fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input label="Leave Title" wire:model.live="formData.leave_title" placeholder="Enter leave title"/>
                    <flux:input label="Leave Code" wire:model.live="formData.leave_code" placeholder="Enter leave code"/>
                    <flux:select label="Leave Nature" wire:model.live="formData.leave_nature">
                        <option value="">Select Leave Nature</option>
                        @foreach($listsForFields['leave_nature'] as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                    </flux:select>
                    <flux:input label="Maximum Days" type="number" wire:model.live="formData.max_days" placeholder="Enter maximum days"/>
                    <div class="col-span-2">
                        <div class="space-y-2">
                            <flux:switch wire:model.live="formData.carry_forward" label="Carry Forward"/>
                            <flux:switch wire:model.live="formData.encashable" label="Encashable"/>
                        </div>
                    </div>
                    <div class="col-span-2">
                        <flux:textarea
                            label="Description"
                            wire:model.live="formData.leave_desc"
                            placeholder="Enter leave description"
                            rows="3"
                        />
                    </div>
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
            <flux:table.column>Title</flux:table.column>
            <flux:table.column>Code</flux:table.column>
            <flux:table.column>Nature</flux:table.column>
            <flux:table.column>Max Days</flux:table.column>
            <flux:table.column>Features</flux:table.column>
            <flux:table.column>Description</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->list as $rec)
                <flux:table.row :key="$rec->id" class="border-b">
                    <flux:table.cell>{{ $rec->leave_title }}</flux:table.cell>
                    <flux:table.cell>{{ $rec->leave_code }}</flux:table.cell>
                    <flux:table.cell>{{ $rec->leave_nature }}</flux:table.cell>
                    <flux:table.cell>{{ $rec->max_days }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex flex-col gap-1">
                            @if($rec->carry_forward)
                                <flux:badge size="sm" color="blue">Carry Forward</flux:badge>
                            @endif
                            @if($rec->encashable)
                                <flux:badge size="sm" color="green">Encashable</flux:badge>
                            @endif
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->leave_desc }}</flux:table.cell>
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

                        <!-- Delete Modal -->
                        <flux:modal name="delete-{{ $rec->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete Leave Type?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this leave type. This action cannot be undone.</p>
                                        <p class="mt-2 text-red-500">Note: Leave types with related records cannot be deleted.</p>
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