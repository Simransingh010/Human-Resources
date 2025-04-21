<div>
    <div class="flex justify-between mt-2">
        <div>
            <flux:heading size="lg" class="flex">
                <flux:icon.cog />
                Work Shift Algorithms
            </flux:heading>
            <flux:subheading>
                Configure work shift algorithms and rules.
            </flux:subheading>
        </div>
        <div>
            <flux:modal.trigger name="mdl-shift-algo">
                <flux:button icon="plus" variant="primary" wire:click="resetForm()"
                             class="bg-blue-500 text-white mt-2 rounded-md">
                    New
                </flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    <flux:modal name="mdl-shift-algo" @close="resetForm" position="right" class="max-w-none">
        <form wire:submit.prevent="saveAlgo">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg" class="flex">
                        <flux:icon.cog />
                        @if($isEditing) Edit Work Shift Algorithm @else New Work Shift Algorithm @endif
                    </flux:heading>
                    <flux:subheading>
                        Configure work shift algorithm settings.
                    </flux:subheading>
                </div>
                <flux:separator/>

                <!-- Basic Information -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:select label="Work Shift" wire:model="algoData.work_shift_id">
                        <option value="">Select Work Shift</option>
                        @foreach($this->workShifts as $shift)
                            <option value="{{ $shift->id }}">{{ $shift->shift_title }}</option>
                        @endforeach
                    </flux:select>

                    <flux:input label="Week Off Pattern" wire:model="algoData.week_off_pattern" 
                        placeholder="e.g. Sunday,Saturday" />
                    <flux:input type="date" label="Start Date" wire:model="algoData.start_date" />
                    <flux:input type="date" label="End Date" wire:model="algoData.end_date" />
                    <flux:input type="time" label="Start Time" wire:model="algoData.start_time" />
                    <flux:input type="time" label="End Time" wire:model="algoData.end_time" />
                </div>

                <!-- Work Breaks and Calendar -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:textarea label="Work Breaks" wire:model="algoData.work_breaks" 
                        placeholder="Enter work breaks configuration" />
                    <flux:select label="Holiday Calendar" wire:model="algoData.holiday_calendar_id">
                        <option value="">Select Holiday Calendar</option>
                        @foreach($this->holidayCalendars as $calendar)
                            <option value="{{ $calendar->id }}">{{ $calendar->title }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <!-- Rules and Configurations -->
                <div class="grid grid-cols-1 gap-4">
                    <flux:textarea label="Half Day Rule" wire:model="algoData.half_day_rule" 
                        placeholder="Enter half day rule configuration" />
                    
                    <flux:textarea label="Overtime Rule" wire:model="algoData.overtime_rule" 
                        placeholder="Enter overtime rule configuration" />
                    
                    <flux:textarea label="Late Penalty" wire:model="algoData.late_panelty" 
                        placeholder="Enter late penalty configuration" />
                    
                    <flux:textarea label="Additional Rules Configuration" wire:model="algoData.rules_config" 
                        placeholder="Enter additional rules configuration in JSON format" />
                </div>

                <!-- Checkboxes -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:checkbox wire:model="algoData.allow_wfh" label="Allow Work From Home" />
                    <flux:checkbox wire:model="algoData.comp_off" label="Compensatory Off" />
                    <flux:checkbox wire:model="algoData.is_inactive" label="Is Inactive" />
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

    <flux:separator class="mb-3 mt-3" />

    <flux:table>
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column sortable :sorted="$sortBy === 'work_shift_id'" :direction="$sortDirection"
                wire:click="sort('work_shift_id')">Work Shift</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'start_date'" :direction="$sortDirection"
                wire:click="sort('start_date')">Start Date</flux:table.column>
            <flux:table.column>Timing</flux:table.column>
            <flux:table.column>Week Off Pattern</flux:table.column>
            <flux:table.column>Holiday Calendar</flux:table.column>
            <flux:table.column>Features</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->algosList as $algo)
                <flux:table.row :key="$algo->id" class="border-b">
                    <flux:table.cell>
                        <flux:badge size="sm" color="blue" inset="top bottom">
                            {{ $algo->work_shift->shift_title }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $algo->start_date ? \Carbon\Carbon::parse($algo->start_date)->format('Y-m-d') : 'N/A' }}
                        @if($algo->end_date)
                            <br/>
                            <span class="text-sm text-gray-500">
                                to {{ \Carbon\Carbon::parse($algo->end_date)->format('Y-m-d') }}
                            </span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $algo->start_time }} - {{ $algo->end_time }}
                    </flux:table.cell>
                    <flux:table.cell>{{ $algo->week_off_pattern ?? 'N/A' }}</flux:table.cell>
                    <flux:table.cell>
                        @if($algo->holiday_calendar)
                            <flux:badge size="sm" color="yellow" inset="top bottom">
                                {{ $algo->holiday_calendar->title }}
                            </flux:badge>
                        @else
                            <span class="text-gray-400">None</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex flex-wrap gap-1">
                            @if($algo->allow_wfh)
                                <flux:badge size="sm" color="green" inset="top bottom">WFH</flux:badge>
                            @endif
                            @if($algo->comp_off)
                                <flux:badge size="sm" color="purple" inset="top bottom">Comp-Off</flux:badge>
                            @endif
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:switch
                            wire:model="algoData.is_inactive"
                            wire:click="toggleStatus({{ $algo->id }})"
                            :checked="!$algo->is_inactive"
                        />
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-center space-x-2">
                            <flux:button variant="primary" size="sm" icon="pencil"
                                wire:click="fetchAlgo({{ $algo->id }})"></flux:button>
                            <flux:modal.trigger name="delete-algo-{{ $algo->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>

                        <flux:modal name="delete-algo-{{ $algo->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete algorithm?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this work shift algorithm.</p>
                                        <p>This action cannot be reversed.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                        wire:click="deleteAlgo({{ $algo->id }})"></flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div> 