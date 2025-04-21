<div>
    <flux:modal.trigger name="mdl-emp-shift">
        <flux:button variant="primary" class="bg-blue-500 mb-4 text-white px-4 py-2 rounded-md">
            @if($isEditing)
                Edit Employee Work Shift
            @else
                Add Employee Work Shift
            @endif
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="mdl-emp-shift" @close="resetForm" class="max-w-none min-w-[360px] bg-neutral-100">
        <form wire:submit.prevent="saveShift">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Employee Work Shift @else Add Employee Work Shift @endif
                    </flux:heading>
                    <flux:subheading>
                        Configure employee work shift settings.
                    </flux:subheading>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <flux:select
                            label="Work Shift"
                            wire:model="shiftData.work_shift_id"
                    >
                        <option value="">Select Work Shift</option>
                        @foreach($this->workShiftsList as $id => $title)
                            <option value="{{ $id }}">{{ $title }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select
                            label="Employee"
                            wire:model="shiftData.employee_id"
                    >
                        <option value="">Select Employee</option>
                        @foreach($this->employeesList as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>

                    <flux:input
                            type="date"
                            label="Start Date"
                            wire:model="shiftData.start_date"
                    />

                    <flux:input
                            type="date"
                            label="End Date"
                            wire:model="shiftData.end_date"
                    />
                </div>

                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary">
                        Save Changes
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:table :paginate="$this->shiftsList" class="">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column>ID</flux:table.column>
            <flux:table.column>Employee</flux:table.column>
            <flux:table.column>Work Shift</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'start_date'" :direction="$sortDirection"
                               wire:click="sort('start_date')">Start Date
            </flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'end_date'" :direction="$sortDirection"
                               wire:click="sort('end_date')">End Date
            </flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->shiftsList as $shift)
                <flux:table.row :key="$shift->id" class="border-b">
                    <flux:table.cell>{{ $shift->id }}</flux:table.cell>
                    <flux:table.cell>{{ $shift->employee->fname }} {{ $shift->employee->lname }}</flux:table.cell>
                    <flux:table.cell>{{ $shift->work_shift->shift_title }}</flux:table.cell>
                    <flux:table.cell>{{ \Carbon\Carbon::parse($shift->start_date)->format('Y-m-d') }}</flux:table.cell>
                    <flux:table.cell>{{ \Carbon\Carbon::parse($shift->end_date)->format('Y-m-d') }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-center space-x-2">
                            <flux:button variant="outline" size="sm" icon="pencil"
                                         wire:click="fetchShift({{ $shift->id }})"></flux:button>
                            <flux:modal.trigger name="delete-shift-{{ $shift->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>

                        <flux:modal name="delete-shift-{{ $shift->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete Work Shift Assignment?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this employee work shift assignment.</p>
                                        <p>This action cannot be reversed.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer/>
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