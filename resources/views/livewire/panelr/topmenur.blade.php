<div class="overflow-x-scroll">
<flux:button.group>
    @foreach ($moduleWires as $wireItem)
        <flux:button
            href="{{ route($wireItem['route']) }}"
            class="{{ request()->route()->getName() === $wireItem['route'] ? 'bg-primary text-white' : '' }}">
            {{ $wireItem['name'] }}
        </flux:button>
    @endforeach
</flux:button.group>
</div>
