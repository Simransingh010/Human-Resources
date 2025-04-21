<div class="space-y-6">
    <div>
        <flux:heading size="lg">{{ $this->user->name }} {{ $this->user->lname }} | {{ $this->user->email }}</flux:heading>
    </div>

    <flux:separator />

    <div class="space-y-4">
        @foreach ($this->listsForFields['permissionHierarchy'] as $firm => $groups)
            <div class="border rounded-xl p-4">
                <flux:heading>{{ $firm }}</flux:heading>

                @foreach ($groups as $group => $permissions)
                    <div class="pl-4">
                        <flux:heading class="mt-2">{{ $group }}</flux:heading>


                        <flux:checkbox.group wire:model="selectedPermissions">
                            @foreach ($permissions as $perm)
                                <flux:checkbox label="{{ $perm['name'] }}" value="{{ $perm['value'] }}" />
                            @endforeach
                        </flux:checkbox.group>
                    </div>
                @endforeach
            </div>
        @endforeach
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
