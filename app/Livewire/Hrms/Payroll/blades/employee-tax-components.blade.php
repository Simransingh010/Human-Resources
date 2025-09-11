<div class="space-y-6"
    x-data="{}"
    @closeModal.window="$dispatch('close-modal', 'employee-tax-components')"
>
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium">Employee Tax Components</h3>
        </div>

        @if(count($this->taxComponents) > 0)
            <!-- Filters Start -->
            <flux:card class="mb-4">
                <flux:heading>Filters</flux:heading>
                <div class="flex flex-wrap gap-4">
                    @foreach($filterFields as $field => $cfg)
                        @if(in_array($field, $visibleFilterFields))
                            <div class="w-1/4">
                                <flux:input
                                    placeholder="Search {{ $cfg['label'] }}"
                                    wire:model.live.debounce.500ms="filters.{{ $field }}"
                                    wire:change="applyFilters"
                                />
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

            @if($this->employees->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                @foreach($fieldConfig as $field => $cfg)
                                    @if(in_array($field, $visibleFields))
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ $cfg['label'] }}
                                        </th>
                                    @endif
                                @endforeach
                                @foreach($taxComponents as $component)
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ $component->title }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($this->employees as $employee)
                                <tr wire:key="emp-{{ $employee->id }}">
                                    @foreach($fieldConfig as $field => $cfg)
                                        @if(in_array($field, $visibleFields))
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                @if($field === 'employee_name')
                                                    {{ trim($employee->fname . ' ' . ($employee->mname ? $employee->mname . ' ' : '') . $employee->lname) }}
                                                    @if(optional($employee->emp_job_profile)->employee_code)
                                                        ({{ optional($employee->emp_job_profile)->employee_code }})
                                                    @endif
                                                @else
                                                    {{ $employee->$field }}
                                                @endif
                                            </td>
                                        @endif
                                    @endforeach
                                    @foreach($this->taxComponents as $taxComponent)
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <flux:input
                                                type="number"
                                                wire:key="cell-{{ $employee->id }}-{{ $taxComponent?->id }}"
                                                wire:model="componentAmounts.{{ $employee->id }}.{{ $taxComponent?->id }}"
                                                class="mt-1 block w-32 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                placeholder="Enter amount"
                                            />
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $this->employees->links() }}
                </div>

                <div class="mt-4 flex justify-end space-x-2">
                    <flux:modal.close>
                        <flux:button>Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button
                        wire:click="saveComponentAmounts"
                    >
                        Save Components
                    </flux:button>
                </div>
            @else
                <div class="text-center py-4 text-gray-500">
                    No employees found matching the filters.
                </div>
            @endif
        @else
            <div class="text-center py-4 text-gray-500">
                No tax components found.
            </div>
        @endif
    </div>
</div> 