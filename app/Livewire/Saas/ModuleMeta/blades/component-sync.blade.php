<div class="space-y-6">
    <div>
        <flux:heading size="lg">{{ $module->name }} ({{ $module->code }})</flux:heading>
        <flux:text class="text-gray-500">Manage components for this module</flux:text>
    </div>

    <flux:separator />

    {{-- <!-- Search Input -->
    <div class="mb-4">
        <flux:input wire:model.live.debounce.300ms="searchTerm" placeholder="Search components..." icon="search" />
    </div> --}}

    <div class="space-y-4 overflow-y-auto max-h-[60vh] pr-2">
        <flux:checkbox.group class="ml-2 mt-2 space-y-1">
            @foreach ($listsForFields['componentlist'] as $id => $comp)
                <div class="flex items-center hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md p-1">
                    <flux:checkbox wire:model="selectedComponents" value="{{ $id }}"
                        label="{{ $comp['name'] }} ({{ $comp['code'] }})" />
                    @if(isset($comp['description']) && $comp['description'])
                        <flux:tooltip text="{{ $comp['description'] }}">
                            <flux:icon name="information-circle" class="w-4 h-4 ml-2 text-gray-400" />
                        </flux:tooltip>
                    @endif
                </div>
            @endforeach
        </flux:checkbox.group>
    </div>

    <div class="mt-4 flex justify-between items-center">
        <div class="text-sm text-gray-500">
            {{ count($selectedComponents) }} components selected
        </div>
        <div class="space-x-2">
            <flux:button x-on:click="$flux.modal('component-sync').close()">
                Cancel
            </flux:button>
            <flux:button wire:click="save" variant="primary">
                Save Changes
            </flux:button>
        </div>
    </div>
</div>