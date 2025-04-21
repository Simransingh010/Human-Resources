<div>
    <flux:modal.trigger name="mdl-relation">
        <flux:button variant="primary" class="bg-blue-500 text-white px-4 py-2 mb-4 rounded-md">
            Add Relation
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="mdl-relation" @close="resetForm" position="right" class="max-w-none">
        <form wire:submit.prevent="saveRelation">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Relation @else Add Relation @endif
                    </flux:heading>
                    <flux:subheading>
                        Manage employee relation details.
                    </flux:subheading>
                </div>

                <!-- Grid layout for form fields -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:select label="Select Employee" wire:model="relationData.employee_id"
                        placeholder="Choose an employee">
                        <option value="">Select an employee</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee['id'] }}">
                                {{ $employee['name'] }}
                            </option>
                        @endforeach
                    </flux:select>

                    <flux:select label="Relation" wire:model="relationData.relation" placeholder="Select relation type">
                        <option value="">Select relation</option>
                        <option value="spouse">Spouse</option>
                        <option value="child">Child</option>
                        <option value="parent">Parent</option>
                        <option value="sibling">Sibling</option>
                        <option value="other">Other</option>
                    </flux:select>

                    <flux:input label="Person Name" wire:model="relationData.person_name"
                        placeholder="Enter person name" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:input label="Occupation" wire:model="relationData.occupation"
                        placeholder="Enter occupation" />
                    <flux:input type="date" label="Date of Birth" wire:model="relationData.dob" />
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

    <flux:table :paginate="$this->relationsList" class="w-full">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column>Employee</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'relation'" :direction="$sortDirection"
                wire:click="sort('relation')">Relation</flux:table.column>
            <flux:table.column>Person Name</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'dob'" :direction="$sortDirection"
                wire:click="sort('dob')">Date of Birth</flux:table.column>
            <flux:table.column>Details</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->relationsList as $relation)
                <flux:table.row :key="$relation->id" class="border-b">
                    <flux:table.cell class="flex items-center gap-3">
                        {{ $relation->employee->fname . ' ' . $relation->employee->lname }}

                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" color="blue" inset="top bottom">
                            {{ ucfirst($relation->relation) }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $relation->person_name }}</flux:table.cell>
                    <flux:table.cell>{{ $relation->dob ? $relation->dob->format('d M Y') : 'N/A' }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="text-sm">
                            @if($relation->occupation)
                                <div>{{ $relation->occupation }}</div>
                            @endif
                            @if($relation->qualification)
                                <div class="text-gray-500">{{ $relation->qualification }}</div>
                            @endif
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:switch wire:model="relationStatuses.{{ $relation->id }}"
                            wire:click="update_rec_status({{$relation->id}})" :checked="!$relation->is_inactive" />
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex space-x-2">
                            <flux:button variant="primary" size="sm" icon="pencil"
                                wire:click="fetchRelation({{ $relation->id }})">
                            </flux:button>
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
                                        wire:click="deleteRelation({{ $relation->id }})">
                                    </flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>