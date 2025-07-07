<div class="space-y-6">
    <div>
        <flux:heading size="lg">{{$this->user->name}} {{$this->user->lname}} | {{$this->user->email}} </flux:heading>

    </div>

    <flux:separator />
    <div class="space-y-4">
        <flux:checkbox.group wire:model="selectedPanels" label="Panels">
            @foreach ($this->listsForFields['panellist'] as $id => $name)
                <flux:checkbox label="{{ $name }}" value="{{ $id }}" />
            @endforeach
        </flux:checkbox.group>
    </div>

    <div class="mt-4 flex justify-end space-x-2">
        <flux:button x-on:click="$flux.modal('panel-sync').close()">
            Close
        </flux:button>
        <flux:button wire:click="save" variant="primary">
            Save
        </flux:button>
    </div>

</div>