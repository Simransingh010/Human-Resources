<div>
    <flux:modal.trigger name="mdl-shift-day">
        <flux:button variant="primary" class="bg-blue-500 mb-4 text-white px-4 py-2 rounded-md">
            @if($isEditing)
                Edit Shift Day
            @else
                Add Shift Day
            @endif
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="mdl-shift-day" @close="resetForm" class="max-w-none min-w-[360px] bg-neutral-100">
        <form wire:submit.prevent="saveShiftDay">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Shift Day @else Add Shift Day @endif
                    </flux:heading>
                    <flux:subheading>
                        Configure work shift day settings.
                    </flux:subheading>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <flux:select
                            label="Work Shift"
                            wire:model="shiftDayData.work_shift_id"
                    >
                        <option value="">Select Work Shift</option>
                        @foreach($this->workShiftsList as $id => $title)
                            <option value="{{ $id }}">{{ $title }}</option>
                        @endforeach
                    </flux:select>

                    <flux:input
                            label="Work Date"
                            type="date"
                            wire:model="shiftDayData.work_date"
                    />

                    <flux:input
                            label="Day Status"
                            wire:model="shiftDayData.day_status"
                            placeholder="Enter day status"
                    />

                    <flux:input
                            label="Start Time"
                            type="time"
                            wire:model="shiftDayData.start_time"
                    />

                    <flux:input
                            label="End Time"
                            type="time"
                            wire:model="shiftDayData.end_time"
                    />
                </div>
                <div class="flex">
                    <flux:spacer/>
                    <flux:button type="submit" variant="primary">
                        Save Changes
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:table :paginate="$this->shiftDaysList" class="w-full">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column class="w-12">ID</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'work_date'" :direction="$sortDirection"
                               wire:click="sort('work_date')">Work Date
            </flux:table.column>
            <flux:table.column>Work Shift</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'start_time'" :direction="$sortDirection"
                               wire:click="sort('start_time')">Start Time
            </flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'end_time'" :direction="$sortDirection"
                               wire:click="sort('end_time')">End Time
            </flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->shiftDaysList as $shiftDay)
                <flux:table.row :key="$shiftDay->id" class="border-b">
                    <flux:table.cell>{{ $shiftDay->id }}</flux:table.cell>
                    <flux:table.cell>{{ \Carbon\Carbon::parse($shiftDay->work_date)->format('Y-m-d') }}</flux:table.cell>
                    <flux:table.cell>{{ $shiftDay->work_shift->shift_title }}</flux:table.cell>
                    <flux:table.cell>{{ \Carbon\Carbon::parse($shiftDay->start_time)->format('H:i') }}</flux:table.cell>
                    <flux:table.cell>{{ \Carbon\Carbon::parse($shiftDay->end_time)->format('H:i') }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" color="blue">{{ $shiftDay->day_status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-center space-x-2">
                            <flux:button variant="ghost" size="sm" icon="pencil"
                                         wire:click="fetchShiftDay({{ $shiftDay->id }})"></flux:button>
                            <flux:modal.trigger name="delete-profile-{{ $shiftDay->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>
                        <flux:modal name="delete-profile-{{ $shiftDay->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete project?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this project.</p>
                                        <p>This action cannot be reversed.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash" wire:click="deleteWorkShiftDay({{ $shiftDay->id }})"></flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div> 