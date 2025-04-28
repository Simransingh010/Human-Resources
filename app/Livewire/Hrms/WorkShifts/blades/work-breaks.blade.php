<div>
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-break" class="flex justify-end">
            <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">
              New
            </flux:button>
        </flux:modal.trigger>
    </div>
    <flux:separator class="mt-2 mb-2" />
    <!-- Heading End -->

    <!-- Filters Start -->
    <div class="flex flex-wrap gap-4 mb-4">
        <flux:input
            label="Search by Title"
            wire:model.live="filters.search_title"
            placeholder="Search by title..."
            class="w-48"
        />
        <flux:input
            label="Search by Time"
            wire:model.live="filters.search_time"
            type="time"
            placeholder="Search by time..."
            class="w-48"
        />
        <div class="flex items-end">
            <flux:button variant="filled" class="px-2" tooltip="Cancel Filter" icon="x-circle"
                         wire:click="clearFilters()"></flux:button>
        </div>
    </div>
    <!-- Filters End -->

    <!-- Modal Start -->
    <flux:modal name="mdl-break" @cancel="resetForm" position="right" class="max-w-none" variant="flyout">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Work Break @else Add Work Break @endif
                    </flux:heading>
                    <flux:subheading>
                        @if($isEditing) Update @else Add new @endif work break details.
                    </flux:subheading>
                </div>

                <!-- Grid layout for form fields -->
                <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
                    <flux:input label="Title" wire:model="formData.break_title" placeholder="Break Title"/>
                    <flux:textarea label="Description" wire:model="formData.break_desc" placeholder="Break Description"/>
                    <flux:input label="Start Time" wire:model="formData.start_time" type="time"/>
                    <flux:input label="End Time" wire:model="formData.end_time" type="time"/>
                    <flux:switch wire:model.live="formData.is_inactive" label="Mark as Inactive"/>
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
            <flux:table.column>Description</flux:table.column>
            <flux:table.column>Start Time</flux:table.column>
            <flux:table.column>End Time</flux:table.column>
            <flux:table.column>Duration</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->list as $rec)
                <flux:table.row :key="$rec->id" class="border-b">
                    <flux:table.cell class="table-cell-wrap">{{ $rec->break_title }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->break_desc }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->start_time->format('H:i') }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->end_time->format('H:i') }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->start_time->diffInMinutes($rec->end_time) }} minutes</flux:table.cell>
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

                        <!-- Delete Modal -->
                        <flux:modal name="delete-{{ $rec->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete Work Break?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this work break. This action cannot be undone.</p>
                                        <p class="mt-2 text-red-500">Note: Breaks assigned to work shifts cannot be deleted.</p>
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