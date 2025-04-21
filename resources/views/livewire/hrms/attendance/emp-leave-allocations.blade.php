<div>
    <flux:modal.trigger name="mdl-leave-allocation">
        <flux:button variant="primary" class="bg-blue-500 text-white dark:text-primary px-4 py-2 mb-4 rounded-md">
            Add Leave Allocation
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="mdl-leave-allocation" position="right" @close="resetForm"
        class="max-w-none min-w-[360px] bg-neutral-100">
        <form wire:submit.prevent="saveAllocation">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Leave Allocation @else Add Leave Allocation @endif
                    </flux:heading>
                    <flux:subheading>
                        Manage employee leave allocation details.
                    </flux:subheading>
                </div>

                <!-- Employee Selection (show only if not in employee context) -->
                @if(!$employeeId)
                    <div class="col-span-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                            Employee
                        </label>
                        <select wire:model="allocationData.employee_id"
                            class="mt-1 py-2 pl-3 pr-3 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                            <option value="">Select Employee</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee['id'] }}">{{ $employee['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <!-- Leave Type, and Template Selection -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="col-span-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                            Leave Type
                        </label>
                        <select wire:model="allocationData.leave_type_id"
                            class="mt-1 py-2 pl-3 pr-3 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                            <option value="">Select Leave Type</option>
                            @foreach($leaveTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->leave_title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-span-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                            Quota Template
                        </label>
                        <select wire:model="allocationData.leaves_quota_template_id"
                            class="mt-1 py-2 pl-3 pr-3 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                            <option value="">Select Template</option>
                            @foreach($quotaTemplates as $template)
                                <option value="{{ $template->id }}">{{ $template->template_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Days and Date Inputs -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:input type="number" label="Days Assigned" wire:model="allocationData.days_assigned" />
                    <flux:input type="date" label="Start Date" wire:model="allocationData.start_date" />
                    <flux:input type="date" label="End Date" wire:model="allocationData.end_date" />
                </div>

                <!-- Days Balance -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:input type="number" label="Days Balance" wire:model="allocationData.days_balance" />
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

    <flux:table :paginate="$this->allocationsList" class="">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column class="w-12">ID</flux:table.column>
            <flux:table.column>Employee</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'leave_type_id'" :direction="$sortDirection"
                wire:click="sort('leave_type_id')">Leave Type</flux:table.column>
            <flux:table.column>Quota Template</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'days_assigned'" :direction="$sortDirection"
                wire:click="sort('days_assigned')">Days Assigned</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'start_date'" :direction="$sortDirection"
                wire:click="sort('start_date')">Start Date</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'end_date'" :direction="$sortDirection"
                wire:click="sort('end_date')">End Date</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'days_balance'" :direction="$sortDirection"
                wire:click="sort('days_balance')">Days Balance</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->allocationsList as $allocation)
                <flux:table.row :key="$allocation->id" class="border-b">
                    <flux:table.cell>{{ $allocation->id }}</flux:table.cell>
                    <flux:table.cell class="flex items-center gap-3">
                        {{ $allocation->employee->fname . ' ' . $allocation->employee->lname }}
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" color="blue" inset="top bottom">
                            {{ $allocation->leave_type->leave_title }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $allocation->leaves_quota_template?->template_name ?? '-' }}
                    </flux:table.cell>
                    <flux:table.cell>{{ $allocation->days_assigned }}</flux:table.cell>
                    <flux:table.cell>{{ \Carbon\Carbon::parse($allocation->start_date)->format('Y-m-d') }}</flux:table.cell>
                    <flux:table.cell>{{ \Carbon\Carbon::parse($allocation->end_date)->format('Y-m-d') }}</flux:table.cell>
                    <flux:table.cell>{{ $allocation->days_balance }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-center space-x-2">
                            <flux:button variant="primary" size="sm" icon="pencil"
                                wire:click="fetchAllocation({{ $allocation->id }})">
                            </flux:button>
                            <flux:modal.trigger name="delete-leave-allocation-{{ $allocation->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>

                        <flux:modal name="delete-leave-allocation-{{ $allocation->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete leave allocation?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this leave allocation.</p>
                                        <p>This action cannot be reversed.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                        wire:click="deleteAllocation({{ $allocation->id }})">
                                    </flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>