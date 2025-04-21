<div>
    <div class="flex justify-between">
        <div>
            <flux:heading size="lg" class="flex">
                <flux:icon.clock />
                Attendance Policy ({{$this->employee->fname}} {{$this->employee->lname}} | {{$this->employee->email}})
            </flux:heading>
            <flux:subheading>
                Configure employee attendance policy details.
            </flux:subheading>
        </div>
        <div>
            <flux:modal.trigger name="mdl-policy">
                <flux:button variant="primary" icon="plus" wire:click="resetForm()"
                             class="bg-blue-500 text-white mt-2 rounded-md ml-4">
                    New
                </flux:button>
            </flux:modal.trigger>
        </div>
    </div>
    <flux:modal name="mdl-policy" @close="resetForm" position="right" class="max-w-none">
        <form wire:submit.prevent="savePolicy">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg" class="flex">
                        <flux:icon.clock />
                        @if($isEditing) Edit Policy @else New Policy @endif ({{$this->employee->fname}} {{$this->employee->lname}} | {{$this->employee->email}})
                    </flux:heading>
                    <flux:subheading>
                        Configure attendance policy details.
                    </flux:subheading>
                </div>
                <flux:separator/>
                
                <!-- First Row -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <flux:select label="Camshot" wire:model="policyData.camshot">
                        <option value="">Select option</option>
                        @foreach($this->listsForFields['camshot'] as $value => $label)
                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select label="Geo Location" wire:model="policyData.geo">
                        <option value="">Select option</option>
                        @foreach($this->listsForFields['geo'] as $value => $label)
                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select label="Manual Marking" wire:model="policyData.manual_marking">
                        <option value="">Select option</option>
                        @foreach($this->listsForFields['manual_marking'] as $value => $label)
                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <!-- Second Row -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <flux:input label="Geo Validation" wire:model="policyData.geo_validation" placeholder="Enter geo validation" />
                    <flux:input label="IP Validation" wire:model="policyData.ip_validation" placeholder="Enter IP validation" />
                    <flux:input type="number" label="Back Date Max Minutes" wire:model="policyData.back_date_max_minutes" placeholder="Enter minutes" />
                </div>

                <!-- Third Row -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <flux:input type="number" label="Max Punches/Day" wire:model="policyData.max_punches_a_day" placeholder="Enter max punches" />
                    <flux:input label="Grace Period (minutes)" wire:model="policyData.grace_period_minutes" placeholder="Enter grace period" />
                    <flux:input label="Mark Absent Rule" wire:model="policyData.mark_absent_rule" placeholder="Enter absent rule" />
                </div>

                <!-- Fourth Row -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <flux:input label="Overtime Rule" wire:model="policyData.overtime_rule" placeholder="Enter overtime rule" />
                    <flux:input type="date" label="Valid From" wire:model="policyData.valid_from" />
                    <flux:input type="date" label="Valid To" wire:model="policyData.valid_to" />
                </div>

                <!-- Fifth Row -->
                <div class="grid grid-cols-1 gap-4">
                    <flux:textarea label="Custom Rules" wire:model="policyData.custom_rules" placeholder="Enter custom rules" />
                    <flux:textarea label="Policy Text" wire:model="policyData.policy_text" placeholder="Enter policy text" />
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
            <flux:table.column>Camshot</flux:table.column>
            <flux:table.column>Geo Location</flux:table.column>
            <flux:table.column>Manual Marking</flux:table.column>
            <flux:table.column>Valid From</flux:table.column>
            <flux:table.column>Valid To</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->policiesList as $policy)
                <flux:table.row :key="$policy->id" class="border-b">
                    <flux:table.cell>
                        <flux:badge size="sm" color="blue" inset="top bottom">
                            {{ $policy->camshot_label }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" color="green" inset="top bottom">
                            {{ $policy->geo_label }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" color="purple" inset="top bottom">
                            {{ $policy->manual_marking_label }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $policy->valid_from ? date('d M Y', strtotime($policy->valid_from)) : 'N/A' }}</flux:table.cell>
                    <flux:table.cell>{{ $policy->valid_to ? date('d M Y', strtotime($policy->valid_to)) : 'N/A' }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-center space-x-2">
                            <flux:button variant="primary" size="sm" icon="pencil"
                                wire:click="fetchPolicy({{ $policy->id }})"></flux:button>
                            <flux:modal.trigger name="delete-policy-{{ $policy->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>

                        <flux:modal name="delete-policy-{{ $policy->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete policy?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this attendance policy.</p>
                                        <p>This action cannot be reversed.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                        wire:click="deletePolicy({{ $policy->id }})"></flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div> 