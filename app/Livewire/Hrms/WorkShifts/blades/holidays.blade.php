<div>
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-holiday" class="flex justify-end">
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
            label="Search by Date"
            wire:model.live="filters.search_date"
            type="date"
            placeholder="Search by date..."
            class="w-48"
        />
        <div class="flex items-end">
            <flux:button variant="filled" class="px-2" tooltip="Cancel Filter" icon="x-circle"
                         wire:click="clearFilters()"></flux:button>
        </div>
    </div>
    <!-- Filters End -->

    <!-- Modal Start -->
    <flux:modal name="mdl-holiday" @cancel="resetForm" position="right" class="max-w-none" variant="flyout">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Holiday @else Add Holiday @endif
                    </flux:heading>
                    <flux:subheading>
                        @if($isEditing) Update @else Add new @endif holiday details.
                    </flux:subheading>
                </div>

                <!-- Grid layout for form fields -->
                <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
                    <div class="relative">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Holiday Calendar</label>
                        <select
                            wire:model="formData.holiday_calendar_id"
                            class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm px-2 py-2"
                        >
                            <option value="">Select Holiday Calendar</option>
                            @foreach($listsForFields['holiday_calendars'] ?? [] as $id => $title)
                                <option value="{{ $id }}">{{ $title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <flux:input label="Title" wire:model="formData.holiday_title" placeholder="Holiday Title"/>
                    <flux:textarea label="Description" wire:model="formData.holiday_desc" placeholder="Holiday Description"/>
                    <flux:input label="Start Date" wire:model="formData.start_date" type="date"/>
                    <flux:input label="End Date" wire:model="formData.end_date" type="date"/>
                    <flux:switch wire:model.live="formData.repeat_annually" label="Repeat Annually"/>
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
            <flux:table.column>Calendar</flux:table.column>
            <flux:table.column>Start Date</flux:table.column>
            <flux:table.column>End Date</flux:table.column>
            <flux:table.column>Repeat Annually</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->list as $rec)
                <flux:table.row :key="$rec->id" class="border-b">
                    <flux:table.cell class="table-cell-wrap">{{ $rec->holiday_title }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->holiday_desc }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->holiday_calendar->title }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ date('Y-m-d', strtotime($rec->start_date)) }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->end_date ? date('Y-m-d', strtotime($rec->end_date)) : '-' }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->repeat_annually ? 'Yes' : 'No' }}</flux:table.cell>
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
                                    <flux:heading size="lg">Delete Holiday?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this holiday. This action cannot be undone.</p>
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
