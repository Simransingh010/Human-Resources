<div>
    <flux:modal.trigger name="mdl-personal">
        <flux:button variant="primary" class="bg-blue-500 text-white px-4 py-2 mb-4 rounded-md">
            Add Personal Details
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="mdl-personal" @close="resetForm" position="right" class="max-w-none">
        <form wire:submit.prevent="savePersonalDetail">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Personal Details @else Add Personal Details @endif
                    </flux:heading>
                    <flux:subheading>
                        Make changes to the employee's personal details.
                    </flux:subheading>
                </div>

                <!-- Grid layout for form fields -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:select label="Select Employee" wire:model="personalData.employee_id"
                        placeholder="Choose an employee">
                        <option value="">Select an employee</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee['id'] }}">
                                {{ $employee['name'] }}
                            </option>
                        @endforeach
                    </flux:select>

                    <flux:input type="date" label="Date of Birth" wire:model="personalData.dob" />

                    <flux:select label="Marital Status" wire:model="personalData.marital_status"
                        placeholder="Select marital status">
                        <option value="">Select status</option>
                        <option value="single">Single</option>
                        <option value="married">Married</option>
                        <option value="divorced">Divorced</option>
                        <option value="widowed">Widowed</option>
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:input type="date" label="Date of Anniversary" wire:model="personalData.doa" />
                    <flux:input label="Nationality" wire:model="personalData.nationality"
                        placeholder="Enter nationality" />
                    <flux:input label="Father's Name" wire:model="personalData.fathername"
                        placeholder="Enter father's name" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:input label="Mother's Name" wire:model="personalData.mothername"
                        placeholder="Enter mother's name" />
                    <flux:input label="Aadhar Number" wire:model="personalData.adharno"
                        placeholder="Enter 12-digit Aadhar number" maxlength="12" />
                    <flux:input label="PAN Number" wire:model="personalData.panno"
                        placeholder="Enter 10-digit PAN number" maxlength="10" />
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

    <flux:table :paginate="$this->personalDetailsList" class="w-full">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column class="w-[250px]">Employee</flux:table.column>
            <flux:table.column class="w-[120px]" sortable :sorted="$sortBy === 'dob'" :direction="$sortDirection"
                wire:click="sort('dob')">Date of Birth</flux:table.column>
            <flux:table.column class="w-[120px]">Marital Status</flux:table.column>
            <flux:table.column class="w-[200px]">Parents</flux:table.column>
            <flux:table.column class="w-[200px]">ID Numbers</flux:table.column>
            <flux:table.column class="w-[120px]">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->personalDetailsList as $detail)
                <flux:table.row :key="$detail->id" class="border-b">
                    <flux:table.cell class="w-[250px] flex items-center gap-3">
                        {{ $detail->employee->fname . ' ' . $detail->employee->lname }}
                    </flux:table.cell>
                    <flux:table.cell class="w-[120px]">{{ $detail->dob ? $detail->dob->format('d M Y') : 'N/A' }}
                    </flux:table.cell>
                    <flux:table.cell class="w-[120px]">
                        <flux:badge size="sm" color="blue" inset="top bottom">
                            {{ ucfirst($detail->marital_status) }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="w-[200px]">
                        <div class="text-sm">
                            <div>Father: {{ $detail->fathername }}</div>
                            <div>Mother: {{ $detail->mothername }}</div>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="w-[200px]">
                        <div class="text-sm">
                            <div>Aadhar: {{ substr_replace($detail->adharno, 'XXXX', 4, 4) }}</div>
                            <div>PAN: {{ substr_replace($detail->panno, 'XXXX', 3, 4) }}</div>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="w-[120px]">
                        <div class="flex justify-center space-x-2">
                            <flux:button variant="primary" size="sm" icon="pencil"
                                wire:click="fetchPersonalDetail({{ $detail->id }})">
                            </flux:button>
                            <flux:modal.trigger name="delete-personal-{{ $detail->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>
                        <flux:modal name="delete-personal-{{ $detail->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete personal details?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this employee's personal details.</p>
                                        <p>This action cannot be reversed.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                        wire:click="deletePersonalDetail({{ $detail->id }})"></flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>