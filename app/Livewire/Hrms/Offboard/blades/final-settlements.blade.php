<div class="space-y-6">
    <div class="flex justify-between items-center">
        <flux:heading>Final Settlements</flux:heading>
        <flux:modal.trigger name="mdl-final-settlement">
            <flux:button variant="primary" icon="plus">New Final Settlement</flux:button>
        </flux:modal.trigger>
    </div>

    <flux:separator />

    <!-- Filters -->
    <flux:card>
        <flux:heading>Filters</flux:heading>
        <div class="flex flex-wrap gap-4">
            @foreach($filterFields as $field => $cfg)
                @if(in_array($field, $visibleFilterFields))
                    <div class="w-1/4 min-w-[16rem]">
                        @switch($cfg['type'])
                            @case('select')
                                @php($list = $listsForFields[$cfg['listKey']] ?? [])
                                <flux:select
                                    variant="listbox"
                                    searchable
                                    placeholder="All {{ $cfg['label'] }}"
                                    wire:model="filters.{{ $field }}"
                                    wire:change="applyFilters"
                                >
                                    <flux:select.option value="">All {{ $cfg['label'] }}</flux:select.option>
                                    @if($field==='exit_id')
                                        @foreach($list as $opt)
                                            <flux:select.option value="{{ $opt['id'] }}">{{ $opt['label'] }}</flux:select.option>
                                        @endforeach
                                    @else
                                        @foreach($list as $val => $lab)
                                            <flux:select.option value="{{ $val }}">{{ $lab }}</flux:select.option>
                                        @endforeach
                                    @endif
                                </flux:select>
                                @break
                            @default
                                <flux:input
                                    placeholder="Search {{ $cfg['label'] }}"
                                    wire:model.live.debounce.400ms="filters.{{ $field }}"
                                    wire:change="applyFilters"
                                />
                        @endswitch
                    </div>
                @endif
            @endforeach

            <flux:button.group>
                <flux:button wire:click="clearFilters" >Clear</flux:button>
            </flux:button.group>
        </div>
    </flux:card>

    <!-- Table -->
    <flux:table :paginate="$this->list" class="w-full">
        <flux:table.columns>
            @foreach($fieldConfig as $field => $cfg)
                @if(in_array($field, $visibleFields))
                    <flux:table.column>{{ $cfg['label'] }}</flux:table.column>
                @endif
            @endforeach
            <flux:table.column class="text-right">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->list as $row)
                <flux:table.row :key="$row->id">
                    @foreach($fieldConfig as $field => $cfg)
                        @if(in_array($field, $visibleFields))
                            <flux:table.cell>
                                @switch($field)
                                    @case('employee_id')
                                        {{ $row->employee ? ($row->employee->fname.' '.$row->employee->lname) : '-' }}
                                        @break
                                    @case('exit_id')
                                        {{ $row->exit ? ('Exit #'.$row->exit->id) : '-' }}
                                        @break
                                    @case('disburse_payroll_slot_id')
                                        {{ $row->disbursePayrollSlot->title ?? '-' }}
                                        @break
                                    @case('settlement_date')
                                        {{ $row->settlement_date ? date('jS M Y', strtotime($row->settlement_date)) : '-' }}
                                        @break
                                    @case('fnf_earning_amount')
                                    @case('fnf_deduction_amount')
                                        {{ number_format((float) $row->$field, 2) }}
                                        @break
                                    @default
                                        {{ $row->$field }}
                                @endswitch
                            </flux:table.cell>
                        @endif
                    @endforeach
                    <flux:table.cell class="text-right">
                        <div class="flex justify-end gap-2">
                            <flux:button size="xs" variant="primary" wire:click="edit({{ $row->id }})">Edit</flux:button>
                            <flux:button size="xs" variant="primary" wire:click="delete({{ $row->id }})">Delete</flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <!-- Create/Edit Modal -->
    <flux:modal name="mdl-final-settlement" @cancel="resetForm">
        <form wire:submit.prevent="store">
            <div class="space-y-4 p-4">
                <flux:heading>Final Settlement</flux:heading>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($fieldConfig as $field => $cfg)
                        <div class="@if(in_array($field, ['remarks','additional_rule'])) col-span-2 @endif">
                            @switch($cfg['type'])
                                @case('select')
                                    @php($list = $listsForFields[$cfg['listKey']] ?? [])
                                    <flux:select
                                        label="{{ $cfg['label'] }}"
                                        searchable
                                        wire:model.live="formData.{{ $field }}"
                                    >
                                        <option value="">Select {{ $cfg['label'] }}</option>
                                        @if($field==='exit_id')
                                            @foreach($list as $opt)
                                                <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
                                            @endforeach
                                        @elseif($field==='full_final_status')
                                            @foreach($list as $val => $lab)
                                                <option value="{{ $val }}">{{ $lab }}</option>
                                            @endforeach
                                        @else
                                            @foreach($list as $val => $lab)
                                                <option value="{{ $val }}">{{ $lab }}</option>
                                            @endforeach
                                        @endif
                                    </flux:select>
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
                                        step="0.01"
                                        min="0"
                                    />
                                    @break

                                @case('textarea')
                                    <flux:textarea
                                        label="{{ $cfg['label'] }}"
                                        wire:model.live="formData.{{ $field }}"
                                        rows="3"
                                    />
                                    @break

                                @default
                                    <flux:input
                                        type="text"
                                        label="{{ $cfg['label'] }}"
                                        wire:model.live="formData.{{ $field }}"
                                    />
                            @endswitch
                            @error("formData.{$field}")
                                <flux:text color="red">{{ $message }}</flux:text>
                            @enderror
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary">Save</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>
