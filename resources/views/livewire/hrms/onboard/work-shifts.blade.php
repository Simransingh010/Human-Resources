<div>
    <flux:modal.trigger name="mdl-shift" class="flex justify-end">
        <flux:button icon="plus" variant="primary" class="bg-blue-500 text-white px-4 mb-4 py-2 rounded-md">
            @if($isEditing)
                Edit Shift
            @else
                New
            @endif
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="mdl-shift" position="right" @close="resetForm" class="max-w-none min-w-[360px] bg-neutral-100">
        <form wire:submit.prevent="saveShift">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Shift @else Add Shift @endif
                    </flux:heading>
                    <flux:subheading>
                        Configure work shift settings.
                    </flux:subheading>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input
                            label="Shift Title"
                            wire:model="shiftData.shift_title"
                            placeholder="Enter shift title"
                    />
                </div>
                <flux:textarea
                        label="Description"
                        wire:model="shiftData.shift_desc"
                        placeholder="Enter shift description"
                />
                {{--                <flux:checkbox --}}
                {{--                    label="Inactive" --}}
                {{--                    wire:model="shiftData.is_inactive"--}}
                {{--                />--}}

                <div class="flex">
                    <flux:spacer/>
                    <flux:button type="submit" variant="primary">
                        Save Changes
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:table :paginate="$this->shiftsList" class="w-full">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column class="w-12">ID</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'shift_title'" :direction="$sortDirection"
                               wire:click="sort('shift_title')">Shift Title
            </flux:table.column>
            <flux:table.column>Description</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->shiftsList as $shift)
                <flux:table.row :key="$shift->id" class="border-b">
                    <flux:table.cell>{{ $shift->id }}</flux:table.cell>
                    <flux:table.cell>{{ $shift->shift_title }}</flux:table.cell>
                    <flux:table.cell>{{ $shift->shift_desc }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:switch
                                :value="!$shift->is_inactive"
                                wire:click="toggleStatus({{ $shift->id }})" :checked="!$shift->is_inactive"
                        />
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex space-x-2">
                            <!-- Dropdown for additional actions -->
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                                <flux:menu>
                                    <flux:menu.item wire:click="showmodal_shift_algo({{ $shift->id }})">
                                        <flux:icon.cog class="w-4 h-4" />
                                        Work Shift Algorithm
                                    </flux:menu.item>
                                    <flux:menu.item wire:click="showmodal_days_status({{ $shift->id }})">
                                        <flux:icon.calendar class="w-4 h-4" />
                                        Work Shift Days Status
                                    </flux:menu.item>
                                    <flux:menu.item wire:click="showmodal_days({{ $shift->id }})">
                                        <flux:icon.calendar class="w-4 h-4" />
                                        Work Shift Days
                                    </flux:menu.item>
                                    <flux:menu.item wire:click="showmodal_days_breaks({{ $shift->id }})">
                                        <flux:icon.clock class="w-4 h-4" />
                                        Work Shift Days Breaks
                                    </flux:menu.item>

                                </flux:menu>
                            </flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="pencil"
                                         wire:click="fetchShift({{ $shift->id }})"></flux:button>
                            <flux:modal.trigger name="delete-profile-{{ $shift->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>

                        <!-- Delete Confirmation Modal -->
                        <flux:modal name="delete-profile-{{ $shift->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete project?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this project.</p>
                                        <p>This action cannot be reversed.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer/>
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                                 wire:click="deleteWorkShift({{ $shift->id }})"></flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <!-- Work Shift Days Modal -->
    <flux:modal name="add-days" position="right" class="max-w-none">
        @if($selectedShiftId)
            <livewire:hrms.work-shift-meta.work-shift-days :key="'days-'.$selectedShiftId" :workShiftId="$selectedShiftId" />
        @endif
    </flux:modal>

    <!-- Work Shift Days Breaks Modal -->
    <flux:modal name="add-days-breaks" position="right" class="max-w-none">
        @if($selectedShiftId)
            <livewire:hrms.work-shift-meta.work-shift-days-breaks :key="'days-breaks-'.$selectedShiftId" :workShiftId="$selectedShiftId" />
        @endif
    </flux:modal>

    <!-- Work Shifts Algorithm Modal -->
    <flux:modal name="add-shift-algo" position="right" class="max-w-none">
        @if($selectedShiftId)
            <livewire:hrms.work-shift-meta.work-shifts-algos :key="'algo-'.$selectedShiftId" :workShiftId="$selectedShiftId" />
        @endif
    </flux:modal>

    <!-- Work Shifts Days Status Modal -->
    <flux:modal name="add-shift-days-status" position="right" class="max-w-none">
        @if($selectedShiftId)
            <livewire:hrms.work-shift-meta.work-shift-day-statuses :key="'days-status-'.$selectedShiftId" :workShiftId="$selectedShiftId" />
        @endif
    </flux:modal>
</div> 