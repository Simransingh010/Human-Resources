<div>
    <div class="flex justify-between">
        <div>
            <flux:heading size="lg" class="flex">
                <flux:icon.phone-arrow-up-right />
                Contact ({{$this->employee->fname}} {{$this->employee->lname}} | {{$this->employee->email}})
            </flux:heading>
            <flux:subheading>
                Manage employee contact details.
            </flux:subheading>
        </div>
        <div>
            <flux:modal.trigger name="mdl-contact">
                <flux:button variant="primary" icon="plus" wire:click="resetForm()"
                             class="bg-blue-500 text-white mt-2 rounded-md">
                    New
                </flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    <flux:modal name="mdl-contact" @close="resetForm" position="right" class="max-w-none">

        <form wire:submit.prevent="saveContact">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg" class="flex">
                        <flux:icon.phone-arrow-up-right />
                        @if($isEditing) Edit Contact @else Contact @endif ({{$this->employee->fname}} {{$this->employee->lname}} | {{$this->employee->email}})
                    </flux:heading>
                    <flux:subheading>
                        Manage employee contact details.
                    </flux:subheading>
                </div>
                <flux:separator/>

                <!-- Grid layout for form fields -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:select label="Contact Type" wire:model="contactData.contact_type"
                        placeholder="Select contact type">
                        @foreach($this->listsForFields['contact_type'] as $value => $label)
                            <flux:select.option value="{{ $value }}">{{$label}}</flux:select.option>
                        @endforeach
                    </flux:select>
                    
                    <flux:input 
                        label="{{ $contactLabel }}"
                        wire:model="contactData.contact_value"
                        placeholder="{{ $contactPlaceholder }}" />

                    <flux:input label="Contact Person" wire:model="contactData.contact_person"
                        placeholder="Enter contact person name" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                    <flux:input label="Relation" wire:model="contactData.relation" placeholder="Enter relation" />
                    <div class="space-y-4 flex flex-col justify-end">
                        <flux:checkbox wire:model="contactData.is_primary" label="Primary Contact" />
                        <flux:checkbox wire:model="contactData.is_for_emergency" label="Emergency Contact" />
                    </div>
                </div>

                <!-- Submit Button -->
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
            <flux:table.column class="w-[200px]">Employee</flux:table.column>
            <flux:table.column class="w-[120px]" sortable :sorted="$sortBy === 'contact_type'"
                :direction="$sortDirection" wire:click="sort('contact_type')">Type</flux:table.column>
            <flux:table.column class="w-[180px]" sortable :sorted="$sortBy === 'contact_value'"
                :direction="$sortDirection" wire:click="sort('contact_value')">Contact</flux:table.column>
            <flux:table.column class="w-[200px]">Contact Person</flux:table.column>
            <flux:table.column class="w-[150px]">Type</flux:table.column>
            <flux:table.column class="w-[100px]">Status</flux:table.column>
            <flux:table.column class="w-[120px]">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->contactsList as $contact)
                <flux:table.row :key="$contact->id" class="border-b">
                    <flux:table.cell class="w-[200px] flex items-center gap-3">
                        {{ $contact->employee->fname . ' ' . $contact->employee->lname }}
                    </flux:table.cell>
                    <flux:table.cell class="w-[120px]">{{ ucfirst($contact->contact_type) }}</flux:table.cell>
                    <flux:table.cell class="w-[180px]">{{ $contact->contact_value }}</flux:table.cell>
                    <flux:table.cell class="w-[200px]">
                        {{ $contact->contact_person }}
                        @if($contact->relation)
                            <span class="text-xs text-gray-500">({{ $contact->relation }})</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="w-[150px]">
                        @if($contact->is_primary)
                            <flux:badge size="sm" color="blue" inset="top bottom">Primary</flux:badge>
                        @endif
                        @if($contact->is_for_emergency)
                            <flux:badge size="sm" color="red" inset="top bottom">Emergency</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="w-[100px]">
                        <div class="flex items-center space-x-2">
                            <flux:switch wire:model="contactStatuses.{{ $contact->id }}"
                                wire:click="update_rec_status({{$contact->id}})" :checked="!$contact->is_inactive" />
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="w-[120px]">
                        <div class="flex justify-center space-x-2">
                            <flux:button variant="primary" size="sm" icon="pencil"
                                wire:click="fetchContact({{ $contact->id }})">
                            </flux:button>
                            <flux:modal.trigger name="delete-contact-{{ $contact->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>
                        <flux:modal name="delete-contact-{{ $contact->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete contact?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this contact.</p>
                                        <p>This action cannot be reversed.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                        wire:click="deleteContact({{ $contact->id }})"></flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>