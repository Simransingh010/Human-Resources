<div>
    <div class="flex justify-between mt-2">
        <div>
            <flux:heading size="lg" class="flex">
                <flux:icon.clock />
                Work Shift Day Breaks
            </flux:heading>
            <flux:subheading>
                Configure work shift day break assignments.
            </flux:subheading>
        </div>
        <div>
            <flux:modal.trigger name="mdl-shift-day-break">
                <flux:button icon="plus" variant="primary" icon="plus" wire:click="resetForm()"
                             class="bg-blue-500 text-white mt-2 rounded-md">
                    New
                </flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    <flux:modal name="mdl-shift-day-break" @close="resetForm" position="right" class="max-w-none">
        <form wire:submit.prevent="saveBreak">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg" class="flex">
                        <flux:icon.clock />
                        @if($isEditing) Edit Work Shift Day Break @else New Work Shift Day Break @endif
                    </flux:heading>
                    <flux:subheading>
                        Assign breaks to work shift days.
                    </flux:subheading>
                </div>
                <flux:separator/>

                <!-- Form Fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select label="Work Shift Day" wire:model="breakData.work_shift_day_id">
                        <option value="">Select Work Shift Day</option>
                        @foreach($this->workShiftDays as $day)
                            <option value="{{ $day->id }}">{{ $day->work_date }}({{ $day->day_status }})</option>
                        @endforeach
                    </flux:select>

                    <flux:select label="Break" wire:model="breakData.work_break_id">
                        <option value="">Select Break</option>
                        @foreach($this->workBreaks as $break)
                            <option value="{{ $break->id }}">{{ $break->break_title }}</option>
                        @endforeach
                    </flux:select>
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
            <flux:table.column sortable :sorted="$sortBy === 'work_shift_day_id'" :direction="$sortDirection"
                wire:click="sort('work_shift_day_id')">Work Shift Day</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'work_break_id'" :direction="$sortDirection"
                wire:click="sort('work_break_id')">Break</flux:table.column>
            <flux:table.column>Break Time</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->breaksList as $break)
                <flux:table.row :key="$break->id" class="border-b">
                    <flux:table.cell>
                        <flux:badge size="sm" color="blue" inset="top bottom">
                            {{ $break->work_shift_day->work_date }}({{ $break->work_shift_day->day_status }})
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $break->work_break->break_title }}</flux:table.cell>
                    <flux:table.cell>
                        {{ date('H:i', strtotime($break->work_break->start_time)) }} - 
                        {{ date('H:i', strtotime($break->work_break->end_time)) }}
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-center space-x-2">
                            <flux:button variant="primary" size="sm" icon="pencil"
                                wire:click="fetchBreak({{ $break->id }})"></flux:button>
                            <flux:modal.trigger name="delete-break-{{ $break->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>

                        <flux:modal name="delete-break-{{ $break->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete work shift day break?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this work shift day break assignment.</p>
                                        <p>This action cannot be reversed.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                        wire:click="deleteBreak({{ $break->id }})"></flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div> 