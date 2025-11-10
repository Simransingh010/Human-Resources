<div>
   
    <flux:modal.trigger name="mdl-student">
        <flux:button variant="primary" class="bg-blue-500 text-white px-4 py-2 mb-4 rounded-md">
            Add Student
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="mdl-student" @close="resetForm" position="right" class="max-w-none">
        <form wire:submit.prevent="saveStudent">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Student @else Add Student @endif
                    </flux:heading>
                    <flux:subheading>
                        Make changes to the student's details.
                    </flux:subheading>
                </div>

                <!-- Grid layout for form fields -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:input label="First Name" wire:model="studentData.fname" placeholder="Student First Name" />
                    <flux:input label="Middle Name" wire:model="studentData.mname" placeholder="Student Middle Name" />
                    <flux:input label="Last Name" wire:model="studentData.lname" placeholder="Student Last Name" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:input type="email" label="Email" wire:model="studentData.email" placeholder="Primary Email" />
                    <flux:input label="Phone" wire:model="studentData.phone" placeholder="Primary Contact Number" />
                    <div>
                        <flux:select label="Study Centre" wire:model="studentData.study_centre_id" placeholder="Select Study Centre">
                            @foreach($listsForFields['study_centres'] ?? [] as $id => $name)
                                <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
                            @endforeach
                        </flux:select>
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

    <flux:table :paginate="$this->studentslist" class="w-full">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column>Students</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'fname'" :direction="$sortDirection"
                wire:click="sort('fname')">First Name
            </flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'lname'" :direction="$sortDirection"
                wire:click="sort('lname')">Last Name
            </flux:table.column>
            <flux:table.column>Study Centre</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection"
                wire:click="sort('created_at')">Created
            </flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->studentslist as $student)
                <flux:table.row :key="$student->id" class="border-b">
                    <flux:table.cell class="flex items-center gap-3">
                        {{ $student->fname }}
                    </flux:table.cell>
                    <flux:table.cell>{{ $student->fname }}</flux:table.cell>
                    <flux:table.cell>{{ $student->lname }}</flux:table.cell>
                    <flux:table.cell>{{ $this->getStudentStudyCentre($student) }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:switch wire:model="studentStatuses.{{ $student->id }}"
                            wire:click="toggleStatus({{ $student->id }})" :checked="!$student->is_inactive" />
                    </flux:table.cell>
                    <flux:table.cell>{{ $student->created_at }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex space-x-2">
                            <flux:button variant="primary" size="sm" icon="pencil"
                                wire:click="fetchStudent({{ $student->id }})"></flux:button>
                            <flux:modal.trigger name="delete-student-{{ $student->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>
                        <flux:modal name="delete-student-{{ $student->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete student?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this student.</p>
                                        <p>This action cannot be reversed.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                        wire:click="deleteStudent({{ $student->id }})"></flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

</div>
