<div class="space-y-6">
    <div>
        <flux:heading size="lg">{{ $panel->name }} ({{ $panel->code }})</flux:heading>
        <flux:text class="text-gray-500">Manage associated modules for this panel</flux:text>
    </div>

    <flux:separator />

    <div class="space-y-6">
        @foreach ($this->listsForFields['modulelist'] as $appName => $modules)
            <div class="space-y-2">
                <flux:label size="sm">{{ $appName }}</flux:label>
                <flux:checkbox.group wire:model="selectedModules">
                    @foreach ($modules as $id => $name)
                        <flux:checkbox label="{{ $name }}" value="{{ $id }}"/>
                    @endforeach
                </flux:checkbox.group>
            </div>
        @endforeach
    </div>

    <div class="mt-4 flex justify-end space-x-2">
        <flux:button x-on:click="$flux.modal('module-sync').close()">
            Close
        </flux:button>
        <flux:button wire:click="save" variant="primary">
            Save
        </flux:button>
    </div>
</div> 