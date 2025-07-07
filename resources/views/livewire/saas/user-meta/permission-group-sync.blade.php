<div class="space-y-6">
    <div>
        <flux:heading size="lg">{{$this->user->name}} | {{$this->user->email}} </flux:heading>
    </div>

    <flux:separator />
    <div class="space-y-6">
        <!-- Firm Roles Section -->
        <div class="space-y-2">
            <flux:text class="font-medium text-gray-700">Firm Roles</flux:text>
            <div class="pl-4 space-y-2 bg-gray-50 p-4 rounded-lg">
                <flux:checkbox.group wire:model="selectedRoles">
                    @foreach ($this->listsForFields['rolelist'] as $roleId => $roleName)
                        @if (str_contains($roleName, '(Firm)'))
                            <flux:checkbox label="{{ str_replace(' (Firm)', '', $roleName) }}" value="{{ $roleId }}" />
                        @endif
                    @endforeach
                </flux:checkbox.group>
            </div>
        </div>

        <!-- Global Roles Section -->
        @if($this->user->role_main !== 'L1_firm')
        <div class="space-y-2">
            <flux:text class="font-medium text-gray-700">Global Roles</flux:text>
            <div class="pl-4 space-y-2 bg-blue-50 p-4 rounded-lg">
                <flux:checkbox.group wire:model="selectedRoles">
                    @foreach ($this->listsForFields['rolelist'] as $roleId => $roleName)
                        @if (str_contains($roleName, '(Global)'))
                            <flux:checkbox label="{{ str_replace(' (Global)', '', $roleName) }}" value="{{ $roleId }}" />
                        @endif
                    @endforeach
                </flux:checkbox.group>
            </div>
        </div>
        @endif
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