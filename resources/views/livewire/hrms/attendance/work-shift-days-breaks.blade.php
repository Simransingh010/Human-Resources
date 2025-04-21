<div>
    <flux:modal.trigger name="mdl-shift-day-break">
        <flux:button variant="primary" class="bg-blue-500 mb-4 text-white px-4 py-2 rounded-md">
            @if($isEditing)
                Edit Shift Day Break
            @else
                Add Shift Day Break
            @endif
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="mdl-shift-day-break" @close="resetForm" position="right" class="max-w-none min-w-[360px] bg-neutral-100">
        <form wire:submit.prevent="saveBreak">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Shift Day Break @else Add Shift Day Break @endif
                    </flux:heading>
                    <flux:subheading>
                        Configure work shift day break settings.
                    </flux:subheading>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <flux:select
                            label="Work Shift Day"
                            wire:model="breakData.work_shift_day_id"
                    >
                        <option value="">Select Work Shift Day</option>
                        @foreach($this->workShiftDaysList as $id => $title)
                            <option value="{{ $id }}">{{ $title }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select
                            label="Work Break"
                            wire:model="breakData.work_break_id"
                    >
                        <option value="">Select Work Break</option>
                        @foreach($this->workBreaksList as $id => $title)
                            <option value="{{ $id }}">{{ $title }}</option>
                        @endforeach
                    </flux:select>
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

    <flux:table :paginate="$this->breaksList" class="w-full">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column class="w-12">ID</flux:table.column>
            <flux:table.column>Shift Day</flux:table.column>
            <flux:table.column>Break</flux:table.column>
            <flux:table.column>Break Time</flux:table.column>
{{--            <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection"--}}
{{--                               wire:click="sort('created_at')">Created At--}}
{{--            </flux:table.column>--}}
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->breaksList as $break)
                <flux:table.row :key="$break->id" class="border-b">
                    <flux:table.cell>{{ $break->id }}</flux:table.cell>
                    <flux:table.cell>
                        {{ $break->work_shift_day->work_shift->shift_title }} -
                        {{ \Carbon\Carbon::parse($break->work_shift_day->work_date)->format('Y-m-d') }}
                    </flux:table.cell>
                    <flux:table.cell>{{ $break->work_break->break_title }}</flux:table.cell>
                    <flux:table.cell>
                        {{ \Carbon\Carbon::parse($break->work_break->start_time)->format('H:i') }} -
                        {{ \Carbon\Carbon::parse($break->work_break->end_time)->format('H:i') }}
                    </flux:table.cell>
{{--                    <flux:table.cell>{{ $break->created_at->format('Y-m-d H:i') }}</flux:table.cell>--}}
                    <flux:table.cell>
                        <div class="flex justify-center space-x-2">
                            <flux:button variant="ghost" size="sm" icon="pencil"
                                         wire:click="fetchBreak({{ $break->id }})"></flux:button>
                            <flux:modal.trigger name="delete-profile-{{ $break->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>
                        <flux:modal name="delete-profile-{{ $break->id }}" class="min-w-[22rem]">
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
                                    <flux:button type="submit" variant="danger" icon="trash" wire:click="deleteBreak({{ $break->id }})"></flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div> 