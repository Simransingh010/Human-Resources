<div>
    <flux:modal.trigger name="mdl-leave-type">
        <flux:button variant="primary" class="bg-blue-500 text-white mb-4 px-4 py-2 rounded-md">
            @if($isEditing)
                Edit Leave Type
            @else
                Add Leave Type
            @endif
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="mdl-leave-type" @close="resetForm" position="right" class="max-w-none min-w-[360px] ">
        <form wire:submit.prevent="saveLeaveType">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Leave Type @else Add Leave Type @endif
                    </flux:heading>
                    <flux:subheading>
                        Configure leave type settings.
                    </flux:subheading>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="col-span-1">
                        <flux:input label="Leave Title" wire:model="leaveTypeData.leave_title"
                            placeholder="Enter leave title" />
                    </div>
                    <div class="col-span-1">
                        <flux:input label="Leave Code" wire:model="leaveTypeData.leave_code"
                            placeholder="Enter leave code" />
                    </div>
                    <div class="col-span-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                            Leave Nature
                        </label>
                        <select wire:model="leaveTypeData.leave_nature"
                            class="mt-1 py-2 pl-3 pr-3 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                            @foreach(App\Models\Hrms\LeaveType::LEAVE_NATURE_SELECT as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-span-1">
                        <flux:input label="Maximum Days" type="number" wire:model="leaveTypeData.max_days"
                            placeholder="Enter maximum days allowed" />
                    </div>
                    <div class="col-span-1">
                        <div class="space-y-2 mt-4">
                            <flux:checkbox label="Carry Forward" wire:model="leaveTypeData.carry_forward" />
                            <flux:checkbox label="Encashable" wire:model="leaveTypeData.encashable" />
                        </div>
                    </div>
                </div>
                <div class="col-span-1">
                    <flux:textarea label="Description" wire:model="leaveTypeData.leave_desc"
                        placeholder="Enter leave description" />
                </div>
                <div class="col-span-3">
                    <div class="flex">
                        <flux:spacer />
                        <flux:button type="submit" variant="primary">
                            Save Changes
                        </flux:button>
                    </div>
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:table :paginate="$this->leaveTypesList" class="w-full">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column class="w-12">ID</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'leave_title'" :direction="$sortDirection"
                wire:click="sort('leave_title')">Leave Title
            </flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'leave_code'" :direction="$sortDirection"
                wire:click="sort('leave_code')">Leave Code
            </flux:table.column>
            <flux:table.column>Leave Nature</flux:table.column>
            <flux:table.column>Max Days</flux:table.column>
            <flux:table.column>Features</flux:table.column>
            <flux:table.column>Description</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->leaveTypesList as $leaveType)
                <flux:table.row :key="$leaveType->id" class="border-b">
                    <flux:table.cell class="">{{ $leaveType->id }}</flux:table.cell>
                    <flux:table.cell>{{ $leaveType->leave_title }}</flux:table.cell>
                    <flux:table.cell>{{ $leaveType->leave_code }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$leaveType->leave_nature === 'paid' ? 'green' : 'yellow'"
                            inset="top bottom">
                            {{ $leaveType::LEAVE_NATURE_SELECT[$leaveType->leave_nature] }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $leaveType->max_days }}</flux:table.cell>
                    <flux:table.cell>{{ $leaveType->leave_desc }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-center space-x-2">
                            <flux:button variant="ghost" size="sm" icon="pencil"
                                wire:click="fetchLeaveType({{ $leaveType->id }})"></flux:button>
                            <flux:modal.trigger name="delete-profile-{{ $leaveType->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>
                        <flux:modal name="delete-profile-{{ $leaveType->id }}" class="min-w-[22rem]">
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
                                    <flux:button type="submit" variant="danger" icon="trash"
                                        wire:click="deleteLeaveType({{ $leaveType->id }})"></flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>