<div xmlns:flux="http://www.w3.org/1999/html">
    <div class="flex justify-between mt-2">
        <div>
            <flux:heading size="lg" class="flex">
                <flux:icon.calendar />
                Work Shift Days ({{ $workShift->shift_title }})
            </flux:heading>
            <flux:subheading>
                Configure work shift day settings.
            </flux:subheading>
        </div>
        <div>
            <flux:modal.trigger name="mdl-day">
                <flux:button icon="plus" variant="primary" icon="plus" wire:click="resetForm()"
                             class="bg-blue-500 text-white mt-2 rounded-md">
                    New
                </flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    <flux:modal name="mdl-day" @close="resetForm" position="right" class="max-w-none">
        <form wire:submit.prevent="saveDay">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg" class="flex">
                        <flux:icon.calendar />
                        @if($isEditing) Edit Day @else New Day @endif ({{ $workShift->shift_title }})
                    </flux:heading>
                    <flux:subheading>
                        Configure work shift day details.
                    </flux:subheading>
                </div>
                <flux:separator/>

                <!-- Form Fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <flux:select label="Work Shift Day Status" wire:model="dayData.work_shift_day_status_id">
                        <option value="">Select Day Status</option>
                        @foreach($this->workshiftdayStatusList as $StatusList)
                            <option value="{{ $StatusList->id }}">{{ $StatusList->day_status_label }}</option>
                        @endforeach
                    </flux:select>
                    <flux:input type="date" label="Work Date" wire:model="dayData.work_date" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input type="time" label="Start Time" wire:model="dayData.start_time" />
                    <flux:input type="time" label="End Time" wire:model="dayData.end_time" />
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

    <flux:separator class="mb-3 mt-3" />

    <flux:table>
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column sortable :sorted="$sortBy === 'work_date'" :direction="$sortDirection"
                wire:click="sort('work_date')">Work Date</flux:table.column>
            <flux:table.column>Day Status</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'start_time'" :direction="$sortDirection"
                wire:click="sort('start_time')">Start Time</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'end_time'" :direction="$sortDirection"
                wire:click="sort('end_time')">End Time</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->daysList as $day)
                <flux:table.row :key="$day->id" class="border-b">
                    <flux:table.cell>
                        <flux:badge size="sm" color="blue" inset="top bottom">
                            {{ date('d M Y', strtotime($day->work_date)) }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $day->day_status->day_status_label ?? 'N/A' }}</flux:table.cell>
                    <flux:table.cell>{{ $day->start_time ? date('H:i', strtotime($day->start_time)) : 'N/A' }}</flux:table.cell>
                    <flux:table.cell>{{ $day->end_time ? date('H:i', strtotime($day->end_time)) : 'N/A' }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-center space-x-2">
                            <flux:button variant="primary" size="sm" icon="pencil"
                                wire:click="fetchDay({{ $day->id }})"></flux:button>
                            <flux:modal.trigger name="delete-day-{{ $day->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>

                        <flux:modal name="delete-day-{{ $day->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete day?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this work shift day.</p>
                                        <p>This action cannot be reversed.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                        wire:click="deleteDay({{ $day->id }})"></flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div> 