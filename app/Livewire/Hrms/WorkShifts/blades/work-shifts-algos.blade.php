<div>
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-algo" class="flex justify-end">
            <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">
              New
            </flux:button>
        </flux:modal.trigger>
    </div>
    <flux:separator class="mt-2 mb-2" />
    <!-- Heading End -->

    <!-- Filters Start -->
    <div class="flex flex-wrap gap-4 mb-4">
        <flux:input
            label="Search by Work Shift"
            wire:model.live="filters.search_shift"
            placeholder="Search by work shift..."
            class="w-48"
        />
        <flux:input
            label="Search by Pattern"
            wire:model.live="filters.search_pattern"
            placeholder="Search by pattern..."
            class="w-48"
        />
        <div class="flex items-end">
            <flux:button variant="filled" class="px-2" tooltip="Cancel Filter" icon="x-circle"
                         wire:click="clearFilters()"></flux:button>
        </div>
    </div>
    <!-- Filters End -->

    <!-- Modal Start -->
    <flux:modal name="mdl-algo" @cancel="resetForm" position="right" class="max-w-none" variant="default">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing)
                            Edit Algorithm
                        @else
                            Add Algorithm
                        @endif
                    </flux:heading>
                    <flux:subheading>
                        @if($isEditing)
                            Update
                        @else
                            Add new
                        @endif work shift algorithm details.
                    </flux:subheading>
                </div>

                <!-- Grid layout for form fields -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="relative">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Work Shift</label>
                        <select
                            wire:model="formData.work_shift_id"
                            class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm px-2 py-2"
                        >
                            <option value="">Select Work Shift</option>
                            @foreach($listsForFields['work_shifts'] ?? [] as $id => $title)
                                <option value="{{ $id }}">{{ $title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <flux:input type="date" label="Start Date" wire:model="formData.start_date"/>
                    <flux:input type="date" label="End Date" wire:model="formData.end_date"/>
                    <flux:input type="time" label="Start Time" wire:model="formData.start_time"/>
                    <flux:input type="time" label="End Time" wire:model="formData.end_time"/>
                    <flux:input label="Week Off Pattern" wire:model="formData.week_off_pattern" placeholder="e.g., SUN,SAT"/>
                    
                    <div class="relative">
                        <flux:select
                            variant="listbox"
                            searchable
                            multiple
                            placeholder="Work Breaks"
                            wire:model="formData.work_breaks"
                            wire:key="work-breaks-select"
                        >
                            @foreach($listsForFields['work_breaks'] ?? [] as $id => $title)
                                <flux:select.option value="{{ $id }}">{{ $title }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    <div class="relative">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Holiday Calendar</label>
                        <select
                            wire:model="formData.holiday_calendar_id"
                            class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm px-2 py-2"
                        >
                            <option value="">Select Holiday Calendar</option>
                            @foreach($listsForFields['holiday_calendars'] ?? [] as $id => $title)
                                <option value="{{ $id }}">{{ $title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <flux:switch wire:model.live="formData.allow_wfh" label="Allow Work From Home"/>
                    <flux:textarea label="Half Day Rule" wire:model="formData.half_day_rule" placeholder="Half day rule configuration"/>
                    <flux:textarea label="Overtime Rule" wire:model="formData.overtime_rule" placeholder="Overtime rule configuration"/>
                    <flux:textarea label="Rules Config" wire:model="formData.rules_config" placeholder="Additional rules configuration"/>
                    <flux:textarea label="Late Penalty" wire:model="formData.late_panelty" placeholder="Late penalty configuration"/>
                    <flux:textarea label="Comp Off" wire:model="formData.comp_off" placeholder="Comp off configuration"/>
                    <flux:switch wire:model.live="formData.is_inactive" label="Mark as Inactive"/>
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

    <!-- Table Start-->
    <flux:table :paginate="$this->list" class="">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column>Work Shift</flux:table.column>
            <flux:table.column>Start Date</flux:table.column>
            <flux:table.column>End Date</flux:table.column>
            <flux:table.column>Time</flux:table.column>
            <flux:table.column>Week Off Pattern</flux:table.column>
            <flux:table.column>Work Breaks</flux:table.column>
            <flux:table.column>Holiday Calendar</flux:table.column>
            <flux:table.column>WFH</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->list as $rec)
                <flux:table.row :key="$rec->id" class="border-b">
                    <flux:table.cell class="table-cell-wrap">{{ $rec->work_shift->shift_title }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->start_date }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->end_date }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->start_time }} - {{ $rec->end_time }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->week_off_pattern }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">
                        @php
                            $workBreaks = json_decode($rec->work_breaks ?? '[]', true) ?? [];
                        @endphp
                        @foreach($workBreaks as $breakId)
                            {{ $listsForFields['work_breaks'][$breakId] ?? '' }}<br>
                        @endforeach
                    </flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ optional($rec->holiday_calendar)->title }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->allow_wfh ? 'Yes' : 'No' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:switch
                            wire:model="statuses.{{ $rec->id }}"
                            wire:click="toggleStatus({{ $rec->id }})"
                        />
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex space-x-2">
                            <flux:button
                                variant="primary"
                                size="sm"
                                icon="pencil"
                                wire:click="edit({{ $rec->id }})"
                            />
                            <flux:modal.trigger name="delete-{{ $rec->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"/>
                            </flux:modal.trigger>
                        </div>

                        <!-- Delete Modal -->
                        <flux:modal name="delete-{{ $rec->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete Algorithm?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this work shift algorithm. This action cannot be undone.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer/>
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                        wire:click="delete({{ $rec->id }})"/>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
    <!-- Table End-->
</div>