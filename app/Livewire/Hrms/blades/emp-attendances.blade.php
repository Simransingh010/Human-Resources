<div class="w-full p-0 m-0">
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-emp-attendance" class="flex justify-end">
            <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">
                @if($isEditing)
                    Edit Attendance Record
                @else
                    New
                @endif
            </flux:button>
        </flux:modal.trigger>
    </div>
    <flux:separator class="mt-2 mb-2" />
    <flux:modal name="mdl-emp-attendance" @close="resetForm" class="max-w-none min-w-[360px] bg-neutral-100">
        <form wire:submit.prevent="saveAttendance">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Attendance Record @else Add Attendance Record @endif
                    </flux:heading>
                    <flux:subheading>
                        Configure employee attendance record details.
                    </flux:subheading>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <flux:input
                        label="Employee"
                        wire:model="attendanceData.employee_id"
                        placeholder="Enter employee name"
                    />

                    <flux:input
                        type="date"
                        label="Work Date"
                        wire:model="attendanceData.work_date"
                    />

                    <flux:select
                        label="Work Shift Day"
                        wire:model="attendanceData.work_shift_day_id"
                    >
                        <option value="">Select Work Shift Day</option>
                        @foreach($this->workShiftDaysList as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select
                        label="Attendance Status"
                        wire:model="attendanceData.attendance_status_main"
                    >
                        <option value="">Select Status</option>
                        <option value="1">Present</option>
                        <option value="2">Absent</option>
                        <option value="3">Half Day</option>
                        <option value="4">Leave</option>
                        <option value="5">Holiday</option>
                    </flux:select>

                    {{--                    <flux:input--}}
                    {{--                        label="Location"--}}
                    {{--                        wire:model="attendanceData.attend_location_id"--}}
                    {{--                        placeholder="Enter location"--}}
                    {{--                    />--}}

                    <flux:input
                        type="number"
                        label="Ideal Working Hours"
                        wire:model="attendanceData.ideal_working_hours"
                        step="0.01"
                        min="0"
                        max="24"
                    />

                    <flux:input
                        type="number"
                        label="Actual Worked Hours"
                        wire:model="attendanceData.actual_worked_hours"
                        step="0.01"
                        min="0"
                        max="24"
                    />

                    <flux:input
                        type="number"
                        label="Day Weightage"
                        wire:model="attendanceData.final_day_weightage"
                        step="0.01"
                        min="0"
                        max="1"
                    />

                    <flux:textarea
                        label="Remarks"
                        wire:model="attendanceData.attend_remarks"
                        placeholder="Enter remarks"
                    />
                </div>

                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary">
                        Save Changes
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:separator class="mt-2 mb-2" />

    <!-- Filters Start -->
    <flux:card>
        <flux:heading>Filters</flux:heading>
        <div class="flex flex-wrap gap-4">
            @foreach($filterFields as $field => $cfg)
                @if(in_array($field, $visibleFilterFields))
                    <div class="w-1/4">
                        @switch($cfg['type'])
                            @case('daterange')
                                <flux:date-picker 
                                    with-today 
                                    mode="range" 
                                    with-presets 
                                    wire:model.live.debounce.500ms="filters.{{ $field }}"
                                    wire:change="applyFilters"
                                />
                                @break

                            @case('select')
                                <flux:select
                                    variant="listbox"
                                    searchable
                                    multiple
                                    placeholder="All {{ $cfg['label'] }}"
                                    wire:model.live.debounce.500ms="filters.{{ $field }}"
                                    wire:change="applyFilters"
                                >
                                    @foreach($listsForFields[$cfg['listKey']] as $val => $lab)
                                        <flux:select.option value="{{ $val }}">{{ $lab }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                @break

                            @default
                                <flux:input
                                    placeholder="Search {{ $cfg['label'] }}"
                                    wire:model.live.debounce.500ms="filters.{{ $field }}"
                                    wire:change="applyFilters"
                                />
                        @endswitch
                    </div>
                @endif
            @endforeach

            <flux:button.group>
                <flux:button variant="outline" wire:click="clearFilters" tooltip="Clear Filters" icon="x-circle"></flux:button>
                <flux:modal.trigger name="mdl-show-hide-filters">
                    <flux:button variant="outline" tooltip="Set Filters" icon="bars-3"></flux:button>
                </flux:modal.trigger>
                <flux:modal.trigger name="mdl-show-hide-columns">
                    <flux:button variant="outline" tooltip="Set Columns" icon="table-cells"></flux:button>
                </flux:modal.trigger>
            </flux:button.group>
        </div>
    </flux:card>

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

    <flux:table :paginate="$this->attendancesList">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column sortable :sorted="$sortBy === 'work_date'" :direction="$sortDirection"
                               wire:click="sort('work_date')">Work Date
            </flux:table.column>
            <flux:table.column>Employee</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->attendancesList as $attendance)
                <flux:table.row :key="$attendance->id" class="border-b">
                    <flux:table.cell>{{ $attendance->work_date->format('d-M-Y') }}</flux:table.cell>
                    <flux:table.cell>{{ $attendance->employee->fname }} {{ $attendance->employee->lname }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="green"> {{ $attendance->attendance_status_main_label ?? '-' }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-center space-x-2">
                            <flux:button
                                wire:click="showAppSync({{ $attendance->id }})"
                                color="green"
                                size="sm"
                                icon="eye"
                                tooltip="View Punches"
                            >
                                Punches
                            </flux:button>
                            <flux:button variant="outline" size="sm" icon="pencil"
                                         wire:click="fetchAttendance({{ $attendance->id }})"></flux:button>
                            <flux:modal.trigger name="delete-attendance-{{ $attendance->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>

                        <flux:modal name="delete-attendance-{{ $attendance->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete Attendance Record?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this attendance record.</p>
                                        <p>This action cannot be reversed.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer/>
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                                 wire:click="deleteAttendance({{ $attendance->id }})"></flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal name="view-punches" title="Punch Records" class="max-w-5xl">
        @if($selectedId)
            <livewire:hrms.attendance-meta.view-punches :attendance-id="$selectedId"
                                                        :wire:key="'view-punches-'.$selectedId"/>
        @endif
    </flux:modal>
</div>
