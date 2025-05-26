<div>
<div class="space-y-6">
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-salary-component" class="flex justify-end">
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

    <!-- Add/
     Salary Component Modal -->
    <flux:modal name="mdl-salary-component" @cancel="resetForm">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Salary Component @else Add Salary Component @endif
                    </flux:heading>
                    <flux:subheading>
                        @if($isEditing) Update @else Add new @endif salary component details.
                    </flux:subheading>
                </div>

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
                                        @foreach($listsForFields[$cfg['listKey']] as $val => $lab)
                                            <option value="{{ $val }}">{{ $lab }}</option>
                                        @endforeach
                                    </flux:select>
                                    @break

                                @case('switch')
                                    <flux:switch
                                            label="{{ $cfg['label'] }}"
                                            wire:model.live="formData.{{ $field }}"
                                    />
                                    @break

                                @case('textarea')
                                    @if($field === 'calculation_json')
                                        <flux:textarea
                                                label="{{ $cfg['label'] }}"
                                                wire:model.live="formData.{{ $field }}"
                                                rows="10"
                                                placeholder="Enter JSON calculation rule"
                                        />
                                    @else
                                        <flux:textarea
                                                label="{{ $cfg['label'] }}"
                                                wire:model.live="formData.{{ $field }}"
                                                rows="3"
                                        />
                                    @endif
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

                <div class="flex justify-end pt-4">
                    <flux:button type="submit" variant="primary">
                        Save
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <!-- Calculation Rule Builder Modal -->
    @php
        function renderNestedOperation($path, $operation) {
            // Get salary components from the Livewire component instance
            $salaryComponents = app()->make('Livewire')->getComponent('hrms.payroll.salary-components')->salaryComponents;
            
            $html = '<div class="nested-operation-container ml-4 p-4 border-l-2 border-blue-200">';
            
            // Operator Selection
            $html .= '<div class="mb-4">';
            $html .= '<label class="block text-sm font-medium text-gray-700 mb-2">Operator for Nested Operation</label>';
            $html .= '<flux:select wire:model.live="rule.' . $path . '.operator">';
            $html .= '<flux:select.option value="+">Add (+)</flux:select.option>';
            $html .= '<flux:select.option value="-">Subtract (-)</flux:select.option>';
            $html .= '<flux:select.option value="*">Multiply (×)</flux:select.option>';
            $html .= '<flux:select.option value="/">Divide (÷)</flux:select.option>';
            $html .= '</flux:select>';
            $html .= '</div>';

            // Nested Operands
            $html .= '<div class="space-y-4">';
            $html .= '<div class="flex justify-between items-center">';
            $html .= '<label class="block text-sm font-medium text-gray-700">Nested Operands</label>';
            $html .= '<flux:button size="sm" wire:click="addOperand(\'' . $path . '\')" class="text-sm">';
            $html .= 'Add Nested Operand';
            $html .= '</flux:button>';
            $html .= '</div>';

            foreach ($operation['operands'] ?? [] as $i => $operand) {
                $html .= '<div class="relative p-4 border rounded-lg bg-white shadow-sm">';
                
                // Type Selection
                $html .= '<div class="flex items-center gap-4 mb-4">';
                $html .= '<div class="flex-1">';
                $html .= '<flux:select wire:model.live="rule.' . $path . '.operands.' . $i . '.type">';
                $html .= '<flux:select.option value="component">Salary Component</flux:select.option>';
                $html .= '<flux:select.option value="constant">Fixed Value</flux:select.option>';
                $html .= '<flux:select.option value="operation">Nested Operation</flux:select.option>';
                $html .= '</flux:select>';
                $html .= '</div>';
                $html .= '<flux:button wire:click="removeOperand(\'' . $path . '\', ' . $i . ')" class="text-red-500">';
                $html .= 'Remove';
                $html .= '</flux:button>';
                $html .= '</div>';

                // Content based on type
                if ($operand['type'] === 'operation') {
                    $html .= renderNestedOperation($path . '.operands.' . $i, $operand);
                } elseif ($operand['type'] === 'component') {
                    $html .= '<div class="ml-4">';
                    $html .= '<label class="block text-sm font-medium text-gray-700 mb-2">Select Component</label>';
                    $html .= '<flux:select wire:model.live="rule.' . $path . '.operands.' . $i . '.key">';
                    
                    // Handle different types of $salaryComponents
                    if (is_array($salaryComponents)) {
                        foreach ($salaryComponents as $id => $component) {
                            $title = is_array($component) ? ($component['title'] ?? '') : 
                                    (is_object($component) ? ($component->title ?? '') : '');
                            $html .= '<flux:select.option value="' . htmlspecialchars($id) . '">' . htmlspecialchars($title) . '</flux:select.option>';
                        }
                    } elseif (is_object($salaryComponents) && method_exists($salaryComponents, 'toArray')) {
                        // Handle Collection
                        foreach ($salaryComponents->toArray() as $id => $component) {
                            $title = is_array($component) ? ($component['title'] ?? '') : 
                                    (is_object($component) ? ($component->title ?? '') : '');
                            $html .= '<flux:select.option value="' . htmlspecialchars($id) . '">' . htmlspecialchars($title) . '</flux:select.option>';
                        }
                    } elseif (is_object($salaryComponents)) {
                        // Handle stdClass or other objects
                        foreach ((array)$salaryComponents as $id => $component) {
                            $title = is_array($component) ? ($component['title'] ?? '') : 
                                    (is_object($component) ? ($component->title ?? '') : '');
                            $html .= '<flux:select.option value="' . htmlspecialchars($id) . '">' . htmlspecialchars($title) . '</flux:select.option>';
                        }
                    }
                    
                    $html .= '</flux:select>';
                    $html .= '</div>';
                } else {
                    $html .= '<div class="ml-4">';
                    $html .= '<label class="block text-sm font-medium text-gray-700 mb-2">Enter Value</label>';
                    $html .= '<flux:input type="number" step="0.01" wire:model.live="rule.' . $path . '.operands.' . $i . '.value" placeholder="Enter value" />';
                    $html .= '</div>';
                }

                $html .= '</div>';
            }

            $html .= '</div>';
            $html .= '</div>';
            
            return $html;
        }
    @endphp

    <flux:modal name="mdl-calculation-rule" class="max-w-lg">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-semibold">Calculation Rule Builder</h3>
            </div>

            <div class="grid gap-6">
                <!-- Left Side: Builder Form -->
                <div class="space-y-6 border rounded-lg p-6 bg-white">
                    <!-- Root Type Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Root Type</label>
                        <flux:select wire:model.live="rule.type">
                            <flux:select.option value="operation">Operation (+, -, ×, ÷)</flux:select.option>
                            <flux:select.option value="component">Salary Component</flux:select.option>
                            <flux:select.option value="constant">Fixed Value</flux:select.option>
                        </flux:select>
                    </div>

                    @if($rule['type'] === 'operation')
                        <div class="space-y-4">
                            <!-- Operator Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Select Operator</label>
                                <flux:select wire:model.live="rule.operator">
                                    <flux:select.option value="+">Add (+)</flux:select.option>
                                    <flux:select.option value="-">Subtract (-)</flux:select.option>
                                    <flux:select.option value="*">Multiply (×)</flux:select.option>
                                    <flux:select.option value="/">Divide (÷)</flux:select.option>
                                </flux:select>
                            </div>

                            <!-- Operands -->
                            <div>
                                <div class="flex justify-between items-center mb-4">
                                    <label class="block text-sm font-medium text-gray-700">Operands</label>
                                    <flux:button size="sm" wire:click="addOperand" class="text-sm">
                                        Add Operand
                                    </flux:button>
                                </div>

                                <div class="space-y-4">
                                    @foreach($rule['operands'] ?? [] as $i => $operand)
                                        <div class="p-4 border rounded-lg bg-gray-50">
                                            <div class="flex items-center gap-4 mb-4">
                                                <div class="flex-1">
                                                    <flux:select wire:model.live="rule.operands.{{ $i }}.type">
                                                        <flux:select.option value="component">Salary Component</flux:select.option>
                                                        <flux:select.option value="constant">Fixed Value</flux:select.option>
                                                        <flux:select.option value="operation">Nested Operation</flux:select.option>
                                                    </flux:select>
                                                </div>
                                                <flux:button variant="danger" size="sm" wire:click="removeOperand({{ $i }})" class="text-sm">
                                                    Remove
                                                </flux:button>
                                            </div>

                                            @if($operand['type'] === 'operation')
                                                <div class="ml-4 border-l-2 border-blue-200 pl-4">
                                                    <!-- Nested Operator -->
                                                    <div class="mb-4">
                                                        <label class="block text-sm font-medium text-gray-700 mb-2">Nested Operator</label>
                                                        <flux:select wire:model.live="rule.operands.{{ $i }}.operator">
                                                            <flux:select.option value="+">Add (+)</flux:select.option>
                                                            <flux:select.option value="-">Subtract (-)</flux:select.option>
                                                            <flux:select.option value="*">Multiply (×)</flux:select.option>
                                                            <flux:select.option value="/">Divide (÷)</flux:select.option>
                                                        </flux:select>
                                                    </div>

                                                    <!-- Nested Operands -->
                                                    <div class="space-y-4">
                                                        <div class="flex justify-between items-center mb-4">
                                                            <label class="block text-sm font-medium text-gray-700">Nested Operands</label>
                                                            <flux:button size="sm" wire:click="addNestedOperand({{ $i }})" class="text-sm">
                                                                Add Nested Operand
                                                            </flux:button>
                                                        </div>

                                                        @foreach($operand['operands'] ?? [] as $j => $nestedOperand)
                                                            <div class="p-4 border rounded-lg bg-white">
                                                                <div class="flex items-center gap-4 mb-4">
                                                                    <div class="flex-1">
                                                                        <flux:select wire:model.live="rule.operands.{{ $i }}.operands.{{ $j }}.type">
                                                                            <flux:select.option value="component">Salary Component</flux:select.option>
                                                                            <flux:select.option value="constant">Fixed Value</flux:select.option>
                                                                            <flux:select.option value="operation">Nested Operation</flux:select.option>
                                                                        </flux:select>
                                                                    </div>
                                                                    <flux:button variant="danger" size="sm" wire:click="removeNestedOperand({{ $i }}, {{ $j }})" class="text-sm">
                                                                        Remove
                                                                    </flux:button>
                                                                </div>

                                                                @if($nestedOperand['type'] === 'operation')
                                                                    <div class="ml-4 border-l-2 border-blue-200 pl-4">
                                                                        <!-- Deeply Nested Operator -->
                                                                        <div class="mb-4">
                                                                            <label class="block text-sm font-medium text-gray-700 mb-2">Nested Operator</label>
                                                                            <flux:select wire:model.live="rule.operands.{{ $i }}.operands.{{ $j }}.operator">
                                                                                <flux:select.option value="+">Add (+)</flux:select.option>
                                                                                <flux:select.option value="-">Subtract (-)</flux:select.option>
                                                                                <flux:select.option value="*">Multiply (×)</flux:select.option>
                                                                                <flux:select.option value="/">Divide (÷)</flux:select.option>
                        </flux:select>
                    </div>

                                                                        <!-- Deeply Nested Operands -->
                                                                        <div class="space-y-4">
                                                                            <div class="flex justify-between items-center mb-4">
                                                                                <label class="block text-sm font-medium text-gray-700">Deep Nested Operands</label>
                                                                                    <flux:button size="sm" wire:click="addDeepNestedOperand({{ $i }}, {{ $j }})" class="text-sm">
                                                                                        Add Deep Nested Operand
                                                                                    </flux:button>
                                                                            </div>

                                                                            @foreach($nestedOperand['operands'] ?? [] as $k => $deepOperand)
                                                                                <div class="p-4 border rounded-lg bg-gray-50">
                                                                                    <div class="flex items-center gap-4 mb-4">
                                                                                        <div class="flex-1">
                                                                                            <flux:select wire:model.live="rule.operands.{{ $i }}.operands.{{ $j }}.operands.{{ $k }}.type">
                                                                                                <flux:select.option value="component">Salary Component</flux:select.option>
                                                                                                <flux:select.option value="constant">Fixed Value</flux:select.option>
                        </flux:select>
                    </div>
                                                                                        <flux:button variant="danger" size="sm" wire:click="removeDeepNestedOperand({{ $i }}, {{ $j }}, {{ $k }})" class="text-sm">
                                                                                            Remove
                                                                                        </flux:button>
                </div>

                                                                                    @if($deepOperand['type'] === 'component')
                                                                                        <div class="ml-4">
                                                                                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Component</label>
                                                                                            <flux:select wire:model.live="rule.operands.{{ $i }}.operands.{{ $j }}.operands.{{ $k }}.key">
                                                                                                @foreach($salaryComponents as $id => $component)
                                                                                                    @php
                                                                                                        $title = $this->getComponentTitle($component);
                                                                                                    @endphp
                                                                                                    <flux:select.option value="{{ $id }}">{{ $title }}</flux:select.option>
                        @endforeach
                    </flux:select>
                                                                                        </div>
                                                                                    @else
                                                                                        <div class="ml-4">
                                                                                            <label class="block text-sm font-medium text-gray-700 mb-2">Enter Value</label>
                                                                                            <flux:input 
                                                                                                type="number" 
                                                                                                step="0.01" 
                                                                                                wire:model.live="rule.operands.{{ $i }}.operands.{{ $j }}.operands.{{ $k }}.value" 
                                                                                                placeholder="Enter value" 
                                                                                            />
                                                                                        </div>
                                                                                    @endif
                                                                                </div>
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                @elseif($nestedOperand['type'] === 'component')
                                                                    <div class="ml-4">
                                                                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Component</label>
                                                                        <flux:select wire:model.live="rule.operands.{{ $i }}.operands.{{ $j }}.key">
                                                                            @foreach($salaryComponents as $id => $component)
                                                                                @php
                                                                                    $title = $this->getComponentTitle($component);
                                                                                @endphp
                                                                                <flux:select.option value="{{ $id }}">{{ $title }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                                                    </div>
                                                                @else
                                                                    <div class="ml-4">
                                                                        <label class="block text-sm font-medium text-gray-700 mb-2">Enter Value</label>
                                                                        <flux:input 
                                                                            type="number" 
                                                                            step="0.01" 
                                                                            wire:model.live="rule.operands.{{ $i }}.operands.{{ $j }}.value" 
                                                                            placeholder="Enter value" 
                                                                        />
                                                                    </div>
                                                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
                                            @elseif($operand['type'] === 'component')
                                                <div class="ml-4">
                                                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Component</label>
                                                    <flux:select wire:model.live="rule.operands.{{ $i }}.key">
                                                        @foreach($salaryComponents as $id => $component)
                                                            @php
                                                                $title = $this->getComponentTitle($component);
                                                            @endphp
                                                            <flux:select.option value="{{ $id }}">{{ $title }}</flux:select.option>
                        @endforeach
                    </flux:select>
                                                </div>
                                            @else
                                                <div class="ml-4">
                                                    <label class="block text-sm font-medium text-gray-700 mb-2">Enter Value</label>
                                                    <flux:input 
                                                        type="number" 
                                                        step="0.01" 
                                                        wire:model.live="rule.operands.{{ $i }}.value" 
                                                        placeholder="Enter value" 
                                                    />
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                            </div>
                    </div>
                </div>
            @elseif($rule['type'] === 'component')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Component</label>
                            <flux:select wire:model.live="rule.key">
                                @foreach($salaryComponents as $id => $component)
                                    @php
                                        $title = $this->getComponentTitle($component);
                                    @endphp
                                    <flux:select.option value="{{ $id }}">{{ $title }}</flux:select.option>
                    @endforeach
                </flux:select>
                        </div>
            @elseif($rule['type'] === 'constant')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Enter Value</label>
                            <flux:input type="number" step="0.01" wire:model.live="rule.value" placeholder="Enter value" />
                        </div>
            @endif
        </div>

                <!-- Right Side: JSON Preview -->
                <div class="border rounded-lg p-6 bg-gray-50">
                    <flux:heading class="mb-4">Live JSON Preview</flux:heading>
                    <pre class="bg-white p-4 rounded-lg overflow-auto max-h-[600px] text-sm">{{ json_encode($rule, JSON_PRETTY_PRINT) }}</pre>
                </div>
            </div>

            <div class="flex justify-end gap-4 mt-6">
                <flux:button wire:click="$dispatch('close-modal', { id: 'mdl-calculation-rule' })">Cancel</flux:button>
                <flux:button wire:click="saveRule">Save Rule</flux:button>
            </div>
        </div>
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
            @foreach($this->list as $item)
                <flux:table.row :key="$item->id">
                    @foreach($fieldConfig as $field => $cfg)
                        @if(in_array($field, $visibleFields))
                            <flux:table.cell class="table-cell-wrap">
                                @switch($cfg['type'])
                                    @case('switch')
                                        @if($item->$field)
                                            <flux:badge color="green">Yes</flux:badge>
                                        @else
                                            <flux:badge color="gray">No</flux:badge>
                                        @endif
                                        @break
                                    @case('select')
                                        @if($field === 'salary_component_group_id')
                                            {{ $listsForFields['component_groups'][$item->$field] ?? $item->$field }}
                                        @elseif($field === 'nature')
                                            {{ $listsForFields['natures'][$item->$field] ?? $item->$field }}
                                        @elseif($field === 'component_type')
                                            {{ $listsForFields['component_types'][$item->$field] ?? $item->$field }}
                                        @elseif($field === 'amount_type')
                                            <div class="flex items-center gap-2">
                                                @if(in_array($item->amount_type, ['static_known', 'static_unknown']))
                                                    {{ $listsForFields['amount_types'][$item->$field] ?? $item->$field }}
                                                @else
                                                    <flux:button variant="primary" size="sm" wire:click="openCalculationRule({{ $item->id }})">Configure</flux:button>
                                                @endif
                                            </div>
                                        @else
                                            {{ $item->$field }}
                                        @endif
                                        @break
                                    @case('textarea')
                                        
                                        @break
                                    @default
                                        {{ $item->$field }}
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
                                    wire:click="edit({{ $item->id }})"
                            ></flux:button>
                            <flux:modal.trigger name="delete-{{ $item->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"/>
                            </flux:modal.trigger>
                        </div>


                        <flux:modal name="delete-{{ $item->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete Salary Component?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this salary component. This action cannot be undone.</p>
                                        <p class="mt-2 text-red-500">Note: Salary components with related records cannot be deleted.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer/>
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button variant="danger" icon="trash" wire:click="delete({{ $item->id }})"/>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

</div>
</div>

