<div class="space-y-6">
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-exit-interview" class="flex justify-end">
            <flux:button variant="primary" icon="plus">New</flux:button>
        </flux:modal.trigger>
    </div>
    <flux:separator />

    <flux:card>
        <flux:heading>Filters</flux:heading>
        <div class="flex flex-wrap gap-4">
            @foreach($filterFields as $field => $cfg)
                @if(in_array($field, $visibleFilterFields))
                    <div class="w-1/4">
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
                        @endswitch
                    </div>
                @endif
            @endforeach
            <flux:button.group>
                <flux:button variant="outline" wire:click="clearFilters" icon="x-circle"></flux:button>
                <flux:modal.trigger name="mdl-show-hide-filters">
                    <flux:button variant="outline" icon="bars-3"></flux:button>
                </flux:modal.trigger>
                <flux:modal.trigger name="mdl-show-hide-columns">
                    <flux:button variant="outline" icon="table-cells"></flux:button>
                </flux:modal.trigger>
            </flux:button.group>
        </div>
    </flux:card>

    <flux:modal name="mdl-show-hide-filters" variant="flyout">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Show/Hide Filters</flux:heading>
            </div>
            <div class="flex flex-wrap items-center gap-4">
                <flux:checkbox.group>
                    @foreach($filterFields as $field => $cfg)
                        <flux:checkbox :checked="in_array($field, $visibleFilterFields)" label="{{ $cfg['label'] }}" wire:click="toggleFilterColumn('{{ $field }}')" />
                    @endforeach
                </flux:checkbox.group>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="mdl-show-hide-columns" variant="flyout" position="right">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Show/Hide Columns</flux:heading>
            </div>
            <div class="flex flex-wrap items-center gap-4">
                <flux:checkbox.group>
                    @foreach($fieldConfig as $field => $cfg)
                        <flux:checkbox :checked="in_array($field, $visibleFields)" label="{{ $cfg['label'] }}" wire:click="toggleColumn('{{ $field }}')" />
                    @endforeach
                </flux:checkbox.group>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="mdl-exit-interview" @cancel="resetForm">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading>@if($isEditing) Edit Exit Interview @else Schedule Exit Interview @endif</flux:heading>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($fieldConfig as $field => $cfg)
                        <div class="@if($cfg['type']==='textarea') col-span-2 @endif">
                            @switch($cfg['type'])
                                @case('select')
                                    @php($list = $listsForFields[$cfg['listKey']] ?? [])
                                    <flux:select label="{{ $cfg['label'] }}" searchable wire:model.live="formData.{{ $field }}">
                                        <option value="">Select {{ $cfg['label'] }}</option>
                                        @if($field==='exit_id')
                                            @foreach($list as $opt)
                                                <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
                                            @endforeach
                                        @else
                                            @foreach($list as $val => $lab)
                                                <option value="{{ $val }}">{{ $lab }}</option>
                                            @endforeach
                                        @endif
                                    </flux:select>
                                    @break
                                @case('date')
                                    <flux:date-picker label="{{ $cfg['label'] }}" wire:model.live="formData.{{ $field }}" selectable-header />
                                    @break
                                @case('number')
                                    <flux:input type="number" label="{{ $cfg['label'] }}" wire:model.live="formData.{{ $field }}" />
                                    @break
                                @case('textarea')
                                    <flux:textarea label="{{ $cfg['label'] }}" wire:model.live="formData.{{ $field }}" rows="3" />
                                    @break
                                @default
                                    <flux:input type="text" label="{{ $cfg['label'] }}" wire:model.live="formData.{{ $field }}" />
                            @endswitch
                        </div>
                    @endforeach
                </div>
                <div class="flex justify-end pt-2">
                    <flux:button type="submit" variant="primary">Save</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

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
            @foreach($this->list as $row)
                <flux:table.row :key="$row->id">
                    @foreach($fieldConfig as $field => $cfg)
                        @if(in_array($field, $visibleFields))
                            <flux:table.cell>
                                @switch($field)
                                    @case('exit_id')
                                        {{ $row->exit ? ('Exit #'.$row->exit->id.' - '.($row->exit->employee->fname ?? '').' '.($row->exit->employee->lname ?? '')) : '-' }}
                                        @break
                                    @case('interviewer_id')
                                        {{ $row->interviewer ? ($row->interviewer->fname.' '.$row->interviewer->lname) : '-' }}
                                        @break
                                    @case('interview_date')
                                        {{ $row->interview_date ? date('jS M Y', strtotime($row->interview_date)) : '-' }}
                                        @break
                                    @default
                                        {{ $row->$field }}
                                @endswitch
                            </flux:table.cell>
                        @endif
                    @endforeach
                    <flux:table.cell>
                        <div class="flex gap-2">
                            <flux:button size="sm" variant="primary" icon="pencil" wire:click="edit({{ $row->id }})" />
                            <flux:button size="sm" variant="danger" icon="trash" wire:click="delete({{ $row->id }})" />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>


