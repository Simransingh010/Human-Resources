<div class="space-y-6" xmlns:flux="http://www.w3.org/1999/html">
    <!-- Heading Start -->
    <div class="">
        @livewire('panel.component-heading')
    </div>
    <flux:separator class="mt-2 mb-2"/>
    <!-- Heading End -->

    <!-- Filters Start -->

    <flux:card class="">
        <div class="flex gap-2">
            <div class="px-4 w-[448px] relative">
                <!-- Loading Spinner for Cycle Selection -->
                <div wire:loading.flex wire:target="selectedCycleId" class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 backdrop-blur rounded">
                    <flux:icon.loading class="w-6 h-6 text-blue-500 animate-spin" />
                </div>
                <flux:select wire:model.live="selectedCycleId" label="Select Payroll Cycle" id="industry"
                             name="industry"
                             class="">
                    <option value="">Select Payroll Cycle</option>
                    @foreach($payrollCycles as $cycle)
                        <option value="{{ $cycle->id }}">{{ $cycle->title }}</option>
                    @endforeach
                </flux:select>
            </div>
            @if($selectedCycleId)
    <div class="h-35 max-w-full overflow-x-auto overflow-y-hidden py-2">
        <flux:radio.group 
            label="Select Employee Groups" 
            variant="segmented" 
            class="flex flex-nowrap gap-2 items-stretch sm:flex-row max-sm:flex-col"
        >
            @forelse($executionGroups as $group)
                <div class="!flex-none min-w-[14rem] max-w-[18rem]">
                    <flux:radio 
                        class="truncate table-cell-wrap !flex-none cursor-btn w-full bg-blue-400/20 min-h-[4.5rem] py-3 px-3 
                               text-center whitespace-normal break-words leading-snug text-sm rounded-xl shadow-sm
                               hover:bg-blue-400/30 transition-colors duration-200"
                        wire:click="loadPayrollSlots({{ $group['id'] }})"
                        label="{{ $group['title'] }}"
                    />
                </div>
            @empty
                <flux:radio label="No Groups Available" value="0"/>
            @endforelse
        </flux:radio.group>
    </div>
@endif

        </div>
        @if($selectedGroupId)
            <div class="flex justify-between mt-4">
                <h4>Select Slots</h4>
            </div>
            <div x-data="{ scroll: $refs.slotScroll }" class="relative">
                <!-- Left Arrow -->
                <button @click="scroll.scrollBy({ left: -200, behavior: 'smooth' })"
                        class="absolute bottom-0 p-2 left-0 text-2xl" type="button">
                    &#8592;
                </button>

                <!-- Scrollable Slots -->
                <div x-ref="slotScroll" class="flex overflow-x-auto space-x-4 py-2 scrollbar-hide relative"
                     style="scroll-behavior: smooth;">
                    <!-- Loading Spinner Overlay -->
                    <div wire:loading.flex wire:target="loadPayrollSlots" class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 backdrop-blur">
                        <flux:icon.loading class="w-8 h-8 text-blue-500 animate-spin" />
                    </div>
                    @forelse($payrollSlots as $slot)
                        <div wire:click="loadSlotDetails({{$slot->id}})"
                             class="border cursor-btn flex-shrink-0 min-w-[160px] p-2 rounded-lg text-center relative
                                                               @if($slot->payroll_slot_status == 'ST') bg-yellow-400/25 @elseif($slot->payroll_slot_status == 'CM') bg-green-400/20 @else bg-red-400/20 @endif">
                            <!-- Loading Spinner for Individual Slot -->
                            <div wire:loading.flex wire:target="loadSlotDetails({{$slot->id}})" class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 backdrop-blur rounded-lg">
                                <flux:icon.loading class="w-6 h-6 text-blue-500 animate-spin" />
                            </div>
                            <div class="">
                                {{-- {{ \Carbon\Carbon::parse($slot->from_date)->format('M Y') }}--}}
                                {{ $slot->title }}
                            </div>
                            <div
                                    class="text-xs {{ $slot->payroll_slot_status === 'completed' ? 'text-green-700' : ($slot->payroll_slot_status === 'pending' ? 'text-yellow-700' : ($slot->payroll_slot_status === 'upcoming' ? 'text-blue-700' : '')) }}">
                                {{ \Carbon\Carbon::parse($slot->from_date)->format('M d') }}
                                - {{ \Carbon\Carbon::parse($slot->to_date)->format('M d') }}
                            </div>
                            <div class="mt-2">
                            <span
                                    class="mt-2 mt-3 rounded-[8px] text-white text-xs px-2
        @if($slot->payroll_slot_status == 'ST') bg-yellow-500
        @elseif($slot->payroll_slot_status == 'CM') bg-green-500
        @else bg-rose-500 @endif">
    {{ \App\Models\Hrms\PayrollSlot::PAYROLL_SLOT_STATUS[$slot->payroll_slot_status] ?? $slot->payroll_slot_status }}
