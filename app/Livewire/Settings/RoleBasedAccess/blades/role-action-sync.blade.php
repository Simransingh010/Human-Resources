<div class="space-y-6 max-w-7xl">
    <div>
        <flux:heading size="lg">{{ $role->name }}</flux:heading>
        <flux:text class="text-gray-500">Assign actions to this role</flux:text>
    </div>

    <flux:separator />

    <!-- App selection radio group -->
    <div class="mb-4">
        <div class="mb-4">

            <label>App</label>

            <flux:tab.group wire:model="selectedAppName">
                <flux:tabs class="mb-4 px-4">
                    @foreach(array_keys($groupedActions) as $appName)
                        <flux:tab :name="$appName" wire:key="tab-{{ \Illuminate\Support\Str::slug($appName) }}">
                            {{ $appName }}
                        </flux:tab>
                    @endforeach
                </flux:tabs>

                @foreach($groupedActions as $appName => $modules)
                    <flux:tab.panel :name="$appName">
                        <div class="flex justify-end space-x-2 mb-2">
                            <flux:button size="xs" variant="outline" wire:click="selectApp('{{ $appName }}')">Select All
                            </flux:button>
                            <flux:button size="xs" variant="ghost" wire:click="deselectApp('{{ $appName }}')">Deselect
                            </flux:button>
                        </div>
                        <flux:accordion class="w-full">
                            @foreach ($modules as $moduleName => $components)
                                <flux:accordion.item expanded>
                                    <flux:accordion.heading>
                                        {{ $moduleName }}
                                    </flux:accordion.heading>
                                    <flux:accordion.content class="pl-4">
                                        <div class="flex justify-end space-x-2 mb-2">
                                            <flux:button size="xs" variant="ghost"
                                                wire:click="toggleModule('{{ $appName }}', '{{ $moduleName }}')">
                                                Toggle
                                            </flux:button>
                                        </div>
                                        <!-- Grid view for components: two per row -->
                                        @foreach (array_chunk($components, 2, true) as $componentChunk)
                                            <div class="flex flex-row gap-6 mb-4">
                                                @foreach ($componentChunk as $componentName => $actions)
                                                    <div class="flex-1 border rounded p-3 bg-gray-50">
                                                        <div class="font-semibold mb-2 flex items-center justify-between">
                                                            <span>{{ $componentName }}</span>
                                                            <flux:button size="xs" variant="ghost"
                                                                wire:click="toggleComponent('{{ $appName }}', '{{ $moduleName }}', '{{ $componentName }}')">
                                                                Toggle
                                                            </flux:button>
                                                        </div>
                                                        <div class="flex flex-row flex-wrap gap-2">
                                                            @foreach ($actions as $typeKey => $typeData)
                                                                @if(!empty($typeData['clusters']))
                                                                    <div class="mb-6 p-2 rounded {{ $typeData['type_bg'] }}">
                                                                        <div class="text-xs font-bold uppercase tracking-wide mb-2">{{ $typeData['type_label'] }}</div>
                                                                        @foreach ($typeData['clusters'] as $clusterName => $groups)
                                                                            <div class="mb-2">
                                                                                <div class="text-sm font-bold text-purple-700 mb-2">{{ $clusterName }}</div>
                                                                                @foreach ($groups as $group)
                                                                                    <div class="flex items-center space-x-1 mb-1">
                                                                                        <flux:checkbox wire:model="selectedActions"
                                                                                            label="{{ $group['parent']['name'] }}"
                                                                                            value="{{ $group['parent']['id'] }}"
                                                                                            class="truncate font-semibold" />
                                                                                        @if(in_array($typeKey, ['RL','BR']))
                                                                                            <flux:dropdown position="top" align="start">
                                                                                                <flux:button size="xs" variant="outline" icon:trailing="chevron-down">
                                                                                                    {{ in_array($group['parent']['id'], $selectedActions) ? ($actionRecordScopes[$group['parent']['id']] ?? 'none') : 'none' }}
                                                                                                </flux:button>
                                                                                                <flux:menu>
                                                                                                    @foreach(\App\Models\Saas\ActionRole::RECORDS_SCOPE_MAIN_SELECT as $value => $label)
                                                                                                        <flux:menu.item
                                                                                                            wire:click="$set('actionRecordScopes.{{ $group['parent']['id'] }}', '{{ $value }}'); if (!selectedActions.includes({{ $group['parent']['id'] }})) selectedActions.push({{ $group['parent']['id'] }});">
                                                                                                            {{ $label }}
                                                                                                        </flux:menu.item>
                                                                                                    @endforeach
                                                                                                </flux:menu>
                                                                                            </flux:dropdown>
                                                                                        @endif
                                                                                    </div>
                                                                                    @foreach ($group['children'] as $child)
                                                                                        <div class="flex items-center space-x-1 mb-1 pl-6">
                                                                                            <flux:checkbox wire:model="selectedActions"
                                                                                                label="{{ $child['name'] }}"
                                                                                                value="{{ $child['id'] }}"
                                                                                                class="truncate" />
                                                                                            @if(in_array($typeKey, ['RL','BR']))
                                                                                                <flux:dropdown position="top" align="start">
                                                                                                    <flux:button size="xs" variant="outline" icon:trailing="chevron-down">
                                                                                                        {{ in_array($child['id'], $selectedActions) ? ($actionRecordScopes[$child['id']] ?? 'none') : 'none' }}
                                                                                                    </flux:button>
                                                                                                    <flux:menu>
                                                                                                        @foreach(\App\Models\Saas\ActionRole::RECORDS_SCOPE_MAIN_SELECT as $value => $label)
                                                                                                            <flux:menu.item
                                                                                                                wire:click="$set('actionRecordScopes.{{ $child['id'] }}', '{{ $value }}'); if (!selectedActions.includes({{ $child['id'] }})) selectedActions.push({{ $child['id'] }});">
                                                                                                                {{ $label }}
                                                                                                            </flux:menu.item>
                                                                                                        @endforeach
                                                                                                    </flux:menu>
                                                                                                </flux:dropdown>
                                                                                            @endif
                                                                                        </div>
                                                                                    @endforeach
                                                                                @endforeach
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endforeach
                                                @if (count($componentChunk) < 2)
                                                    <div class="flex-1"></div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </flux:accordion.content>
                                </flux:accordion.item>
                            @endforeach
                        </flux:accordion>
                    </flux:tab.panel>
                @endforeach
            </flux:tab.group>
        </div>
    </div>

    <div class="space-y-4 overflow-y-auto max-h-[70vh] pr-2">
        <flux:accordion class="w-full">
            @if($selectedAppName && isset($groupedActions[$selectedAppName]))
                @foreach ([$selectedAppName => $groupedActions[$selectedAppName]] as $appName => $modules)
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
                            <flux:accordion variant="reverse">
                                @foreach ($modules as $moduleName => $components)
                                    <flux:accordion.item expanded>
                                        <flux:accordion.heading>
                                            {{ $moduleName }}
                                        </flux:accordion.heading>
                                        <flux:accordion.content class="pl-4">
                                            <div class="flex justify-end space-x-2 mb-2">
                                                <flux:button size="xs" variant="ghost"
                                                    wire:click="toggleModule('{{ $appName }}', '{{ $moduleName }}')">
                                                    Toggle
                                                </flux:button>
                                            </div>
                                            <flux:accordion variant="reverse">
                                                @foreach ($components as $componentName => $actions)
                                                    <flux:accordion.item expanded>
                                                        <flux:accordion.heading>
                                                            {{ $componentName }}
                                                        </flux:accordion.heading>
                                                        <flux:accordion.content class="pl-4">
                                                            <div class="flex justify-end space-x-2 mb-2">
                                                                <flux:button size="xs" variant="ghost"
                                                                    wire:click="toggleComponent('{{ $appName }}', '{{ $moduleName }}', '{{ $componentName }}')">
                                                                    Toggle
                                                                </flux:button>
                                                            </div>
                                                            <flux:checkbox.group class="space-y-1">
                                                                @foreach ($actions as $typeKey => $typeData)
                                                                    @if(!empty($typeData['clusters']))
                                                                        <div class="mb-6 p-2 rounded {{ $typeData['type_bg'] }}">
                                                                            <div class="text-xs font-bold uppercase tracking-wide mb-2">{{ $typeData['type_label'] }}</div>
                                                                            @foreach ($typeData['clusters'] as $clusterName => $groups)
                                                                                <div class="mb-2">
                                                                                    <div class="text-sm font-bold text-purple-700 mb-2">{{ $clusterName }}</div>
                                                                                    @foreach ($groups as $group)
                                                                                        <div class="flex items-center space-x-1 mb-1">
                                                                                            <flux:checkbox wire:model="selectedActions"
                                                                                                label="{{ $group['parent']['name'] }}"
                                                                                                value="{{ $group['parent']['id'] }}"
                                                                                                class="truncate font-semibold" />
                                                                                            @if(in_array($typeKey, ['RL','BR']))
                                                                                                <flux:dropdown position="top" align="start">
                                                                                                    <flux:button size="xs" variant="outline" icon:trailing="chevron-down">
                                                                                                        {{ in_array($group['parent']['id'], $selectedActions) ? ($actionRecordScopes[$group['parent']['id']] ?? 'none') : 'none' }}
                                                                                                    </flux:button>
                                                                                                    <flux:menu>
                                                                                                        @foreach(\App\Models\Saas\ActionRole::RECORDS_SCOPE_MAIN_SELECT as $value => $label)
                                                                                                            <flux:menu.item
                                                                                                                wire:click="$set('actionRecordScopes.{{ $group['parent']['id'] }}', '{{ $value }}'); if (!selectedActions.includes({{ $group['parent']['id'] }})) selectedActions.push({{ $group['parent']['id'] }});">
                                                                                                                {{ $label }}
                                                                                                            </flux:menu.item>
                                                                                                        @endforeach
                                                                                                    </flux:menu>
                                                                                                </flux:dropdown>
                                                                                            @endif
                                                                                        </div>
                                                                                        @foreach ($group['children'] as $child)
                                                                                            <div class="flex items-center space-x-1 mb-1 pl-6">
                                                                                                <flux:checkbox wire:model="selectedActions"
                                                                                                    label="{{ $child['name'] }}"
                                                                                                    value="{{ $child['id'] }}"
                                                                                                    class="truncate" />
                                                                                                @if(in_array($typeKey, ['RL','BR']))
                                                                                                    <flux:dropdown position="top" align="start">
                                                                                                        <flux:button size="xs" variant="outline" icon:trailing="chevron-down">
                                                                                                            {{ in_array($child['id'], $selectedActions) ? ($actionRecordScopes[$child['id']] ?? 'none') : 'none' }}
                                                                                                        </flux:button>
                                                                                                        <flux:menu>
                                                                                                            @foreach(\App\Models\Saas\ActionRole::RECORDS_SCOPE_MAIN_SELECT as $value => $label)
                                                                                                                <flux:menu.item
                                                                                                                    wire:click="$set('actionRecordScopes.{{ $child['id'] }}', '{{ $value }}'); if (!selectedActions.includes({{ $child['id'] }})) selectedActions.push({{ $child['id'] }});">
                                                                                                                    {{ $label }}
                                                                                                                </flux:menu.item>
                                                                                                            @endforeach
                                                                                                        </flux:menu>
                                                                                                    </flux:dropdown>
                                                                                                @endif
                                                                                            </div>
                                                                                        @endforeach
                                                                                    @endforeach
                                                                                </div>
                                                                            @endforeach
                                                                        </div>
                                                                    @endif
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
                        </flux:accordion.content>
                    </flux:accordion.item>
                @endforeach
            @endif
        </flux:accordion>
    </div>

    <div class="mt-4 flex justify-end space-x-2">
        <flux:button x-on:click="$flux.modal('role-action-sync').close()">
            Close
        </flux:button>
        <!-- Save button removed: autosave is enabled -->
    </div>
</div>