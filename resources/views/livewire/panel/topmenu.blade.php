<div 
    class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-transparent"
    x-data="{
        isNavigating: false,
        init() {
            this.scrollToActive();
        },
        scrollToActive() {
            this.$nextTick(() => {
                const activeBtn = this.$el.querySelector('[data-active=true]');
                if (activeBtn) {
                    activeBtn.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
                }
            });
        }
    }"
    @navigation-started.window="isNavigating = true"
    @navigation-ended.window="isNavigating = false; scrollToActive()"
    x-on:livewire:navigated.window="isNavigating = false; scrollToActive()"
    wire:key="topmenu-{{ $currentModuleId }}-{{ $selectedWire }}"
>
    @if(count($moduleWires) > 0)
    <flux:button.group>
        @foreach ($moduleWires as $wireItem)
            @php
                $wire = $wireItem['wire'];
                $isActive = $selectedWire === $wire;
                $isRouteBased = \App\Services\MenuCoordinator::isRouteBased($wire);
                $routeUrl = $isRouteBased ? \App\Services\MenuCoordinator::getRouteUrl($wire) : null;
            @endphp
            
            @if($isRouteBased && $routeUrl)
                <a href="{{ $routeUrl }}" 
                   wire:navigate
                   @click="if(isNavigating) { $event.preventDefault(); return; } isNavigating = true; $dispatch('navigation-started')"
                   data-active="{{ $isActive ? 'true' : 'false' }}"
                   :class="{ 'pointer-events-none': isNavigating }">
                    <flux:button
                        variant="{{ $isActive ? 'primary' : 'ghost' }}"
                        :disabled="isNavigating"
                        class="{{ $isActive ? '!bg-primary !text-white font-semibold' : '' }} transition-all">
                        {{ $wireItem['name'] }}
                    </flux:button>
                </a>
            @else
                <flux:button
                    wire:click="selectWire('{{ $wire }}')"
                    @click="if(isNavigating) { $event.preventDefault(); return; } isNavigating = true; $dispatch('navigation-started')"
                    variant="{{ $isActive ? 'primary' : 'ghost' }}"
                    data-active="{{ $isActive ? 'true' : 'false' }}"
                    :disabled="isNavigating"
                    :class="{ 'pointer-events-none': isNavigating }"
                    class="{{ $isActive ? '!bg-primary !text-white font-semibold' : '' }} transition-all">
                    {{ $wireItem['name'] }}
                </flux:button>
            @endif
        @endforeach
    </flux:button.group>
    @else
        <div class="text-gray-400 text-sm py-2">No menu items available</div>
    @endif
</div>
