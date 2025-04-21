<div>
    <div class="flex justify-between">
        <div>
            <flux:heading size="lg" class="flex">
                <flux:icon.newspaper />
                Job Profile ({{$this->employee->fname}} {{$this->employee->lname}} | {{$this->employee->email}})
            </flux:heading>
            <flux:subheading>
                Configure employee job profile details.
            </flux:subheading>
        </div>
        <div>
            <flux:modal.trigger name="mdl-profile">
                <flux:button variant="primary" icon="plus" wire:click="resetForm()"
                             class="bg-blue-500 text-white mt-2 rounded-md">
                    New
                </flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    <flux:modal name="mdl-profile" @close="resetForm" position="right" class="max-w-none">

        <form wire:submit.prevent="saveProfile">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg" class="flex">
                        <flux:icon.newspaper />
                        @if($isEditing) Edit Job Profile @else Job Profile @endif ({{$this->employee->fname}} {{$this->employee->lname}} | {{$this->employee->email}})
                    </flux:heading>
                    <flux:subheading>
                        Configure employee job profile details.
                    </flux:subheading>
                </div>

                <!-- First Row -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <flux:input label="Employee Code" wire:model="profileData.employee_code"
                        placeholder="Enter employee code" />

                    <flux:input type="date" label="Date of Hire" wire:model="profileData.doh" />

                    <flux:input type="date" label="Date of Exit" wire:model="profileData.doe" />
                </div>

                <!-- Second Row -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <flux:select label="Department" wire:model="profileData.department_id"
                        placeholder="Select department">
                        <option value="">Select department</option>
                        @foreach($this->departmentsList as $department)
                            <option value="{{ $department->id }}">
                                {{ $department->title }}
                            </option>
                        @endforeach
                    </flux:select>

                    <flux:select label="Designation" wire:model="profileData.designation_id"
                        placeholder="Select designation">
                        <option value="">Select designation</option>
                        @foreach($this->designationsList as $designation)
                            <option value="{{ $designation->id }}">
                                {{ $designation->title }}
                            </option>
                        @endforeach
                    </flux:select>

                    <flux:select label="Employment Type" wire:model="profileData.employment_type"
                        placeholder="Select employment type">
                        <option value="">Select type</option>
                        @foreach($this->employmentTypesList as $type)
                            <option value="{{ $type->id }}">
                                {{ $type->title }}
                            </option>
                        @endforeach
                    </flux:select>
                </div>

                <!-- Third Row -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:select label="Reporting Manager" wire:model="profileData.reporting_manager"
                        placeholder="Select reporting manager">
                        <option value="">Select manager</option>
                        @foreach($this->managersList as $manager)
                            <option value="{{ $manager['id'] }}">
                                {{ $manager['name'] }}
                            </option>
                        @endforeach
                    </flux:select>
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
            <flux:table.column>Employee</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'employee_code'" :direction="$sortDirection"
                wire:click="sort('employee_code')">Employee Code</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'doh'" :direction="$sortDirection"
                wire:click="sort('doh')">Date of Hire</flux:table.column>
            <flux:table.column>Department</flux:table.column>
            <flux:table.column>Designation</flux:table.column>
            <flux:table.column>Employment Type</flux:table.column>
            <flux:table.column>Manager</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->jobProfilesList as $profile)
                <flux:table.row :key="$profile->id" class="border-b">
                    <flux:table.cell class="flex items-center gap-3">
                        {{ $profile->employee->fname . ' ' . $profile->employee->lname }}
                    </flux:table.cell>
                    <flux:table.cell>{{ $profile->employee_code }}</flux:table.cell>
                    <flux:table.cell>{{ $profile->doh ? date('d M Y', strtotime($profile->doh)) : 'N/A' }}</flux:table.cell>
                    <flux:table.cell>{{ $profile->department->title ?? 'N/A' }}</flux:table.cell>
                    <flux:table.cell>{{ $profile->designation->title ?? 'N/A' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" color="blue" inset="top bottom">
                            {{ $profile->employment_type->title ?? 'N/A' }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($profile->reporting_manager)
                            {{ optional($profile->manager)->fname . ' ' . optional($profile->manager)->lname }}
                        @else
                            N/A
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-center space-x-2">
                            <flux:button variant="primary" size="sm" icon="pencil"
                                wire:click="fetchProfile({{ $profile->id }})"></flux:button>
                            <flux:modal.trigger name="delete-profile-{{ $profile->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>
                        <flux:modal name="delete-profile-{{ $profile->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete job profile?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this job profile.</p>
                                        <p>This action cannot be reversed.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                        wire:click="deleteProfile({{ $profile->id }})"></flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>