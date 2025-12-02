<div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Leave Balances</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Manage employee leave allocations and balances</p>
        </div>
        <flux:modal.trigger name="mdl-leave-balance">
            <flux:button variant="primary" icon="plus">
                Add Balance
            </flux:button>
        </flux:modal.trigger>
    </div>

    <!-- Filters Bar -->
    <flux:card class="!p-3">
        <div class="flex items-center gap-4">
            <!-- Search -->
            <div class="w-72">
                <flux:input 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Search employees..." 
                    icon="magnifying-glass"
                    clearable
                />
            </div>
            
            <!-- Period Selector -->
            <div class="w-56">
                <flux:select wire:model.live="selectedPeriod" placeholder="Select Period">
                    @foreach($periods as $key => $label)
                        <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <!-- Per Page -->
            <div class="w-20">
                <flux:select wire:model.live="perPage">
                    <flux:select.option value="10">10</flux:select.option>
                    <flux:select.option value="25">25</flux:select.option>
                    <flux:select.option value="50">50</flux:select.option>
                    <flux:select.option value="100">100</flux:select.option>
                </flux:select>
            </div>
        </div>
    </flux:card>

    <!-- Pivot Table -->
    <flux:card class="!p-0 overflow-hidden relative">
        <!-- Loading Overlay -->
        <div wire:loading wire:target="search, selectedPeriod, perPage, gotoPage, previousPage, nextPage" 
             class="absolute inset-0 bg-white/70 dark:bg-gray-900/70 backdrop-blur-sm z-20 flex items-center justify-center">
            <div class="flex flex-col items-center gap-2">
                <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm text-gray-600 dark:text-gray-400">Loading...</span>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider sticky left-0 bg-gray-50 dark:bg-gray-800 z-10 min-w-[200px]">
                            Employee
                        </th>
                        @foreach($this->leaveTypes as $leaveType)
                            <th class="px-3 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider min-w-[120px]">
                                <div class="flex flex-col items-center gap-1">
                                    <span>{{ $leaveType->leave_title }}</span>
                                    @if($leaveType->leave_code)
                                        <span class="text-[10px] font-normal text-gray-400">({{ $leaveType->leave_code }})</span>
                                    @endif
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($this->pivotData as $employee)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition-colors">
                            <!-- Employee Name (Sticky) -->
                            <td class="px-4 py-3 sticky left-0 bg-white dark:bg-gray-900 z-10 border-r border-gray-100 dark:border-gray-800">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400 text-sm font-medium">
                                        {{ strtoupper(substr($employee->fname ?? 'N', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ $employee->fname }} {{ $employee->lname }}
                                        </div>
                                        @if($employee->email)
                                            <div class="text-xs text-gray-500">{{ $employee->email }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            
                            <!-- Leave Type Cells -->
                            @foreach($this->leaveTypes as $leaveType)
                                @php
                                    $balance = $this->getBalanceForEmployee($employee, $leaveType->id);
                                @endphp
                                <td class="px-2 py-2">
                                    <button 
                                        wire:click="editCell({{ $employee->id }}, {{ $leaveType->id }})"
                                        class="w-full group"
                                    >
                                        <div class="rounded-lg p-2 {{ $this->getBalanceBgClass($balance) }} hover:ring-2 hover:ring-blue-500/50 transition-all cursor-pointer">
                                            @if($balance)
                                                <div class="text-center">
                                                    <!-- Balance Value -->
                                                    <div class="text-lg font-bold {{ $this->getBalanceColorClass($balance) }}">
                                                        {{ number_format($balance->balance, 1) }}
                                                    </div>
                                                    <!-- Breakdown -->
                                                    <div class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">
                                                        {{ number_format($balance->consumed_days, 0) }} / {{ number_format($balance->allocated_days, 0) }} used
                                                    </div>
                                                    <!-- Edit hint on hover -->
                                                    <div class="opacity-0 group-hover:opacity-100 transition-opacity mt-1">
                                                        <span class="text-[10px] text-blue-600 dark:text-blue-400">Click to edit</span>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="text-center py-2">
                                                    <div class="text-gray-400 dark:text-gray-600">—</div>
                                                    <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                                                        <span class="text-[10px] text-blue-600 dark:text-blue-400">Click to add</span>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </button>
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($this->leaveTypes) + 1 }}" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <flux:icon name="users" class="w-12 h-12 text-gray-300 dark:text-gray-600" />
                                    <p class="text-gray-500 dark:text-gray-400">No employees found</p>
                                    @if($search)
                                        <flux:button variant="ghost" wire:click="$set('search', '')" size="sm">
                                            Clear search
                                        </flux:button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        @if($this->pivotData->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $this->pivotData->links() }}
            </div>
        @endif
    </flux:card>

    <!-- Legend -->
    <div class="flex items-center gap-6 text-xs text-gray-500 dark:text-gray-400">
        <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded bg-emerald-100 dark:bg-emerald-900/30"></div>
            <span>Good (>5 days)</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded bg-amber-100 dark:bg-amber-900/30"></div>
            <span>Low (1-5 days)</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded bg-red-100 dark:bg-red-900/30"></div>
            <span>Exhausted (≤0 days)</span>
        </div>
    </div>

    <!-- Edit Modal -->
    <flux:modal name="mdl-leave-balance" class="max-w-lg" @close="resetForm">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        {{ $isEditing ? 'Edit Leave Balance' : 'Add Leave Balance' }}
                    </flux:heading>
                    <flux:subheading>
                        {{ $isEditing ? 'Update the leave balance details' : 'Create a new leave balance entry' }}
                    </flux:subheading>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <!-- Employee -->
                    <div class="col-span-2">
                        <flux:select 
                            label="Employee" 
                            wire:model="formData.employee_id"
                            :disabled="$isEditing"
                        >
                            <flux:select.option value="">Select Employee</flux:select.option>
                            @foreach($listsForFields['employees'] as $id => $name)
                                <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    <!-- Leave Type -->
                    <div class="col-span-2">
                        <flux:select 
                            label="Leave Type" 
                            wire:model="formData.leave_type_id"
                            :disabled="$isEditing"
                        >
                            <flux:select.option value="">Select Leave Type</flux:select.option>
                            @foreach($listsForFields['leave_types'] as $id => $name)
                                <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    <!-- Period -->
                    <flux:input type="date" label="Period Start" wire:model="formData.period_start" />
                    <flux:input type="date" label="Period End" wire:model="formData.period_end" />

                    <!-- Days -->
                    <flux:input type="number" label="Allocated Days" wire:model="formData.allocated_days" step="0.5" min="0" />
                    <flux:input type="number" label="Consumed Days" wire:model="formData.consumed_days" step="0.5" min="0" />
                    <flux:input type="number" label="Carry Forward" wire:model="formData.carry_forwarded_days" step="0.5" min="0" />
                    <flux:input type="number" label="Lapsed Days" wire:model="formData.lapsed_days" step="0.5" min="0" />

                    <!-- Balance (calculated, shown for reference) -->
                    <div class="col-span-2 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Calculated Balance:</span>
                            <span class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ number_format(($formData['allocated_days'] ?? 0) + ($formData['carry_forwarded_days'] ?? 0) - ($formData['consumed_days'] ?? 0) - ($formData['lapsed_days'] ?? 0), 1) }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">= Allocated + Carry Forward - Consumed - Lapsed</p>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">
                        {{ $isEditing ? 'Update' : 'Create' }}
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <!-- Transactions Modal -->
    <flux:modal name="leave-transactions" class="max-w-4xl">
        @if($selectedId)
            <livewire:hrms.leave.EmpLeaveBalance.emp-leave-transactions 
                :bala-id="$selectedId"
                :wire:key="'emp-leave-transactions-'.$selectedId"
            />
        @endif
    </flux:modal>
</div>
