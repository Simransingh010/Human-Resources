<div>
    <flux:modal.trigger name="mdl-profile">
        <flux:button variant="primary" class="bg-blue-500 text-white px-4 py-2 mb-4 rounded-md">
            Add Job Profile
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="mdl-profile" @close="resetForm" position="right" class="max-w-none">
        <form wire:submit.prevent="saveProfile">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Job Profile @else Add Job Profile @endif
                    </flux:heading>
                    <flux:subheading>
                        Make changes to the job profile details.
                    </flux:subheading>
                </div>

                <!-- Grid layout for form fields -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:select label="Select Employee" wire:model="profileData.employee_id"
                        placeholder="Choose an employee">
                        <option value="">Select an employee</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee['id'] }}">
                                {{ $employee['name'] }}
                            </option>
                        @endforeach
                    </flux:select>

                    <flux:input label="Employee Code" wire:model="profileData.employee_code"
                        placeholder="Enter employee code" />

                    <flux:input type="date" label="Date of Hire" wire:model="profileData.doh" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:select label="Department" wire:model="profileData.department_id"
                        placeholder="Select department">
                        <option value="">Select department</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}">
                                {{ $department->title }}
                            </option>
                        @endforeach
                    </flux:select>

                    <flux:select label="Designation" wire:model="profileData.designation_id"
                        placeholder="Select designation">
                        <option value="">Select designation</option>
                        @foreach($designations as $designation)
                            <option value="{{ $designation->id }}">
                                {{ $designation->title }}
                            </option>
                        @endforeach
                    </flux:select>

                    <flux:select label="Employment Type" wire:model="profileData.employment_type"
                        placeholder="Select employment type">
                        <option value="">Select type</option>
                        @foreach($employment_types as $type)
                            <option value="{{ $type->id }}">
                                {{ $type->title }}
                            </option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:select label="Reporting Manager" wire:model="profileData.reporting_manager"
                        placeholder="Select reporting manager">
                        <option value="">Select manager</option>
                        @foreach($managers as $manager)
                            <option value="{{ $manager['id'] }}">
                                {{ $manager['name'] }}
                            </option>
                        @endforeach
                    </flux:select>

                    <flux:input type="date" label="Date of Exit" wire:model="profileData.doe" />
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

    <flux:table :paginate="$this->profilesList" class="w-full">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column class="w-[250px]">Employee</flux:table.column>
            <flux:table.column class="w-[120px]">Employee Code</flux:table.column>
            <flux:table.column class="w-[120px]" sortable :sorted="$sortBy === 'doh'" :direction="$sortDirection"
                wire:click="sort('doh')">Date of Hire</flux:table.column>
            <flux:table.column class="w-[150px]">Department</flux:table.column>
            <flux:table.column class="w-[150px]">Designation</flux:table.column>
            <flux:table.column class="w-[150px]">Manager</flux:table.column>
            <flux:table.column class="w-[120px]">Type</flux:table.column>
            <flux:table.column class="w-[120px]">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->profilesList as $profile)
                <flux:table.row :key="$profile->id" class="border-b">
                    <flux:table.cell class="w-[250px] flex items-center gap-3">
                        {{ $profile->employee->fname . ' ' . $profile->employee->lname }}
                        <span class="text-xs text-gray-500">
                            {{ $profile->employee->email }}
                        </span>
                    </flux:table.cell>
                    <flux:table.cell class="w-[120px]">{{ $profile->employee_code }}</flux:table.cell>
                    <flux:table.cell class="w-[120px]">{{ $profile->doh ? $profile->doh->format('d M Y') : 'N/A' }}
                    </flux:table.cell>
                    <flux:table.cell class="w-[150px]">{{ $profile->department->title ?? 'N/A' }}</flux:table.cell>
                    <flux:table.cell class="w-[150px]">{{ $profile->designation->title ?? 'N/A' }}</flux:table.cell>
                    <flux:table.cell class="w-[150px]">
                        @if($profile->employee)
                            {{ $profile->employee->fname . ' ' . $profile->employee->lname }}
                        @else
                            N/A
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="w-[120px]">
                        <flux:badge size="sm" color="blue" inset="top bottom">
                            {{ $profile->employment_type->title ?? 'N/A' }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="w-[120px]">
                        <div class="flex justify-center space-x-2">
                            <flux:button variant="primary" size="sm" icon="pencil"
                                wire:click="fetchProfile({{ $profile->id }})">
                            </flux:button>
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