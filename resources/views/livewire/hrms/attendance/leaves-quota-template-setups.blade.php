<div>
    <flux:modal.trigger name="mdl-quota-setup">
        <flux:button variant="primary" class="bg-blue-500 text-white dark:text-primary px-4 py-2 mb-4 rounded-md">
            @if($isEditing)
                Edit Quota Setup
            @else
                Add Quota Setup
            @endif
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="mdl-quota-setup" position="right" @close="resetForm"
        class="max-w-none min-w-[360px] bg-neutral-100">
        <form wire:submit.prevent="saveSetup">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Quota Setup @else Add Quota Setup @endif
                    </flux:heading>
                    <flux:subheading>
                        Configure leave quota template setup details.
                    </flux:subheading>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    @if(!$templateId)
                        <flux:select label="Quota Template" wire:model="setupData.leaves_quota_template_id" required>
                            <option value="">Select Template</option>
                            @foreach($this->templatesList as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </flux:select>
                    @endif

                    <flux:select label="Leave Type" wire:model="setupData.leave_type_id" required>
                        <option value="">Select Leave Type</option>
                        @foreach($this->leaveTypesList as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>

                    <flux:input type="number" label="Days Assigned" wire:model="setupData.days_assigned" min="0"
                        required />

                    <flux:select label="Allocation Period Unit" wire:model="setupData.alloc_period_unit" required>
                        <option value="">Select Period Unit</option>
                        <option value="days">Days</option>
                        <option value="months">Months</option>
                        <option value="years">Years</option>
                    </flux:select>

                    <flux:input type="number" label="Period Value" wire:model="setupData.alloc_period_value" min="1"
                        required />
                </div>

                <div class="flex justify-end pt-4">
                    <flux:button type="submit" variant="primary">
                        Save Changes
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:table :paginate="$this->setupsList" class="w-full">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column class="w-12">ID</flux:table.column>
            <flux:table.column>Template</flux:table.column>
            <flux:table.column>Leave Type</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'days_assigned'" :direction="$sortDirection"
                wire:click="sort('days_assigned')">Days Assigned</flux:table.column>
            <flux:table.column>Allocation Period</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->setupsList as $setup)
                <flux:table.row :key="$setup->id" class="border-b">
                    <flux:table.cell>{{ $setup->id }}</flux:table.cell>
                    <flux:table.cell>{{ $setup->leaves_quota_template->name }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" color="blue" inset="top bottom">
                            {{ $setup->leave_type->leave_title }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $setup->days_assigned }}</flux:table.cell>
                    <flux:table.cell>
                        {{ $setup->alloc_period_value }} {{ ucfirst($setup->alloc_period_unit) }}
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-center space-x-2">
                            <flux:button variant="outline" size="sm" icon="pencil"
                                wire:click="fetchSetup({{ $setup->id }})">
                            </flux:button>
                            <flux:modal.trigger name="delete-setup-{{ $setup->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>

                        <flux:modal name="delete-setup-{{ $setup->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete Setup?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this quota template setup.</p>
                                        <p>This action cannot be reversed.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                        wire:click="deleteSetup({{ $setup->id }})">
                                    </flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>