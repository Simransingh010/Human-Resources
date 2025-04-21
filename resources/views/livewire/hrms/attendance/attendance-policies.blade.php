<div>
    <flux:modal.trigger name="mdl-policy">
        <flux:button variant="primary" class="bg-blue-500 text-white dark:text-primary px-4 py-2 mb-4 rounded-md">
            @if($isEditing)
                Edit Policy
            @else
                Add Policy
            @endif
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="mdl-policy" position="right" @close="resetForm" class="max-w-none min-w-[360px] bg-neutral-100">
        <form wire:submit.prevent="savePolicy">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Policy @else Add Policy @endif
                    </flux:heading>
                    <flux:subheading>
                        Configure attendance policy settings.
                    </flux:subheading>
                </div>

                <!-- Custom Select Elements - Full width -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <!-- Camera Shot Select -->
                    <div class="col-span-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                            Camera Shot
                        </label>
                        <select
                                wire:model="policyData.camshot"
                                class="mt-1 py-2 pl-3 pr-3 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                        >
                            <option value="">Select Option</option>
                            <option value="1">Allowed</option>
                            <option value="2">Required</option>
                            <option value="3">Denied</option>
                        </select>
                    </div>

                    <!-- Geo Location Select -->
                    <div class="col-span-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                            Geo Location
                        </label>
                        <select
                                wire:model="policyData.geo"
                                class="mt-1 py-2 pl-3 pr-3 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                        >
                            <option value="">Select Option</option>
                            <option value="1">Allowed</option>
                            <option value="2">Required</option>
                            <option value="3">Denied</option>
                        </select>
                    </div>

                    <!-- Manual Marking Select -->
                    <div class="col-span-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                            Manual Marking
                        </label>
                        <select
                                wire:model="policyData.manual_marking"
                                class="mt-1 py-2 pl-3 pr-3 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                        >
                            <option value="">Select Option</option>
                            <option value="1">Allowed</option>
                            <option value="2">Required</option>
                            <option value="3">Denied</option>
                        </select>
                    </div>
                </div>

                <!-- Number and Date Inputs -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:input
                            label="Back Date Max Minutes"
                            type="number"
                            wire:model="policyData.back_date_max_minutes"
                    />
                    <flux:input
                            label="Max Punches per Day"
                            type="number"
                            wire:model="policyData.max_punches_a_day"
                    />
                    <flux:input
                            label="Grace Period (minutes)"
                            wire:model="policyData.grace_period_minutes"
                    />
                </div>

                <!-- Date Inputs -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:input
                            type="date"
                            label="Valid From"
                            wire:model="policyData.valid_from"
                    />
                    <flux:input
                            type="date"
                            label="Valid To"
                            wire:model="policyData.valid_to"
                    />
                    <!-- Empty div to maintain grid alignment -->
                    <div>
                        <div class="flex justify-end pt-4">
                            <flux:button type="submit" variant="primary" class="mt-2">
                                Save Changes
                            </flux:button>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->

            </div>
        </form>
    </flux:modal>

    <!-- Table Section (unchanged) -->
    <flux:table :paginate="$this->policiesList" class="">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column class="">ID</flux:table.column>
            <flux:table.column class="" sortable :sorted="$sortBy === 'camshot'" :direction="$sortDirection"
                               wire:click="sort('camshot')">Camera Shot</flux:table.column>
            <flux:table.column class="" sortable :sorted="$sortBy === 'geo'" :direction="$sortDirection"
                               wire:click="sort('geo')">Geo Location</flux:table.column>
            <flux:table.column class="" sortable :sorted="$sortBy === 'manual_marking'" :direction="$sortDirection"
                               wire:click="sort('manual_marking')">Manual Marking</flux:table.column>
            <flux:table.column class="" sortable :sorted="$sortBy === 'back_date_max_minutes'" :direction="$sortDirection"
                               wire:click="sort('back_date_max_minutes')">Back Date Minutes</flux:table.column>
            <flux:table.column class="" sortable :sorted="$sortBy === 'max_punches_a_day'" :direction="$sortDirection"
                               wire:click="sort('max_punches_a_day')">Max Punches</flux:table.column>
            <flux:table.column class="" sortable :sorted="$sortBy === 'grace_period_minutes'" :direction="$sortDirection"
                               wire:click="sort('grace_period_minutes')">Grace Period</flux:table.column>
            <flux:table.column class="" sortable :sorted="$sortBy === 'valid_from'" :direction="$sortDirection"
                               wire:click="sort('valid_from')">Valid From</flux:table.column>
            <flux:table.column class="" sortable :sorted="$sortBy === 'valid_to'" :direction="$sortDirection"
                               wire:click="sort('valid_to')">Valid To</flux:table.column>
{{--            <flux:table.column class="" sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection"--}}
{{--                               wire:click="sort('created_at')">Created</flux:table.column>--}}
            <flux:table.column class="">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->policiesList as $policy)
                <flux:table.row :key="$policy->id" class="border-b">
                    <flux:table.cell class="">{{ $policy->id }}</flux:table.cell>
                    <flux:table.cell class="">
                        <flux:badge size="sm" :color="$policy->camshot == 2 ? 'green' : ($policy->camshot == 3 ? 'red' : 'blue')">
                            {{ $policy->camshot_label }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="">
                        <flux:badge size="sm" :color="$policy->geo == 2 ? 'green' : ($policy->geo == 3 ? 'red' : 'blue')">
                            {{ $policy->geo_label }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="">
                        <flux:badge size="sm" :color="$policy->manual_marking == 2 ? 'green' : ($policy->manual_marking == 3 ? 'red' : 'blue')">
                            {{ $policy->manual_marking_label }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="">{{ $policy->back_date_max_minutes ?? '-' }}</flux:table.cell>
                    <flux:table.cell class="">{{ $policy->max_punches_a_day ?? '-' }}</flux:table.cell>
                    <flux:table.cell class="">{{ $policy->grace_period_minutes ?? '-' }}</flux:table.cell>
                    <flux:table.cell class="">
                        {{ $policy->valid_from ? \Carbon\Carbon::parse($policy->valid_from)->format('Y-m-d') : '-' }}
                    </flux:table.cell>
                    <flux:table.cell class="">
                        {{ $policy->valid_to ? \Carbon\Carbon::parse($policy->valid_to)->format('Y-m-d') : '-' }}
                    </flux:table.cell>
{{--                    <flux:table.cell class="">{{ $policy->created_at->format('Y-m-d H:i') }}</flux:table.cell>--}}
                    <flux:table.cell class="">
                        <div class="flex justify-center space-x-2">
                            <flux:button variant="primary" size="sm" icon="pencil"
                                         wire:click="fetchPolicy({{ $policy->id }})"></flux:button>
                            <flux:modal.trigger name="delete-profile-{{ $policy->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>
                        <flux:modal name="delete-profile-{{ $policy->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete project?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this project.</p>
                                        <p>This action cannot be reversed.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash" wire:click="deletePolicy({{ $policy->id }})"></flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>