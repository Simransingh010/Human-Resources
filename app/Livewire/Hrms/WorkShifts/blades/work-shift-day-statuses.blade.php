<div>
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-status" class="flex justify-end">
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
            label="Search by Label"
            wire:model.live="filters.search_label"
            placeholder="Search by label..."
            class="w-48"
        />
        <flux:input
            label="Search by Code"
            wire:model.live="filters.search_code"
            placeholder="Search by code..."
            class="w-48"
        />
        <div class="relative w-48">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 py-0.5 mb-1">Filter by Work Shift</label>
            <select
                wire:model.live="filters.search_shift"
                class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm px-2 py-2"
            >
                <option value="">Select Work Shift</option>
                @foreach($listsForFields['work_shifts'] ?? [] as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end">
            <flux:button variant="filled" class="px-2" tooltip="Cancel Filter" icon="x-circle"
                         wire:click="clearFilters()"></flux:button>
        </div>
    </div>
    <!-- Filters End -->

    <!-- Modal Start -->
    <flux:modal name="mdl-status" @cancel="resetForm" position="right" class="max-w-none" variant="flyout">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Day Status @else Add Day Status @endif
                    </flux:heading>
                    <flux:subheading>
                        @if($isEditing) Update @else Add new @endif day status details.
                    </flux:subheading>
                </div>

                <!-- Grid layout for form fields -->
                <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
                    <div class="relative">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Work Shift</label>
                        <select
                            wire:model="formData.work_shift_id"
                            class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm px-2 py-2"
                        >
                            <option value="">Select Work Shift</option>
                            @foreach($listsForFields['work_shifts'] ?? [] as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <flux:input label="Status Code" wire:model="formData.day_status_code" placeholder="Status Code"/>
                    <flux:input label="Status Label" wire:model="formData.day_status_label" placeholder="Status Label"/>
                    <flux:textarea label="Description" wire:model="formData.day_status_desc" placeholder="Status Description"/>
                    <flux:input 
                        label="Paid Percent" 
                        wire:model="formData.paid_percent" 
                        type="number" 
                        min="0" 
                        max="100" 
                        step="1"
                        placeholder="100"
                    />
                    <flux:switch wire:model.live="formData.count_as_working_day" label="Count as Working Day"/>
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
            <flux:table.column>Work Shift</flux:table.column>
            <flux:table.column>Code</flux:table.column>
            <flux:table.column>Label</flux:table.column>
            <flux:table.column>Description</flux:table.column>
            <flux:table.column>Paid %</flux:table.column>
            <flux:table.column>Working Day</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->list as $rec)
                <flux:table.row :key="$rec->id" class="border-b">
                    <flux:table.cell class="table-cell-wrap">{{ $rec->work_shift->shift_title }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->day_status_code }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->day_status_label }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->day_status_desc }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->paid_percent }}%</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->count_as_working_day ? 'Yes' : 'No' }}</flux:table.cell>
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
                                    <flux:heading size="lg">Delete Day Status?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this day status. This action cannot be undone.</p>
                                        <p class="mt-2 text-red-500">Note: Statuses assigned to work shift days cannot be deleted.</p>
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