@props(['path' => '', 'operation'])

<div class="nested-operation-container ml-4 p-4 border-l-2 border-blue-200">
    <!-- Operator Selection -->
    <div class="mb-4">
        <flux:label>Operator</flux:label>
        <flux:select wire:model.live="rule.{{ $path }}.operator">
            @foreach($operators as $op => $label)
                <flux:select.option value="{{ $op }}">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <!-- Operands -->
    <div class="space-y-4">
        <div class="flex justify-between items-center">
            <flux:label>Operands</flux:label>
            <flux:button wire:click="addOperand('{{ $path }}')" variant="secondary" size="sm">
                Add Operand
            </flux:button>
        </div>

        @foreach($operation['operands'] ?? [] as $i => $operand)
            <div class="relative p-4 border rounded-lg bg-white shadow-sm">
                <div class="flex items-center gap-4 mb-4">
                    <div class="flex-1">
                        <flux:select wire:model.live="rule.{{ $path }}.operands.{{ $i }}.type">
                            <flux:select.option value="component">Salary Component</flux:select.option>
                            <flux:select.option value="constant">Fixed Value</flux:select.option>
                            <flux:select.option value="operation">Nested Operation</flux:select.option>
                        </flux:select>
                    </div>
                    <flux:button wire:click="removeOperand('{{ $path }}', {{ $i }})" variant="danger" size="sm">
                        Remove
                    </flux:button>
                </div>

                @if($operand['type'] === 'operation')
                    @include('livewire.hrms.payroll.blades.partials.nested-operation', [
                        'path' => $path . ".operands.{$i}",
                        'operation' => $operand
                    ])
                @elseif($operand['type'] === 'component')
                    <div class="mt-2">
                        <flux:select wire:model.live="rule.{{ $path }}.operands.{{ $i }}.key">
                            @foreach($salaryComponents as $id => $component)
                                @php
                                    $title = $this->getComponentTitle($component);
                                @endphp
                                <flux:select.option value="{{ $id }}">{{ $title }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                @else
                    <div class="mt-2">
                        <flux:input 
                            type="number" 
                            step="0.01" 
                            wire:model.live="rule.{{ $path }}.operands.{{ $i }}.value" 
                            placeholder="Enter value" 
                        />
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div> 