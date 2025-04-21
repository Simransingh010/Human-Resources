<div class="w-full p-0 m-0">
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-calendar" class="flex justify-end">
            <flux:button variant="primary" icon="plus" class="bg-blue-500 text-white px-4 py-2 rounded-md">
                @if($isEditing)
                    Edit Holiday Calendar
                @else
                    New
                @endif
            </flux:button>
        </flux:modal.trigger>
    </div>
    <flux:separator class="mt-2 mb-2" />
    <flux:modal name="mdl-calendar" @close="resetForm" position="right" class="max-w-none">
        <form wire:submit.prevent="saveCalendar">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Holiday Calendar @else Add Holiday Calendar @endif
                    </flux:heading>
                    <flux:subheading>
                        Make changes to the holiday calendar details.
                    </flux:subheading>
                </div>

                <!-- Grid layout for form fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input label="Title" wire:model="calendarData.title" placeholder="Calendar Title"/>
                    <flux:input label="Description" wire:model="calendarData.description" placeholder="Calendar Description"/>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end pt-4">
                    <flux:button type="submit" variant="primary">
                        Save Changes
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <form wire:submit.prevent="applyFilters">
        <flux:heading level="3" size="lg">Filter Records</flux:heading>
        <flux:card size="sm" class="sm:p-2 !rounded-xl rounded-xl! mb-1 rounded-t-lg p-0 bg-zinc-50 hover:bg-zinc-50 dark:hover:bg-zinc-700">
            <div class="grid md:grid-cols-3 gap-2 items-end">
                <div class="">
                    <flux:select
                        variant="listbox"
                        searchable
                        multiple
                        placeholder="Calendars"
                        wire:model="filters.calendars"
                        wire:key="calendars-filter"
                    >
                        @foreach($this->listsForFields['calendars'] as $key => $value)
                            <flux:select.option value="{{ $key }}">{{ $value }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="flex flex-wrap gap-2">
                    <flux:select
                        variant="listbox"
                        multiple
                        placeholder="Status"
                        wire:model="filters.status"
                        wire:key="status-filter"
                    >
                        <flux:select.option value="0">Active</flux:select.option>
                        <flux:select.option value="1">Inactive</flux:select.option>
                    </flux:select>

                    <div class="min-w-[100px]">
                        <flux:button type="submit" variant="primary" class="w-full">Go</flux:button>
                    </div>
                    <div class="min-w-[100px]">
                        <flux:button variant="filled" class="w-full px-2" tooltip="Cancel Filter" icon="x-circle"
                                   wire:click="clearFilters()"></flux:button>
                    </div>
                </div>
            </div>
        </flux:card>
    </form>

    <flux:table :paginate="$this->calendarlist" class="w-full">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column>Title</flux:table.column>
            <flux:table.column>Description</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->calendarlist as $calendar)
                <flux:table.row :key="$calendar->id" class="border-b">
                    <flux:table.cell class="flex items-center gap-3">
                        {{ $calendar->title }}
                    </flux:table.cell>
                    <flux:table.cell class="items-center gap-3">
                        {{ $calendar->description }}
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:switch wire:model="calendarStatuses.{{ $calendar->id }}"
                                   wire:click="toggleStatus({{ $calendar->id }})" :checked="!$calendar->is_inactive"/>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex space-x-2">
                            <flux:dropdown>
                                <flux:button icon="ellipsis-vertical" size="sm"></flux:button>
                                <flux:menu>
                                    <flux:modal.trigger wire:click="showmodal_holidays({{ $calendar->id }})">
                                        <flux:menu.item icon="calendar" class="mt-0.5">Manage Holidays</flux:menu.item>
                                    </flux:modal.trigger>
                                </flux:menu>
                            </flux:dropdown>
                            <flux:button variant="primary" size="sm" icon="pencil"
                                       wire:click="fetchCalendar({{ $calendar->id }})"></flux:button>
                            <flux:modal.trigger name="delete-calendar-{{ $calendar->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>
                        <flux:modal name="delete-calendar-{{ $calendar->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete calendar?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this holiday calendar.</p>
                                        <p>This action cannot be reversed.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer/>
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                               wire:click="deleteCalendar({{ $calendar->id }})"></flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal name="add-holidays" title="Manage Holidays" class="p-10 max-w-none">
        @if ($selectedCalendarId)
            <livewire:hrms.holidays-meta.holidays :calendarId="$selectedCalendarId"
                                                          :wire:key="'add-holidays-'.$selectedCalendarId"/>
        @endif
    </flux:modal>
</div> 