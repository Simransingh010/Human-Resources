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
                            <flux:table.column align="center" variant="strong" sortable :sorted="$sortBy === 'employee_name'" :direction="$sortDirection" wire:click="sort('employee_name')">Employee Name</flux:table.column>
                            <flux:table.column align="center" variant="strong" sortable :sorted="$sortBy === 'department'" :direction="$sortDirection" wire:click="sort('department')">Department</flux:table.column>
                            <flux:table.column align="center" variant="strong" sortable :sorted="$sortBy === 'availed_date'" :direction="$sortDirection" wire:click="sort('availed_date')">Availed Week Off Date</flux:table.column>
                            <flux:table.column align="center" variant="strong" sortable :sorted="$sortBy === 'consumed_date'" :direction="$sortDirection" wire:click="sort('consumed_date')">Consumed Week Off Date</flux:table.column>
                            <flux:table.column align="center" variant="strong" sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Status</flux:table.column>
{{--                            <flux:table.column align="center" variant="strong" sortable :sorted="$sortBy === 'remarks'" :direction="$sortDirection" wire:click="sort('remarks')">Remarks</flux:table.column>--}}
                        </flux:table.columns>
                        <flux:table.rows>
                            <tbody>
                            @if(count($weekOffTable) === 0)
                                <tr>
                                    <td colspan="6" class="text-center py-8 text-gray-400 text-lg">
                                        No week off data for this slot.
                                    </td>
                                </tr>
                            @else
                                @foreach($weekOffTable as $row)
                                    <flux:table.row>
                                        <flux:table.cell align="center" variant="strong">
                                            {{ $row['employee_name'] }}
                                        </flux:table.cell>
                                        <flux:table.cell align="center" variant="strong">
                                            {{ $row['department'] }}
                                        </flux:table.cell>
                                        <flux:table.cell align="center" variant="strong">
                                            {{ $row['availed_date'] }}
                                        </flux:table.cell>
                                        <flux:table.cell align="center" variant="strong">
                                            {{ $row['consumed_date'] }}
                                        </flux:table.cell>
                                        <flux:table.cell align="center" variant="strong">
                                            {{ $row['status'] }}
                                        </flux:table.cell>
{{--                                        <flux:table.cell align="center" variant="strong">--}}
{{--                                            {{ $row['remarks'] }}--}}
{{--                                        </flux:table.cell>--}}
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
</div>
