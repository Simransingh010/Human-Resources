<div class="w-full p-0 m-0">
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <div class="flex gap-2">
            <flux:modal.trigger name="mdl-student">
                <flux:button variant="primary" class="bg-blue-500 text-white px-4 py-2 rounded-md">
                    Add Student
                </flux:button>
            </flux:modal.trigger>
        </div>
    </div>
    <flux:separator class="mt-2 mb-2"/>

    <form wire:submit.prevent="applyFilters">
        <flux:heading level="3" size="lg">Filter Records</flux:heading>
        <flux:card size="sm" class="sm:p-2 !rounded-xl rounded-xl! mb-1 rounded-t-lg p-0 bg-zinc-50 hover:bg-zinc-50 dark:hover:bg-zinc-700">
            <div class="grid md:grid-cols-3 gap-2 items-end">
                <div>
                    <flux:input type="text" placeholder="Student Name" wire:model.debounce.500ms="filters.students" wire:change="applyFilters"/>
                </div>
                <div>
                    <flux:input type="text" placeholder="Phone" wire:model.debounce.500ms="filters.phone" wire:change="applyFilters"/>
                </div>
                <div>
                    <flux:input type="text" placeholder="Email" wire:model.debounce.500ms="filters.email" wire:change="applyFilters"/>
                </div>
                <div class="flex flex-wrap gap-2">
                    <flux:button variant="filled" class="w-full px-2" tooltip="Cancel Filter" icon="x-circle" wire:click="clearFilters()"></flux:button>
                    <flux:button
                        variant="{{ $this->viewMode === 'card' ? 'primary' : 'outline' }}"
                        wire:click="$set('viewMode','card')"
                        icon="table-cells"
                        class="mr-2"
                    ></flux:button>
                    <flux:button
                        variant="{{ $this->viewMode === 'table' ? 'primary' : 'outline' }}"
                        wire:click="$set('viewMode','table')"
                        icon="adjustments-horizontal"
                    ></flux:button>
                </div>
            </div>
        </flux:card>
    </form>

    @if(($this->viewMode ?? 'table') === 'card')
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mt-6">
            @foreach ($this->studentslist as $student)
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow p-3 flex flex-col gap-3 hover:shadow-lg transition-all border border-zinc-100 dark:border-zinc-700">
                    <div class="flex items-center gap-4">
                        <div>
                            <img src="{{ asset('images/human-img.png') }}" alt="Avatar"
                                 class="w-14 h-14 rounded-full object-cover border border-gray-300 shadow-sm"/>
                        </div>
                        <div class="flex flex-col">
                            <span class="font-semibold text-lg">{{ $student->fname }} {{ $student->mname }} {{ $student->lname }}</span>
                            <span class="text-xs text-gray-500">{{ $this->getStudentStudyCentre($student) }}</span>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1 mt-2">
                        <span class="text-sm text-gray-700 dark:text-gray-200"><b>Phone:</b> {{ $student->phone }}</span>
                        <span class="text-sm text-gray-700 dark:text-gray-200"><b>Email:</b> {{ $student->email }}</span>
                    </div>
                    <div class="flex items-center justify-between mt-2">
                        <div class="flex items-center gap-2">
                            <flux:switch wire:model="studentStatuses.{{ $student->id }}"
                                         wire:click="toggleStatus({{ $student->id }})"
                                         :checked="!$student->is_inactive"/>
                            <span class="text-xs">{{ $student->is_inactive ? 'Inactive' : 'Active' }}</span>
                        </div>
                        <div class="flex gap-2">
                            <flux:button variant="primary" size="sm" icon="pencil"
                                         wire:click="showstudentModal({{ $student->id }})"></flux:button>
                            <flux:modal.trigger name="delete-student-{{ $student->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>
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
                                <flux:spacer/>
                                <flux:modal.close>
                                    <flux:button variant="ghost">Cancel</flux:button>
                                </flux:modal.close>
                                <flux:button type="submit" variant="danger" icon="trash"
                                             wire:click="deleteStudent({{ $student->id }})"></flux:button>
                            </div>
                        </div>
                    </flux:modal>
                </div>
            @endforeach
        </div>
    @else
        <flux:table :paginate="$this->studentslist" class="w-full">
            <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
                <flux:table.column>Name</flux:table.column>
                <flux:table.column>Phone</flux:table.column>
                <flux:table.column>Email</flux:table.column>
                <flux:table.column>Study Centre</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->studentslist as $student)
                    <flux:table.row :key="$student->id" class="border-b">
                        <flux:table.cell>
                            <div class="flex gap-2">
                                <div>
                                    <img src="{{ asset('images/human-img.png') }}" alt="Avatar"
                                         class="w-10 h-10 rounded-full object-cover border border-gray-300 shadow-sm"/>
                                </div>
                                <div>
                                    <flux:text class="font-bold">
                                        {{ $student->fname }} {{ $student->mname }} {{ $student->lname }}
                                    </flux:text>
                                    <flux:text class="text-xs text-gray-500">
                                        {{ $this->getStudentStudyCentre($student) }}
                                    </flux:text>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>{{ $student->phone }}</flux:table.cell>
                        <flux:table.cell class="table-cell-wrap">{{ $student->email }}</flux:table.cell>
                        <flux:table.cell>{{ $this->getStudentStudyCentre($student) }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:switch wire:model="studentStatuses.{{ $student->id }}"
                                             wire:click="toggleStatus({{ $student->id }})"
                                             :checked="!$student->is_inactive"/>
                                <span class="text-xs px-2 py-1 rounded {{ $student->is_inactive ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                    {{ $student->is_inactive ? 'Inactive' : 'Active' }}
                                </span>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex space-x-2">
                                <flux:button variant="primary" size="sm" icon="pencil"
                                             wire:click="showstudentModal({{ $student->id }})"></flux:button>
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
                                        <flux:spacer/>
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
    @endif

    <div class="mt-6 flex justify-center">
        {{ $this->studentslist->links() }}
    </div>

    <flux:modal name="edit-student" title="Onboard Student" class="p-10 max-w-none">
        @if ($selectedStudentId)
            <livewire:hrms.onboard.onboard-students :studentId="$selectedStudentId" :wire:key="'onboard-students-'.$selectedStudentId"/>
        @endif
    </flux:modal>

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
                <div class="flex justify-end pt-4">
                    <flux:button type="submit" variant="primary">Save Changes</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>
