<div class="w-full p-0 m-0">
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        {{--    --}}
    </div>
    <flux:separator class="mt-2 mb-2"/>

    <form wire:submit.prevent="applyFilters">
        <flux:heading level="3" size="lg">Filter Records</flux:heading>
        <flux:card size="sm"
                   class="sm:p-2 !rounded-xl rounded-xl! mb-1 rounded-t-lg p-0 bg-zinc-50 hover:bg-zinc-50 dark:hover:bg-zinc-700">

            <div class="grid md:grid-cols-3 gap-2 items-end">
                <div class="">
                    <flux:input type="text" placeholder="Employee Name" wire:model.debounce.500ms="filters.employees"
                                wire:change="applyFilters"/>
                </div>
                <div>
                    <flux:input type="text" placeholder="Phone" wire:model.debounce.500ms="filters.phone"
                                wire:change="applyFilters"/>
                </div>
                <div>
                    <flux:input type="text" placeholder="Email" wire:model.debounce.500ms="filters.email"
                                wire:change="applyFilters"/>
                </div>
                <div class="flex flex-wrap gap-2">
                    <flux:button variant="filled" class="w-full px-2" tooltip="Cancel Filter" icon="x-circle"
                                 wire:click="clearFilters()"></flux:button>
                    <flux:button
                            variant="{{ $this->viewMode === 'card' ? 'primary' : 'outline' }}"
                            wire:click="setViewMode('card')"
                            icon="table-cells"
                            class="mr-2"
                    ></flux:button>
                    <flux:button
                            variant="{{ $this->viewMode === 'table' ? 'primary' : 'outline' }}"
                            wire:click="setViewMode('table')"
                            icon="adjustments-horizontal"
                    ></flux:button>
                </div>
            </div>

        </flux:card>
    </form>
    {{-- Employee Grid --}}
    @if($this->viewMode === 'card')
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mt-6">
            @foreach ($this->employeeslist as $employee)
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow p-3 flex flex-col gap-3 hover:shadow-lg transition-all border border-zinc-100 dark:border-zinc-700">
                    <div class="flex items-center gap-4">
                        <div>
                            @if($this->getEmployeeImageUrl($employee))
                                <img src="{{ $this->getEmployeeImageUrl($employee) }}" alt="Avatar"
                                     class="w-14 h-14 rounded-full object-cover border border-gray-300 shadow-sm"/>
                            @elseif(in_array($employee->gender, ['male', 1, '1']))
                                <img src="{{ asset('images/male-img.png') }}" alt="Avatar"
                                     class="w-14 h-14 rounded-full object-cover border border-gray-300 shadow-sm"/>
                            @elseif(in_array($employee->gender, ['female', 2, '2']))
                                <img src="{{ asset('images/female-img.png') }}" alt="Avatar"
                                     class="w-14 h-14 rounded-full object-cover border border-gray-300 shadow-sm"/>
                            @else
                                <img src="{{ asset('images/human-img.png') }}" alt="Avatar"
                                     class="w-14 h-14 rounded-full object-cover border border-gray-300 shadow-sm"/>
                            @endif
                        </div>

                        <div class="flex flex-col">
                            <span class="font-semibold text-lg">{{ $employee->fname }} {{ $employee->mname }} {{ $employee->lname }}</span>
                            <span class="text-xs text-gray-500">
                                {{ $this->getEmployeeDepartment($employee) }}
                                @if($this->getEmployeeDepartment($employee) !== '-' && $this->getEmployeeDesignation($employee) !== '-')
                                &bull;
                                @endif
                                {{ $this->getEmployeeDesignation($employee) }}
                            </span>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1 mt-2">
                        <span class="text-sm text-gray-700 dark:text-gray-200"><b>Phone:</b> {{ $employee->phone }}</span>
                        <span class="text-sm text-gray-700 dark:text-gray-200"><b>Email:</b> {{ $employee->email }}</span>
                    </div>
                    <div class="flex items-center gap-2 mt-2">
                        <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                            <div class="bg-green-500 h-2.5 rounded-full"
                                 style="width: {{ number_format($this->getProfileCompletionPercentage($employee->id), 0) }}%"></div>
                        </div>
                        <span class="text-xs font-medium">{{ number_format($this->getProfileCompletionPercentage($employee->id), 0) }}%</span>
                    </div>
                    <div class="flex items-center justify-between mt-2">
                        <div class="flex items-center gap-2">
                            <flux:switch wire:model="employeeStatuses.{{ $employee->id }}"
                                         wire:click="toggleStatus({{ $employee->id }})"
                                         :checked="!$employee->is_inactive"/>
                            <span class="text-xs">{{ $employee->is_inactive ? 'Inactive' : 'Active' }}</span>
                        </div>
                        <div class="flex gap-2">
                            <flux:button variant="primary" size="sm" icon="pencil"
                                         wire:click="showemployeeModal({{ $employee->id }})"></flux:button>
                            <flux:modal.trigger name="delete-employee-{{ $employee->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>
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
                </div>
            @endforeach
        </div>
    @else
        {{-- Table view --}}
        <flux:table :paginate="$this->employeeslist" class="w-full">
            <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
                <flux:table.column>Name</flux:table.column>
                <flux:table.column>Phone</flux:table.column>
                <flux:table.column>Email</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->employeeslist as $employee)
                    <flux:table.row :key="$employee->id" class="border-b">
                        <flux:table.cell>
                            <div class="flex gap-2">
                                <div>
                                    @if($this->getEmployeeImageUrl($employee))
                                        <img src="{{ $this->getEmployeeImageUrl($employee) }}" alt="Avatar"
                                             class="w-14 h-14 rounded-full object-cover border border-gray-300 shadow-sm"/>
                                    @elseif(in_array($employee->gender, ['male', 1, '1']))
                                        <img src="{{ asset('images/male-img.png') }}" alt="Avatar"
                                             class="w-14 h-14 rounded-full object-cover border border-gray-300 shadow-sm"/>
                                    @elseif(in_array($employee->gender, ['female', 2, '2']))
                                        <img src="{{ asset('images/female-img.png') }}" alt="Avatar"
                                             class="w-14 h-14 rounded-full object-cover border border-gray-300 shadow-sm"/>
                                    @else
                                        <img src="{{ asset('images/human-img.png') }}" alt="Avatar"
                                             class="w-14 h-14 rounded-full object-cover border border-gray-300 shadow-sm"/>
                                    @endif
                                </div>
                                <div>
                                    <flux:text class="font-bold">
                                        {{ $employee->fname }} {{ $employee->mname }} {{ $employee->lname }}
                                    </flux:text>
                                    <flux:text>
                                        {{ $this->getEmployeeDepartment($employee) }} <br>
                                        {{ $this->getEmployeeDesignation($employee) }}
                                    </flux:text>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $employee->phone }}
                        </flux:table.cell>
                        <flux:table.cell class="table-cell-wrap">
                            {{ $employee->email }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:switch wire:model="employeeStatuses.{{ $employee->id }}"
                                             wire:click="toggleStatus({{ $employee->id }})"
                                             :checked="!$employee->is_inactive"/>
                                <span class="text-xs px-2 py-1 rounded {{ $employee->is_inactive ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                    {{ $employee->is_inactive ? 'Inactive' : 'Active' }}
                                </span>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex space-x-2">
                                <flux:button variant="primary" size="sm" icon="pencil"
                                             wire:click="showemployeeModal({{ $employee->id }})"></flux:button>
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
    @endif
    {{-- Pagination --}}
    <div class="mt-6 flex justify-center">
        {{ $this->employeeslist->links() }}
    </div>

    <flux:modal name="edit-employee" title="Edit Employee Details" class="p-10 max-w-none">
        @if ($selectedEmpId)
            <livewire:hrms.onboard.onboard-employees :employeeId="$selectedEmpId"
                                                     :wire:key="'onboard-employees-'.$selectedEmpId"/>
        @endif
    </flux:modal>

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
