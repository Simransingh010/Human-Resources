<div class="space-y-6">
    <flux:modal id="calcRuleModal" wire:model="show">
    <x-slot name="title">Edit Calculation Rule</x-slot>

    <div class="space-y-4">
        {{--  Type selector  --}}
        <div>
            <label class="font-semibold">Type</label>
            <select wire:model="rule.type" class="w-full border rounded px-2 py-1">
                <option value="conditional">Conditional</option>
                <option value="operation">Operation</option>
                <option value="function">Function</option>
                <option value="constant">Constant</option>
                <option value="component">Component</option>
            </select>
        </div>

        {{--  Conditional  --}}
        @if($rule['type']==='conditional')
            <div class="border rounded p-3">
                <h5 class="font-semibold">If</h5>
                @include('livewire.payroll.partials.rule-editor', ['path'=>'rule.if'])
            </div>
            <div class="border rounded p-3">
                <h5 class="font-semibold">Then</h5>
                @include('livewire.payroll.partials.rule-editor', ['path'=>'rule.then'])
            </div>
            <div class="border rounded p-3">
                <h5 class="font-semibold">Else</h5>
                @include('livewire.payroll.partials.rule-editor', ['path'=>'rule.else'])
            </div>
        @endif

        {{--  Operation  --}}
        @if($rule['type']==='operation')
            <div>
                <label class="font-semibold">Operator</label>
                <select wire:model="rule.operator" class="w-full border rounded px-2 py-1">
                    <option value="+">+</option>
                    <option value="-">−</option>
                    <option value="*">×</option>
                    <option value="/">÷</option>
                    <option value=">=">&ge;</option>
                    <option value="<=">&le;</option>
                </select>
            </div>

            <div>
                <label class="font-semibold">Operands</label>
                @foreach($rule['operands'] as $i => $operand)
                    <div class="flex items-center space-x-2 mb-2" wire:key="op-{{ $i }}">
                        @include('livewire.payroll.partials.rule-editor', ['path'=>"rule.operands.{$i}"])
                        <button wire:click.prevent="removeOperand({{ $i }})" class="text-red-600">✕</button>
                    </div>
                @endforeach
                <button wire:click.prevent="addOperand" class="text-blue-600">+ Add Operand</button>
            </div>
        @endif

        {{--  Function  --}}
        @if($rule['type']==='function')
            <div>
                <label class="font-semibold">Function Name</label>
                <select wire:model="rule.name" class="w-full border rounded px-2 py-1">
                    <option value="round">round</option>
                    <option value="max">max</option>
                    <option value="min">min</option>
                </select>
            </div>

            <div>
                <label class="font-semibold">Args</label>
                @foreach($rule['args'] as $i => $arg)
                    <div class="flex items-center space-x-2 mb-2" wire:key="arg-{{ $i }}">
                        @include('livewire.payroll.partials.rule-editor', ['path'=>"rule.args.{$i}"])
                        <button wire:click.prevent="removeArg({{ $i }})" class="text-red-600">✕</button>
                    </div>
                @endforeach
                <button wire:click.prevent="addArg" class="text-blue-600">+ Add Arg</button>
            </div>
        @endif

        {{--  Constant  --}}
        @if($rule['type']==='constant')
            <div>
                <label class="font-semibold">Value</label>
                <input type="number" step="any" wire:model="rule.value"
                       class="w-full border rounded px-2 py-1"/>
            </div>
        @endif

        {{--  Component  --}}
        @if($rule['type']==='component')
            <div>
                <label class="font-semibold">Component Key</label>
                <select wire:model="rule.key" class="w-full border rounded px-2 py-1">
                    <option value="">— select —</option>
                    @foreach($components as $key => $title)
                        <option value="{{ $key }}">{{ $title }}</option>
                    @endforeach
                </select>
            </div>
        @endif
    </div>

    <x-slot name="footer">
        <flux:button wire:click="save" variant="primary">Save</flux:button>
    </x-slot>
</flux:modal>

</div>
