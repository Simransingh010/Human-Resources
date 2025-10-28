<div class="space-y-6">
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <div class="flex gap-2">
            <flux:button 
                variant="outline" 
                wire:click="clearFilters" 
                tooltip="Clear All Filters" 
                icon="x-circle"
            >
                Clear Filters
            </flux:button>
        </div>
    </div>
    <flux:separator class="mt-2 mb-2" />
    <!-- Heading End -->

    <!-- College Selection Section -->
    <flux:card>
        <flux:heading>Select College</flux:heading>
        <div class="space-y-4">
            <div class="w-1/2">
                <flux:input
                    placeholder="Search colleges by name, code, or city..."
                    wire:model.live.debounce.500ms="collegeSearch"
                    icon="magnifying-glass"
                />
            </div>
            
            @if($colleges->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($colleges as $college)
                        <div class="border rounded-lg p-4 cursor-pointer transition-all duration-200 hover:shadow-md {{ $selectedCollegeId == $college->id ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }}"
                             wire:click="selectCollege({{ $college->id }})">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="font-semibold text-lg">{{ $college->name }}</h3>
                                    <p class="text-sm text-gray-600">{{ $college->code }}</p>
                                    <p class="text-sm text-gray-500">{{ $college->city }}</p>
                                </div>
                                <div class="flex items-center">
                                    @if($college->is_inactive)
                                        <flux:badge color="gray">Inactive</flux:badge>
                                    @else
                                        <flux:badge color="green">Active</flux:badge>
                                    @endif
                                    @if($selectedCollegeId == $college->id)
                                        <flux:icon.check class="ml-2 text-blue-500" />
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    <flux:icon.building-office class="mx-auto h-12 w-12 text-gray-400" />
                    <p class="mt-2">No colleges found</p>
                </div>
            @endif
        </div>
    </flux:card>

    @if($selectedCollegeId)
        @php
            $selectedCollege = $colleges->firstWhere('id', $selectedCollegeId);
        @endphp
        
        <!-- Selected College Info -->
        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading>Selected: {{ $selectedCollege->name ?? 'Unknown' }}</flux:heading>
                    <flux:subheading>{{ $selectedCollege->code ?? '' }} - {{ $selectedCollege->city ?? '' }}</flux:subheading>
                </div>
                <div class="flex items-center gap-2">
                    <flux:badge color="blue">{{ $assignedEmployees->count() }} assigned</flux:badge>
                    <flux:badge color="gray">{{ $employees->count() }} total employees</flux:badge>
                </div>
            </div>
        </flux:card>

        <!-- Employee Assignment Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Available Employees -->
            <flux:card>
                <div class="flex items-center justify-between mb-4">
                    <flux:heading>Available Employees</flux:heading>
                    <div class="flex items-center gap-2">
                        <flux:switch
                            label="Show Assigned Only"
                            wire:model.live="showAssignedOnly"
                        />
                    </div>
                </div>

                <!-- Search and Select All -->
                <div class="space-y-4 mb-4">
                    <flux:input
                        placeholder="Search employees by name, email, or phone..."
                        wire:model.live.debounce.500ms="employeeSearch"
                        icon="magnifying-glass"
                    />
                    
                    <div class="flex items-center justify-between">
                        <flux:checkbox
                            label="Select All"
                            wire:model.live="selectAll"
                            wire:click="toggleSelectAll"
                        />
                        <div class="text-sm text-gray-600">
                            {{ count($selectedEmployeeIds) }} selected
                        </div>
                    </div>
                </div>

                <!-- Employee List -->
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    @if($employees->count() > 0)
                        @foreach($employees as $employee)
                            <div class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50">
                                <div class="flex items-center space-x-3">
                                    <flux:checkbox
                                        wire:click="toggleEmployee({{ $employee->id }})"
                                        :checked="in_array($employee->id, $selectedEmployeeIds)"
                                    />
                                    <div>
                                        <div class="font-medium">
                                            {{ $employee->fname }} {{ $employee->lname }}
                                        </div>
                                        <div class="text-sm text-gray-600">
                                            {{ $employee->email }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $employee->phone }}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    @if($assignedEmployees->contains('id', $employee->id))
                                        <flux:badge color="green">Assigned</flux:badge>
                                    @else
                                        <flux:badge color="gray">Not Assigned</flux:badge>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-8 text-gray-500">
                            <flux:icon.user-group class="mx-auto h-12 w-12 text-gray-400" />
                            <p class="mt-2">No employees found</p>
                        </div>
                    @endif
                </div>

                <!-- Action Buttons -->
                <div class="mt-4 pt-4 border-t">
                    <div class="flex justify-between">
                        <div class="text-sm text-gray-600">
                            {{ count($selectedEmployeeIds) }} employees selected
                        </div>
                        <flux:button
                            variant="primary"
                            wire:click="assignEmployees"
                            :disabled="empty($selectedEmployeeIds)"
                            icon="plus"
                        >
                            Assign Selected Employees
                        </flux:button>
                    </div>
                </div>
            </flux:card>

            <!-- Assigned Employees -->
            <flux:card>
                <flux:heading>Assigned Employees</flux:heading>
                <flux:subheading>Currently assigned to {{ $selectedCollege->name ?? 'this college' }}</flux:subheading>

                <div class="space-y-2 max-h-96 overflow-y-auto mt-4">
                    @if($assignedEmployees->count() > 0)
                        @foreach($assignedEmployees as $employee)
                            <div class="flex items-center justify-between p-3 border rounded-lg bg-green-50">
                                <div class="flex items-center space-x-3">
                                    <flux:icon.check-circle class="text-green-500" />
                                    <div>
                                        <div class="font-medium">
                                            {{ $employee->fname }} {{ $employee->lname }}
                                        </div>
                                        <div class="text-sm text-gray-600">
                                            {{ $employee->email }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $employee->phone }}
                                        </div>
                                    </div>
                                </div>
                                <flux:button
                                    variant="danger"
                                    size="sm"
                                    icon="trash"
                                    wire:click="removeEmployee({{ $employee->id }})"
                                    wire:confirm="Are you sure you want to remove this employee from the college?"
                                />
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-8 text-gray-500">
                            <flux:icon.user-group class="mx-auto h-12 w-12 text-gray-400" />
                            <p class="mt-2">No employees assigned to this college</p>
                        </div>
                    @endif
                </div>
            </flux:card>
        </div>

        <!-- Bulk Actions -->
        @if($assignedEmployees->count() > 0)
            <flux:card>
                <flux:heading>Bulk Actions</flux:heading>
                <div class="flex items-center gap-4">
                    <flux:button
                        variant="danger"
                        icon="trash"
                        wire:click="clearAllAssignments"
                        wire:confirm="Are you sure you want to remove all employees from this college? This action cannot be undone."
                    >
                        Remove All Employees
                    </flux:button>
                    <div class="text-sm text-gray-600">
                        This will remove all {{ $assignedEmployees->count() }} assigned employees from the college.
                    </div>
                </div>
            </flux:card>
        @endif
    @else
        <!-- No College Selected -->
        <flux:card>
            <div class="text-center py-12">
                <flux:icon.building-office class="mx-auto h-16 w-16 text-gray-400" />
                <h3 class="mt-4 text-lg font-medium text-gray-900">Select a College</h3>
                <p class="mt-2 text-gray-600">Choose a college from the list above to manage employee assignments.</p>
            </div>
        </flux:card>
    @endif

    <!-- Statistics Card -->
    <flux:card>
        <flux:heading>Statistics</flux:heading>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">{{ $colleges->count() }}</div>
                <div class="text-sm text-gray-600">Total Colleges</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600">{{ $employees->count() }}</div>
                <div class="text-sm text-gray-600">Total Employees</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-purple-600">{{ $assignedEmployees->count() }}</div>
                <div class="text-sm text-gray-600">Assigned to Selected College</div>
            </div>
        </div>
    </flux:card>
</div>
