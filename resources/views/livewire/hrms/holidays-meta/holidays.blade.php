<div>
    <div class="flex justify-between">
        <div>
            <flux:heading size="lg" class="flex">
                <flux:icon.calendar />
                Holidays ({{$this->calendar->title}})
            </flux:heading>
            <flux:subheading>
                Manage holiday calendar details.
            </flux:subheading>
        </div>
        <div>
            <flux:modal.trigger name="mdl-holiday">
                <flux:button variant="primary" icon="plus" wire:click="resetForm()"
                             class="bg-blue-500 text-white mt-2 rounded-md">
                    New
                </flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    <flux:modal name="mdl-holiday" @close="resetForm" position="right" class="max-w-none">
        <form wire:submit.prevent="saveHoliday">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg" class="flex">
                        <flux:icon.calendar />
                        @if($isEditing) Edit Holiday @else Add Holiday @endif ({{$this->calendar->title}})
                    </flux:heading>
                    <flux:subheading>
                        Manage holiday details.
                    </flux:subheading>
                </div>
                <flux:separator/>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input label="Holiday Title" wire:model="holidayData.holiday_title" placeholder="Holiday Title"/>
                    <flux:input label="Description" wire:model="holidayData.holiday_desc" placeholder="Holiday Description"/>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input type="date" label="Start Date" wire:model="holidayData.start_date"/>
                    <flux:input type="date" label="End Date" wire:model="holidayData.end_date"/>
                </div>

                <div class="space-y-4">
                    <flux:checkbox wire:model="holidayData.repeat_annually" label="Repeat Annually"/>
                </div>

                <div class="flex justify-end pt-4">
                    <flux:button type="submit" variant="primary">
                        Save Changes
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:separator class="mb-3 mt-3" />

    <flux:table class="">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column>Holiday Title</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'start_date'" :direction="$sortDirection"
                               wire:click="sort('start_date')">Start Date
            </flux:table.column>
            <flux:table.column>End Date</flux:table.column>
            <flux:table.column>Type</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->holidayslist as $holiday)
                <flux:table.row :key="$holiday->id" class="border-b">
                    <flux:table.cell class="flex items-center gap-3">
                        {{ $holiday->holiday_title }}
                    </flux:table.cell>
                    <flux:table.cell>{{ $holiday->start_date }}</flux:table.cell>
                    <flux:table.cell>{{ $holiday->end_date ? $holiday->end_date : '-' }}</flux:table.cell>
                    <flux:table.cell>
                        @if($holiday->repeat_annually)
                            <flux:badge size="sm" color="blue" inset="top bottom">Annual</flux:badge>
                        @else
                            <flux:badge size="sm" color="green" inset="top bottom">One-time</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center space-x-2">
                            <flux:switch wire:model="holidayStatuses.{{ $holiday->id }}"
                                         wire:click="update_rec_status({{$holiday->id}})"
                                         :checked="!$holiday->is_inactive"/>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-center space-x-2">
                            <flux:button variant="primary" size="sm" icon="pencil"
                                         wire:click="fetchHoliday({{ $holiday->id }})"></flux:button>
                            <flux:modal.trigger name="delete-holiday-{{ $holiday->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>
                        <flux:modal name="delete-holiday-{{ $holiday->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete Holiday?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this holiday.</p>
                                        <p>This action cannot be reversed.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer/>
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                                 wire:click="deleteHoliday({{ $holiday->id }})"></flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>
