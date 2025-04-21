<div>
    <div class="flex justify-between mt-2">
        <div>
            <flux:heading size="lg" class="flex">
                <flux:icon.cog />
                Work Shift Day Statuses
            </flux:heading>
            <flux:subheading>
                Configure work shift day statuses and rules.
            </flux:subheading>
        </div>
        <div>
            <flux:modal.trigger name="mdl-day-status">
                <flux:button icon="plus" variant="primary" wire:click="resetForm()"
                             class="bg-blue-500 text-white mt-2 rounded-md">
                    New
                </flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    <flux:modal name="mdl-day-status" @close="resetForm" position="right" class="max-w-none">
        <form wire:submit.prevent="saveStatus">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg" class="flex">
                        <flux:icon.cog />
                        @if($isEditing) Edit Work Shift Day Status @else New Work Shift Day Status @endif
                    </flux:heading>
                    <flux:subheading>
                        Configure work shift day status settings.
                    </flux:subheading>
                </div>
                <flux:separator/>

                <!-- Basic Information -->

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:input label="Status Code" wire:model="statusData.day_status_code"
                                placeholder="e.g. WFH, LEAVE, etc." />
                    <flux:input label="Status Label" wire:model="statusData.day_status_label" 
                        placeholder="e.g. Work From Home" />
                    <flux:input type="number" label="Paid Percent" wire:model="statusData.paid_percent" 
                        min="0" max="100" step="0.01" />
                </div>

                <!-- Description -->
                <div class="grid grid-cols-1 gap-4">
                    <flux:textarea label="Description" wire:model="statusData.day_status_desc" 
                        placeholder="Enter status description" />
                </div>

                <!-- Checkboxes -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:checkbox wire:model="statusData.count_as_working_day" label="Count as Working Day" />
                    <flux:checkbox wire:model="statusData.is_inactive" label="Is Inactive" />
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
            <flux:table.column sortable :sorted="$sortBy === 'work_shift_id'" :direction="$sortDirection"
                wire:click="sort('work_shift_id')">Work Shift</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'day_status_code'" :direction="$sortDirection"
                wire:click="sort('day_status_code')">Status Code</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'day_status_label'" :direction="$sortDirection"
                wire:click="sort('day_status_label')">Status Label</flux:table.column>
            <flux:table.column>Description</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'paid_percent'" :direction="$sortDirection"
                wire:click="sort('paid_percent')">Paid %</flux:table.column>
            <flux:table.column>Working Day</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->statusList as $status)
                <flux:table.row :key="$status->id" class="border-b">
                    <flux:table.cell>
                        <flux:badge size="sm" color="blue" inset="top bottom">
                            {{ $status->work_shift->shift_title }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $status->day_status_code }}</flux:table.cell>
                    <flux:table.cell>{{ $status->day_status_label }}</flux:table.cell>
                    <flux:table.cell>
                        <span class="text-sm text-gray-600">
                            {{ $status->day_status_desc ?? 'N/A' }}
                        </span>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" color="{{ $status->paid_percent == 100 ? 'green' : 'yellow' }}" inset="top bottom">
                            {{ $status->paid_percent }}%
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($status->count_as_working_day)
                            <flux:badge size="sm" color="green" inset="top bottom">Yes</flux:badge>
                        @else
                            <flux:badge size="sm" color="red" inset="top bottom">No</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:switch
                            wire:model="statusData.is_inactive"
                            wire:click="toggleStatus({{ $status->id }})"
                            :checked="!$status->is_inactive"
                        />
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-center space-x-2">
                            <flux:button variant="primary" size="sm" icon="pencil"
                                wire:click="fetchStatus({{ $status->id }})"></flux:button>
                            <flux:modal.trigger name="delete-status-{{ $status->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>

                        <flux:modal name="delete-status-{{ $status->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete day status?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this work shift day status.</p>
                                        <p>This action cannot be reversed.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                        wire:click="deleteStatus({{ $status->id }})"></flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div> 