<div>
    <flux:modal.trigger name="mdl-leave-request-log">
        <flux:button variant="primary" class="bg-blue-500 text-white dark:text-primary px-4 py-2 mb-4 rounded-md">
            @if($isEditing)
                Edit Leave Request Log
            @else
                Add Leave Request Log
            @endif
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="mdl-leave-request-log" position="right" @close="resetForm"
        class="max-w-none min-w-[360px] bg-neutral-100">
        <form wire:submit.prevent="saveLog">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Leave Request Log @else Add Leave Request Log @endif
                    </flux:heading>
                    <flux:subheading>
                        Manage leave request log details.
                    </flux:subheading>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="col-span-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                            Leave Request
                        </label>
                        <select wire:model="logData.emp_leave_request_id"
                            class="mt-1 py-2 pl-3 pr-3 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                            <option value="">Select Leave Request</option>
                            @foreach($this->leaveRequestsList as $request)
                                <option value="{{ $request['id'] }}">{{ $request['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-span-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                            Status
                        </label>
                        <select wire:model="logData.status"
                            class="mt-1 py-2 pl-3 pr-3 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                            <option value="">Select Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>

                    <div class="col-span-1">
                        <flux:input type="datetime-local" label="Status DateTime" 
                            wire:model="logData.status_datetime" required />
                    </div>

                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                            Remarks
                        </label>
                        <textarea wire:model="logData.remarks" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                            placeholder="Enter remarks"></textarea>
                    </div>
                </div>

                <div class="flex justify-end pt-4">
                    <flux:button type="submit" variant="primary">
                        Save Changes
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:table :paginate="$this->logsList" class="w-full">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column class="w-12">ID</flux:table.column>
            <flux:table.column>Leave Request</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'status'" :direction="$sortDirection"
                wire:click="sort('status')">Status</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'status_datetime'" :direction="$sortDirection"
                wire:click="sort('status_datetime')">Status DateTime</flux:table.column>
            <flux:table.column>Remarks</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->logsList as $log)
                <flux:table.row :key="$log->id" class="border-b">
                    <flux:table.cell>{{ $log->id }}</flux:table.cell>
                    <flux:table.cell>
                        {{ $log->emp_leave_request->employee->fname }} 
                        {{ $log->emp_leave_request->employee->lname }}
                        ({{ \Carbon\Carbon::parse($log->emp_leave_request->apply_from)->format('Y-m-d') }}
                        to {{ \Carbon\Carbon::parse($log->emp_leave_request->apply_to)->format('Y-m-d') }})
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" 
                            :color="$log->status === 'approved' ? 'green' : ($log->status === 'rejected' ? 'red' : 'yellow')" 
                            inset="top bottom">
                            {{ ucfirst($log->status) }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $log->status_datetime }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="max-w-xs truncate">{{ $log->remarks ?? '-' }}</div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-center space-x-2">
                            <flux:button variant="outline" size="sm" icon="pencil"
                                wire:click="fetchLog({{ $log->id }})">
                            </flux:button>
                            <flux:modal.trigger name="delete-log-{{ $log->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>

                        <flux:modal name="delete-log-{{ $log->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete Log?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this leave request log.</p>
                                        <p>This action cannot be reversed.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                        wire:click="deleteLog({{ $log->id }})">
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