<div class="space-y-6">
    <div>
        <flux:heading size="lg">{{ $panel->name }} ({{ $panel->code }})</flux:heading>
        <flux:text class="text-gray-500">Manage associated components for this panel</flux:text>
    </div>

    <flux:separator />

    <div class="space-y-4 overflow-y-auto max-h-[70vh] pr-2">
        <flux:accordion class="w-full">
            @foreach ($groupedComponents as $appName => $modules)
                <flux:accordion.item expanded>
                    <flux:accordion.heading>
                        {{ $appName }}
                    </flux:accordion.heading>
                    <flux:accordion.content class="pl-4">
                        <div class="flex justify-end space-x-2 mb-2">
                            <flux:button size="xs" variant="outline" wire:click="selectApp('{{ $appName }}')">Select All
                            </flux:button>
                            <flux:button size="xs" variant="ghost" wire:click="deselectApp('{{ $appName }}')">Deselect
                            </flux:button>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            @foreach ($modules as $moduleName => $components)
                                <flux:accordion variant="reverse">
                                    <flux:accordion.item expanded>
                                        <flux:accordion.heading class="font-bold text-lg">
                                            {{ $moduleName }}
                                        </flux:accordion.heading>
                                        <flux:accordion.content class="pl-4">
                                            <div class="flex justify-end space-x-2 mb-2">
                                                <flux:button size="xs" variant="ghost"
                                                    wire:click="toggleModule('{{ $appName }}', '{{ $moduleName }}')">
                                                    Toggle
                                                </flux:button>
                                            </div>
                                            <flux:checkbox.group wire:model="selectedComponents"
                                                class="grid grid-cols-2 gap-x-3 gap-y-4">
                                                @foreach ($components as $componentRec)
                                                    <div class="flex items-center min-w-0">
                                                        <div class="truncate" style="flex:1 1 0; min-width:0; max-width:220px;">
                                                            <flux:checkbox
                                                                :checked="in_array($componentRec['id'], $selectedComponents)"
                                                                class="truncate" label="{{ $componentRec['name'] }}"
                                                                value="{{ $componentRec['id'] }}" />
                                                        </div>
                                                        <div style="width:32px; display:flex; justify-content:center;">
                                                            <flux:tooltip toggleable>
                                                                <flux:button icon="information-circle" size="xs" variant="ghost" />
                                                                <flux:tooltip.content class="max-w-[20rem] space-y-2">
                                                                    {{ $componentRec['wire'] }}
                                                                </flux:tooltip.content>
                                                            </flux:tooltip>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </flux:checkbox.group>
                                        </flux:accordion.content>
                                    </flux:accordion.item>
                                </flux:accordion>
                            @endforeach
                        </div>
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

//php artisan make:migration add_firm_id_to_panel_user_table --table=panel_user
