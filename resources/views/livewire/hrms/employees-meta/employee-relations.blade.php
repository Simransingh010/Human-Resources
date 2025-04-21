<div>
    <div class="flex justify-between">
        <div>
            <flux:heading size="lg" class="flex">
                <flux:icon.user-group />
                Relation ({{$this->employee->fname}} {{$this->employee->lname}} | {{$this->employee->email}})
            </flux:heading>
            <flux:subheading>
                Configure employee relation details.
            </flux:subheading>
        </div>
        <div>
            <flux:modal.trigger name="mdl-relation">
                <flux:button variant="primary" icon="plus" wire:click="resetForm()"
                             class="bg-blue-500 text-white mt-2 rounded-md">
                    New
                </flux:button>
            </flux:modal.trigger>
        </div>
    </div>
    <flux:modal name="mdl-relation" @close="resetForm" position="right" class="max-w-none">
        <form wire:submit.prevent="saveRelation">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg" class="flex">
                        <flux:icon.user-group />
                        @if($isEditing) Edit Relation @else Relation @endif ({{$this->employee->fname}} {{$this->employee->lname}} | {{$this->employee->email}})
                    </flux:heading>
                    <flux:subheading>
                        Configure employee relation details.
                    </flux:subheading>
                </div>
                <flux:separator/>
                <!-- First Row -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <flux:select label="Relation" wire:model="relationData.relation" placeholder="Select relation type">
                        <option value="">Select relation</option>
                        @foreach($this->listsForFields['relation'] as $value => $label)
                            <flux:select.option value="{{ $value }}">{{$label}}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input label="Person Name" wire:model="relationData.person_name"
                        placeholder="Enter person name" />

                    <flux:input type="date" label="Date of Birth" wire:model="relationData.dob" />
                </div>

                <!-- Second Row -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:input label="Occupation" wire:model="relationData.occupation"
                        placeholder="Enter occupation" />
                    <flux:input label="Qualification" wire:model="relationData.qualification"
                        placeholder="Enter qualification" />
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
    <flux:table>
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column sortable :sorted="$sortBy === 'relation'" :direction="$sortDirection"
                wire:click="sort('relation')">Relation</flux:table.column>
            <flux:table.column>Person Name</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'dob'" :direction="$sortDirection"
                wire:click="sort('dob')">Date of Birth</flux:table.column>
            <flux:table.column>Occupation</flux:table.column>
            <flux:table.column>Qualification</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->relationsList as $relation)
                <flux:table.row :key="$relation->id" class="border-b">
                    <flux:table.cell>
                        <flux:badge size="sm" color="blue" inset="top bottom">
                            {{ ucfirst($relation->relation) }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $relation->person_name }}</flux:table.cell>
                    <flux:table.cell>{{ $relation->dob ? date('d M Y', strtotime($relation->dob)) : 'N/A' }}</flux:table.cell>
                    <flux:table.cell>{{ $relation->occupation ?? 'N/A' }}</flux:table.cell>
                    <flux:table.cell>{{ $relation->qualification ?? 'N/A' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:switch wire:model="relationStatuses.{{ $relation->id }}"
                            wire:click="update_rec_status({{$relation->id}})" :checked="!$relation->is_inactive" />
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-center space-x-2">
                            <flux:button variant="primary" size="sm" icon="pencil"
                                wire:click="fetchRelation({{ $relation->id }})"></flux:button>
                            <flux:modal.trigger name="delete-relation-{{ $relation->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>

                        <flux:modal name="delete-relation-{{ $relation->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete relation?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this relation.</p>
                                        <p>This action cannot be reversed.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                        wire:click="deleteRelation({{ $relation->id }})"></flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>