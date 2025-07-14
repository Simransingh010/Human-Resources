<div class="space-y-6">
    <!-- Filters -->
    <flux:card class="">
        <div class="flex gap-2">
            <div class="px-4 w-[448px]">
                <flux:select wire:model.live="selectedCycleId" label="Select Payroll Cycle" id="weekoff_cycle"
                             name="weekoff_cycle">
                    <option value="">Select Payroll Cycle</option>
                    @foreach($payrollCycles as $cycle)
                        <option value="{{ $cycle->id }}">{{ $cycle->title }}</option>
                    @endforeach
                </flux:select>
            </div>
            @if($selectedCycleId)
                <div>
                    <flux:radio.group label="Select Employee Groups" variant="segmented" class="max-sm:flex-col">
                        @forelse($executionGroups as $group)
                            <div class="!flex-none !w-48">
                                <flux:radio class="!flex-none cursor-btn !w-48 bg-blue-400/20 h-8"
                                            wire:click="$set('selectedGroupId', {{ $group->id }})"
                                            :checked="$selectedGroupId == $group->id"
                                            label="{{ $group->title }}"/>
                            </div>
                        @empty
                            <flux:radio label="No Groups Available" value="0"/>
                        @endforelse
                    </flux:radio.group>
                </div>
            @endif
        </div>
    </flux:card>

    <!-- Main Content -->
    <div class="flex gap-6 min-h-[400px]">
        <!-- Vertical Slots List -->
        <div class="w-64 border rounded bg-white p-2 flex flex-col gap-2">
            <div class="font-semibold mb-2">Payroll Slots</div>
            @if($payrollSlots && count($payrollSlots))
                @foreach($payrollSlots as $slot)
                    <button type="button"
                            wire:click="selectSlot({{ $slot->id }})"
                            class="w-full text-left px-3 py-2 rounded border
                                @if($selectedSlotId == $slot->id) bg-blue-100 border-blue-500
                                @elseif($slot->payroll_slot_status == 'ST') bg-yellow-400/25
                                @elseif($slot->payroll_slot_status == 'CM') bg-green-400/20
                                @elseif($slot->payroll_slot_status == 'PN') bg-rose-400/20
                                @elseif($slot->payroll_slot_status == 'ND') bg-slate-400/20
                                @elseif($slot->payroll_slot_status == 'NX') bg-blue-400/20
                                @elseif($slot->payroll_slot_status == 'HT') bg-orange-400/20
                                @elseif($slot->payroll_slot_status == 'SP') bg-rose-400/20
                                @elseif($slot->payroll_slot_status == 'RS') bg-indigo-400/20
                                @elseif($slot->payroll_slot_status == 'L') bg-zinc-400/20
                                @elseif($slot->payroll_slot_status == 'PB') bg-green-400/20
                                @else bg-gray-200 @endif">
                        <div class="font-medium">{{ $slot->title }}</div>
                        <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($slot->from_date)->format('M d') }} - {{ \Carbon\Carbon::parse($slot->to_date)->format('M d') }}</div>
                        <div class="text-xs mt-1">
                            <span class="inline-block px-2 py-0.5 rounded text-white text-xs
                                @if($slot->payroll_slot_status == 'ST') bg-yellow-500
                                @elseif($slot->payroll_slot_status == 'CM') bg-green-500
                                @elseif($slot->payroll_slot_status == 'PN') bg-rose-500
                                @elseif($slot->payroll_slot_status == 'ND') bg-slate-500
                                @elseif($slot->payroll_slot_status == 'NX') bg-blue-500
                                @elseif($slot->payroll_slot_status == 'HT') bg-orange-500
                                @elseif($slot->payroll_slot_status == 'SP') bg-rose-500
                                @elseif($slot->payroll_slot_status == 'RS') bg-indigo-500
                                @elseif($slot->payroll_slot_status == 'L') bg-zinc-500
                                @elseif($slot->payroll_slot_status == 'PB') bg-green-500
                                @else bg-gray-400 @endif">
                                {{ \App\Models\Hrms\PayrollSlot::PAYROLL_SLOT_STATUS[$slot->payroll_slot_status] ?? $slot->payroll_slot_status }}
                            </span>
                        </div>
                    </button>
                @endforeach
            @else
                <div class="text-gray-400">No slots available</div>
            @endif
        </div>

        <!-- Slot Details or Placeholder -->
        <div class="flex-1 border rounded bg-white p-6">
            @if($slotDetails)
                <div class="text-lg font-semibold mb-2">{{ $slotDetails->title }}</div>
                <div class="mb-1 text-sm text-gray-600">{{ \Carbon\Carbon::parse($slotDetails->from_date)->format('d M Y') }} - {{ \Carbon\Carbon::parse($slotDetails->to_date)->format('d M Y') }}</div>
                <div class="mb-1 text-sm">Status: <span class="font-medium">{{ \App\Models\Hrms\PayrollSlot::PAYROLL_SLOT_STATUS[$slotDetails->payroll_slot_status] ?? $slotDetails->payroll_slot_status }}</span></div>
                <div class="mb-2">
                    @if($slotHasEnded)
                        <span class="inline-block px-2 py-1 bg-green-100 text-green-800 text-xs rounded">Slot Ended - Sync Available</span>
                    @else
                        <span class="inline-block px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded">Slot Active - Sync Not Available</span>
                    @endif
                </div>
                <div class="flex justify-between items-center mb-2">
                    <div></div>
                    @if($slotHasEnded)
                        <flux:button variant="danger" wire:click="openBulkSyncModal">Bulk Sync</flux:button>
                    @endif
                </div>
                <div class="mt-6 relative min-h-[120px]">
                    <div class="flex justify-end mb-2">
                        <flux:input
                            class="w-64"
                            placeholder="Search Employee Name..."
                            wire:model.live.debounce.200ms="searchName"
                        />
                    </div>
                    <!-- Loading Spinner Overlay -->
                    <div wire:loading.flex wire:target="selectSlot" class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 backdrop-blur">
                        <flux:icon.loading class="w-10 h-10 text-blue-500 animate-spin" />
                    </div>
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column align="center" variant="strong" sortable :sorted="$sortBy === 'employee'" :direction="$sortDirection" wire:click="sort('employee')">Employee Name</flux:table.column>
                            <flux:table.column align="center" variant="strong">Employee ID</flux:table.column>
                            <flux:table.column align="center" variant="strong" sortable :sorted="$sortBy === 'total'" :direction="$sortDirection" wire:click="sort('total')">Total Week Off Days</flux:table.column>
                            <flux:table.column align="center" variant="strong" sortable :sorted="$sortBy === 'available'" :direction="$sortDirection" wire:click="sort('available')">Available</flux:table.column>
                            <flux:table.column align="center" variant="strong" sortable :sorted="$sortBy === 'consumed'" :direction="$sortDirection" wire:click="sort('consumed')">Consumed</flux:table.column>
                            <flux:table.column align="center" variant="strong" sortable :sorted="$sortBy === 'carry_forward'" :direction="$sortDirection" wire:click="sort('carry_forward')">Carry Forward</flux:table.column>
                            <flux:table.column align="center" variant="strong">Actions</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            <tbody wire:loading.remove wire:target="selectSlot">
                            @if(count($weekOffTable) === 0)
                                <tr>
                                    <td colspan="7" class="text-center py-8 text-gray-400 text-lg">
                                        No employees with week off days for this slot.
                                    </td>
                                </tr>
                            @else
                                @foreach($weekOffTable as $row)
                                    <flux:table.row :key="$row['employee']->id">
                                        <flux:table.cell align="center" variant="strong">
                                            {{ $row['employee_name'] }}
                                        </flux:table.cell>
                                        <flux:table.cell align="center" variant="strong">
                                            {{ $row['employee']->id }}
                                        </flux:table.cell>
                                        <flux:table.cell align="center" variant="strong">
                                            {{ $row['total'] }}
                                        </flux:table.cell>
                                        <flux:table.cell align="center" variant="strong">
                                            {{ $row['available'] }}
                                        </flux:table.cell>
                                        <flux:table.cell align="center" variant="strong">
                                            {{ $row['consumed'] }}
                                        </flux:table.cell>
                                        <flux:table.cell align="center" variant="strong">
                                            {{ $row['carry_forward'] }}
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            @if($slotHasEnded)
                                                <flux:button variant="danger" wire:click="openSyncModal({{ $row['employee']->id }})">Sync</flux:button>
                                            @else
                                                <span class="text-gray-400 text-sm">Slot not ended</span>
                                            @endif
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            @endif
                            </tbody>
                        </flux:table.rows>
                    </flux:table>
                </div>
            @else
                <div class="text-gray-400 text-center mt-20">Select a slot to view details</div>
            @endif
        </div>
    </div>
    <flux:modal name="confirm" wire:model="showSyncModal">
        <div class="p-6 space-y-4">
            <div class="text-lg font-semibold">Sync Week off Days</div>
            <div>
                <label for="sync_days" class="block mb-1 font-medium">Number of Days</label>
                <input type="number" min="0" max="{{ $syncDays }}" wire:model.live="syncDays" id="sync_days" class="w-32 border rounded px-2 py-1" />
            </div>
            <div class="flex justify-end gap-2 mt-4">
                <flux:button variant="danger" wire:click="confirmSync">Sync</flux:button>
                <flux:button variant="filled" wire:click="closeSyncModal">Cancel</flux:button>
            </div>
        </div>
    </flux:modal>
    <flux:modal name="bulk-confirm" wire:model="showBulkSyncModal">
        <div class="p-6 space-y-4">
            <div class="text-lg font-semibold">Bulk Sync Week off Days</div>
            <div>Are you sure you want to sync all week offs of all employees into leaves?</div>
            <div><strong>This action is irreversible.</strong></div>
            <div class="flex justify-end gap-2 mt-4">
                <flux:button variant="danger" wire:click="confirmBulkSync">Confirm</flux:button>
                <flux:button variant="filled" wire:click="closeBulkSyncModal">Cancel</flux:button>
            </div>
        </div>
    </flux:modal>

</div>