</span>
                            </div>
                        </div>
                    @empty
                        <div class="bg-red-400/20 border flex-shrink-0 min-w-[160px] p-2 rounded-lg text-center">
                            <div class="">No Slots</div>
                            <div class="text-xs">No data available</div>
                            <div class="bg-rose-500 mt-2 mt-3 rounded-[8px] text-white text-xs px-2">NO DATA</div>
                        </div>
                    @endforelse
                </div>

                <!-- Right Arrow -->
                <button @click="scroll.scrollBy({ left: 200, behavior: 'smooth' })"
                        class="absolute bottom-0 p-2 right-0 text-2xl" type="button">
                    &#8594;
                </button>
            </div>
        @endif
    </flux:card>
    @if($selectedGroupId)
        <div class="flex gap-3">
            <!-- Right: Form Card -->
            <flux:card class="w-full md:w-2/3 relative ">
                <!-- Loading Spinner Overlay for Main Content -->
                <div wire:loading.flex wire:target="loadSlotDetails, loadPayrollSlots" class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 backdrop-blur">
                    <flux:icon.loading class="w-10 h-10 text-blue-500 animate-spin" />
                </div>
                @if(!$payrollSlotDetails)
                    <div class="mt-3">
                        <flux:callout icon="information-circle" variant="secondary" inline>
                            <flux:callout.heading>Select a Payroll Slot</flux:callout.heading>
{{--                            <flux:callout.description>Please select a payroll slot from the list above to view its details and manage the payroll process.</flux:callout.description>--}}
                        </flux:callout>
{{--                        </flux:callout>--}}
                    </div>
                @elseif($payrollSlotDetails?->payroll_slot_status == 'PN' || $payrollSlotDetails?->payroll_slot_status == 'NX')
                    <flux:callout icon="cube" variant="success" inline>
                        <flux:callout.heading>Start Payroll Cycles</flux:callout.heading>
                        <x-slot name="actions">
                            <flux:modal.trigger name="mdl-start-payroll" class="flex justify-end">
                                <flux:button variant="primary" class="p-2 mt-1 cursor-btn" size="sm">Start Payroll
                                </flux:button>
                            </flux:modal.trigger>
                        </x-slot>
                    </flux:callout>
                @elseif($payrollSlotDetails?->payroll_slot_status == 'ST' || $payrollSlotDetails?->payroll_slot_status == 'RS' )
                    <div class="mt-3">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Payroll Steps</h4>
                        @foreach($payrollSteps as $step)
                            @php
                                $icon = 'document'; // default icon
                                if($step->step_code_main == 'fetch_attendance') {
                                    $icon = 'user-group';
                                } elseif($step->step_code_main == 'lop_attendance') {
                                    $icon = 'cog-8-tooth';
                                } elseif($step->step_code_main == 'tds_calculation') {
                                    $icon = 'arrow-path-rounded-square';
                                }
                                elseif($step->step_code_main == 'static_unknown') {
                                    $icon = 'currency-rupee';
                                }
                                elseif($step->step_code_main == 'override_amounts') {
                                    $icon = 'currency-rupee';
                                }
                                elseif($step->step_code_main == 'salary_holds') {
                                    $icon = 'lock-closed';
                                }
                                elseif($step->step_code_main == 'salary_advances') {
                                    $icon = 'banknotes';
                                }
                                elseif($step->step_code_main == 'salary_arrears') {
                                    $icon = 'minus-circle'; // Using minus-circle icon for arrears
                                }
                                elseif($step->step_code_main == 'review_override_components') {
                                    $icon = 'eye';
                                }
                            @endphp
                            <flux:callout class="mb-2" :icon="$icon" variant="secondary" inline>
                                <flux:callout.heading>{{ $step->step_title }}</flux:callout.heading>
                                <x-slot name="actions">
                                    @if($step->step_code_main == 'fetch_attendance')
                                        <flux:tooltip content="Mark Complete">
                                            <flux:button class="cursor-btn mt-2" size="xs" :icon="'check-circle'"
                                                         wire:click="completePayrollStep({{$step->id}}, {{ $payrollSlotDetails->id }})">
                                            </flux:button>
                                        </flux:tooltip>
                                        <flux:tooltip content="View Logs">
                                            <flux:button class="cursor-btn mt-2" size="xs" :icon="'document-duplicate'"
                                                         wire:click="showLogs({{$step->id}}, {{ $payrollSlotDetails->id }})">
                                            </flux:button>
                                        </flux:tooltip>
                                        <flux:tooltip content="View Details">
                                            <flux:button class="cursor-btn mt-2" size="xs" :icon="'eye'"
                                                         wire:click="openAttendanceStep({{ $step->id }}, {{ $payrollSlotDetails->id }})">
                                            </flux:button>
                                        </flux:tooltip>
                                        @if($payrollSlotDetails?->payroll_slot_status !== 'IP')
                                        <flux:tooltip content="Fetch Attendance">
                                            <flux:button class="cursor-btn mt-2" variant="primary" size="xs"
                                                         wire:click="runPayrollStep({{$step->id}}, {{ $payrollSlotDetails->id }})">
                                                Fetch
                                            </flux:button>
                                        </flux:tooltip>
                                        @endif
                                    @endif

                                    @if($step->step_code_main == 'lop_attendance')
                                        <flux:tooltip content="Mark Complete">
                                            <flux:button class="cursor-btn mt-2" size="xs" :icon="'check-circle'"
                                                         wire:click="completePayrollStep({{$step->id}}, {{ $payrollSlotDetails->id }})">
                                            </flux:button>
                                        </flux:tooltip>
                                        <flux:tooltip content="View Logs">
                                            <flux:button class="cursor-btn mt-2" size="xs" :icon="'document-duplicate'"
                                                         wire:click="showLogs({{$step->id}}, {{ $payrollSlotDetails->id }})">
                                            </flux:button>
                                        </flux:tooltip>
                                        <flux:tooltip content="Fetch Attendance">
                                            <flux:button class="cursor-btn mt-2" variant="primary" size="xs"
                                                         wire:click="lopAdjustmentStep({{ $payrollSlotDetails->id }})">
                                                Adjustment
                                            </flux:button>
                                        </flux:tooltip>
                                    @endif

                                    @if($step->step_code_main == 'static_unknown')
                                        <flux:tooltip content="Mark Complete">
                                            <flux:button class="cursor-btn mt-2" size="xs" :icon="'check-circle'"
                                                         wire:click="completePayrollStep({{$step->id}}, {{ $payrollSlotDetails->id }})">
                                            </flux:button>
                                        </flux:tooltip>
                                        <flux:tooltip content="View Logs">
                                            <flux:button class="cursor-btn mt-2" size="xs" :icon="'document-duplicate'"
                                                         wire:click="showLogs({{$step->id}}, {{ $payrollSlotDetails->id }})">
                                            </flux:button>
                                        </flux:tooltip>
                                        <flux:button class="cursor-btn mt-2" variant="primary" size="xs"
                                                     wire:click="staticUnknownComponentsStep({{ $payrollSlotDetails->id }})">
                                            Manually Entry
                                        </flux:button>
                                    @endif
                                        @if($step->step_code_main == 'override_amounts')
                                            <flux:tooltip content="Mark Complete">
                                                <flux:button class="cursor-btn mt-2" size="xs" :icon="'check-circle'"
                                                             wire:click="completePayrollStep({{$step->id}}, {{ $payrollSlotDetails->id }})">
                                                </flux:button>
                                            </flux:tooltip>
                                            <flux:tooltip content="View Logs">
                                                <flux:button class="cursor-btn mt-2" size="xs" :icon="'document-duplicate'"
                                                             wire:click="showLogs({{$step->id}}, {{ $payrollSlotDetails->id }})">
                                                </flux:button>
                                            </flux:tooltip>
                                            <flux:button class="cursor-btn mt-2" variant="primary" size="xs"
                                                         wire:click="reviewOverrideComponentsStep({{ $payrollSlotDetails->id }})">
                                                Review Overrides
                                            </flux:button>
                                            <flux:button class="cursor-btn mt-2" variant="primary" size="xs"
                                                         wire:click="overrideAmountsStep({{ $payrollSlotDetails->id }})">
                                                Override Amounts
                                            </flux:button>
                                        @endif

                                    @if($step->step_code_main == 'salary_holds')
                                        <flux:tooltip content="View Holds">
                                            <flux:button class="cursor-btn mt-2" size="xs" :icon="'eye'"
                                                         wire:click="viewSalaryHolds({{ $payrollSlotDetails->id }})">
                                            </flux:button>
                                        </flux:tooltip>
                                    @endif

                                    @if($step->step_code_main == 'salary_advances')
                                        <flux:tooltip content="View Advances">
                                            <flux:button class="cursor-btn mt-2" size="xs" :icon="'eye'"
                                                         wire:click="viewSalaryAdvances({{ $payrollSlotDetails->id }})">
                                            </flux:button>
                                        </flux:tooltip>
                                    @endif

                                    @if($step->step_code_main == 'salary_arrears')
                                        <flux:tooltip content="View Arrears">
                                            <flux:button class="cursor-btn mt-2" size="xs" :icon="'eye'"
                                                         wire:click="viewSalaryArrears({{ $payrollSlotDetails->id }})">
                                            </flux:button>
                                        </flux:tooltip>
                                    @endif

