<div class="space-y-6">
    <!-- Header -->
    <div>
        <flux:heading size="lg">
            Leave Balance Changes
            @if($batch)
                <span class="text-sm font-normal text-gray-500 ml-2">
                    (Batch #{{ $batch->id }} | {{ $batch->modulecomponent }} | {{ $batch->user->name }})
                </span>
            @endif
        </flux:heading>
    </div>

    <!-- Table -->
    <flux:table :paginate="$this->batchItems" class="w-full">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column sortable :sorted="$sortBy === 'id'" :direction="$sortDirection" wire:click="sort('id')"
                class="w-12">
                ID
            </flux:table.column>
            <flux:table.column>Employee</flux:table.column>
            <flux:table.column>Leave Type</flux:table.column>
            <flux:table.column>Period</flux:table.column>
            <flux:table.column>Days</flux:table.column>
            <flux:table.column>Operation</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->batchItems as $item)
                @php
                    $leaveData = $this->getLeaveData($item->new_data);
                @endphp
                <flux:table.row :key="$item->id" class="border-b">
                    <flux:table.cell>{{ $item->id }}</flux:table.cell>
                    <flux:table.cell>
                        @if($leaveData)
                            {{ $leaveData['employee_name'] }}
                        @else
                            <span class="text-gray-400">N/A</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($leaveData)
                            {{ $leaveData['leave_type'] }}
                        @else
                            <span class="text-gray-400">N/A</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($leaveData)
                            <div class="text-sm">
                                <div>From: {{ $leaveData['period_start'] }}</div>
                                <div>To: {{ $leaveData['period_end'] }}</div>
                            </div>
                        @else
                            <span class="text-gray-400">N/A</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($leaveData)
                            <div class="text-sm">
                                <div>Allocated: {{ $leaveData['allocated_days'] }}</div>
                                <div>Balance: {{ $leaveData['balance'] }}</div>
                            </div>
                        @else
                            <span class="text-gray-400">N/A</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$item->operation === 'insert' ? 'green' : ($item->operation === 'update' ? 'blue' : 'red')">
                            {{ $item->operation }}
                        </flux:badge>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>