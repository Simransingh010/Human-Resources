<div class="space-y-6">
    <div>
        <flux:heading size="lg">{{ $app->name }}  ({{ $app->code }})</flux:heading>
        <flux:text class="text-gray-500">Manage associated modules for this app</flux:text>
    </div>

    <flux:separator />

    <div class="space-y-4">
        <flux:modal name="selectedModules" variant >
            <flux:checkbox.group wire:model="selectedModules" label="Modules">
                <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-2 gap-6">
                    @foreach ($this->listsForFields['modulelist'] as $id => $name)
                        <flux:checkbox label="{{ $name }}" size="lg" value="{{ $id }}" />
                    @endforeach
                </div>
            </flux:checkbox.group>
        </flux:modal>

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