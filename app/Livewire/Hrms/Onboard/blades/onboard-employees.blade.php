<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Progress Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Employee Onboarding</h1>
            <div class="mt-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">
                        Step {{ $currentStep }} of {{ $totalSteps }}
                    </span>
                    <span class="text-sm font-medium text-gray-700">
                        {{ count($completedSteps) }}/{{ $totalSteps }} completed
                    </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div class="bg-green-500 h-2.5 rounded-full"
                         style="width: {{ (count($completedSteps)/$totalSteps)*100 }}%"></div>
                </div>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Sidebar Navigation -->
            <!-- Left Side - Step Indicators -->
            <div class="flex-shrink-0">
                <nav aria-label="Progress" class="px-4 pt-6">
                    <ol role="list" class="overflow-hidden">
                        @foreach(range(1, 10) as $step)
                            <li class="relative {{ $step < 10 ? 'pb-4' : '' }}">
                                @if($step < 10)
                                    <div class="absolute top-4 left-4 mt-1 -ml-px h-full w-0.5
                        {{ in_array($step, $completedSteps) ? 'bg-emerald-500' : ($currentStep > $step ? 'bg-indigo-600' : 'bg-gray-300') }}"
                                         aria-hidden="true">
                                    </div>
                                @endif

                                <a wire:click="goToStep({{ $step }})"
                                   class="group relative flex items-start cursor-pointer
                                       {{ ($step == 1 || in_array($step, $completedSteps) || ($selectedEmpId && $step <= max($completedSteps) + 1)) ? '' : '' }}">
                    <span class="flex h-9 items-center" aria-hidden="true">
                        @if(in_array($step, $completedSteps))
                            <!-- Completed Step -->
                                <span class="relative z-10 flex size-8 items-center justify-center rounded-full bg-green-500 group-hover:bg-green-600">
                                <svg class="size-5 text-white" viewBox="0 0 20 20" fill="currentColor"
                                     aria-hidden="true">
                                    <path fill-rule="evenodd"
                                          d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z"
                                          clip-rule="evenodd"/>
                                </svg>
                            </span>
                        @elseif($currentStep == $step)
                            <!-- Current Step -->
                                <span class="relative z-10 flex size-8 items-center justify-center rounded-full border-2 border-indigo-600 bg-white">
                                <span class="size-2.5 rounded-full bg-indigo-600"></span>
                            </span>
                        @else
                            <!-- Upcoming Step -->
                                <span class="relative z-10 flex size-8 items-center justify-center rounded-full border-2 border-gray-300 bg-white group-hover:border-gray-400">
                                <span class="size-2.5 rounded-full bg-transparent group-hover:bg-gray-300"></span>
                            </span>
                            @endif
                    </span>
                                    <span class="ml-4 flex min-w-0 flex-col">
                        <span class="text-sm font-medium
                            {{ in_array($step, $completedSteps) ? 'text-green-600' : ($currentStep == $step ? 'text-indigo-600' : 'text-gray-500') }}">
                            @switch($step)
                                @case(1) Basic Info @break
                                @case(2) Address @break
                                @case(3) Bank @break
                                @case(4) Contacts @break
                                @case(5) Job Profile @break
                                @case(6) Personal @break
                                @case(7) Documents @break
                                @case(8) Relations @break
                                @case(9) Work Shift @break
                                @case(10) Attendance @break
                            @endswitch
                        </span>
                        <span class="text-sm text-gray-400">
                            @switch($step)
                                @case(1) Personal details @break
                                @case(2) Residential info @break
                                @case(3) Account details @break
                                @case(4) Phone & Emergency @break
                                @case(5) Department & Role @break
                                @case(6) DOB & Gender @break
                                @case(7) ID proofs @break
                                @case(8) Family links @break
                                @case(9) Shift timing @break
                                @case(10) Policy rules @break
                            @endswitch
                        </span>
                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ol>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="flex-1 bg-white shadow rounded-lg p-6">
                @if($currentStep === 1)
                    <form wire:submit.prevent="saveEmployee">
                        <div class="space-y-6">
                            <div>
                                <h2 class="text-lg font-medium text-gray-900">Basic Information</h2>
                                <p class="mt-1 text-sm text-gray-500">Enter the employee's basic details</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">First Name *</label>
                                    <input type="text" wire:model.defer="employeeData.fname" required
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Middle Name</label>
                                    <input type="text" wire:model.defer="employeeData.mname"
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Last Name</label>
                                    <input type="text" wire:model.defer="employeeData.lname"
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Email *</label>
                                    <input type="email" wire:model.defer="employeeData.email" required
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Phone *</label>
                                    <input type="tel" wire:model.defer="employeeData.phone" required
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Gender *</label>
                                <div class="mt-2 space-x-4">
                                    <label class="inline-flex items-center">
                                        <input type="radio" wire:model.defer="employeeData.gender" value="1" required
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                        <span class="ml-2 text-gray-700">Male</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" wire:model.defer="employeeData.gender" value="2"
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                        <span class="ml-2 text-gray-700">Female</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" wire:model.defer="employeeData.gender" value="3"
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                        <span class="ml-2 text-gray-700">Other</span>
                                    </label>
                                </div>
                            </div>

                            <div class="flex justify-end pt-4">
                                <button type="submit"
                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    {{ $selectedEmpId ? 'Update Information' : 'Create Employee' }}
                                </button>
                            </div>
                        </div>
                    </form>
                @elseif(!$selectedEmpId)
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 15.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                        <h3 class="mt-2 text-lg font-medium text-gray-900">Complete Basic Information First</h3>
                        <p class="mt-1 text-sm text-gray-500">Please complete Step 1 before proceeding to other sections.</p>
                        <div class="mt-6">
                            <button wire:click="goToStep(1)"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Go to Basic Information
                            </button>
                        </div>
                    </div>
                @else
                <!-- Dynamic Step Content -->
                    <div>
                        @if($currentStep === 2)
                            <livewire:hrms.employees-meta.employee-addresses :employeeId="$selectedEmpId"
                                                                             :key="'addresses-'.$selectedEmpId" />
                        @elseif($currentStep === 3)
                            <livewire:hrms.employees-meta.employee-bank-accounts :employeeId="$selectedEmpId"
                                                                                 :key="'bank-accounts-'.$selectedEmpId" />
                        @elseif($currentStep === 4)
                            <livewire:hrms.employees-meta.employee-contacts :employeeId="$selectedEmpId"
                                                                            :key="'contacts-'.$selectedEmpId" />
                        @elseif($currentStep === 5)
                            <livewire:hrms.employees-meta.employee-job-profiles :employeeId="$selectedEmpId"
                                                                                :key="'job-profile-'.$selectedEmpId" />
                        @elseif($currentStep === 6)
                            <livewire:hrms.employees-meta.employee-personal-details :employeeId="$selectedEmpId"
                                                                                    :key="'personal-details-'.$selectedEmpId" />
                        @elseif($currentStep === 7)
                            <livewire:hrms.employees-meta.employee-docs :employeeId="$selectedEmpId"
                                                                        :key="'documents-'.$selectedEmpId" />
                        @elseif($currentStep === 8)
                            <livewire:hrms.employees-meta.employee-relations :employeeId="$selectedEmpId"
                                                                             :key="'relations-'.$selectedEmpId" />
                        @elseif($currentStep === 9)
                            <livewire:hrms.employees-meta.employee-work-shift :employeeId="$selectedEmpId"
                                                                              :key="'work-shift-'.$selectedEmpId" />
                        @elseif($currentStep === 10)
                            <livewire:hrms.employees-meta.employee-attendance-policy :employeeId="$selectedEmpId"
                                                                                     :key="'attendance-policy-'.$selectedEmpId" />
                        @endif
                    </div>
                @endif

            <!-- Navigation Buttons -->
                @if($selectedEmpId || $currentStep === 1)
                    <div class="mt-8 flex justify-between border-t pt-4">
                        @if($currentStep > 1)
                            <button wire:click="previousStep"
                                    class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Previous
                            </button>
                        @else
                            <div></div>
                        @endif

                        @if($currentStep < $totalSteps && $selectedEmpId)
                            <button wire:click="nextStep"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Next
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>