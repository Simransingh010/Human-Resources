<div class="space-y-6">
    <!-- Heading and Apply Leave Button -->
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold">My Leave Requests</h2>
        <div class="flex gap-2">
            <flux:input
                class="w-64"
                placeholder="Search by type or reason..."
                wire:model.live.debounce.300ms="search"
            />
           
                <flux:button variant="primary" icon="plus" wire:click="openApplyModal">Apply Leave</flux:button>
            
        </div>
    </div>

    <!-- Apply Leave Modal (content to be implemented) -->
    <flux:modal name="mdl-apply-leave" class="max-w-3xl">
        <form wire:submit.prevent="applyLeave">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Apply for Leave</flux:heading>
                    <flux:subheading>Fill in the details to apply for a leave.</flux:subheading>
                </div>

                <!-- Leave Duration Buttons -->
                <div class="flex gap-2 mb-2">
                    <flux:button
                        :variant="$applyForm['leave_age'] === 'single' ? 'primary' : 'secondary'"
                        wire:click="$set('applyForm.leave_age', 'single')"
                        type="button"
                    >Single Day</flux:button>
                    <flux:button
                        :variant="$applyForm['leave_age'] === 'multi' ? 'primary' : 'secondary'"
                        wire:click="$set('applyForm.leave_age', 'multi')"
                        type="button"
                    >Multi Day</flux:button>
                    <flux:button
                        :variant="$applyForm['leave_age'] === 'half' ? 'primary' : 'secondary'"
                        wire:click="$set('applyForm.leave_age', 'half')"
                        type="button"
                    >Half Day</flux:button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <flux:select
                            label="Leave Type"
                            wire:model.live="applyForm.leave_type_id"
                            required
                        >
                            <option value="">Select Leave Type</option>
                            @foreach($leaveTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->leave_title }}</option>
                            @endforeach
                        </flux:select>
                        @php
                            $leaveTypeId = data_get($applyForm, 'leave_type_id');
                            $bal = $leaveTypeId ? $leaveBalances->firstWhere('leave_type_id', $leaveTypeId) : null;
                        @endphp
                        @if($leaveTypeId && $bal)
                            <div class="text-xs mt-1 text-gray-600">
                                <span class="font-medium">Balance:</span>
                                {{ $bal->balance }} days (Allocated: {{ $bal->allocated_days }}, Consumed: {{ $bal->consumed_days }})
                            </div>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        @if($applyForm['leave_age'] === 'single')
                            <div class="flex-1">
                                <flux:input
                                    type="date"
                                    label="Date"
                                    wire:model.live="applyForm.apply_from"
                                    required
                                />
                            </div>
                        @elseif($applyForm['leave_age'] === 'multi')
                            <div class="flex-1">
                                <flux:input
                                    type="date"
                                    label="From"
                                    wire:model.live="applyForm.apply_from"
                                    required
                                />
                            </div>
                            <div class="flex-1">
                                <flux:input
                                    type="date"
                                    label="To"
                                    wire:model.live="applyForm.apply_to"
                                    required
                                />
                            </div>
                        @elseif($applyForm['leave_age'] === 'half')
                            <div class="flex-1">
                                <flux:input
                                    type="date"
                                    label="Date"
                                    wire:model.live="applyForm.apply_from"
                                    required
                                />
                            </div>
                            <div class="flex-1">
                                <flux:select
                                    label="Half Day Type"
                                    wire:model.live="applyForm.half_day_type"
                                    required
                                >
                                    <option value="">Select Half</option>
                                    <option value="first_half">First Half</option>
                                    <option value="second_half">Second Half</option>
                                </flux:select>
                            </div>
                        @endif
                    </div>
                </div>
                <div>
                    <flux:textarea
                        label="Reason"
                        wire:model.live="applyForm.reason"
                        rows="2"
                        maxlength="1000"
                    />
                </div>
                <div class="flex justify-end pt-4">
                    <flux:button type="submit" variant="primary" :disabled="!$leaveTypeId || !$bal || $bal->balance <= 0">Submit</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <!-- Table -->
    <div class="bg-white rounded shadow p-4">
        <flux:table>
            <flux:table.columns>
                <flux:table.column align="center" variant="strong" sortable :sorted="$sortBy === 'leave_type_id'" :direction="$sortDirection" wire:click="$set('sortBy', 'leave_type_id'); $set('sortDirection', $sortDirection === 'asc' ? 'desc' : 'asc')">Leave Type</flux:table.column>
                <flux:table.column align="center" variant="strong" sortable :sorted="$sortBy === 'apply_from'" :direction="$sortDirection" wire:click="$set('sortBy', 'apply_from'); $set('sortDirection', $sortDirection === 'asc' ? 'desc' : 'asc')">From</flux:table.column>
                <flux:table.column align="center" variant="strong" sortable :sorted="$sortBy === 'apply_to'" :direction="$sortDirection" wire:click="$set('sortBy', 'apply_to'); $set('sortDirection', $sortDirection === 'asc' ? 'desc' : 'asc')">To</flux:table.column>
                <flux:table.column align="center" variant="strong" sortable :sorted="$sortBy === 'apply_days'" :direction="$sortDirection" wire:click="$set('sortBy', 'apply_days'); $set('sortDirection', $sortDirection === 'asc' ? 'desc' : 'asc')">Days</flux:table.column>
                <flux:table.column align="center" variant="strong">Reason</flux:table.column>
                <flux:table.column align="center" variant="strong" sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="$set('sortBy', 'status'); $set('sortDirection', $sortDirection === 'asc' ? 'desc' : 'asc')">Status</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                <tbody>
                @forelse($leaveRequests as $leave)
                    <flux:table.row :key="$leave->id">
                        <flux:table.cell align="center">{{ $leave->leave_type->leave_title ?? 'N/A' }}</flux:table.cell>
                        <flux:table.cell align="center">{{ $leave->apply_from }}</flux:table.cell>
                        <flux:table.cell align="center">{{ $leave->apply_to }}</flux:table.cell>
                        <flux:table.cell align="center">{{ $leave->apply_days }}</flux:table.cell>
                        <flux:table.cell align="center">{{ $leave->reason }}</flux:table.cell>
                        <flux:table.cell align="center">
                            <span class="px-2 py-1 rounded text-xs @if($leave->status === 'approved') bg-green-100 text-green-800 @elseif($leave->status === 'rejected') bg-red-100 text-red-800 @elseif($leave->status === 'applied') bg-yellow-100 text-yellow-800 @else bg-gray-100 text-gray-800 @endif">
                                {{ $leave->status_label }}
                            </span>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-8 text-gray-400 text-lg">
                            No leave requests found.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </flux:table.rows>
        </flux:table>
        <!-- Pagination -->
        <div class="mt-4 flex justify-end">
            @if(method_exists($leaveRequests, 'links'))
                {{ $leaveRequests->links() }}
            @endif
        </div>
    </div>
</div>
