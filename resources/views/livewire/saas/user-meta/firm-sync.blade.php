<div><div class="space-y-6">
    <div>
        <flux:heading size="lg">{{$this->user->name}} {{$this->user->lname}} | {{$this->user->email}} </flux:heading>
    </div>

    <flux:separator />
    <div class="space-y-4">
        <flux:checkbox.group wire:model="selectedFirms" label="Firms">
            @foreach ($this->listsForFields['firmlist'] as $id => $name)
                <div class="flex items-center justify-between p-2 border rounded-md">
                    <div class="flex items-center space-x-2">
                        <flux:checkbox label="{{ $name }}" value="{{ $id }}" :disabled="$this-  >user->role_main === 'L1_firm'" />
                    </div>
                    <div class="flex items-center space-x-2">
                        <flux:button wire:click="showPanelsModal({{ $id }})" size="xs">Panels</flux:button>
                        <flux:button wire:click="showRolesModal({{ $id }})" size="xs">Roles</flux:button>
                        <flux:button wire:click="showActionsModal({{ $id }})" size="xs">Actions</flux:button>
                    </div>
                </div>
            @endforeach
        </flux:checkbox.group>
    </div>

    <div class="mt-4 flex justify-end space-x-2">
        <flux:button x-on:click="$flux.modal('firm-sync').close()">
            Close
        </flux:button>
        @if($this->user->role_main !== 'L1_firm')
        <flux:button wire:click="save" variant="primary">
            Save
        </flux:button>
        @endif
    </div>
</div>

<!-- Shared Modal Panel Sync -->
<flux:modal name="firm-panel-sync" title="Manage Panels" class="p-10">
    @if ($selectedFirmId)
        <livewire:saas.user-meta.panel-sync :userId="$this->user->id" :firmId="$selectedFirmId" :wire:key="'firm-panel-sync-'.$selectedFirmId" />
    @endif
</flux:modal>

<!-- Shared Modal Role Sync -->
<flux:modal name="firm-permission-group-sync" title="Manage Roles" class="p-10">
    @if ($selectedFirmId)
        <livewire:saas.user-meta.permission-group-sync :userId="$this->user->id" :firmId="$selectedFirmId" :wire:key="'firm-permission-group-sync-'.$selectedFirmId" />
    @endif
</flux:modal>

<!-- Shared Modal Action Sync -->
<flux:modal name="firm-permission-sync" title="Manage Actions" class="p-10 max-w-7xl">
    @if ($selectedFirmId)
        <livewire:saas.user-meta.permission-sync :userId="$this->user->id" :firmId="$selectedFirmId" :wire:key="'firm-permission-sync-'.$selectedFirmId" />
        
    @endif
</flux:modal>

</div>