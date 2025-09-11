<div class="space-y-6">
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-employee-exit-initiation" class="flex justify-end">
            <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">
                New Exit Request
            </flux:button>
        </flux:modal.trigger>
    </div>

    <flux:separator class="mt-2 mb-2" />

    <!-- Filters -->
    <flux:card>
        <flux:heading>Filters</flux:heading>
        <div class="flex flex-wrap gap-4">
            @foreach($filterFields as $field => $cfg)
                @if(in_array($field, $visibleFilterFields))
                    <div class="w-1/4 min-w-[16rem]">
                        @switch($cfg['type'])
                            @case('select')
                                <flux:select
                                    variant="listbox"
                                    searchable
                                    placeholder="All {{ $cfg['label'] }}"
                                    wire:model="filters.{{ $field }}"
                                    wire:change="applyFilters"
                                >
                                    <flux:select.option value="">All {{ $cfg['label'] }}</flux:select.option>
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
                <flux:button wire:click="clearFilters"  />
            </flux:button.group>
        </div>
    </flux:card>

    <!-- Create/Edit Modal -->
    <flux:modal name="mdl-employee-exit-initiation" @cancel="resetForm">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading >Exit Request Initiation</flux:heading>
                    <flux:subheading>Search employee, set exit details, and save to generate the workflow.</flux:subheading>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($fieldConfig as $field => $cfg)
                        <div class="@if($cfg['type'] === 'textarea') col-span-2 @endif">
                            @switch($cfg['type'])
                                @case('select')
                                    <flux:select
                                        label="{{ $cfg['label'] }}"
                                        searchable
                                        wire:model.live="formData.{{ $field }}"
                                        required="{{ in_array($field, ['employee_id','exit_type','exit_reason','exit_request_date']) }}"
                                    >
                                        <option value="">Select {{ $cfg['label'] }}</option>
                                        @foreach($listsForFields[$cfg['listKey']] as $val => $lab)
                                            <option value="{{ $val }}">{{ $lab }}</option>
                                        @endforeach
                                    </flux:select>
                                    @error("formData.{$field}")
                                        <flux:text color="red" >{{ $message }}</flux:text>
                                    @enderror
                                    @break

                                @case('textarea')

                                    @break

                                @case('date')
                                    <flux:date-picker
                                        label="{{ $cfg['label'] }}"
                                        wire:model.live="formData.{{ $field }}"
                                        selectable-header
                                    />
                                    @break

                                @case('number')
                                    <flux:input
                                        type="number"
                                        label="{{ $cfg['label'] }}"
                                        wire:model.live="formData.{{ $field }}"
                                        min="0"
                                        step="1"
                                        required="{{ in_array($field, ['notice_period_days']) }}"
                                    />
                                    @break

                                @default
                                    <flux:input
                                        type="{{ $cfg['type'] }}"
                                        label="{{ $cfg['label'] }}"
                                        wire:model.live="formData.{{ $field }}"
                                    />
                            @endswitch
                        </div>
                    @endforeach
                </div>

                <!-- Auto-filled employee info -->
                <flux:card class="border border-dashed mt-2">
                    <flux:heading >Employee Details</flux:heading>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-2">
                        <div>
                            <flux:label>Employee Code</flux:label>
                            <flux:text>{{ $employeeInfo['employee_code'] ?? '-' }}</flux:text>
                        </div>
                        <div>
                            <flux:label>Department</flux:label>
                            <flux:text>{{ $employeeInfo['department'] ?? '-' }}</flux:text>
                        </div>
                        <div>
                            <flux:label>Designation</flux:label>
                            <flux:text>{{ $employeeInfo['designation'] ?? '-' }}</flux:text>
                        </div>
                    </div>
                </flux:card>

                <div class="flex justify-end pt-4">
                    <flux:button type="submit" variant="primary">Save & Generate Workflow</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <!-- List -->
    <flux:table :paginate="$this->list" class="w-full">
        <flux:table.columns>
            @foreach($fieldConfig as $field => $cfg)
                @if(in_array($field, $visibleFields))
                    <flux:table.column>{{ $cfg['label'] }}</flux:table.column>
                @endif
            @endforeach
            <flux:table.column>Status</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->list as $item)
                <flux:table.row :key="$item->id">
                    @foreach($fieldConfig as $field => $cfg)
                        @if(in_array($field, $visibleFields))
                            <flux:table.cell>
                                @switch($field)
                                    @case('employee_id')
                                        @php($jp = $item->employee->emp_job_profile)
                                        {{ trim(($item->employee->fname ?? '').' '.($item->employee->lname ?? '')) }}
                                        @if($jp && $jp->employee_code)
                                            ({{ $jp->employee_code }})
                                        @endif
                                        @break
                                    @case('exit_type')
                                        {{ \App\Models\Hrms\EmployeeExit::EXIT_TYPES[$item->exit_type] ?? $item->exit_type }}
                                        @break
                                    @case('notice_period_days')
                                        {{ number_format($item->notice_period_days) }}
                                        @break
                                    @case('exit_request_date')
                                    @case('last_working_day')
                                    @case('actual_relieving_date')
                                        {{ $item->$field ? date('jS F Y', strtotime($item->$field)) : '' }}
                                        @break
                                    @default
                                        {{ $item->$field }}
                                @endswitch
                            </flux:table.cell>
                        @endif
                    @endforeach
                    <flux:table.cell>
                        @switch($item->status)
                            @case('completed')
                                <flux:badge color="green">Completed</flux:badge>
                                @break
                            @case('cancelled')
                                <flux:badge color="red">Cancelled</flux:badge>
                                @break
                            @default
                                <flux:badge color="blue">{{ ucfirst(str_replace('_',' ', $item->status)) }}</flux:badge>
                        @endswitch
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>
