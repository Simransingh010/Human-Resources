<div>
    <div class="flex justify-between">
        <div>
            <flux:heading size="lg" class="flex">
                <flux:icon.map-pin />
                Address ({{$this->employee->fname}} {{$this->employee->lname}} | {{$this->employee->email}})
            </flux:heading>
            <flux:subheading>
                Manage employee address details.
            </flux:subheading>
        </div>
        <div>
            <flux:modal.trigger name="mdl-address">
                <flux:button variant="primary" icon="plus" wire:click="resetForm()"
                             class="bg-blue-500 text-white mt-2 rounded-md">
                    New
                </flux:button>
            </flux:modal.trigger>
        </div>
    </div>



    <flux:modal name="mdl-address" @close="resetForm" position="right" class="max-w-none">
        <form wire:submit.prevent="saveAddress">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg" class="flex">
                        <flux:icon.map-pin />
                        @if($isEditing) Edit Address @else Addresses @endif ({{$this->employee->fname}} {{$this->employee->lname}} | {{$this->employee->email}})
                    </flux:heading>
                    <flux:subheading>
                        Manage employee address details.
                    </flux:subheading>
                </div>
                <flux:separator/>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {{--                    <flux:input label="Employee" wire:model="addressData.employee_id" value="{{$this->employee->id}}" placeholder="{{$this->employee->fname}}" />--}}
                    <flux:input label="Country" wire:model="addressData.country" placeholder="Country"/>
                    <flux:input label="State" wire:model="addressData.state" placeholder="State"/>
                    <flux:input label="City" wire:model="addressData.city" placeholder="City"/>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                    <flux:input label="Town" wire:model="addressData.town" placeholder="Town"/>
                    <flux:input label="Post Office" wire:model="addressData.postoffice" placeholder="Post Office"/>
                    <flux:input label="Village" wire:model="addressData.village" placeholder="Village"/>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                    <flux:input label="Pincode" wire:model="addressData.pincode" placeholder="Pincode"/>
                    <div class="space-y-4">
                        <flux:checkbox wire:model="addressData.is_primary" label="Primary Address"/>
                        <flux:checkbox wire:model="addressData.is_permanent" label="Permanent Address"/>
                    </div>
                </div>

                <flux:textarea label="Address" wire:model="addressData.address" placeholder="Full Address"/>

                <div class="flex justify-end pt-4">
                    <flux:button type="submit" variant="primary">
                        Save Changes
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:separator class="mb-3 mt-3" />

    <flux:table class="">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column>Employee</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'city'" :direction="$sortDirection"
                               wire:click="sort('city')">City
            </flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'state'" :direction="$sortDirection"
                               wire:click="sort('state')">State
            </flux:table.column>
            <flux:table.column>Type</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->addresseslist as $address)
                <flux:table.row :key="$address->id" class="border-b">
                    <flux:table.cell class="flex items-center gap-3">
                        {{ $address->employee->fname . ' ' . $address->employee->lname }}

                    </flux:table.cell>
                    <flux:table.cell>{{ $address->city }}</flux:table.cell>
                    <flux:table.cell>{{ $address->state }}</flux:table.cell>
                    <flux:table.cell>
                        @if($address->is_primary)
                            <flux:badge size="sm" color="blue" inset="top bottom">Primary</flux:badge>
                        @endif
                        @if($address->is_permanent)
                            <flux:badge size="sm" color="green" inset="top bottom">Permanent</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center space-x-2">
                            <flux:switch wire:model="addressStatuses.{{ $address->id }}"
                                         wire:click="update_rec_status({{$address->id}})"
                                         :checked="!$address->is_inactive"/>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-center space-x-2">
                            <flux:button variant="primary" size="sm" icon="pencil"
                                         wire:click="fetchAddress({{ $address->id }})"></flux:button>
                            <flux:modal.trigger name="delete-address-{{ $address->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>
                        <flux:modal name="delete-address-{{ $address->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete Address?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this address.</p>
                                        <p>This action cannot be reversed.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer/>
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                                 wire:click="deleteAddress({{ $address->id }})"></flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>