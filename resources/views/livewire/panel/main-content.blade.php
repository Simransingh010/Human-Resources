{{--<div class="flex-1 p-4">--}}

{{--    @livewire($selectedWire, [], key($selectedWire))--}}
{{--    @livewire($selectedWire, [], key('main-content-' . $selectedWire))--}}
{{--    @livewire($selectedWire, [], key(session('selectedWire', 'default')))--}}

{{--</div>--}}


<div class="flex-1 p-4">
    @livewire($selectedWire, [], key(session('selectedWire', 'default')))
</div>


{{--<div class="flex-1 p-4" wire:key="main-content-wrapper-{{ $selectedWire ?? 'default' }}-{{ time() }}">--}}
{{--    @livewire($selectedWire ?? 'default-component', [], key('main-content-' . ($selectedWire ?? 'default') . '-' . time()))--}}
{{--</div>--}}
