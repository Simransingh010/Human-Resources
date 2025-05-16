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
    <flux:card>
        <flux:heading>Filters</flux:heading>
        <div class="flex flex-wrap gap-4">
            @foreach($filterFields as $field => $cfg)
                @if(in_array($field, $visibleFilterFields))
                    <div class="w-1/4" wire:key="filter-field-{{ $field }}">
                        @switch($cfg['type'])
                            @case('boolean')
                                <flux:select
                                    placeholder="All {{ $cfg['label'] }}"
                                    wire:model.live="filters.{{ $field }}"
                                    wire:change="$refresh"
                                >
                                    <option value="">All {{ $cfg['label'] }}</option>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </flux:select>
                                @break

                            @default
                                <flux:input
                                    placeholder="Search {{ $cfg['label'] }}"
                                    wire:model.live.debounce.500ms="filters.{{ $field }}"
                                    wire:change="$refresh"
                                />
                        @endswitch
                    </div>
                @endif
            @endforeach

            <flux:button.group>
                <flux:button variant="outline" wire:click="clearFilters" tooltip="Clear Filters"
                            icon="x-circle"></flux:button>
                <flux:modal.trigger name="mdl-show-hide-filters">
                    <flux:button variant="outline" tooltip="Set Filters" icon="funnel"></flux:button>
                </flux:modal.trigger>
                <flux:modal.trigger name="mdl-show-hide-columns">
                    <flux:button variant="outline" tooltip="Set Columns" icon="bars-3"></flux:button>
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

    <!-- Modal Start -->
    <flux:modal name="mdl-algo" @cancel="resetForm"  class="max-w-6xl" variant="default">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Algorithm @else Add Algorithm @endif
                    </flux:heading>
                    <flux:subheading>
                        @if($isEditing) Update @else Add new @endif work shift algorithm details.
                    </flux:subheading>
                </div>

                <!-- Grid layout for form fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($fieldConfig as $field => $cfg)
                        <div class="@if($cfg['type'] === 'textarea') col-span-2 @endif">
                            @switch($cfg['type'])
                                @case('select')
                                    <flux:select
                                        label="{{ $cfg['label'] }}"
                                        wire:model.live="formData.{{ $field }}"
                                    >
                                        <option value="">Select {{ $cfg['label'] }}</option>
                                        @foreach($listsForFields[$cfg['listKey']] ?? [] as $id => $title)
                                            <option value="{{ $id }}">{{ $title }}</option>
                                        @endforeach
                                    </flux:select>
                                    @break
                                @case('multiselect')
                                    <flux:select variant="listbox" multiple placeholder="Select {{ $cfg['label'] }}" wire:model.live="formData.{{ $field }}">
                                        @foreach($listsForFields[$cfg['listKey']] ?? [] as $id => $title)
                                            <flux:select.option value="{{ $id }}">{{ $title }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    @break

                                @case('date')
                                    <flux:date-picker
                                        label="{{ $cfg['label'] }}"
                                        wire:model.live="formData.{{ $field }}"
                                        selectable-header
                                    />
                                    @break

                                @case('time')
                                    <flux:input
                                        type="time"
                                        label="{{ $cfg['label'] }}"
                                        wire:model.live="formData.{{ $field }}"
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

    <!-- Table Start-->
    <flux:table :paginate="$this->list">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700 table-cell-wrap">
            @foreach($fieldConfig as $field => $cfg)
                @if(in_array($field, $visibleFields))
                    <flux:table.column wire:key="column-{{ $field }}">{{ $cfg['label'] }}</flux:table.column>
                @endif
            @endforeach
            <flux:table.column class="table-cell-wrap">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->list as $rec)
                <flux:table.row :key="$rec->id" class="border-b">
                    @foreach($fieldConfig as $field => $cfg)
                        @if(in_array($field, $visibleFields))
                            <flux:table.cell class="table-cell-wrap">
                                @switch($field)
                                    @case('work_shift_id')
                                        {{ $rec->work_shift->shift_title ?? 'N/A' }}
                                        @break

                                    @case('holiday_calendar_id')
                                        {{ $rec->holiday_calendar->title ?? 'N/A' }}
                                        @break

                                    @case('work_breaks')
                                        @php
                                            $workBreaks = json_decode($rec->work_breaks ?? '[]', true) ?? [];
                                        @endphp
                                        @foreach($workBreaks as $breakId)
                                            {{ $listsForFields['work_breaks'][$breakId] ?? '' }}<br>
                                        @endforeach
                                        @break

                                    @case('start_date')
                                    @case('end_date')
                                        {{ $rec->$field ? date('jS F Y', strtotime($rec->$field)) : '-' }}
                                        @break

                                    @case('start_time')
                                    @case('end_time')
                                        {{ $rec->$field ? date('H:i', strtotime($rec->$field)) : '-' }}
                                        @break

                                    @case('allow_wfh')
                                    @case('is_inactive')
                                        <flux:switch
                                            wire:model="statuses.{{ $rec->id }}"
                                            wire:click="toggleStatus({{ $rec->id }})"
                                        />
                                        @break
                                    @case('week_off_pattern')
                                        <div class="space-y-2">

                                            @if($displayText)
                                                <div class="text-sm text-gray-600">{{ $displayText }}</div>
                                            @endif

                                            <flux:button 
                                                variant="outline"
                                                size="sm"
                                                icon="cog"
                                                wire:click="configureWeekOffPattern({{ $rec->id }})"
                                            >
                                                Configure Week Off
                                            </flux:button>
                                        </div>
                                        @break
                                    @default
                                        {{ $rec->$field }}
                                @endswitch
                            </flux:table.cell>
                        @endif
                    @endforeach
                    <flux:table.cell>
                        <div class="flex space-x-2">
                            <flux:button
                                variant="primary"
                                size="sm"
                                icon="pencil"
                                wire:click="edit({{ $rec->id }})"
                            />

                            @php
                                $batchStatus = $this->getBatchStatus($rec->id);
                            @endphp

                            @if($batchStatus === 'sync_days_rolled_back' || !$batchStatus)
                            <flux:button wire:click="syncWorkShiftDays({{ $rec->id }})">Sync</flux:button>
                           
                            @endif

                            @if($batchStatus === 'sync_days')
                            <flux:button wire:click="rollbackSync({{ $rec->id }})">Rollback</flux:button>
                            @endif

                            <flux:modal.trigger name="delete-{{ $rec->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"/>
                            </flux:modal.trigger>
                        </div>

                        <!-- Delete Modal -->
                        <flux:modal name="delete-{{ $rec->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
{{--                                    <flux:heading size="lg">Delete Algorithm?</flux:heading>--}}
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

    <!-- Week Off Pattern Modal -->
    <flux:modal name="mdl-week-off" @cancel="resetForm" class="max-w-6xl" variant="default">
        <form wire:submit="saveWeekOffPattern">
            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4"> 
                    <div>
                        <flux:select
                            label="Pattern Type"
                            wire:model.live="weekOffPattern.type"
                        >
                            <option value="">Select Pattern Type</option>
                            @foreach($weekOffTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>

                <!-- Fixed Weekly Pattern -->
                @if($weekOffPattern['type'] === 'fixed_weekly' || $weekOffPattern['type'] === 'combined')
                    <div class="space-y-2">
                        <div class="flex flex-wrap gap-2">
                            @foreach($weekDays as $value => $label)
                                <flux:button
                                    variant="{{ in_array($value, $weekOffPattern['fixed_weekly']['off_days']) ? 'primary' : 'outline' }}"
                                    wire:click="toggleFixedWeekDay({{ $value }})"
                                >
                                    {{ $label }}
                                </flux:button>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- <!-- Rotating Pattern -->
                @if($weekOffPattern['type'] === 'rotating' || $weekOffPattern['type'] === 'combined')
                    <div class="col-span-2">
                        <div class="space-y-4">
                            <div class="flex flex-wrap gap-2">
                                @foreach($weekOffPattern['rotating']['cycle'] as $index => $value)
                                    <flux:button
                                        variant="{{ $value ? 'primary' : 'outline' }}"
                                        wire:click="toggleRotatingDay({{ $index }})"
                                        class="w-24"
                                    >
                                        @if($index === 0)
                                            Sunday
                                        @elseif($index === 1)
                                            Monday
                                        @elseif($index === 2)
                                            Tuesday
                                        @elseif($index === 3)
                                            Wednesday
                                        @elseif($index === 4)
                                            Thursday
                                        @elseif($index === 5)
                                            Friday
                                        @else
                                            Saturday
                                        @endif
                                        <div class="text-xs opacity-75">Day {{ $index + 1 }}</div>
                                    </flux:button>
                                @endforeach
                            </div>
                            <div class="flex gap-4 items-center">
                                <flux:input
                                    type="number"
                                    label="Cycle Offset (0-6)"
                                    wire:model.live="weekOffPattern.rotating.offset"
                                    min="0"
                                    max="6"
                                    class="w-32"
                                />
                                <div class="text-sm text-gray-500">
                                    Offset determines which day in the cycle corresponds to the start date
                                </div>
                            </div>
                        </div>
                    </div>
                @endif --}}

                <!-- Holiday Calendar -->
                @if($weekOffPattern['type'] === 'holiday_calendar' || $weekOffPattern['type'] === 'combined')
                    <div class="col-span-2">
{{--                            <flux:heading size="sm">Holiday Calendar Settings</flux:heading>--}}
                        <flux:select
                            label="Holiday Calendar"
                            wire:model.live="weekOffPattern.holiday_calendar.id"
                        >
                            <option value="">Select Calendar</option>
                            @foreach($listsForFields['holiday_calendars'] ?? [] as $id => $title)
                                <option value="{{ $id }}">{{ $title }}</option>
                            @endforeach
                        </flux:select>
                        <flux:switch
                            label="Include Public Holidays"
                            wire:model.live="weekOffPattern.holiday_calendar.use_public_holidays"
                        />
                    </div>
                @endif

                <!-- Exceptions -->
                <div class="col-span-2">
                    <div class="flex justify-between items-center">
                        <flux:button
                            variant="outline"
                            size="sm"
                            wire:click="addException"
                            icon="plus"
                        >
                            Add Exception
                        </flux:button>
                    </div>
                    <div class="space-y-2">
                        @if(isset($weekOffPattern['exceptions']) && is_array($weekOffPattern['exceptions']))
                            @foreach($weekOffPattern['exceptions'] as $index => $exception)
                                <div class="flex items-center gap-2">
                                    <flux:date-picker
                                        wire:model.live="weekOffPattern.exceptions.{{ $index }}.date"
                                        selectable-header
                                    />
                                    <flux:switch
                                        wire:model.live="weekOffPattern.exceptions.{{ $index }}.off"
                                        label="Off Day"
                                    />
                                    <flux:button
                                        variant="danger"
                                        size="sm"
                                        wire:click="removeException({{ $index }})"
                                        icon="trash"
                                    />
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>

{{--                    <!-- JSON Preview -->--}}
{{--                    <div class="col-span-2">--}}
{{--                        <flux:heading size="sm">JSON Preview</flux:heading>--}}
{{--                        <flux:textarea--}}
{{--                            readonly--}}
{{--                            value="{{ json_encode($weekOffPattern, JSON_PRETTY_PRINT) }}"--}}
{{--                            rows="5"--}}
{{--                        />--}}
{{--                    </div>--}}
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end pt-4">
                    <flux:button type="submit" variant="primary">
                        Save Pattern
                    </flux:button>
                </div>

        </form>
    </flux:modal>
</div>