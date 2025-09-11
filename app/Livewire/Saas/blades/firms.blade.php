<div>
    <!-- Heading Start -->
    <div class="flex justify-between items-center">
        @livewire('panel.component-heading')
        <div class="flex items-center gap-2">
            <flux:modal.trigger name="mdl-firm" class="flex justify-end">
                <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">
                  New
                </flux:button>
            </flux:modal.trigger>
        </div>
    </div>
    <!-- Heading End -->

    <flux:separator class="mt-2 mb-4" />

    <!-- Filters Bar Start -->
    <form wire:submit.prevent="applyFilters">
        <flux:card size="sm" class="sm:p-4 p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                <div class="md:col-span-2">
                    <flux:input type="text" label="Search" placeholder="Search by name or short name"
                                wire:model.debounce.500ms="filters.q" wire:change="applyFilters"/>
                </div>
                <div>
                    <flux:select label="Firm Type" wire:model="filters.firm_type" wire:change="applyFilters">
                        <flux:select.option value="">All Types</flux:select.option>
                        @foreach($this->listsForFields['firm_type'] as $key => $value)
                            <flux:select.option value="{{ $key }}">{{ $value }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div>
                    <flux:select label="Agency" variant="listbox" searchable wire:model="filters.agency_id" wire:change="applyFilters" placeholder="All Agencies">
                        <flux:select.option value="">All Agencies</flux:select.option>
                        @foreach($this->listsForFields['agencylist'] as $key => $value)
                            <flux:select.option value="{{ $key }}">{{ $value }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div>
                    <flux:select label="Status" wire:model="filters.status" wire:change="applyFilters">
                        <flux:select.option value="">All</flux:select.option>
                        <flux:select.option value="active">Active</flux:select.option>
                        <flux:select.option value="inactive">Inactive</flux:select.option>
                    </flux:select>
                </div>
                <div class="flex gap-2 md:col-span-5 justify-end">
                    <flux:button variant="filled" icon="x-circle" wire:click="clearFilters">Clear</flux:button>
                </div>
            </div>
        </flux:card>
    </form>
    <!-- Filters Bar End -->

    <!-- Modal Start -->
    <flux:modal name="mdl-firm" @cancel="resetForm" position="right" class="max-w-none" variant="flyout">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Firm @else Add Firm @endif
                    </flux:heading>
                    <flux:subheading>
                        @if($isEditing) Update  @else Add new @endif  firm details.
                    </flux:subheading>
                </div>

                <!-- Grid layout for form fields -->
                <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
                    <flux:input label="Name" wire:model="formData.name" placeholder="Firm Name"/>
                    <flux:input label="Short Name" wire:model="formData.short_name" placeholder="Short Name"/>
                    <flux:select label="Firm Type" wire:model="formData.firm_type" >
                        <flux:select.option value="">-- Select Firm Type --</flux:select.option>
                        <!-- static placeholder -->
                        @foreach($this->listsForFields['firm_type'] as $key => $value)
                            <flux:select.option value="{{ $key }}">{{$value}}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select label="Related Agency" variant="listbox" searchable wire:model="formData.agency_id"
                                 placeholder="Related Agency">
                        <flux:select.option value="">-- Select Agency --</flux:select.option>
                        <!-- static placeholder -->
                        @foreach($this->listsForFields['agencylist'] as $key => $value)
                            <flux:select.option value="{{ $key }}">{{$value}}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select label="Parent Firm" variant="listbox" searchable wire:model="formData.parent_firm_id"
                                 placeholder="Parent Firm">
                        <flux:select.option value="">-- Select Parent Firm --</flux:select.option>
                        <!-- static placeholder -->
                        @foreach($this->listsForFields['firmlist'] as $key => $value)
                            <flux:select.option value="{{ $key }}">{{$value}}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:switch wire:model.live="formData.is_master_firm" label="Set as Master"/>
                    <flux:switch wire:model.live="formData.is_inactive" label="Mark as Inactive"/>

                    <!-- File upload fields for logos -->
                    <flux:input type="file" label="Favicon" wire:model="favicon" accept="image/*"/>
                    @if($isEditing && $this->faviconUrl)
                        <div class="flex items-center space-x-2">
                            <img src="{{ $this->faviconUrl }}" alt="Favicon" class="h-8 w-8 rounded"/>
                            <flux:button wire:click="removeLogo('favicon')" variant="danger" size="sm">Remove</flux:button>
                        </div>
                    @endif

                    <flux:input type="file" label="Square Logo" wire:model="squareLogo" accept="image/*"/>
                    @if($isEditing && $this->squareLogoUrl)
                        <div class="flex items-center space-x-2">
                            <img src="{{ $this->squareLogoUrl }}" alt="Square Logo" class="h-8 w-8 rounded"/>
                            <flux:button wire:click="removeLogo('squareLogo')" variant="danger" size="sm">Remove</flux:button>
                        </div>
                    @endif

                    <flux:input type="file" label="Wide Logo" wire:model="wideLogo" accept="image/*"/>
                    @if($isEditing && $this->wideLogoUrl)
                        <div class="flex items-center space-x-2">
                            <img src="{{ $this->wideLogoUrl }}" alt="Wide Logo" class="h-8 w-8 rounded"/>
                            <flux:button wire:click="removeLogo('wideLogo')" variant="danger" size="sm">Remove</flux:button>
                        </div>
                    @endif

                </div>

                <!-- Submit Button -->
                <div class="flex justify-end pt-4">
                    <flux:button type="submit" variant="primary">
                        Save
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
    <!-- Modal End -->


    <!-- Table Start-->
    <flux:table :paginate="$this->list" class="w-full mt-4">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700 table-cell-wrap">
            <flux:table.column>Name</flux:table.column>
            <flux:table.column>Short Name</flux:table.column>
            <flux:table.column>Firm Type</flux:table.column>
            <flux:table.column>Agency</flux:table.column>
            <flux:table.column>Parent Firm</flux:table.column>
            <flux:table.column>Assigned Panels</flux:table.column>
            <flux:table.column>Set as Master</flux:table.column>
            <flux:table.column>Mark as Inactive</flux:table.column>
            <flux:table.column>Favicon</flux:table.column>
            <flux:table.column>Square Logo</flux:table.column>
            <flux:table.column>Wide Logo</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows class="table-cell-wrap">
            @foreach ($this->list as $firm)
                <flux:table.row :key="$firm->id" class="border-b">
                    <flux:table.cell class="table-cell-wrap">{{ $firm->name }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $firm->short_name }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $firm->firm_type_label }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $firm->agency?->name ?? '-' }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $firm->firm?->name ?? '-' }}</flux:table.cell>
                    <flux:table.cell align="start">
                        <div class="flex flex-wrap gap-1">
                            @forelse($firm->panels as $panel)
                            <flux:badge color="blue">{{ $panel->name }} ({{ $panel->panel_type_label }})</flux:badge>
                            @empty
                                -
                            @endforelse
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:switch wire:model="setMasterStatuses.{{ $firm->id }}"
                                     wire:click="toggleSetMasterStatus({{ $firm->id }})"/>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:switch wire:model="statuses.{{ $firm->id }}"
                                     wire:click="toggleStatus({{ $firm->id }})"/>
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($firm->getFirstMediaUrl('favicon'))
                            <img src="{{ $firm->getFirstMediaUrl('favicon') }}" alt="Favicon" class="h-8 w-8 rounded"/>
                        @else
                            -
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($firm->getFirstMediaUrl('squareLogo'))
                            <img src="{{ $firm->getFirstMediaUrl('squareLogo') }}" alt="Square Logo" class="h-8 w-8 rounded"/>
                        @else
                            -
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($firm->getFirstMediaUrl('wideLogo'))
                            <img src="{{ $firm->getFirstMediaUrl('wideLogo') }}" alt="Wide Logo" class="h-8 w-8 rounded"/>
                        @else
                            -
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex space-x-2">
                           
                            <flux:button
                                wire:click="openPanelComponentModal({{ $firm->id }})"
                                color="zinc"
                                size="sm"
                                icon="adjustments-horizontal"
                                tooltip="Panel Component Access"
                            />
                            <flux:button
                                wire:click="openFirmRolesModal({{ $firm->id }})"
                                color="zinc"
                                size="sm"
                                icon="shield-check"
                                tooltip="Firm Roles"
                            />
                            <flux:button
                                wire:click="openFirmUsersModal({{ $firm->id }})"
                                color="zinc"
                                size="sm"
                                icon="user-group"
                                tooltip="Firm Users"
                            />
                            <flux:button variant="primary" size="sm" icon="pencil"
                                         wire:click="edit({{ $firm->id }})"/>
                            <flux:modal.trigger name="delete-firm-{{ $firm->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"/>
                            </flux:modal.trigger>
                        </div>

                        <!-- Delete Modal -->
                        <flux:modal name="delete-firm-{{ $firm->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete Firm?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this firm. This action cannot be undone.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer/>
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                                 wire:click="delete({{ $firm->id }})"/>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
    <!-- Table End-->

    <!-- Related Component Calls in Modal Start-->
    <flux:modal name="app-access" title="App Access" class="p-10">
        @if($selectedId)
            <livewire:saas.firm-meta.app-access :firm-id="$selectedId" :wire:key="'app-access-'.$selectedId"/>
        @endif
    </flux:modal>
    <!-- Related Component Calls in Modal Over-->

    <!-- Firm Users List Modal -->
    <flux:modal name="firm-users-list-modal" class="p-10 max-w-3xl">
        @if($selectedFirmForUsers)
            <div>
                <div class="flex justify-between items-center">
                    <flux:heading size="lg">Users for {{ $selectedFirmForUsers->name }}</flux:heading>
                    <flux:button variant="primary" wire:click="openFirmUserAddModal">
                        New User
                    </flux:button>
                </div>

                <flux:separator class="my-4" />

                <!-- List of existing users -->
                <flux:table :items="$this->firmUsers" class="w-full mt-4">
                    <flux:table.columns class="table-cell-wrap">
                        <flux:table.column class='table-cell-wrap'>Name</flux:table.column>
                        <flux:table.column class='table-cell-wrap'>Email</flux:table.column>
                        <flux:table.column class='table-cell-wrap'>Phone</flux:table.column>
                        <flux:table.column class='table-cell-wrap'>Actions</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse($this->firmUsers as $user)
                            <flux:table.row :key="$user->id">
                                <flux:table.cell class="table-cell-wrap">{{ $user->name }}</flux:table.cell>
                                <flux:table.cell class="table-cell-wrap">{{ $user->email }}</flux:table.cell>
                                <flux:table.cell class="table-cell-wrap">{{ $user->phone }}</flux:table.cell>
                                <flux:table.cell class="table-cell-wrap">
                                    <div class="flex space-x-2">
                                        <flux:button variant="outline" size="sm" icon="user-group"
                                                     wire:click="openRoleModal({{ $user->id }})">
                                            Roles
                                        </flux:button>
                                        <flux:button variant="outline" size="sm" icon="bolt"
                                                     wire:click="openActionModal({{ $user->id }})">
                                            Actions
                                        </flux:button>
                                        <flux:button variant="outline" size="sm" icon="arrow-path"
                                                     wire:click="syncUser({{ $user->id }})">
                                            Sync
                                        </flux:button>
                                        <flux:button variant="primary" size="sm" icon="pencil"
                                                     wire:click="editFirmUser({{ $user->id }})" />
                                        <flux:modal.trigger name="delete-user-{{ $user->id }}">
                                            <flux:button variant="danger" size="sm" icon="trash"/>
                                        </flux:modal.trigger>
                                    </div>

                                    <!-- Delete Confirmation Modal -->
                                    <flux:modal name="delete-user-{{ $user->id }}" class="min-w-[22rem]">
                                        <div class="space-y-6">
                                            <div>
                                                <flux:heading size="lg">Remove User?</flux:heading>
                                                <flux:text class="mt-2">
                                                    <p>Are you sure you want to remove this user from the firm?</p>
                                                </flux:text>
                                            </div>
                                            <div class="flex gap-2">
                                                <flux:spacer/>
                                                <flux:modal.close>
                                                    <flux:button variant="ghost">Cancel</flux:button>
                                                </flux:modal.close>
                                                <flux:button variant="danger" icon="trash" wire:click="removeUserFromFirm({{ $user->id }})">
                                                    Remove
                                                </flux:button>
                                            </div>
                                        </div>
                                    </flux:modal>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="4">No users with Role L1_firm found for this firm.</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif
    </flux:modal>

    <!-- Add the new modals for Panel, Component, and Module Sync -->
    <flux:modal name="panel-sync" title="Panel Access" class="p-10">
        @if($selectedUserId)
            <livewire:saas.user-meta.panel-sync :userId="$selectedUserId" :firmId="$selectedFirmForUsers?->id" :wire:key="'panel-sync-'.$selectedUserId" />
        @endif
    </flux:modal>

    <flux:modal name="component-sync" title="Component Access" class="p-10">
        @if($selectedUserId && $selectedPanelId)
            <livewire:saas.panel-meta.component-sync :panelId="$selectedPanelId" :wire:key="'component-sync-'.$selectedPanelId" />
        @endif
    </flux:modal>

    <flux:modal name="module-sync" title="Module Access" class="p-10">
        @if($selectedUserId)
            <livewire:saas.panel-meta.module-sync :userId="$selectedUserId" :firmId="$selectedFirmForUsers?->id" :wire:key="'module-sync-'.$selectedUserId" />
        @endif
    </flux:modal>

    <!-- Add/Edit Firm User Modal -->
    <flux:modal name="firm-user-add-edit-modal" @cancel="resetUserForm" position="right" class="max-w-none" variant="flyout">
        @if($selectedFirmForUsers)
            <form wire:submit.prevent="storeFirmUser">
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">
                            @if($isEditingUser)
                                Edit User
                            @else
                                Add User to {{ $selectedFirmForUsers->name }}
                            @endif
                        </flux:heading>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:input label="Name" wire:model="newUserFormData.name" placeholder="User Name"/>
                        <flux:input label="Email" wire:model="newUserFormData.email" placeholder="Email"/>
                        <flux:input label="Password" type="password" wire:model="newUserFormData.password" placeholder="Password"/>
                        <flux:input label="Phone" wire:model="newUserFormData.phone" placeholder="Phone"/>
                        <flux:input label="Passcode" wire:model="newUserFormData.passcode" placeholder="Passcode"/>
                    </div>

                    <div class="flex justify-end pt-4">
                        <flux:button type="submit" variant="primary">
                            Save
                        </flux:button>
                    </div>
                </div>
            </form>
        @endif
    </flux:modal>

    <!-- Panel Component Access Modal -->
    <flux:modal name="panel-component-access" title="Panel Access" class="p-10">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Panel Access</flux:heading>
                <flux:text class="text-gray-500">Assign panels to this firm. Use the button to configure components for each panel.</flux:text>
            </div>
            <div class="mb-4">
                <flux:checkbox.group wire:model="assignedPanelIds" label="Panels">
                    @foreach($availablePanels as $panel)
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center">
                                <flux:checkbox value="{{ $panel->id }}" label="{{ $panel->name }} ({{ $panel->panel_type_label }})" />
                            </div>
                            <flux:button size="xs" variant="outline" icon="adjustments-horizontal" wire:click="openPanelComponentsModal({{ $panel->id }})">
                                Components
                            </flux:button>
                        </div>
                    @endforeach
                </flux:checkbox.group>
            </div>
            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="closePanelComponentModal">Cancel</flux:button>
                <flux:button type="button" variant="primary" wire:click="savePanelAssignments">Save</flux:button>
            </div>
        </div>
    </flux:modal>

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

    <!-- New Modal: Panel Components Modal -->
    <flux:modal name="panel-components-modal" title="Panel Components" class="p-10">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Assign Components to Panel</flux:heading>
                <flux:text class="text-gray-500">Assign components to <span class="font-bold">{{ $selectedPanelName }}</span> for this firm.</flux:text>
            </div>
            @if($selectedPanelId && count($availableAppModules ?? []))
                <div class="mb-4">
                    <flux:tab.group wire:model="selectedAppName">
                        <flux:tabs class="mb-4 px-4">
                            @foreach(array_keys($availableAppModules) as $appName)
                                <flux:tab :name="$appName" wire:key="tab-{{ \Illuminate\Support\Str::slug($appName) }}">
                                    {{ $appName }}
                                </flux:tab>
                            @endforeach
                        </flux:tabs>
                        @foreach($availableAppModules as $appName => $modules)
                            <flux:tab.panel :name="$appName">
                                <div class="flex justify-end space-x-2 mb-2">
                                    <flux:button size="xs" variant="outline" wire:click="toggleAppComponents('{{ $appName }}')">Toggle</flux:button>
                                </div>
                                <flux:accordion class="w-full">
                                    @foreach ($modules as $moduleName => $components)
                                        <flux:accordion.item expanded>
                                            <flux:accordion.heading>
                                                {{ $moduleName }}
                                            </flux:accordion.heading>
                                            <flux:accordion.content class="pl-4">
                                                <div class="flex justify-end space-x-2 mb-2">
                                                    <flux:button size="xs" variant="ghost" wire:click="toggleModuleComponents('{{ $appName }}', '{{ $moduleName }}')">Toggle</flux:button>
                                                </div>
                                                <flux:checkbox.group wire:model="selectedComponentIds" class="flex flex-col gap-2">
                                                    @foreach($components as $comp)
                                                        <flux:checkbox value="{{ is_array($comp) ? $comp['id'] : $comp->id }}" label="{{ is_array($comp) ? $comp['name'] : $comp->name }}" />
                                                    @endforeach
                                                </flux:checkbox.group>
                                            </flux:accordion.content>
                                        </flux:accordion.item>
                                    @endforeach
                                </flux:accordion>
                            </flux:tab.panel>
                        @endforeach
                    </flux:tab.group>
                </div>
            @elseif($selectedPanelId)
                <div class="mb-4 text-gray-500">No components available for this panel.</div>
            @endif
            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="closePanelComponentsModal">Cancel</flux:button>
                <flux:button type="button" variant="primary" wire:click="savePanelComponentSync">Save</flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Firm Roles Modal -->
    <flux:modal name="firm-roles-modal" title="Firm Roles" class="max-w-6xl">
        @if($firmRolesModalFirmId)
            <livewire:saas.roles :firmId="$firmRolesModalFirmId" :wire:key="'firm-roles-'.$firmRolesModalFirmId"/>
        @endif
    </flux:modal>

</div>
