<div class="space-y-6" xmlns:flux="http://www.w3.org/1999/html">
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-shift" class="flex justify-end">
            <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">
                New
            </flux:button>
        </flux:modal.trigger>
    </div>
    <flux:separator class="mt-2 mb-2"/>
    <!-- Heading End -->

<!-- Filter Fields Show/Hide Modal -->
    <flux:modal name="mdl-show-hide-filters" variant="flyout">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Show/Hide Filters</flux:heading>
            </div>
            <div class="flex flex-wrap items-center gap-4">
                <flux:checkbox.group>
                    @foreach($filterFields as $field => $cfg)
                        <flux:checkbox
                                :checked="in_array($field, $visibleFilterFields)"
                                label="{{ $cfg['label'] }}"
                                wire:click="toggleFilterColumn('{{ $field }}')"
                        />
                    @endforeach
                </flux:checkbox.group>
            </div>
        </div>
    </flux:modal>

    <!-- Columns Show/Hide Modal -->
    <flux:modal name="mdl-show-hide-columns" variant="flyout" position="right">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Show/Hide Columns</flux:heading>
            </div>
            <div class="flex flex-wrap items-center gap-4">
                <flux:checkbox.group>
                    @foreach($fieldConfig as $field => $cfg)
                        <flux:checkbox
                                :checked="in_array($field, $visibleFields)"
                                label="{{ $cfg['label'] }}"
                                wire:click="toggleColumn('{{ $field }}')"
                        />
                    @endforeach
                </flux:checkbox.group>
            </div>
        </div>
    </flux:modal>

    <!-- Modal Start -->
    <flux:modal name="mdl-shift" @cancel="resetForm" position="right" class="max-w-none" variant="flyout">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Work Shift @else Add Work Shift @endif
                    </flux:heading>
                    <flux:subheading>
                        @if($isEditing) Update @else Add new @endif work shift details.
                    </flux:subheading>
                </div>

                <!-- Grid layout for form fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($fieldConfig as $field => $cfg)
                        <div class="@if($cfg['type'] === 'textarea') col-span-2 @endif">
                            @switch($cfg['type'])
                                @case('date')
                                <flux:date-picker
                                        label="{{ $cfg['label'] }}"
                                        wire:model.live="formData.{{ $field }}"
                                        selectable-header
                                />
                                @break

                                @case('boolean')
                                <flux:switch
                                        label="{{ $cfg['label'] }}"
                                        wire:model.live="formData.{{ $field }}"
                                />
                                @break

                                @case('textarea')
                                <flux:textarea
                                        label="{{ $cfg['label'] }}"
                                        wire:model.live="formData.{{ $field }}"
                                        placeholder="{{ $cfg['label'] }}"
                                />
                                @break

                                @default
                                <flux:input
                                        label="{{ $cfg['label'] }}"
                                        wire:model.live="formData.{{ $field }}"
                                        placeholder="{{ $cfg['label'] }}"
                                />
                            @endswitch
                        </div>
                    @endforeach
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end pt-4">
                    <flux:button type="submit" variant="primary">
                        Save
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
    <!-- Modal End -->

    <!-- Work Shifts Grid Layout -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach ($this->list as $rec)
            <flux:card class="p-4 hover:shadow-lg transition-shadow duration-200 bg-zinc-100">
                <div class="space-y-4">
                    <!-- Header with Status -->
                    <div class="flex justify-between items-center">
                        @foreach($fieldConfig as $field => $cfg)
                            @if(in_array($field, $visibleFields) && $field !== 'is_inactive' && $field !== 'name')
                        <div class="font-semibold text-lg">
                            {{ $rec->$field }}
                        </div>
                            @endif
                        @endforeach
                        <flux:switch
                            wire:model="statuses.{{ $rec->id }}"
                            wire:click="toggleStatus({{ $rec->id }})"
                        />
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap gap-2 pt-4">
                        <flux:button class="cursor-pointer" variant="outline" size="sm" icon="wrench-screwdriver"  wire:target="showWorkShiftSetup({{  $rec->id }})" wire:loading.class="opacity-50" wire wire:click="showWorkShiftSetup({{ $rec->id }})">Setup</flux:button>

                        <flux:button class="cursor-pointer" variant="filled" size="sm" icon="calendar-days"  wire:target="showWorkShiftDays({{ $rec->id }})"  wire:loading.class="opacity-50" wire:click="showWorkShiftDays({{ $rec->id }})">Days</flux:button>

                        <flux:button class="cursor-pointer" variant="danger" size="sm" icon="user-group"  wire:target="showEmpWorkShifts({{ $rec->id }})"  wire:loading.class="opacity-50" wire:click="showEmpWorkShifts({{ $rec->id }})">Employees</flux:button>

                    </div>
                </div>
            </flux:card>
        @endforeach
    </div>

    <!-- Work Shift Algos Modal -->
    <flux:modal name="work-shifts-algos-modal" title="Work Shift Algos" class="max-w-5xl">
        @if($selectedShiftId)
            <livewire:hrms.work-shifts.work-shifts-algos
                    :selectedWorkShiftId="$selectedShiftId"
                    :wire:key="'work-shifts-algos-'.$selectedShiftId"/>
        @endif
    </flux:modal>

    <!-- Work Shift Days Modal -->
    <flux:modal name="work-shift-days-modal" title="Work Shift Days" class="max-w-5xl">
        @if($selectedShiftId)
            <livewire:hrms.work-shifts.work-shift-meta.work-shift-days
                    :selectedDayId="$selectedShiftId"
                    :wire:key="'work-shift-days-'.$selectedShiftId"/>
        @endif
    </flux:modal>

    <!-- Employee Work Shifts Modal -->
    <flux:modal name="emp-work-shifts-modal" title="Employee Work Shifts" class="max-w-5xl">
        <livewire:hrms.work-shifts.work-shift-meta.emp-work-shifts
                :work-shift-id="$selectedShiftId"
                :wire:key="'emp-work-shifts-'.$selectedShiftId"/>
    </flux:modal>
</div> 