<div class="space-y-6">
    <div>
        <flux:heading size="lg">{{ $firm->name }}</flux:heading>
        <flux:text class="text-gray-500">Manage access to apps and their modules for this firm.</flux:text>
    </div>

    <flux:separator />

    <div class="space-y-8">
        <!-- App & Module Access Section -->
        @foreach ($this->listsForFields['apps'] as $appName => $modules)
            <div>
                <flux:label size="md">{{ $appName }}</flux:label>
                <div class="mt-4 ml-4">
                    <flux:checkbox.group wire:model="selectedModules">
                        @foreach ($modules as $module)
                            <flux:checkbox
                                label="{{ $module['name'] }}"
                                value="{{ $module['id'] }}"
                            />
                        @endforeach
                    </flux:checkbox.group>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6 flex justify-end space-x-2">
        <flux:button x-on:click="$flux.modal('firm-access-sync').close()">Cancel</flux:button>
        <flux:button wire:click="save" variant="primary">Save Access</flux:button>
    </div>
</div>
