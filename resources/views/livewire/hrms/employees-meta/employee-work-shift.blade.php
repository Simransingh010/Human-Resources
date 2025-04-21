<div>
    <div class="flex justify-between">
        <div>
            <flux:heading size="lg" class="flex">
                <flux:icon.calendar />
                Work Shifts ({{$this->employee->fname}} {{$this->employee->lname}} | {{$this->employee->email}})
            </flux:heading>
            <flux:subheading>
                Configure employee work shift assignments.
            </flux:subheading>
        </div>
        <div>
            <flux:modal.trigger name="mdl-shift">
                <flux:button variant="primary" icon="plus" wire:click="resetForm()"
                             class="bg-blue-500 text-white mt-2 rounded-md ml-4">
                    New
                </flux:button>
            </flux:modal.trigger>
        </div>
    </div>
    <flux:modal name="mdl-shift" @close="resetForm" position="right" class="max-w-none">
        <form wire:submit.prevent="saveShift">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg" class="flex">
                        <flux:icon.calendar />
                        @if($isEditing) Edit Work Shift @else New Work Shift @endif ({{$this->employee->fname}} {{$this->employee->lname}} | {{$this->employee->email}})
                    </flux:heading>
                    <flux:subheading>
                        Configure work shift details.
                    </flux:subheading>
                </div>
                <flux:separator/>
                
                <!-- Form Fields -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <flux:select label="Work Shift" wire:model="shiftData.work_shift_id">
                        <option value="">Select work shift</option>
                        @foreach($this->listsForFields['work_shifts'] as $id => $name)
                            <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input type="date" label="Start Date" wire:model="shiftData.start_date" />
                    <flux:input type="date" label="End Date" wire:model="shiftData.end_date" />
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
            <flux:table.column>Work Shift</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'start_date'" :direction="$sortDirection"
                wire:click="sort('start_date')">Start Date</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'end_date'" :direction="$sortDirection"
                wire:click="sort('end_date')">End Date</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->shiftsList as $shift)
                <flux:table.row :key="$shift->id" class="border-b">
                    <flux:table.cell>
                        <flux:badge size="sm" color="blue" inset="top bottom">
                            {{ $shift->work_shift->shift_title }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $shift->start_date ? date('d M Y', strtotime($shift->start_date)) : 'N/A' }}</flux:table.cell>
                    <flux:table.cell>{{ $shift->end_date ? date('d M Y', strtotime($shift->end_date)) : 'N/A' }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-center space-x-2">
                            <flux:button variant="primary" size="sm" icon="pencil"
                                wire:click="fetchShift({{ $shift->id }})"></flux:button>
                            <flux:modal.trigger name="delete-shift-{{ $shift->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>

                        <flux:modal name="delete-shift-{{ $shift->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete work shift?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this work shift assignment.</p>
                                        <p>This action cannot be reversed.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                        wire:click="deleteShift({{ $shift->id }})"></flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div> 