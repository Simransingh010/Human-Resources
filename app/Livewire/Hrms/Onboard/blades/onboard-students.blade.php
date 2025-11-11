<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-4">
            <h1 class="text-xl font-bold text-gray-900">Student Onboarding</h1>
            <div class="mt-3">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">
                        Step {{ $currentStep }} of {{ $totalSteps }}
                    </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div class="bg-blue-500 h-2.5 rounded-full" style="width: {{ ($currentStep/$totalSteps)*100 }}%"></div>
                </div>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-5">
            @if($currentStep === 1)
                <form wire:submit.prevent="saveStudentStep">
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">First Name *</label>
                                <input type="text" wire:model.defer="studentData.fname" required
                                       class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Middle Name</label>
                                <input type="text" wire:model.defer="studentData.mname"
                                       class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Last Name</label>
                                <input type="text" wire:model.defer="studentData.lname"
                                       class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 focus:outline-none">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email *</label>
                                <input type="email" wire:model.defer="studentData.email" required
                                       class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Phone *</label>
                                <input type="text" wire:model.defer="studentData.phone" required
                                       class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Study Centre</label>
                                <input type="number" wire:model.defer="studentData.study_centre_id"
                                       class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 focus:outline-none">
                            </div>
                        </div>
                        <div class="flex justify-between pt-2">
                            <div></div>
                            <div class="flex gap-2">
                                <button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded-md">Save</button>
                                <button type="button" wire:click="nextStep" class="px-4 py-2 text-white bg-indigo-600 rounded-md">Next</button>
                            </div>
                        </div>
                    </div>
                </form>
            @elseif($currentStep === 2)
                <form wire:submit.prevent="savePersonalStep">
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Gender</label>
                                <input type="text" wire:model.defer="personalData.gender"
                                       class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Father Name</label>
                                <input type="text" wire:model.defer="personalData.fathername"
                                       class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Mother Name</label>
                                <input type="text" wire:model.defer="personalData.mothername"
                                       class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 focus:outline-none">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Mobile</label>
                                <input type="text" wire:model.defer="personalData.mobile_number"
                                       class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">DOB</label>
                                <input type="date" wire:model.defer="personalData.dob"
                                       class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Admission Date</label>
                                <input type="date" wire:model.defer="personalData.admission_date"
                                       class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 focus:outline-none">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nationality</label>
                                <input type="text" wire:model.defer="personalData.nationality"
                                       class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Aadhar No</label>
                                <input type="text" wire:model.defer="personalData.adharno"
                                       class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">PAN No</label>
                                <input type="text" wire:model.defer="personalData.panno"
                                       class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 focus:outline-none">
                            </div>
                        </div>
                        <div class="flex justify-between pt-2">
                            <button type="button" wire:click="previousStep" class="px-4 py-2 text-gray-700 bg-white border rounded-md">Previous</button>
                            <div class="flex gap-2">
                                <button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded-md">Save</button>
                                <button type="button" wire:click="nextStep" class="px-4 py-2 text-white bg-indigo-600 rounded-md">Next</button>
                            </div>
                        </div>
                    </div>
                </form>
            @elseif($currentStep === 3)
                <form wire:submit.prevent="saveEducationStep">
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Student Code</label>
                                <input type="text" wire:model.defer="educationData.student_code"
                                       class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Date of Joining</label>
                                <input type="date" wire:model.defer="educationData.doh"
                                       class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Study Centre</label>
                                <input type="number" wire:model.defer="educationData.study_centre_id"
                                       class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 focus:outline-none">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Reporting Coach (Employee ID)</label>
                                <input type="number" wire:model.defer="educationData.reporting_coach_id"
                                       class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Location</label>
                                <input type="number" wire:model.defer="educationData.location_id"
                                       class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Date of Exit</label>
                                <input type="date" wire:model.defer="educationData.doe"
                                       class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 focus:outline-none">
                            </div>
                        </div>
                        <div class="flex justify-between pt-2">
                            <button type="button" wire:click="previousStep" class="px-4 py-2 text-gray-700 bg-white border rounded-md">Previous</button>
                            <div class="flex gap-2">
                                <button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded-md">Save</button>
                                <button type="button" wire:click="completeOnboarding" class="px-4 py-2 text-white bg-green-600 rounded-md">Finish</button>
                            </div>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>

