<div xmlns:flux="http://www.w3.org/1999/html">
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-quota-setup" class="flex justify-end">
            <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">
                New
            </flux:button>
        </flux:modal.trigger>
    </div>
    <flux:separator class="mt-2 mb-2" />
    <!-- Heading End -->

    <!-- Filters Start -->
    <div class="grid lg:grid-cols-4 gap-4 mb-4">
        <div class="relative w-48">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 py-0.5 mb-1">Filter by Template</label>
            <select
                wire:model.live="filters.search_template"
                class="block w-full rounded-md border-gray-300 px-2 py-2 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
            >
                <option value="">All Templates</option>
                @foreach($this->templatesList as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="relative w-48">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 py-0.5 mb-1">Filter by Leave Type</label>
            <select
                wire:model.live="filters.search_leave_type"
                class="block w-full rounded-md border-gray-300 px-2 py-2 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
            >
                <option value="">All Leave Types</option>
                @foreach($this->leaveTypesList as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[100px] flex justify-end">
            <flux:button variant="filled" class="px-2 mt-6" tooltip="Cancel Filter" icon="x-circle"
                         wire:click="clearFilters"></flux:button>
        </div>
    </div>
    <!-- Filters End -->

    <!-- Modal Start -->
    <flux:modal name="mdl-quota-setup" @cancel="resetForm" class="max-w-none">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Quota Setup @else Add Quota Setup @endif
                    </flux:heading>
                    <flux:subheading>
                        @if($isEditing) Update @else Add new @endif leave quota template setup details.
                    </flux:subheading>
                </div>

                <!-- Grid layout for form fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select label="Template" wire:model.live="formData.leaves_quota_template_id" required>
                        <option value="">Select Template</option>
                        @foreach($this->templatesList as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select label="Leave Type" wire:model.live="formData.leave_type_id" required>
                        <option value="">Select Leave Type</option>
                        @foreach($this->leaveTypesList as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:input label="Days Assigned" type="number" wire:model.live="formData.days_assigned" placeholder="Enter days assigned" required/>
                    <flux:input label="Allocation Period Unit" type="number" wire:model.live="formData.alloc_period_unit" placeholder="Enter allocation period unit" required/>
                    <flux:input label="Allocation Period Value" type="number" wire:model.live="formData.alloc_period_value" placeholder="Enter period value" required/>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end pt-4">
                    <flux:button type="submit" variant="primary">
                        Save
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
    <!-- Modal End -->

    <!-- Table Start-->
    <flux:table :paginate="$this->list" class="">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column>Template</flux:table.column>
            <flux:table.column>Leave Type</flux:table.column>
            <flux:table.column>Days Assigned</flux:table.column>
            <flux:table.column>Allocation Period Unit</flux:table.column>
            <flux:table.column>Allocation Period Value</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->list as $rec)
                <flux:table.row :key="$rec->id" class="border-b">
                    <flux:table.cell>{{ $rec->leaves_quota_template->name }}</flux:table.cell>
                    <flux:table.cell>{{ $rec->leave_type->leave_title }}</flux:table.cell>
                    <flux:table.cell>{{ $rec->days_assigned }}</flux:table.cell>
                    <flux:table.cell>{{ $rec->alloc_period_unit }}</flux:table.cell>
                    <flux:table.cell>{{ $rec->alloc_period_value }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex space-x-2">
                            <flux:button
                                variant="primary"
                                size="sm"
                                icon="pencil"
                                wire:click="edit({{ $rec->id }})"
                            />
                            <flux:modal.trigger name="delete-{{ $rec->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"/>
                            </flux:modal.trigger>
                        </div>

                        <!-- Delete Modal -->
                        <flux:modal name="delete-{{ $rec->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete Setup?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this quota setup. This action cannot be undone.</p>
                                        <p class="mt-2 text-red-500">Note: Setups with related records cannot be deleted.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer/>
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                        wire:click="delete({{ $rec->id }})"/>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
    <!-- Table End-->
</div> 