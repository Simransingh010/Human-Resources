<div class="space-y-6">
    <div>
        <flux:heading size="lg">{{ $panel->name }} ({{ $panel->code }})</flux:heading>
        <flux:text class="text-gray-500">Manage associated components for this panel</flux:text>
    </div>

    <flux:separator />

    <div class="space-y-4 overflow-y-auto max-h-[60vh] pr-2">
        @foreach ($groupedComponents as $appName => $modules)
            <div class="border p-2 rounded-md  w-full" x-data="{ openApp: true }">
                <h4
                    class="font-bold text-primary mb-2 cursor-pointer flex justify-between items-center"
                    @click="openApp = !openApp"
                >
                    {{ $appName }}
                    <span x-text="openApp ? '−' : '+'"></span>
                </h4>

                <div x-show="openApp"  x-transition>
                    @foreach ($modules as $moduleName => $components)
                        <div class="ml-3 mb-3"  x-data="{ openModule: true }">
                            <h5
                                class="text-sm font-semibold text-gray-700 cursor-pointer flex justify-between items-center"
                                @click="openModule = !openModule"
                            >
                                {{ $moduleName }}
                                <span x-text="openModule ? '−' : '+'"></span>
                            </h5>

                            <div x-show="openModule" x-transition>
                                <flux:checkbox.group class="ml-2 mt-2 space-y-1">
                                    @foreach ($components as $componentRec)
                                        <flux:checkbox
                                            wire:model="selectedComponents"
                                            label="{{ $componentRec['name'] }} ({{ $componentRec['wire'] }})"
                                            value="{{ $componentRec['id'] }}"
                                        />
                                    @endforeach
                                </flux:checkbox.group>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4 flex justify-end space-x-2">
        <flux:button x-on:click="$flux.modal('component-sync').close()">
            Close
        </flux:button>
        <flux:button wire:click="save" variant="primary">
            Save
        </flux:button>
    </div>
</div>
