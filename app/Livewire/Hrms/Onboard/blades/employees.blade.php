<div class="w-full p-0 m-0">
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <div class="flex">
            <flux:modal.trigger name="mdl-employee" class="flex justify-end">
                <flux:button variant="primary" icon="plus" class="bg-blue-500 me-3 text-white px-4 py-2 rounded-md">
                    @if($isEditing)
                        Edit Employee Record
                    @else
                        New
                    @endif
                </flux:button>
            </flux:modal.trigger>
        </div>
    </div>
    <flux:separator class="mt-2 mb-2"/>
    <flux:modal name="mdl-employee" @close="resetForm" position="right" class="max-w-none">
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
                    <flux:input label="First Name" wire:model="employeeData.fname" placeholder="Employee First Name"/>
                    <flux:input label="Middle Name" wire:model="employeeData.mname"
                                placeholder="Employee Middle Name"/>
                    <flux:input label="Last Name" wire:model="employeeData.lname" placeholder="Employee Last Name"/>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:input type="email" label="Email" wire:model="employeeData.email"
                                placeholder="Primary Email"/>
                    <flux:input label="Phone" wire:model="employeeData.phone" placeholder="Primary Contact Number"/>
                    <div>
                        <flux:radio.group wire:model="employeeData.gender" label="Gender" variant="segmented">
                            <flux:radio wire:model="employeeData.gender" value="1" label="Male" name="gender"/>
                            <flux:radio wire:model="employeeData.gender" value="2" label="Female" name="gender"/>
                            <flux:radio wire:model="employeeData.gender" value="3" label="Others" name="gender"/>
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

    <form wire:submit.prevent="applyFilters">
        <flux:heading level="3" size="lg">Filter Records</flux:heading>
        <flux:card size="sm"
                   class="sm:p-2 !rounded-xl rounded-xl! mb-1 rounded-t-lg p-0 bg-zinc-50 hover:bg-zinc-50 dark:hover:bg-zinc-700">

            <div class="grid md:grid-cols-3 gap-2 items-end">
                <div class="">
                    <flux:select
                            variant="listbox"
                            searchable
                            multiple
                            placeholder="Employees"
                            wire:model="filters.employees"
                            wire:key="employees-filter"
                    >
                        @foreach($this->listsForFields['employeelist'] as $key => $value)
                            <flux:select.option value="{{ $key }}">{{ $value }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div>
                    <flux:input type="text" placeholder="Phone" wire:model="filters.phone"/>
                </div>
                <div>
                    <flux:input type="text" placeholder="Email" wire:model="filters.email"/>
                </div>
                <div class="flex flex-wrap gap-2">
                    <flux:select
                            variant="listbox"
                            multiple
                            placeholder="Status"
                            wire:model="filters.status"
                            wire:key="status-filter"
                    >
                        <flux:select.option value="0">Active</flux:select.option>
                        <flux:select.option value="1">Inactive</flux:select.option>
                    </flux:select>

                    <div class="min-w-[100px]">
                        <flux:button type="submit" variant="primary" class="w-full">Go</flux:button>
                    </div>
                    <div class="min-w-[100px]">
                        <flux:button variant="filled" class="w-full px-2" tooltip="Cancel Filter" icon="x-circle"
                                     wire:click="clearFilters()"></flux:button>
                    </div>
                </div>
            </div>

        </flux:card>
    </form>
    <flux:table :paginate="$this->employeeslist" class="w-full">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column>Employees</flux:table.column>
            <flux:table.column>Phone</flux:table.column>
            <flux:table.column>Email</flux:table.column>

            {{--            <flux:table.column sortable :sorted="$sortBy === 'fname'" :direction="$sortDirection"--}}
            {{--                               wire:click="sort('fname')">First Name--}}
            {{--            </flux:table.column>--}}
            {{--            <flux:table.column sortable :sorted="$sortBy === 'lname'" :direction="$sortDirection"--}}
            {{--                               wire:click="sort('lname')">Last Name--}}
            {{--            </flux:table.column>--}}
            <flux:table.column>Status</flux:table.column>
            {{--            <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection"--}}
            {{--                               wire:click="sort('created_at')">Created--}}
            {{--            </flux:table.column>--}}
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->employeeslist as $employee)
                <flux:table.row :key="$employee->id" class="border-b">
                    <flux:table.cell class="flex items-center gap-3">
                        {{ $employee->fname }} {{ $employee->mname }} {{ $employee->lname }}
                    </flux:table.cell>
                    <flux:table.cell class="items-center gap-3">
                        {{ $employee->phone }}
                    </flux:table.cell>
                    <flux:table.cell class="items-center gap-3">
                        {{ $employee->email }}
                    </flux:table.cell>
                    {{--                    <flux:table.cell>{{ $employee->fname }}</flux:table.cell>--}}
                    {{--                    <flux:table.cell>{{ $employee->lname }}</flux:table.cell>--}}
                    <flux:table.cell>
                        <flux:switch wire:model="employeeStatuses.{{ $employee->id }}"
                                     wire:click="toggleStatus({{ $employee->id }})" :checked="!$employee->is_inactive"/>
                    </flux:table.cell>
                    {{--                    <flux:table.cell>{{ $employee->created_at }}</flux:table.cell>--}}
                    <flux:table.cell>
                        <div class="flex space-x-2">
                            {{--                            <flux:dropdown>--}}
                            {{--                                <flux:button icon="calendar-days" size="sm"></flux:button>--}}

                            {{--                                <flux:menu>--}}
                            {{--                                    <flux:modal.trigger wire:click="showmodal_leave_allocations({{ $employee->id }})">--}}
                            {{--                                        <flux:menu.item icon="calendar-days" class="mt-0.5">Leave Allocations</flux:menu.item>--}}
                            {{--                                    </flux:modal.trigger>--}}
                            {{--                                    <flux:modal.trigger wire:click="showmodal_leave_requests({{ $employee->id }})">--}}
                            {{--                                        <flux:menu.item icon="clock" class="mt-0.5">Leave Requests</flux:menu.item>--}}
                            {{--                                    </flux:modal.trigger>--}}
                            {{--                                </flux:menu>--}}
                            {{--                            </flux:dropdown>--}}
                            <flux:dropdown>
                                <flux:button icon="ellipsis-vertical" size="sm"></flux:button>

                                <flux:menu>
                                    <flux:modal.trigger wire:click="showmodal_jobprofile({{ $employee->id }})">
                                        <flux:menu.item icon="newspaper" class="mt-0.5">Job Profiles</flux:menu.item>
                                    </flux:modal.trigger>
                                    <flux:modal.trigger wire:click="showmodal_addprofile({{ $employee->id }})">
                                        <flux:menu.item icon="user-circle" class="mt-0.5">Personal Details
                                        </flux:menu.item>
                                    </flux:modal.trigger>
                                    <flux:modal.trigger
                                            wire:click="showmodal_employeebankaccounts({{ $employee->id }})">
                                        <flux:menu.item icon="building-library" class="mt-0.5">Bank Accounts
                                        </flux:menu.item>
                                    </flux:modal.trigger>
                                    <flux:modal.trigger wire:click="showmodal_addresses({{ $employee->id }})">
                                        <flux:menu.item icon="map-pin" class="">Addresses</flux:menu.item>
                                    </flux:modal.trigger>
                                    <flux:modal.trigger wire:click="showmodal_contacts({{ $employee->id }})">
                                        <flux:menu.item icon="phone-arrow-up-right" class="mt-0.5">Contacts
                                        </flux:menu.item>
                                    </flux:modal.trigger>
                                    <flux:modal.trigger wire:click="showmodal_adddoc({{ $employee->id }})">
                                        <flux:menu.item icon="document-text" class="mt-0.5">Documents</flux:menu.item>
                                    </flux:modal.trigger>
                                    <flux:modal.trigger wire:click="showmodal_addrelatons({{ $employee->id }})">
                                        <flux:menu.item icon="user-group" class="mt-0.5">Relations</flux:menu.item>
                                    </flux:modal.trigger>
                                    <flux:modal.trigger wire:click="showmodal_attendance_policy({{ $employee->id }})">
                                        <flux:menu.item icon="clock" class="mt-0.5">Attendance Policy</flux:menu.item>
                                    </flux:modal.trigger>
                                    <flux:modal.trigger wire:click="showmodal_work_shift({{ $employee->id }})">
                                        <flux:menu.item icon="calendar" class="mt-0.5">Work Shift</flux:menu.item>
                                    </flux:modal.trigger>
                                </flux:menu>
                            </flux:dropdown>
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
                                    <flux:spacer/>
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
    <flux:modal name="add-addresses" title="Add Address" class="p-10 max-w-none">
        @if ($selectedEmpId)
            <livewire:hrms.employees-meta.employee-addresses :employeeId="$selectedEmpId"
                                                             :wire:key="'add-addresses-'.$selectedEmpId"/>
        @endif
    </flux:modal>
    <flux:modal name="add-bank-account" title="Add Bank Accounts" class="p-10 max-w-none">
        @if ($selectedEmpId)
            <livewire:hrms.employees-meta.employee-bank-accounts :employeeId="$selectedEmpId"
                                                                 :wire:key="'add-bank-account-'.$selectedEmpId"/>
        @endif
    </flux:modal>
    <flux:modal name="add-contacts" title="Add Contacts" class="p-10 max-w-none">
        @if ($selectedEmpId)
            <livewire:hrms.employees-meta.employee-contacts :employeeId="$selectedEmpId"
                                                            :wire:key="'add-contacts-'.$selectedEmpId"/>
        @endif
    </flux:modal>
    <flux:modal name="add-job-profile" title="Add Job Profiles" class="p-10 max-w-none">
        @if ($selectedEmpId)
            <livewire:hrms.employees-meta.employee-job-profiles :employeeId="$selectedEmpId"
                                                                :wire:key="'add-job-profile-'.$selectedEmpId"/>
        @endif
    </flux:modal>
    <flux:modal name="add-personal-details" title="Add Personal Details" class="p-10 max-w-none">
        @if ($selectedEmpId)
            <livewire:hrms.employees-meta.employee-personal-details :employeeId="$selectedEmpId"
                                                                    :wire:key="'add-personal-details-'.$selectedEmpId"/>
        @endif
    </flux:modal>
    <flux:modal name="add-documents" title="Add Documents" class="p-10 max-w-none">
        @if ($selectedEmpId)
            <livewire:hrms.employees-meta.employee-docs :employeeId="$selectedEmpId"
                                                        :wire:key="'add-documents-'.$selectedEmpId"/>
        @endif
    </flux:modal>
    <flux:modal name="add-relations" title="Add Relations" class="p-10 max-w-none">
        @if ($selectedEmpId)
            <livewire:hrms.employees-meta.employee-relations :employeeId="$selectedEmpId"
                                                             :wire:key="'add-relations-'.$selectedEmpId"/>
        @endif
    </flux:modal>
    <flux:modal name="add-attendance-policy" title="Attendance Policy" class="p-10 max-w-none">
        @if ($selectedEmpId)
            <livewire:hrms.employees-meta.employee-attendance-policy :employeeId="$selectedEmpId"
                                                                     :wire:key="'add-attendance-policy-'.$selectedEmpId"/>
        @endif
    </flux:modal>
    <flux:modal name="add-work-shift" title="Work Shift" class="p-10 max-w-none">
        @if ($selectedEmpId)
            <livewire:hrms.employees-meta.employee-work-shift :employeeId="$selectedEmpId"
                                                              :wire:key="'add-work-shift-'.$selectedEmpId"/>
        @endif

    </flux:modal>
</div>