{{--                                    @if($step->step_code_main == 'review_override_components')--}}
{{--                                        <flux:tooltip content="Mark Complete">--}}
{{--                                            <flux:button class="cursor-btn mt-2" size="xs" :icon="'check-circle'"--}}
{{--                                                         wire:click="completePayrollStep({{$step->id}}, {{ $payrollSlotDetails->id }})">--}}
{{--                                            </flux:button>--}}
{{--                                        </flux:tooltip>--}}
{{--                                        <flux:tooltip content="View Logs">--}}
{{--                                            <flux:button class="cursor-btn mt-2" size="xs" :icon="'document-duplicate'"--}}
{{--                                                         wire:click="showLogs({{$step->id}}, {{ $payrollSlotDetails->id }})">--}}
{{--                                            </flux:button>--}}
{{--                                        </flux:tooltip>--}}

{{--                                    @endif--}}

                                    @if($step->step_code_main == 'tds_calculation')

                                        <flux:tooltip content="Mark Complete">
                                            <flux:button class="cursor-btn mt-2" size="xs" :icon="'check-circle'"
                                                         wire:click="completePayrollStep({{$step->id}}, {{ $payrollSlotDetails->id }})">
                                            </flux:button>
                                        </flux:tooltip>
                                        <flux:tooltip content="View Logs">
                                            <flux:button class="cursor-btn mt-2" size="xs" :icon="'document-duplicate'"
                                                         wire:click="showLogs({{$step->id}}, {{ $payrollSlotDetails->id }})">
                                            </flux:button>
                                        </flux:tooltip>
                                        <flux:button class="cursor-btn mt-2" variant="primary" size="xs"
                                                     wire:click="tdsCalculationStep({{ $payrollSlotDetails->id }})">
                                            Auto Calculate
                                        </flux:button>
                                        <flux:button class="cursor-btn mt-2" size="xs"
                                                     wire:click="employeeTaxComponentsStep({{$step->id}}, {{ $payrollSlotDetails->id }})">
                                            Manually Entry
                                        </flux:button>
                                    @endif

                                </x-slot>
                            </flux:callout>
                        @endforeach
                        <div class="mt-3">
                            <flux:callout class="" icon="plus-circle" variant="warning" inline>
                                <flux:callout.heading>Click here to complete Payroll</flux:callout.heading>
                                <x-slot name="actions">
                                    <flux:button variant="primary" class="cursor-btn"
                                                 wire:click="completePayroll({{ $payrollSlotDetails->id }}, {{ $selectedCycleId }}, {{ $selectedGroupId }})">
                                        Complete
                                    </flux:button>
                                </x-slot>
                            </flux:callout>
                        </div>
                        <div class="mt-4 flex justify-end">

                        </div>
                    </div>
                @elseif($payrollSlotDetails?->payroll_slot_status == 'CM')
                    <div class="mt-3">
                        <flux:callout class="" icon="plus-circle" variant="success" inline>
                            <flux:callout.heading>
                                <div class="flex items-center gap-2">
                                    <span>Payroll Process Completed</span>
{{--                                    <flux:badge variant="success">Completed</flux:badge>--}}
                                </div>
                            </flux:callout.heading>
                            <x-slot name="actions">
                                <flux:button wire:click="showSalaryTracks({{ $payrollSlotDetails->id }})"
                                             class="cursor-btn mt-2"
                                             size="xs">Details
                                </flux:button>

                                <flux:modal.trigger name="mdl-re-start-payroll" class="flex justify-end">
                                    <flux:button variant="primary" class="p-2 mt-1 cursor-btn" size="sm">Re-Start Payroll
                                    </flux:button>
                                </flux:modal.trigger>

                                <flux:modal.trigger name="mdl-lock-payroll" class="flex justify-end">
                                    <flux:button variant="danger" class="p-2 mt-1 cursor-btn" size="sm">Lock Payroll
                                    </flux:button>
                                </flux:modal.trigger>

                                <flux:modal.trigger name="mdl-logs" class="flex justify-end">
                                    <flux:button class="cursor-btn mt-2" size="xs">Logs</flux:button>
                                </flux:modal.trigger>
                            </x-slot>
                        </flux:callout>
                    </div>
                    @elseif($payrollSlotDetails?->payroll_slot_status == 'L')
                    <div class="mt-3">
                        <flux:callout class="bg-red-50" icon="lock-closed" variant="danger" inline>
                            <flux:callout.heading>
                                <div class="flex items-center gap-2">
                                    <span>Payroll Process Locked</span>
                                    <flux:badge variant="danger">Locked</flux:badge>
                                </div>

                            </flux:callout.heading>
                            <x-slot name="actions">
                                <flux:button wire:click="showSalaryTracks({{ $payrollSlotDetails->id }})"
                                             class="cursor-btn mt-2"
                                             size="xs">Details
                                </flux:button>

                                <flux:modal.trigger name="mdl-logs" class="flex justify-end">
                                    <flux:button class="cursor-btn mt-2" size="xs">Logs</flux:button>
                                </flux:modal.trigger>
                                <flux:modal.trigger name="mdl-publish-payroll" class="flex justify-end">
                                    <flux:button >Publish Payroll
                                    </flux:button>
                                </flux:modal.trigger>
                            </x-slot>
                        </flux:callout>
                    </div>
                    @elseif($payrollSlotDetails?->payroll_slot_status == 'PB')
                    <div class="mt-3">
                        <flux:callout class="bg-red-50" icon="computer-desktop" variant="warning" inline>
                            <flux:callout.heading>
                                <div class="flex items-center gap-2">
                                    <span>Payroll Process Published</span>
                                    <flux:badge variant="pill">Published</flux:badge>
                                </div>

                            </flux:callout.heading>
                            <x-slot name="actions">
                                <flux:button wire:click="showSalaryTracks({{ $payrollSlotDetails->id }})"
                                             class="cursor-btn mt-2"
                                             size="xs">Details
                                </flux:button>

                               
                            </x-slot>
                        </flux:callout>
                    </div>
                @else
                    <div class="mt-3">
                        <flux:callout class="" icon="plus-circle" variant="success" inline>
                            <flux:callout.heading>Payroll Status
                                is: {{$payrollSlotDetails?->payroll_slot_status}}</flux:callout.heading>
                        </flux:callout>
                    </div>
                @endif
            </flux:card>
            <flux:card class="w-[448px] relative">
                <!-- Loading Spinner Overlay for Logs -->
                <div wire:loading.flex wire:target="loadSlotDetails, loadPayrollSlots" class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 backdrop-blur overflow-auto h-24">
                    <flux:icon.loading class="w-8 h-8 text-blue-500 animate-spin" />
                </div>
                <h4 class="font-semibold mb-2">Logs</h4>
                @if(!$payrollSlotDetails)
                    <div class="text-gray-400 text-center py-8">
                        <flux:icon.information-circle class="w-8 h-8 mx-auto mb-2" />
                        <p class="text-sm">Select a payroll slot to view logs</p>
                    </div>
                @else
                <ul class="space-y-4">
                    @foreach($payrollSlotCmds as $cmds)
                        @php
                            $remarks = json_decode($cmds->run_payroll_remarks, true);
                        @endphp
                        <li class="flex items-start gap-2">
                            <span class="mt-5 w-2 h-2  @if($slot->payroll_slot_status == 'ST') bg-yellow-400/25 @elseif($slot->payroll_slot_status == 'CM') bg-green-400/20 @else bg-red-400/20 @endif rounded-full"></span>
                            <div class="w-full rounded-md p-1 border bg-green-400/20">
                                <div class="font-medium">{{ $cmds->payroll_slot_status_label }} - {{ $remarks['step_title'] ?? '' }}</div>
                                <div class="text-xs ">{{ $remarks['remark'] ?? '' }}</div>
                                <div class="text-xs ">{{ \Carbon\Carbon::parse($cmds->created_at)->format('jS F Y \a\t g:i A') }}</div>
                            </div>
                        </li>
                    @endforeach
                </ul>
                @endif
            </flux:card>
        </div>
    @endif
    <flux:modal name="mdl-start-payroll" @cancel="resetForm">
        <form wire:submit.prevent="startPayroll({{$payrollSlotDetails?->id}})">
            <!-- Right: Inputs -->
            <div class="mb-4 w-full mt-3">
                <flux:label class="text-sm font-medium text-gray-700" for="remarks">To initiate the payroll
                    instruction cycle, you first need to define your payroll policy, gather employee information,
                    and set up your payroll system.
                </flux:label>
                <flux:textarea name="remarks" placeholder="Remarks" rows="2"
                               class="mt-2"/>
                <div class="flex justify-end">
                    <flux:button wire:click="startPayroll({{$payrollSlotDetails?->id}})" variant="primary"
                                 class="w-48 mt-3" size="sm">Start Payroll
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
    <flux:modal name="mdl-re-start-payroll" @cancel="resetForm">
        <form>
            <!-- Right: Inputs -->
            <div class="mb-4 w-full mt-3">
                <flux:label class="text-sm font-medium text-gray-700" for="remarks">Restarting of Payroll will replace the existing data of PayRoll, If you are sure then only you should proceed.
                </flux:label>
                <flux:textarea name="remarks" placeholder="Remarks" rows="2"
                               class="mt-2"/>
                <div class="flex justify-end">
                    <flux:button wire:click="restartPayroll({{$payrollSlotDetails?->id}})" variant="primary"
                                 class="w-48 mt-3" size="sm">Re-Start Payroll
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
    <flux:modal name="mdl-logs" @cancel="resetForm">
        <h4 class="font-semibold mb-2 mt-3">Step Logs</h4>
        <ul class="space-y-4">
            @foreach($this->getStepLogs($selectedStepId, $selectedSlotId) as $log)
                <li class="flex items-start gap-2">
                    <span class="mt-5 w-2 h-2
                        @if($log['status'] == 'CM') bg-green-500
                        @elseif($log['status'] == 'ST') bg-yellow-500
                        @elseif($log['status'] == 'RN') bg-blue-500
                        @else bg-red-500 @endif
                            rounded-full"></span>
                    <div class="w-full rounded-md p-1 border
                        @if($log['status'] == 'CM') bg-green-400/20
                        @elseif($log['status'] == 'ST') bg-yellow-400/25
                        @elseif($log['status'] == 'RN') bg-blue-400/20
                        @else bg-red-400/20 @endif">
                        <div class="text-xs font-medium text-gray-700">
                            {{ $log['step_title'] }}: {{ $log['remarks'] }}
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ $log['created_at']->format('Y-m-d H:i A') }}
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    </flux:modal>
    <flux:modal name="salary-tracks" title="Salary Tracks" class="max-w-7xl">
        @if($selectedSlotId)
            <livewire:hrms.payroll.emp-salary-tracks :slot-id="$selectedSlotId"
                                                     :wire:key="'emp-salary-tracks-'.$selectedSlotId"/>
        @endif
    </flux:modal>
    <flux:modal name="attendance-step" title="Attendance Step" class="max-w-6xl">
        <livewire:hrms.payroll.attendance-payroll-step
                :payroll-slot-id="$payrollSlotDetails?->id"
                :employee-ids="$selectedEmployees"
                :from-date="$payrollSlotDetails?->from_date"
                :to-date="$payrollSlotDetails?->to_date"
                :wire:key="'attendance-step-'.$payrollSlotDetails?->id"/>
    </flux:modal>
    <flux:modal name="lop-adjustment-steps" title="Lop Adjustment Steps" class="max-w-6xl">
        @if($selectedSlotId)
            <livewire:hrms.payroll.lop-adjustment-step :slot-id="$selectedSlotId"
                                                       :wire:key="'lop-adjustment-step-'.$selectedSlotId"/>
        @endif
    </flux:modal>

    <!-- Add TDS Calculations Modal -->
    <flux:modal name="tds-calculations" title="TDS Calculations" class="max-w-6xl">
        @if($selectedSlotId)
            <livewire:hrms.payroll.tds-calculations :slot-id="$selectedSlotId"
                                                    :wire:key="'tds-calculations-'.$selectedSlotId"/>
        @endif
    </flux:modal>

    <!-- Add Static Unknown Components Modal -->
    <flux:modal name="set-head-amount-manually" title="Set Head Amount Manually" class="max-w-6xl">
        @if($selectedSlotId)
            <livewire:hrms.payroll.set-head-amount-manually :payroll-slot-id="$selectedSlotId"
                                                               :wire:key="'set-head-amount-manually-'.$selectedSlotId"/>
        @endif
    </flux:modal>
{{--    override amounts modal--}}
<flux:modal name="override-amounts" title="Override Amounts" class="max-w-7xl">
    @if($selectedSlotId)
        <livewire:hrms.payroll.override-head-amount-manually :payroll-slot-id="$selectedSlotId"
            :wire:key="'override-head-amount-manually-'.$selectedSlotId" />
    @endif
