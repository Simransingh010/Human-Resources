<div class="space-y-6">
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-salary-hold" class="flex justify-end">
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
                    <div class="w-1/4">
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

    <!-- Add Salary Hold Modal -->
    <flux:modal name="mdl-salary-hold" @cancel="closeHoldModal">
        <form wire:submit.prevent="holdSalary">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Create Salary Hold</flux:heading>
                    <flux:subheading>Select employee, payroll period, and add remarks to hold salary.</flux:subheading>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>

                        <flux:select
                                searchable
                            label="Employee"

                            wire:model.live="selectedEmployee"
                        >
                            <option value="">Select Employee</option>
                            @foreach($listsForFields['employees'] as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </flux:select
>
                        @error('selectedEmployee') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="relative">
                        <flux:select
                            variant="listbox"
                            multiple
                            label="Payroll Period"
                            placeholder="Choose payroll periods..."
                            wire:model.live="selectedPayrollSlots"
                        >
                            @foreach($listsForFields['payrollSlots'] as $id => $period)
                                <flux:select.option value="{{ $id }}">{{ $period }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        @error('selectedPayrollSlots') <span class="text-danger">{{ $message }}</span> @enderror

                        <!-- Loader overlay -->
                        <div
                            wire:loading
                            wire:target="selectedEmployee"
                            class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-60 z-10"
                            style="min-height: 40px;"
                        >
                            <flux:icon.loading />
                        </div>
                    </div>
                    <div class="col-span-2">
                        <flux:textarea
                            label="Remarks"
                            wire:model.live="remarks"
                            rows="3"
                        />
                        @error('remarks') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="flex justify-end pt-4">
                    <flux:button type="submit" variant="primary">
                        Create Hold
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <!-- Data Table -->
    <flux:table :paginate="$this->list" class="w-full">
        <flux:table.columns>
            @foreach($fieldConfig as $field => $cfg)
                @if(in_array($field, $visibleFields))
                    <flux:table.column>{{ $cfg['label'] }}</flux:table.column>
                @endif
            @endforeach
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->list as $hold)
                <flux:table.row :key="$hold->id">
                    @foreach($fieldConfig as $field => $cfg)
                        @if(in_array($field, $visibleFields))
                            <flux:table.cell class="table-cell-wrap">
                                @switch($field)
                                    @case('employee_name')
                                        {{ $hold->employee->fname }} {{ $hold->employee->lname }}
                                        @break
                                    @case('payroll_period')
                                        {{ $hold->payrollSlot->from_date->format('jS F Y') }} to {{ $hold->payrollSlot->to_date->format('jS F Y') }}
                                        @break
                                    @case('created_at')
                                        {{ $hold->created_at->format('jS F Y H:i:s') }}
                                        @break
                                    @default
                                        {{ $hold->$field }}
                                @endswitch
                            </flux:table.cell>
                        @endif
                    @endforeach
                    <flux:table.cell>
                        <flux:button variant="danger" size="sm" icon="trash" wire:click="removeHold({{ $hold->id }})" tooltip="Remove Hold" />
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>