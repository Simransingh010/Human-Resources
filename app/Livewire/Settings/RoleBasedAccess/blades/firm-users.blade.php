<div class="space-y-6">
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <div class="flex gap-2">
            <flux:modal.trigger name="mdl-firm-user" class="flex justify-end">
                <flux:button variant="primary" icon="plus"
                             class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">
                    New User
                </flux:button>
            </flux:modal.trigger>
            <flux:button variant="outline" icon="arrow-path" tooltip="Bulk Sync All Users"
                         wire:click="bulkSyncUsers">
                Bulk Sync
            </flux:button>
        </div>
    </div>
    <flux:separator class="mt-2 mb-2" />
    <!-- Heading End -->

    <!-- Filters Start -->
    <flux:card>
        <flux:heading>Filters</flux:heading>
        <div class="flex flex-wrap gap-4">
            @foreach($filterFields as $field => $cfg)
                @if(in_array($field, $visibleFilterFields))
                    <div class="w-1/4">
                        @switch($cfg['type'])
                            @case('select')
                                <flux:select variant="listbox" searchable
                                             placeholder="All {{ $cfg['label'] }}"
                                             wire:model="filters.{{ $field }}"
                                             wire:change="applyFilters">
                                    <flux:select.option value="">
                                        All {{ $cfg['label'] }}
                                    </flux:select.option>
                                    @foreach($listsForFields[$cfg['listKey']] as $val => $lab)
                                        <flux:select.option value="{{ $val }}">{{ $lab }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                @break
                            @default
                                <flux:input placeholder="Search {{ $cfg['label'] }}"
                                            wire:model.live.debounce.500ms="filters.{{ $field }}"
                                            wire:change="applyFilters" />
                        @endswitch
                    </div>
                @endif
            @endforeach

            <flux:button.group>
                <flux:button variant="outline" wire:click="clearFilters"
                             tooltip="Clear Filters" icon="x-circle" />
                <flux:modal.trigger name="mdl-show-hide-filters">
                    <flux:button variant="outline" tooltip="Set Filters" icon="bars-3" />
                </flux:modal.trigger>
                <flux:modal.trigger name="mdl-show-hide-columns">
                    <flux:button variant="outline" tooltip="Set Columns" icon="table-cells" />
                </flux:modal.trigger>
            </flux:button.group>
        </div>
    </flux:card>

    <!-- Show/Hide Filters Modal -->
    <flux:modal name="mdl-show-hide-filters" variant="flyout">
        <div class="space-y-6">
            <flux:heading size="lg">Show/Hide Filters</flux:heading>
            <flux:checkbox.group>
                @foreach($filterFields as $field => $cfg)
                    <flux:checkbox
                            :checked="in_array('{{ $field }}', $visibleFilterFields)"
                            label="{{ $cfg['label'] }}"
                            wire:click="toggleFilterColumn('{{ $field }}')" />
                @endforeach
            </flux:checkbox.group>
        </div>
    </flux:modal>

    <!-- Show/Hide Columns Modal -->
    <flux:modal name="mdl-show-hide-columns" variant="flyout" position="right">
        <div class="space-y-6">
            <flux:heading size="lg">Show/Hide Columns</flux:heading>
            <flux:checkbox.group>
                @foreach($fieldConfig as $field => $cfg)
                    <flux:checkbox
                            :checked="in_array('{{ $field }}', $visibleFields)"
                            label="{{ $cfg['label'] }}"
                            wire:click="toggleColumn('{{ $field }}')" />
                @endforeach
            </flux:checkbox.group>
        </div>
    </flux:modal>

    <!-- Add/Edit User Modal -->
    <flux:modal name="mdl-firm-user" @cancel="resetForm" class="max-w-3xl">
        <form wire:submit.prevent="store">
            <flux:heading size="lg">
                {{ $isEditing ? 'Edit User' : 'Add User' }}
            </flux:heading>
            <flux:subheading>
                {{ $isEditing ? 'Update' : 'Add new' }} user details.
            </flux:subheading>

            <div class="grid grid-cols-1 gap-4 mt-4">
                @foreach($fieldConfig as $field => $cfg)
                    <div>
                        @switch($cfg['type'])
                            @case('switch')
                                <flux:switch
                                        label="{{ $cfg['label'] }}"
                                        wire:model.live="formData.{{ $field }}" />
                                @break

                            @case('textarea')
                                <flux:textarea
                                        label="{{ $cfg['label'] }}"
                                        wire:model.live="formData.{{ $field }}"
                                        rows="3" />
                                @break

                            @default
                                <flux:input
                                        type="{{ $cfg['type'] }}"
                                        label="{{ $cfg['label'] }}"
                                        wire:model.live="formData.{{ $field }}" />
                        @endswitch
                    </div>
                @endforeach
            </div>

            <div class="flex justify-end mt-4">
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Data Table -->
    <flux:table :paginate="$this->list" class="w-full">
        <flux:table.columns>
            @foreach($fieldConfig as $field => $cfg)
                @if(in_array($field, $visibleFields))
                    <flux:table.column>{{ $cfg['label'] }}</flux:table.column>
                @endif
            @endforeach
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->list as $item)
                <flux:table.row :key="$item->id">
                    @foreach($fieldConfig as $field => $cfg)
                        @if(in_array($field, $visibleFields))
                            <flux:table.cell>
                                @switch($cfg['type'])
                                    @case('switch')
                                        <flux:badge color="{{ $item->$field ? 'gray' : 'green' }}">
                                            {{ $item->$field ? 'Inactive' : 'Active' }}
                                        </flux:badge>
                                        @break
                                    @default
                                        {{ $item->$field }}
                                @endswitch
                            </flux:table.cell>
                        @endif
                    @endforeach
                    <flux:table.cell>
                        <flux:button variant="outline" size="sm" icon="user-group"
                                     wire:click="openRoleModal({{ $item->id }})">
                            Roles
                        </flux:button>
                        <flux:button variant="outline" size="sm" icon="bolt"
                                     wire:click="openActionModal({{ $item->id }})">
                            Actions
                        </flux:button>
                        <flux:button variant="outline" size="sm" icon="arrow-path"
                                     wire:click="syncUser({{ $item->id }})">
                            Sync
                        </flux:button>
                        <flux:button variant="primary" size="sm" icon="pencil"
                                     wire:click="edit({{ $item->id }})" />
                        <flux:modal.trigger name="delete-{{ $item->id }}">
                            <flux:button variant="danger" size="sm" icon="trash" />
                        </flux:modal.trigger>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <!-- Role Assignment Modal -->
    <flux:modal name="user-role-modal" :open="$showRoleModal"
                @cancel="closeRoleModal" class="max-w-2xl">
        <form wire:submit.prevent="saveUserRoles">
            <flux:heading size="lg">Assign Roles to {{ $roleModalUserName }}</flux:heading>
            <flux:separator />
            <div class="h-1"></div>
            <flux:checkbox.group>
                @foreach($roleModalAvailableRoles as $roleId => $roleName)
                    <flux:checkbox 
                        :checked="in_array((string) $roleId, $roleModalSelectedRoles)"
                        :label="$roleName"
                        wire:click="toggleRoleModalSelectedRole('{{ (string) $roleId }}')"
                    />
                @endforeach
            </flux:checkbox.group>
            <div class="flex justify-end mt-4 space-x-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Save Roles</flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Direct Action Assignment Modal -->
    <flux:modal name="user-action-modal" :open="$showActionModal"
                @cancel="closeActionModal" class="max-w-7xl">
        <form wire:submit.prevent="saveUserActions">
            <flux:heading size="lg">Assign Actions to {{ $actionModalUserName }}</flux:heading>
            <flux:text class="text-gray-500 mb-4">
                Assign actions directly to this user (in addition to those from roles).
            </flux:text>
            <flux:separator />

            <!-- Tabs for Apps -->
            <label class="block text-sm font-medium text-gray-700 mb-2">App</label>
            <flux:tab.group wire:model="actionModalSelectedApp">
                <flux:tabs class="mb-4 px-4">
                    @foreach($actionModalAppList as $appName)
                        <flux:tab :name="$appName" wire:key="tab-{{ Str::slug($appName) }}">
                            {{ $appName }}
                        </flux:tab>
                    @endforeach
                </flux:tabs>

                @foreach($actionModalGroupedActions as $appName => $modules)
                    <flux:tab.panel :name="$appName">
                        <!-- Select/Deselect All -->
                        <div class="flex justify-end mb-2 space-x-2">
                            <flux:button size="xs" variant="outline"
                                         wire:click="$set('actionModalSelectedActions', collect($actionModalGroupedActions['{{ $appName }}']).flatten(5)->pluck('parent.id')->merge(collect($actionModalGroupedActions['{{ $appName }}']).flatten(5)->pluck('children.*.id')->flatten())->unique()->values()->all())">
                                Select All
                            </flux:button>
                            <flux:button size="xs" variant="ghost"
                                         wire:click="$set('actionModalSelectedActions', [])">
                                Deselect
                            </flux:button>
                        </div>

                        <flux:accordion class="w-full">
                            @foreach($modules as $moduleName => $components)
                                <flux:accordion.item expanded>
                                    <flux:accordion.heading>{{ $moduleName }}</flux:accordion.heading>
                                    <flux:accordion.content class="pl-4">

                                        <!-- Module-level Toggle -->
                                        <div class="flex justify-end mb-2">
                                            <flux:button size="xs" variant="ghost"
                                                         wire:click="toggleModule('{{ $appName }}','{{ $moduleName }}')">
                                                Toggle
                                            </flux:button>
                                        </div>

                                        @foreach(array_chunk($components,2,true) as $chunk)
                                            <div class="flex gap-6 mb-4">
                                                @foreach($chunk as $componentName => $types)
                                                    <div class="flex-1 border rounded p-3 bg-gray-50">
                                                        <div class="flex justify-between items-center mb-2 font-semibold">
                                                            <span>{{ $componentName }}</span>
                                                            <flux:button size="xs" variant="ghost"
                                                                         wire:click="toggleComponent('{{ $appName }}','{{ $moduleName }}','{{ $componentName }}')">
                                                                Toggle
                                                            </flux:button>
                                                        </div>

                                                        @foreach($types as $typeKey => $typeData)
                                                            @if(! empty($typeData['clusters']))
                                                                <div class="mb-6 p-2 rounded {{ $typeData['type_bg'] }}">
                                                                    <div class="text-xs font-bold uppercase mb-2">
                                                                        {{ $typeData['type_label'] }}
                                                                    </div>

                                                                    <!-- ✔️ CORRECTLY WRAPPED checkbox.group -->
                                                                    <flux:checkbox.group wire:model="actionModalSelectedActions" wire:key="action-checkbox-group-{{ $actionModalUserId }}">

                                                                        @foreach($typeData['clusters'] as $clusterName => $groups)
                                                                            <div class="mb-2" wire:key="cluster-{{ $typeKey }}-{{ $clusterName }}">
                                                                                <div class="text-sm font-bold text-purple-700 mb-2">
                                                                                    {{ $clusterName }}
                                                                                </div>

                                                                                @foreach($groups as $group)
                                                                                    <div class="flex items-center mb-1 space-x-2"
                                                                                         wire:key="parent-action-{{ $group['parent']['id'] }}">
                                                                                        <flux:checkbox
                                                                                                :checked="in_array((string) $group['parent']['id'], $actionModalSelectedActions)"
                                                                                                :label="$group['parent']['name']"
                                                                                                class="truncate font-semibold"
                                                                                                wire:click="toggleActionModalSelectedAction('{{ (string) $group['parent']['id'] }}')"
                                                                                        />

                                                                                        @if(in_array($typeKey, ['RL','BR']))
                                                                                            <flux:dropdown position="top" align="start">
                                                                                                <flux:button size="xs" variant="outline" icon:trailing="chevron-down">
                                                                                                    {{ in_array($group['parent']['id'],$actionModalSelectedActions)
                                                                                                       ? ($actionModalActionScopes[$group['parent']['id']] ?? 'all')
                                                                                                       : 'all' }}
                                                                                                </flux:button>
                                                                                                <flux:menu>
                                                                                                    @foreach(\App\Models\Saas\ActionUser::RECORDS_SCOPE_MAIN_SELECT as $val => $lab)
                                                                                                        <flux:menu.item wire:click="setActionScope({{ $group['parent']['id'] }},'{{ $val }}')">
                                                                                                            {{ $lab }}
                                                                                                        </flux:menu.item>
                                                                                                    @endforeach
                                                                                                </flux:menu>
                                                                                            </flux:dropdown>
                                                                                        @endif
                                                                                    </div>

                                                                                    @foreach($group['children'] as $child)
                                                                                        <div class="pl-6 flex items-center mb-1 space-x-2"
                                                                                             wire:key="child-action-{{ $child['id'] }}">
                                                                                            <flux:checkbox
                                                                                                    :checked="in_array((string) $child['id'], $actionModalSelectedActions)"
                                                                                                    :label="$child['name']"
                                                                                                    class="truncate"
                                                                                                    wire:click="toggleActionModalSelectedAction('{{ (string) $child['id'] }}')"
                                                                                            />

                                                                                            @if(in_array($typeKey, ['RL','BR']))
                                                                                                <flux:dropdown position="top" align="start">
                                                                                                    <flux:button size="xs" variant="outline" icon:trailing="chevron-down">
                                                                                                        {{ in_array($child['id'],$actionModalSelectedActions)
                                                                                                           ? ($actionModalActionScopes[$child['id']] ?? 'all')
                                                                                                           : 'all' }}
                                                                                                    </flux:button>
                                                                                                    <flux:menu>
                                                                                                        @foreach(\App\Models\Saas\ActionUser::RECORDS_SCOPE_MAIN_SELECT as $val => $lab)
                                                                                                            <flux:menu.item wire:click="setActionScope({{ $child['id'] }},'{{ $val }}')">
                                                                                                                {{ $lab }}
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

                                                                    </flux:checkbox.group>
                                                                    <!-- end checkbox.group -->

                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @endforeach
                                                @if(count($chunk) < 2)
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

            <!-- Save/Cancel -->
            <div class="flex justify-end mt-4 space-x-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Save Actions</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