</flux:modal>
    <!-- Add Employee Tax Components Modal -->
    <flux:modal name="employee-tax-components" title="Employee Tax Components" class="max-w-6xl">
        @if($selectedSlotId)
            <livewire:hrms.payroll.employee-tax-components
                    :payroll-slot-id="$selectedSlotId"
                    :wire:key="'employee-tax-components-'.$selectedSlotId"/>
        @endif
    </flux:modal>
    <flux:modal name="mdl-publish-payroll" @cancel="resetForm">
        <form wire:submit.prevent="publishPayroll({{$payrollSlotDetails?->id}})">
            <div class="mb-4 w-full mt-3">
                <flux:label class="text-sm font-medium text-gray-700" >
                    Are you sure you want to publish this payroll? This will allocate week-off leaves and cannot be undone.
                </flux:label>
                <flux:textarea name="remarks" placeholder="Remarks" rows="2"
                               class="mt-2"/>
                <div class="flex justify-end">
                    <flux:button
                    wire:click="publishPayroll({{$payrollSlotDetails?->id}})">
                        Publish Payroll
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
    <flux:modal name="mdl-lock-payroll" @cancel="resetForm">
        <form wire:submit.prevent="lockPayroll({{$payrollSlotDetails?->id}})">
            <div class="mb-4 w-full mt-3">
                <flux:label class="text-sm font-medium text-gray-700" for="lock_confirmation">
                    To lock this payroll, please type "LOCK" in capital letters. This action cannot be undone.
                    Once locked, you will not be able to restart or modify this payroll.
                </flux:label>
                <flux:input 
                    wire:model.live="lockConfirmation" 
                    name="lock_confirmation" 
                    placeholder="Type LOCK here" 
                    class="mt-2"
                    error="$errors->first('lockConfirmation')"
                />
                <div class="flex justify-end">
                    <flux:button 
                        type="submit"
                        variant="danger"
                        class="w-48 mt-3" 
                        size="sm">
                        Lock Payroll
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
    <!-- Add Salary Holds Modal -->
    <flux:modal name="salary-holds" title="Salary Holds" class="max-w-6xl">
        @if($selectedSlotId)
            <livewire:hrms.payroll.salary-holds :payroll-slot-id="$selectedSlotId"
                                               :wire:key="'salary-holds-'.$selectedSlotId"/>
        @endif
    </flux:modal>

    <!-- Add Salary Advances Modal -->
    <flux:modal name="salary-advances" title="Salary Advances" class="max-w-6xl">
        @if($selectedSlotId)
            <livewire:hrms.payroll.salary-advances-step :payroll-slot-id="$selectedSlotId"
                                                      :wire:key="'salary-advances-step-'.$selectedSlotId"/>
        @endif
    </flux:modal>

    <!-- Add Salary Arrears Modal -->
    <flux:modal name="salary-arrears" title="Salary Arrears" class="max-w-6xl">
        @if($selectedSlotId)
            <livewire:hrms.payroll.salary-arrears-step :payroll-slot-id="$selectedSlotId"
                                                      :wire:key="'salary-arrears-step-'.$selectedSlotId"/>
        @endif
    </flux:modal>

    <!-- Add Review Override Components Modal -->
    <flux:modal name="review-override-components" title="Review Override Components" class="max-w-7xl">
        @if($selectedSlotId)
            <livewire:hrms.payroll.review-override-components :payroll-slot-id="$selectedSlotId"
                                                             :wire:key="'review-override-components-'.$selectedSlotId"/>
        @endif
    </flux:modal>

</div>
