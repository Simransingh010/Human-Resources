<div class="overflow-x-scroll">
<flux:button.group>
    @foreach ($moduleWires as $wireItem)
        @php
            $isRouteBased = \App\Services\MenuCoordinator::isRouteBased($wireItem['wire']);
            $routeUrl = $isRouteBased 
                ? \App\Services\MenuCoordinator::getRouteUrl($wireItem['wire'], $currentModuleId, session('selectedAppId'))
                : null;
        @endphp
        
        @if($isRouteBased && $routeUrl)
            <a href="{{ $routeUrl }}" wire:navigate
               @click="$dispatch('navigation-started')"
               x-on:livewire:navigated.window="$dispatch('navigation-ended')">
                <flux:button
                    class="{{ $selectedWire === $wireItem['wire'] ? 'bg-primary text-white' : '' }}">
                    {{ $wireItem['name'] }}
                </flux:button>
            </a>
        @else
            <flux:button
                wire:click="selectWire('{{ $wireItem['wire'] }}')"
                @click="$dispatch('navigation-started')"
                class="{{ $selectedWire === $wireItem['wire'] ? 'bg-primary text-white' : '' }}">
                {{ $wireItem['name'] }}
            </flux:button>
        @endif
    @endforeach
</flux:button.group>
</div>
