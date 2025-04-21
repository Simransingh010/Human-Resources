<div class="space-y-6">
    <div>
        <flux:heading size="lg">{{$this->user->name}} {{$this->user->lname}} | {{$this->user->email}} </flux:heading>

    </div>

    <flux:separator />
    <div class="space-y-4">
        @foreach ($this->listsForFields['permissiongrouplist'] as $firmName => $groups)
            <flux:heading>{{ $firmName }}</flux:heading>

            <div class="grid grid-cols-2 gap-2">
                @foreach ($groups as $group)
                    <flux:checkbox
                        wire:model="selectedPermissionGroups"
                        label="{{ $group['name'] }}"
                        value="{{ $group['id'] }}"
                    />
                @endforeach
            </div>
        @endforeach
    </div>


    <div class="mt-4 flex justify-end space-x-2">
        <flux:button x-on:click="$flux.modal('permission-group-sync').close()">
            Close
        </flux:button>
        <flux:button wire:click="save" variant="primary">
            Save
        </flux:button>
    </div>

</div>
