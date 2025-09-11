<div class="min-h-screen bg-gray-50 p-6">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Review Override Components</h1>
                <p class="text-gray-600">Monitor and review salary component overrides for the selected payroll slot</p>
            </div>
            <div class="flex items-center gap-3">
                <flux:button
                    wire:click="refreshData"
                    class="flex items-center gap-2 px-6 py-3"
                >
                    Refresh Data
                </flux:button>
                
                <!-- Bulk Reset Button -->
                @if($filteredEmployees->count() > 0 && $components->count() > 0)
                    <flux:button

                        wire:click="resetAllOverrides"
                        class="flex items-center gap-2 px-6 py-3"
                    >

                        Reset Selected Components
                    </flux:button>
                @endif
            </div>
        </div>
    </div>

    <!-- Search and Filters Section -->
    <flux:card class="mb-6 shadow-sm border-0">
        <div class="p-6">
            <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">
                <div class="flex-1 max-w-md">
                    <flux:input
                        class="w-full"
                        placeholder="Search by employee name or code..."
                        wire:model.live.debounce.300ms="searchName"
                        icon="magnifying-glass"
                    />
                </div>

            </div>
        </div>
    </flux:card>


    <!-- Data Table Section -->
    @if($filteredEmployees->count() > 0 && $components->count() > 0)
        <flux:card class="shadow-sm border-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50 z-10">
                                <div class="font-semibold text-gray-900 text-sm flex items-center gap-2">
                                    <flux:icon.user class="w-4 h-4 text-gray-400" />
                                    Employee Details
                                </div>
                            </th>
                            @foreach ($components as $salaryComponent)
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[200px]">
                                    <div class="space-y-1">
                                        <div class="font-semibold text-gray-900 text-sm">
                                            {{ $salaryComponent->title }}
                                        </div>
                                        <div class="flex items-center gap-2 text-xs">
                                            <span class="px-2 py-1 rounded-full text-xs font-medium 
                                                {{ $salaryComponent->nature === 'earning' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ ucfirst($salaryComponent->nature) }}
                                            </span>
                                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ ucfirst($salaryComponent->component_type) }}
                                            </span>
                                        </div>
                                    </div>
                                </th>
                            @endforeach
                            
                            <!-- Actions Column -->
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[120px] sticky right-0 bg-gray-50 z-10">
                                <div class=" font-semibold text-gray-900 text-sm flex items-center gap-2">
                                    <flux:icon.cog-6-tooth class="w-4 h-4 text-gray-400" />
                                    Actions
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($filteredEmployees as $employee)
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap sticky left-0 bg-white z-10">
                                    <div class="flex items-center space-x-3">
                                        <div class="">
                                            <div class="">
                                                <span class="text-white font-semibold text-sm">
                                                    {{ strtoupper(substr($employee->fname ?? '', 0, 1)) }}{{ strtoupper(substr($employee->lname ?? '', 0, 1)) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $employee->fname }} {{ $employee->mname ?? '' }} {{ $employee->lname }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                Code: {{ $employee->emp_job_profile->employee_code ?? 'N/A' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                @foreach ($components as $salaryComponent)
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($this->hasOverrideEntry($employee->id, $salaryComponent->id))
                                            @php
                                                $amount = $this->getEmployeeOverrideAmount($employee->id, $salaryComponent->id);
                                                $payable = $this->getEmployeeOverridePayable($employee->id, $salaryComponent->id);
                                                $entryDate = $this->getOverrideEntryDate($employee->id, $salaryComponent->id);
                                            @endphp
                                            <div class="space-y-2">
                                                <div class="flex items-center gap-2">
                                                    <div class="text-lg font-bold text-gray-900">
                                                        ₹{{ number_format($amount, 2) }}
                                                    </div>
                                                    @if($payable != $amount)
                                                        <div class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">
                                                            Payable: ₹{{ number_format($payable, 2) }}
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="flex items-center gap-2 text-xs text-gray-500">
                                                    <flux:icon.clock class="w-3 h-3" />
                                                    {{ $entryDate ? \Carbon\Carbon::parse($entryDate)->format('M d, Y H:i') : 'N/A' }}
                                                </div>
                                            </div>
                                        @else
                                            <div class="text-center py-3">
                                                <div class="w-8 h-8 mx-auto rounded-full bg-gray-100 flex items-center justify-center">
                                                    <flux:icon.minus class="w-4 h-4 text-gray-400" />
                                                </div>
                                                <div class="text-xs text-gray-400 mt-1">No Override</div>
                                            </div>
                                        @endif
                                    </td>
                                @endforeach
                                
                                <!-- Actions Column -->
                                <td class="px-6 py-4 whitespace-nowrap sticky right-0 bg-white z-10">
                                    <div class="flex items-center gap-2">
                                        @if($this->hasAnyOverrideEntry($employee->id))
                                            <flux:button
                                                variant="danger"
                                                size="xs"
                                                wire:click="resetIndividualOverrides({{ $employee->id }})"
                                                class="flex items-center gap-1"
                                            >

                                                Reset
                                            </flux:button>
                                        @else
                                            <span class="text-xs text-gray-400 px-2 py-1">No Overrides</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </flux:card>
    @else
        <flux:card class="shadow-sm border-0">
            <div class="p-12 text-center">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
                    <flux:icon.information-circle class="w-8 h-8 text-gray-400" />
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Override Entries Found</h3>
                <p class="text-gray-500 max-w-md mx-auto">
                    @if($filteredEmployees->count() === 0)
                        No employees with override amounts found for this payroll slot. All components are using default values.
                    @elseif($components->count() === 0)
                        No salary components with overrides found for this payroll slot.
                    @else
                        No override entries found for this payroll slot. All components are using default values.
                    @endif
                </p>
            </div>
        </flux:card>
    @endif

    <!-- Loading Overlay -->
    <div wire:loading.flex class="fixed inset-0 z-50 flex items-center justify-center bg-white/90 backdrop-blur-sm">
        <div class="text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-blue-100 flex items-center justify-center">
                <flux:icon.loading class="w-8 h-8 text-blue-600 animate-spin" />
            </div>
            <div class="text-lg font-medium text-gray-900 mb-2">Loading Data</div>
            <div class="text-sm text-gray-500">Please wait while we fetch the latest information...</div>
        </div>
    </div>

    <!-- Individual Reset Confirmation Modal -->
    <flux:modal name="reset-individual-confirmation" title="Confirm Reset" class="max-w-2xl">
        <div class="p-6">
            <!-- Warning Message -->
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-center gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-red-800">⚠️ Confirm Reset</h3>
                        <p class="text-red-700 mt-1">
                            Are you sure you want to delete these override entries for
                            <strong>{{ $resetTargetId ? ($employees->get($resetTargetId)?->fname . ' ' . $employees->get($resetTargetId)?->lname) : 'this employee' }}</strong>?
                        </p>
                    </div>
                </div>
            </div>

            <!-- Component Selection -->
            <div class="mb-6">
                <h4 class="font-semibold text-gray-900 mb-3">Select Components to Reset:</h4>
                <div class="space-y-3">
                    <!-- Select All Checkbox -->
                    <flux:checkbox
                            wire:model.live="selectAllComponents"
                            label="Select All Components"
                    />

                    <!-- Individual Component Checkboxes -->
                    <div class="border-t pt-3">
                        <flux:checkbox.group label="Available Components" class="space-y-2">
                            @foreach($components as $salaryComponent)
                                @if($this->hasOverrideEntry($resetTargetId, $salaryComponent->id))
                                    <flux:checkbox
                                            wire:model.live="selectedComponents"

                                            description="{{ ucfirst($salaryComponent->nature) }} - {{ ucfirst($salaryComponent->component_type) }}"
                                    />
                                @endif
                            @endforeach
                        </flux:checkbox.group>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end gap-3">
                <flux:button

                        wire:click="cancelReset"
                        class="px-6"
                >
                    Cancel
                </flux:button>
                <flux:button

                        wire:click="executeReset"
                        :disabled="empty($selectedComponents)"
                        class="px-6"
                >

                    Reset Selected Components
                </flux:button>
            </div>
        </div>
    </flux:modal>


    <!-- Reset Override Confirmation Modal -->
    <flux:modal name="reset-override-confirmation" title="Confirm Bulk Override Reset" class="max-w-2xl">
        <div class="p-6">
            <!-- Warning Message -->
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-center gap-3">

                    <div>
                        <h3 class="text-lg font-semibold text-red-800">⚠️ Bulk Reset - Irreversible Action</h3>
                        <p class="text-red-700 mt-1">
                            This action will permanently delete
                            @if($resetType === 'individual')
                                all override entries for the selected employee.
                            @else
                                <strong>ALL override entries for this entire payroll slot.</strong>
                            @endif
                            <strong>This action cannot be undone.</strong>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Reset Details -->
            <div class="mb-6">
                <h4 class="font-semibold text-gray-900 mb-3">Reset Details:</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Reset Type:</span>
                        <span class="font-medium text-gray-900">{{ ucfirst($resetType) }}</span>
                    </div>
                    @if($resetType === 'individual' && $resetTargetId)
                        @php
                            $employee = $employees->get($resetTargetId);
                            $employeeName = $employee ? ($employee->fname . ' ' . $employee->lname) : 'Unknown Employee';
                        @endphp
                        <div class="flex justify-between">
                            <span class="text-gray-600">Employee:</span>
                            <span class="font-medium text-gray-900">{{ $employeeName }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-gray-600">Payroll Slot:</span>
                        <span class="font-medium text-gray-900">{{ $slot->title ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Affected Entries:</span>
                        <span class="font-medium text-red-600">{{ $this->getAffectedEntriesCount() }}</span>
                    </div>
                </div>
            </div>

            <!-- Component Selection -->
            <div class="mb-6">
                <h4 class="font-semibold text-gray-900 mb-3">Select Components to Reset:</h4>
                <div class="space-y-3">
                    <!-- Select All Checkbox -->
                    <flux:checkbox
                        wire:model.live="selectAllComponents"
                        label="Select All Components"

                    />

                    <!-- Individual Component Checkboxes -->
                    <div class="border-t pt-3">
                        <flux:checkbox.group label="Available Components" class="space-y-2">
                            @foreach($components as $salaryComponent)
                                <flux:checkbox
                                    wire:model.live="selectedComponents"
                                    value="{{ $salaryComponent->id }}"
                                    label="{{ $salaryComponent->title }}"
                                    description="{{ ucfirst($salaryComponent->nature) }} - {{ ucfirst($salaryComponent->component_type) }}"
                                />
                            @endforeach
                        </flux:checkbox.group>
                    </div>
                </div>
            </div>

            <!-- Confirmation Input -->
            <div class="mb-6">
                <flux:label for="reset_confirmation" class="text-sm font-medium text-gray-700">
                    Type "CANCEL" to confirm <span class="text-red-500">*</span>
                </flux:label>
                <flux:input
                    wire:model.live="resetConfirmation"
{{--                    id="reset_confirmation"--}}
                    placeholder="Type CANCEL here"
                    class="mt-2"
{{--                    error="$errors->first('resetConfirmation')"--}}
                />
                @error('resetConfirmation')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end gap-3">
                <flux:button

                    wire:click="cancelReset"
                    class="px-6"
                >
                    Cancel
                </flux:button>
                <flux:button

                    wire:click="executeReset"
                    :disabled="empty(trim($resetConfirmation)) || empty(trim($resetReason))"
                    class="px-6"
                >
                    Reset Overrides
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>

