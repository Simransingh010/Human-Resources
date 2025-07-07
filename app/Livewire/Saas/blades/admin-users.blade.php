<div><div>
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-user" class="flex justify-end">
            <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">
                New
            </flux:button>
        </flux:modal.trigger>
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
                                <flux:select
                                    variant="listbox"
                                    searchable
                                    placeholder="All {{ $cfg['label'] }}"
                                    wire:model="filters.{{ $field }}"
                                    wire:change="applyFilters"
                                >
                                    <flux:select.option value="">All {{ $cfg['label'] }}</flux:select.option>
                                    @foreach($listsForFields[$cfg['listKey']] as $val => $lab)
                                        <flux:select.option value="{{ $val }}">{{ $lab }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                @break

                            @case('number')
                                <flux:input
                                    type="number"
                                    placeholder="Search {{ $cfg['label'] }}"
                                    wire:model.live.debounce.500ms="filters.{{ $field }}"
                                    wire:change="applyFilters"
                                />
                                @break

                            @default
                                <flux:input
                                    placeholder="Search {{ $cfg['label'] }}"
                                    wire:model.live.debounce.500ms="filters.{{ $field }}"
                                    wire:change="applyFilters"
                                />
                        @endswitch
                    </div>
                @endif
            @endforeach

            <flux:button.group>
                <flux:button variant="outline" wire:click="clearFilters" tooltip="Clear Filters" icon="x-circle"></flux:button>
                <flux:modal.trigger name="mdl-show-hide-filters">
                    <flux:button variant="outline" tooltip="Set Filters" icon="bars-3"></flux:button>
                </flux:modal.trigger>
                <flux:modal.trigger name="mdl-show-hide-columns">
                    <flux:button variant="outline" tooltip="Set Columns" icon="table-cells"></flux:button>
                </flux:modal.trigger>
            </flux:button.group>
        </div>
    </flux:card>

    <!-- Filter Fields Show/Hide Modal -->
    <flux:modal name="mdl-show-hide-filters" variant="flyout">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Show/Hide Filters</flux:heading>
            </div>
            <div class="flex flex-wrap items-center gap-4">
                <flux:checkbox.group>
                    @foreach($filterFields as $field => $cfg)
                        <flux:checkbox 
                            :checked="in_array($field, $visibleFilterFields)" 
                            label="{{ $cfg['label'] }}" 
                            wire:click="toggleFilterColumn('{{ $field }}')" 
                        />
                    @endforeach
                </flux:checkbox.group>
            </div>
        </div>
    </flux:modal>

    <!-- Columns Show/Hide Modal -->
    <flux:modal name="mdl-show-hide-columns" variant="flyout" position="right">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Show/Hide Columns</flux:heading>
            </div>
            <div class="flex flex-wrap items-center gap-4">
                <flux:checkbox.group>
                    @foreach($fieldConfig as $field => $cfg)
                        <flux:checkbox 
                            :checked="in_array($field, $visibleFields)" 
                            label="{{ $cfg['label'] }}" 
                            wire:click="toggleColumn('{{ $field }}')" 
                        />
                    @endforeach
                </flux:checkbox.group>
            </div>
        </div>
    </flux:modal>

    <!-- Modal Start -->
    <flux:modal name="mdl-user" @cancel="resetForm" position="right" class="max-w-none" >
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit User @else Add User @endif
                    </flux:heading>
                    <flux:subheading>
                        @if($isEditing) Update  @else Add new @endif  User details.
                    </flux:subheading>
                </div>

                <!-- Grid layout for form fields -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:input label="Name" wire:model="formData.name" placeholder="User Name"/>
                    <flux:input label="Email" wire:model="formData.email" placeholder="Email"/>
                    <flux:input label="Password" wire:model="formData.password" placeholder="Password"/>
                    <flux:input label="Passcode" wire:model="formData.passcode" placeholder="Passcode"/>
                    <flux:input label="Phone" wire:model="formData.phone" placeholder="Phone"/>
                    <flux:select
                        label="Role"
                        
                        wire:model="formData.role_main"
                        placeholder="Select a Role"
                    >
                        @foreach($this->listsForFields['role_main'] as $key => $value)
                            <flux:select.option :value="$key" :label="$value" />
                        @endforeach
                    </flux:select>
                    <flux:switch wire:model.live="formData.is_inactive" label="Mark as Inactive"/>

                    <flux:checkbox.group wire:model="panels" label="Panels">
                        @foreach($this->listsForFields['panellist'] as $key => $value)
                            <flux:checkbox :label="$value" :value="$key" />
                        @endforeach
                    </flux:checkbox.group>

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
    <flux:table :paginate="$this->list" class="w-full">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column>Name</flux:table.column>
            <flux:table.column>Email</flux:table.column>
            <flux:table.column>Password</flux:table.column>
            <flux:table.column>Passcode</flux:table.column>
            <flux:table.column>Phone</flux:table.column>
            <flux:table.column>Role</flux:table.column>
            <flux:table.column>Mark as Inactive</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->list as $rec)
                <flux:table.row :key="$rec->id" class="border-b">
                    <flux:table.cell class="table-cell-wrap">{{ $rec->name }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->email }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->password }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->passcode }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->phone }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ \App\Models\User::ROLE_MAIN_TYPES[$rec->role_main] ?? $rec->role_main }}</flux:table.cell>

                    <flux:table.cell>
                        <flux:switch wire:model="statuses.{{ $rec->id }}"
                                     wire:click="toggleStatus({{ $rec->id }})"/>
                    </flux:table.cell>
                    <flux:table.cell class="table-cell-wrap" >

                            <!-- Manage Metas -->
                        <flux:button wire:click="showmodal_firmSync({{ $rec->id }})" size="xs">Firms</flux:button>




                            <flux:button variant="primary" size="xs" icon="pencil"
                                         wire:click="edit({{ $rec->id }})"/>
                            <flux:modal.trigger name="delete-{{ $rec->id }}">
                                <flux:button variant="danger" size="xs" icon="trash"/>
                            </flux:modal.trigger>



                        <!-- Delete Modal -->
                        <flux:modal name="delete-{{ $rec->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete Record?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this record. This action cannot be undone.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer/>
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                                 wire:click="delete({{ $rec->id }})"/>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
    <!-- Table End-->

    <!-- Shared Modal Panel Sync -->
    <flux:modal name="panel-sync" title="Manage Panels" class="p-10">
        @if ($selectedUserId)
            <livewire:saas.user-meta.panel-sync :userId="$selectedUserId" :wire:key="'panel-sync-'.$selectedUserId" />
        @endif
    </flux:modal>

    <!-- Shared Modal Firm Sync -->
    <flux:modal name="firm-sync" title="Manage Firms" class="p-10">
        @if ($selectedUserId)
            <livewire:saas.user-meta.firm-sync :userId="$selectedUserId" :wire:key="'firm-sync-'.$selectedUserId" />
        @endif
    </flux:modal>

    <!-- Shared Modal PermGroup Sync -->
    <flux:modal name="permission-group-sync" title="Manage Roles" class="p-10">
        @if ($selectedUserId)
            <livewire:saas.user-meta.permission-group-sync :userId="$selectedUserId" :wire:key="'permission-group-sync-'.$selectedUserId" />
        @endif
    </flux:modal>

    <!-- Shared Modal Permission Sync -->
    <flux:modal name="permission-sync" title="Manage Permissions" class="p-10 max-w-7xl">
        @if ($selectedUserId)
            <livewire:saas.user-meta.permission-sync :userId="$selectedUserId" :wire:key="'permission-sync-'.$selectedUserId" />
        @endif
    </flux:modal>


</div>
</div>
