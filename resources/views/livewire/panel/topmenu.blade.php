<flux:button.group>
    @foreach ($moduleWires as $wireItem)
        <flux:button
            wire:click="selectWire('{{ $wireItem['wire'] }}')"
            class="{{ $selectedWire === $wireItem['wire'] ? 'bg-primary text-white' : '' }}">
            {{ $wireItem['name'] }}
        </flux:button>
    @endforeach
</flux:button.group>
