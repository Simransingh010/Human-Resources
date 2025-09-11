<div class="space-y-6 max-w-7xl">
    <div>
        <flux:heading size="lg">{{ $this->user->name }}</flux:heading>
        @foreach($groupedActions as $firmName => $apps)
            <flux:text class="text-sm font-medium text-gray-600">{{ $firmName }}</flux:text>
            @break
        @endforeach
    </div>

    <flux:separator />

    <!-- App selection radio group -->
    <div class="mb-4">
        <div class="mb-4">
            <label>App</label>

            <flux:tab.group wire:model="selectedAppName">
                <flux:tabs class="mb-4 px-4">
                    @foreach($groupedActions as $firmName => $apps)
                        @foreach($apps as $appName => $modules)
                            <flux:tab :name="$appName" wire:key="tab-{{ \Illuminate\Support\Str::slug($appName) }}">
                                {{ $appName }}
                            </flux:tab>
                        @endforeach
                    @endforeach
                </flux:tabs>

                @foreach($groupedActions as $firmName => $apps)
                    @foreach($apps as $appName => $modules)
                        <flux:tab.panel :name="$appName">
                            <div class="flex justify-end space-x-2 mb-2">
                                <flux:button size="xs" variant="outline" wire:click="selectApp('{{ $firmName }}', '{{ $appName }}')">Select All</flux:button>
                                <flux:button size="xs" variant="ghost" wire:click="deselectApp('{{ $firmName }}', '{{ $appName }}')">Deselect</flux:button>
                            </div>
                            <flux:accordion class="w-full">
                                @foreach ($modules as $moduleName => $components)
                                    <flux:accordion.item expanded>
                                        <flux:accordion.heading>{{ $moduleName }}</flux:accordion.heading>
                                        <flux:accordion.content class="pl-4">
                                            <div class="flex justify-end space-x-2 mb-2">
                                                <flux:button size="xs" variant="ghost" wire:click="toggleModule('{{ $firmName }}', '{{ $appName }}', '{{ $moduleName }}')">
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
                                                                <flux:button size="xs" variant="ghost" wire:click="toggleComponent('{{ $firmName }}', '{{ $appName }}', '{{ $moduleName }}', '{{ $componentName }}')">
                                                                    Toggle
                                                                </flux:button>
                                                            </div>
                                                            <div class="flex flex-row flex-wrap gap-2">
                                                                @php
                                                                    $hasActions = false;
                                                                    foreach ($actions as $typeKey => $typeData) {
                                                                        if (!empty($typeData['clusters'])) {
                                                                            $hasActions = true;
                                                                            break;
                                                                        }
                                                                    }
                                                                @endphp

                                                                @if($hasActions)
                                                                    @foreach ($actions as $typeKey => $typeData)
                                                                        @if(!empty($typeData['clusters']))
                                                                            <div class="mb-6 p-2 rounded {{ $typeData['type_bg'] }}" wire:key="type-{{ $typeKey }}">
                                                                                <div class="text-xs font-bold uppercase tracking-wide mb-2">{{ $typeData['type_label'] }}</div>
                                                                                @foreach ($typeData['clusters'] as $clusterName => $groups)
                                                                                    <div class="mb-2" wire:key="cluster-{{ $typeKey }}-{{ $clusterName }}">
                                                                                        <div class="text-sm font-bold text-purple-700 mb-2">{{ $clusterName }}</div>
                                                                                        @foreach ($groups as $group)
                                                                                            @php
                                                                                                $firmId = $this->userFirmsCollection[$firmName]->id;
                                                                                                $parentValue = $group['parent']['id'] . '|' . $firmId;
                                                                                            @endphp
                                                                                            <div class="flex items-center space-x-1 mb-1" wire:key="parent-action-{{ $group['parent']['id'] }}">
                                                                                                <flux:checkbox wire:model.live="selectedActions" value="{{ $parentValue }}" label="{{ $group['parent']['name'] }}" class="truncate font-semibold" />
                                                                                                @if(in_array($typeKey, ['RL', 'BR']))
                                                                                                    <flux:dropdown position="top" align="start">
                                                                                                        <flux:button size="xs" variant="outline" icon:trailing="chevron-down">
                                                                                                            {{ in_array($parentValue, $selectedActions) ? ($actionRecordScopes[$parentValue] ?? 'none') : 'none' }}
                                                                                                        </flux:button>
                                                                                                        <flux:menu>
                                                                                                            @foreach(\App\Models\Saas\ActionUser::RECORDS_SCOPE_MAIN_SELECT as $value => $label)
                                                                                                                <flux:menu.item wire:click="selectRecordScope({{ $group['parent']['id'] }}, {{ $firmId }}, '{{ $value }}')">
                                                                                                                    {{ $label }}
                                                                                                                </flux:menu.item>
                                                                                                            @endforeach
                                                                                                        </flux:menu>
                                                                                                    </flux:dropdown>
                                                                                                @endif
                                                                                            </div>
                                                                                            @foreach ($group['children'] as $child)
                                                                                                @php
                                                                                                    $childValue = $child['id'] . '|' . $firmId;
                                                                                                @endphp
                                                                                                <div class="flex items-center space-x-1 mb-1 pl-6" wire:key="child-action-{{ $child['id'] }}">
                                                                                                    <flux:checkbox wire:model.live="selectedActions" value="{{ $childValue }}" label="{{ $child['name'] }}" class="truncate" />
                                                                                                    @if(in_array($typeKey, ['RL', 'BR']))
                                                                                                        <flux:dropdown position="top" align="start">
                                                                                                            <flux:button size="xs" variant="outline" icon:trailing="chevron-down">
                                                                                                                {{ in_array($childValue, $selectedActions) ? ($actionRecordScopes[$childValue] ?? 'none') : 'none' }}
                                                                                                            </flux:button>
                                                                                                            <flux:menu>
                                                                                                                @foreach(\App\Models\Saas\ActionUser::RECORDS_SCOPE_MAIN_SELECT as $value => $label)
                                                                                                                    <flux:menu.item wire:click="selectRecordScope({{ $child['id'] }}, {{ $firmId }}, '{{ $value }}')">
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
                                                                @else
                                                                    <div class="w-full p-4 text-center text-gray-500 italic">
                                                                        <flux:text>No actions available for this component</flux:text>
                                                                    </div>
                                                                @endif
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
                @endforeach
            </flux:tab.group>
        </div>
    </div>

    <div class="mt-4 flex justify-end space-x-2">
        <flux:button x-on:click="$flux.modal('permission-sync').close()">
            Close
        </flux:button>
    </div>
</div>
