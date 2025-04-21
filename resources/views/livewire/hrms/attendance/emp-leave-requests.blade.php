<div>
    <flux:modal.trigger name="mdl-leave-request">
        <flux:button variant="primary" class="bg-blue-500 text-white dark:text-primary px-4 py-2 mb-4 rounded-md">
            Add Leave Request
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="mdl-leave-request" position="right" @close="resetForm"
        class="max-w-none min-w-[360px] bg-neutral-100">
        <form wire:submit.prevent="saveLeaveRequest">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Leave Request @else Add Leave Request @endif
                    </flux:heading>
                    <flux:subheading>
                        Manage employee leave request details.
                    </flux:subheading>
                </div>

                <!-- Employee and Leave Type Selection -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    @if(!$employeeId)
                        <div class="col-span-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                                Employee
                            </label>
                            <select wire:model="leaveRequestData.employee_id"
                                class="mt-1 py-2 pl-3 pr-3 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                                <option value="">Select Employee</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee['id'] }}">{{ $employee['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="col-span-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                            Leave Type
                        </label>
                        <select wire:model="leaveRequestData.leave_type_id"
                            class="mt-1 py-2 pl-3 pr-3 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-black dark:text-white dark:bg-gray-700 dark:border-gray-600">
                            <option value="">Select Leave Type</option>
                            @foreach($leaveTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->leave_title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-span-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                            Status
                        </label>
                        <select wire:model="leaveRequestData.status"
                            class="mt-1 py-2 pl-3 pr-3 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                            @foreach(App\Models\Hrms\EmpLeaveRequest::STATUS_SELECT as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Date and Days Inputs -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:input type="date" label="From Date" wire:model="leaveRequestData.apply_from" />
                    <flux:input type="date" label="To Date" wire:model="leaveRequestData.apply_to" />
                    <flux:input type="number" label="Days" wire:model="leaveRequestData.apply_days" />
                </div>

                <!-- Reason Textarea -->
                <div class="grid grid-cols-1 gap-4">
                    <div class="col-span-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                            Reason
                        </label>
                        <textarea wire:model="leaveRequestData.reason" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                            placeholder="Enter reason for leave"></textarea>
                    </div>
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

    <flux:table :paginate="$this->leaveRequestsList" class="">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column class="w-12">ID</flux:table.column>
            <flux:table.column>Employee</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'leave_type_id'" :direction="$sortDirection"
                wire:click="sort('leave_type_id')">Leave Type</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'apply_from'" :direction="$sortDirection"
                wire:click="sort('apply_from')">From Date</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'apply_to'" :direction="$sortDirection"
                wire:click="sort('apply_to')">To Date</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'apply_days'" :direction="$sortDirection"
                wire:click="sort('apply_days')">Days</flux:table.column>
            <flux:table.column>Reason</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'status'" :direction="$sortDirection"
                wire:click="sort('status')">Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->leaveRequestsList as $request)
                <flux:table.row :key="$request->id" class="border-b">
                    <flux:table.cell>{{ $request->id }}</flux:table.cell>
                    <flux:table.cell class="flex items-center gap-3">
                        {{ $request->employee->fname . ' ' . $request->employee->lname }}
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" color="blue" inset="top bottom">
                            {{ $request->leave_type->leave_title }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ \Carbon\Carbon::parse($request->apply_from)->format('Y-m-d') }}</flux:table.cell>
                    <flux:table.cell>{{ \Carbon\Carbon::parse($request->apply_to)->format('Y-m-d') }}</flux:table.cell>
                    <flux:table.cell>{{ $request->apply_days }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="max-w-xs truncate">{{ $request->reason ?? '-' }}</div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$request->status === 'approved' ? 'green' : ($request->status === 'rejected' ? 'red' : 'yellow')" inset="top bottom">
                            {{ $request::STATUS_SELECT[$request->status] }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-center space-x-2">
                            <flux:button variant="primary" size="sm" icon="pencil"
                                wire:click="fetchLeaveRequest({{ $request->id }})">
                            </flux:button>
                            <flux:modal.trigger name="delete-leave-request-{{ $request->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>

                        <flux:modal name="delete-leave-request-{{ $request->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete leave request?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this leave request.</p>
                                        <p>This action cannot be reversed.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                        wire:click="deleteLeaveRequest({{ $request->id }})">
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