<div class="space-y-6">
    <div>
        <flux:heading size="lg">{{ $panel->name }} ({{ $panel->code }})</flux:heading>
        <flux:text class="text-gray-500">Manage associated components for this panel</flux:text>
    </div>

    <flux:separator/>

    <div class="space-y-4 overflow-y-auto max-h-[70vh] pr-2">
        <flux:accordion class="w-full">
            @foreach ($groupedComponents as $appName => $modules)
                <flux:accordion.item>
                    <flux:accordion.heading>
                        {{ $appName }}

                    </flux:accordion.heading>
                    <flux:accordion.content class="pl-4">
                        <div class="flex justify-end space-x-2 mb-2">
                            <flux:button size="xs" variant="outline" wire:click="selectApp('{{ $appName }}')">Select All</flux:button>
                            <flux:button size="xs" variant="ghost" wire:click="deselectApp('{{ $appName }}')">Deselect</flux:button>
                        </div>
                        <flux:accordion variant="reverse">
                            @foreach ($modules as $moduleName => $components)
                                <flux:accordion.item>
                                    <flux:accordion.heading>
                                        {{ $moduleName }}

                                    </flux:accordion.heading>
                                    <flux:accordion.content class="pl-4">
                                        <div class="flex justify-end space-x-2 mb-2">
                                            <flux:button size="xs" variant="ghost" wire:click="toggleModule('{{ $appName }}', '{{ $moduleName }}')">
                                                Toggle
                                            </flux:button>
                                        </div>
                                        <flux:checkbox.group class="space-y-1">
                                            @foreach ($components as $componentRec)
                                                <div class="flex justify-start space-x-2 mb-2">
                                                    <flux:checkbox
                                                            wire:model="selectedComponents"
                                                            class="w-full truncate"
                                                            label="{{ $componentRec['name'] }}"
                                                            value="{{ $componentRec['id'] }}"
                                                    />
                                                    <flux:tooltip toggleable>
                                                        <flux:button icon="information-circle" size="xs" variant="ghost" />
                                                        <flux:tooltip.content class="max-w-[20rem] space-y-2">
                                                            {{ $componentRec['wire'] }}
                                                        </flux:tooltip.content>
                                                    </flux:tooltip>
                                                </div>
                                            @endforeach
                                        </flux:checkbox.group>
                                    </flux:accordion.content>
                                </flux:accordion.item>
                            @endforeach
                        </flux:accordion>
                    </flux:accordion.content>
                </flux:accordion.item>
            @endforeach
        </flux:accordion>
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