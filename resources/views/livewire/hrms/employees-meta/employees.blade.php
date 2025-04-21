<div>
   
    <flux:modal.trigger name="mdl-employee">
        <flux:button variant="primary" class="bg-blue-500 text-white px-4 py-2 mb-4 rounded-md">

                Add Employee

        </flux:button>
    </flux:modal.trigger>

    <flux:modal  name="mdl-employee" @close="resetForm" position="right" class="max-w-none">
        <form wire:submit.prevent="saveEmployee">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Employee @else Add Employee @endif
                    </flux:heading>
                    <flux:subheading>
                        Make changes to the employee's details.
                    </flux:subheading>
                </div>

                <!-- Grid layout for form fields -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:input label="First Name" wire:model="employeeData.fname" placeholder="Employee First Name" />
                    <flux:input label="Middle Name" wire:model="employeeData.mname"
                        placeholder="Employee Middle Name" />
                    <flux:input label="Last Name" wire:model="employeeData.lname" placeholder="Employee Last Name" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:input type="email" label="Email" wire:model="employeeData.email"
                        placeholder="Primary Email" />
                    <flux:input label="Phone" wire:model="employeeData.phone" placeholder="Primary Contact Number" />
                    <div>
                        <flux:radio.group wire:model="employeeData.gender" label="Gender" variant="segmented">
                            <flux:radio wire:model="employeeData.gender" value="1" label="Male" name="gender" />
                            <flux:radio wire:model="employeeData.gender" value="2" label="Female" name="gender" />
                            <flux:radio wire:model="employeeData.gender" value="3" label="Others" name="gender" />
                        </flux:radio.group>
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


    <flux:table :paginate="$this->employeeslist" class="w-full">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column>Employees</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'fname'" :direction="$sortDirection"
                wire:click="sort('fname')">First Name
            </flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'lname'" :direction="$sortDirection"
                wire:click="sort('lname')">Last Name
            </flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection"
                wire:click="sort('created_at')">Created
            </flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->employeeslist as $employee)
                <flux:table.row :key="$employee->id" class="border-b">
                    <flux:table.cell class="flex items-center gap-3">
                        {{ $employee->fname }}
                    </flux:table.cell>
                    <flux:table.cell>{{ $employee->fname }}</flux:table.cell>
                    <flux:table.cell>{{ $employee->lname }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:switch wire:model="employeeStatuses.{{ $employee->id }}"
                            wire:click="toggleStatus({{ $employee->id }})" :checked="!$employee->is_inactive" />
                    </flux:table.cell>
                    <flux:table.cell>{{ $employee->created_at }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex space-x-2">
                            <flux:button variant="primary" size="sm" icon="pencil"
                                wire:click="fetchEmployee({{ $employee->id }})"></flux:button>
                            <flux:modal.trigger name="delete-employee-{{ $employee->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>
                        <flux:modal name="delete-employee-{{ $employee->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete employee?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this employee.</p>
                                        <p>This action cannot be reversed.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                        wire:click="deleteEmployee({{ $employee->id }})"></flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

</div>