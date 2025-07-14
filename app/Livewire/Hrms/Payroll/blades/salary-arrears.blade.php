<div class="space-y-6">
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-salary-arrear" class="flex justify-end">
            <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md" wire:click="openArrearModal">
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

                            @case('date')
                                <flux:date-picker
                                    placeholder="Search {{ $cfg['label'] }}"
                                    wire:model.live="filters.{{ $field }}"
                                    wire:change="$refresh"
                                    selectable-header
                                />
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

    <!-- Add Salary Arrear Modal -->
    <flux:modal name="mdl-salary-arrear" @cancel="closeArrearModal">
        <form wire:submit.prevent="saveArrear">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Salary Arrear @else Create Salary Arrear @endif
                    </flux:heading>
                    <flux:subheading>
                        @if($isEditing) Update the details and save changes. @else Fill in the details to create a new salary arrear. @endif
                    </flux:subheading>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <flux:select
                            label="Employee"
                            wire:model.live="selectedEmployee"
                        >
                            <option value="">Select Employee</option>
                            @foreach($listsForFields['employees'] as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </flux:select>
                        @error('selectedEmployee') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <flux:select
                            label="Component"
                            wire:model.live="salary_component_id"
                        >
                            <option value="">Select Component</option>
                            @foreach($listsForFields['components'] as $id => $title)
                                <option value="{{ $id }}">{{ $title }}</option>
                            @endforeach
                        </flux:select>
                        @error('salary_component_id') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <flux:date-picker
                            label="Effective From"
                            wire:model.live="effective_from"
                            selectable-header
                            placeholder="Select effective from date"
                        />
                        @error('effective_from') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <flux:date-picker
                            label="Effective To"
                            wire:model.live="effective_to"
                            selectable-header
                            placeholder="Select effective to date"
                        />
                        @error('effective_to') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <flux:input
                            type="number"
                            label="Total Amount"
                            wire:model.live="total_amount"
                        />
                        @error('total_amount') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
{{--                    <div>--}}
{{--                        <flux:input--}}
{{--                            type="number"--}}
{{--                            label="Paid Amount"--}}
{{--                            wire:model.live="paid_amount"--}}
{{--                        />--}}
{{--                        @error('paid_amount') <span class="text-danger">{{ $message }}</span> @enderror--}}
{{--                    </div>--}}
                    <div>
                        <flux:input
                            type="number"
                            label="Installments"
                            wire:model.live="installments"
                        />
                        @error('installments') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <span class="text-danger">Installment Amount: ₹{{ $installment_amount }}</span>

                    </div>
                    <div>
                        <flux:select
                            label="Status"
                            wire:model.live="arrear_status"
                        >
                            <option value="">Select Status</option>
                            @foreach($listsForFields['statuses'] as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </flux:select>
                        @error('arrear_status') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <flux:select
                            label="Disburse WEF Payroll Slot"
                            wire:model.live="disburse_wef_payroll_slot_id"
                        >
                            <option value="">Select Payroll Slot</option>
                            @foreach($listsForFields['payrollSlots'] as $id => $period)
                                <option value="{{ $id }}">{{ $period }}</option>
                            @endforeach
                        </flux:select>
                        @error('disburse_wef_payroll_slot_id') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-span-2">
                        <flux:textarea
                            label="Additional Rule"
                            wire:model.live="additional_rule"
                            rows="2"
                        />
                        @error('additional_rule') <span class="text-danger">{{ $message }}</span> @enderror
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
                        @if($isEditing) Save Changes @else Create Arrear @endif
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
                    <flux:table.column class="table-cell-wrap">{{ $cfg['label'] }}</flux:table.column>
                @endif
            @endforeach
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach($this->list as $arrear)
                <flux:table.row :key="$arrear->id" class="table-cell-wrap">
                    @foreach($fieldConfig as $field => $cfg)
                        @if(in_array($field, $visibleFields))
                            <flux:table.cell class="table-cell-wrap">
                                @switch($field)
                                    @case('employee_name')
                                        {{ $arrear->employee->fname }} {{ $arrear->employee->lname }}
                                        @break
                                    @case('salary_component_id')
                                        {{ $arrear->salary_component->title ?? '' }}
                                        @break
                                    @case('effective_from')
                                        {{ $arrear->effective_from ? $arrear->effective_from->format('jS F Y') : '' }}
                                        @break
                                    @case('effective_to')
                                        {{ $arrear->effective_to ? $arrear->effective_to->format('jS F Y') : '' }}
                                        @break
                                    @case('disburse_wef_payroll_slot_id')
                                        {{ $listsForFields['payrollSlots'][$arrear->disburse_wef_payroll_slot_id] ?? '' }}
                                        @break
                                    @case('created_at')
                                        {{ $arrear->created_at->format('jS F Y H:i:s') }}
                                        @break
                                    @default
                                        {{ $arrear->$field }}
                                @endswitch
                            </flux:table.cell>
                        @endif
                    @endforeach
                    <flux:table.cell>
                        <div class="flex space-x-2">
                            <flux:button variant="primary" size="sm" icon="pencil" wire:click="edit({{ $arrear->id }})" tooltip="Edit" />
                            <flux:button variant="danger" size="sm" icon="trash" wire:click="removeArrear({{ $arrear->id }})" tooltip="Remove Arrear" />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div> 