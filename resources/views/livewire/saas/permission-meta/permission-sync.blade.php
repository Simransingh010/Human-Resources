<div class="space-y-6">
    <div>
        <flux:heading size="lg">{{ $permissionGroup->name }}</flux:heading>
        <flux:text class="text-gray-500">Manage associated permissions for this permission group</flux:text>
    </div>

    <flux:separator />
    
    <div class="space-y-4">
        <flux:checkbox.group wire:model="selectedPermissions" label="Permissions">
            @foreach ($this->listsForFields['permissionlist'] as $id => $name)
                <flux:checkbox label="{{ $name }}" value="{{ $id }}"/>
            @endforeach
        </flux:checkbox.group>
    </div>

    <div class="mt-4 flex justify-end space-x-2">
        <flux:button x-on:click="$flux.modal('permission-sync').close()">
            Close
        </flux:button>
        <flux:button wire:click="save" variant="primary">
            Save
        </flux:button>
    </div>
</div> 