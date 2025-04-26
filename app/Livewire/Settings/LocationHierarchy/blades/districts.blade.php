<div>
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-district" class="flex justify-end">
            <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">
              New
            </flux:button>
        </flux:modal.trigger>
    </div>
    <flux:separator class="mt-2 mb-2" />
    <!-- Heading End -->

    <!-- Filters Start -->
    <div class="flex flex-row gap-4 mb-4">
        <flux:input
            label="Search by Name"
            wire:model.live="filters.search_name"
            placeholder="Search by name..."
        />
        <flux:input
            label="Search by Code"
            wire:model.live="filters.search_code"
            placeholder="Search by code..."
        />
        <div class="relative w-48">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 py-0.5 mb-1">
                Filter by Country
            </label>
            <select
                wire:model.live="filters.search_country"
                class="block w-full rounded-md border-gray-300 px-2 py-2 dark:border-gray-700 dark:bg-gray-800 shadow-sm
                       focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
            >
                <option value="">Select Country</option>
                @foreach($listsForFields['countries'] ?? [] as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="relative w-48">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 py-0.5 mb-1">
                Filter by State
            </label>
            <select
                wire:model.live="filters.search_state"
                class="block w-full rounded-md border-gray-300 px-2 py-2 dark:border-gray-700 dark:bg-gray-800 shadow-sm
                       focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
            >
                <option value="">Select State</option>
                @foreach($listsForFields['states'] ?? [] as $id => $name)
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
    <flux:modal name="mdl-district" @cancel="resetForm" position="right" class="max-w-none" variant="default">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit District @else Add District @endif
                    </flux:heading>
                    <flux:subheading>
                        @if($isEditing) Update @else Add new @endif district details.
                    </flux:subheading>
                </div>

                <!-- Grid layout for form fields -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="relative w-full">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 py-0.5 mb-1">Country</label>
                        <select
                            wire:model="formData.country_id"
                            wire:change="triggerUpdate('countrychanged')"
                            class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm px-2 py-2 @error('formData.country_id') border-red-500 @enderror"
                        >
                            <option value="">Select Country</option>
                            @foreach($listsForFields['countries'] ?? [] as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('formData.country_id')
                            <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="relative w-full">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 py-0.5 mb-1">State</label>
                        <select
                            wire:model="formData.state_id"
                            class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm px-2 py-2 @error('formData.state_id') border-red-500 @enderror"
                        >
                            <option value="">Select State</option>
                            @foreach($listsForFields['states'] ?? [] as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('formData.state_id')
                            <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Name</label>
                        <input
                            type="text"
                            wire:model="formData.name"
                            placeholder="District Name"
                            class="block w-full rounded-md border-gray-300 px-2 py-2 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm @error('formData.name') border-red-500 @enderror"
                        >
                        @error('formData.name')
                            <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Code</label>
                        <input
                            type="text"
                            wire:model="formData.code"
                            placeholder="District Code"
                            class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-2 py-2 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm @error('formData.code') border-red-500 @enderror"
                        >
                        @error('formData.code')
                            <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="flex items-center col-span-2">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model.live="formData.is_inactive" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                            <flux:switch wire:model.live="formData.is_inactive" label="Mark as Inactive"/>
                        </label>
                        @error('formData.is_inactive')
                            <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                        @enderror
                    </div>
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
            <flux:table.column>Name</flux:table.column>
            <flux:table.column>Code</flux:table.column>
            <flux:table.column>State</flux:table.column>
{{--            <flux:table.column>Country</flux:table.column>--}}
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->list as $rec)
                <flux:table.row :key="$rec->id" class="border-b">
                    <flux:table.cell class="table-cell-wrap">{{ $rec->name }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->code }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->state->name }}</flux:table.cell>
{{--                    <flux:table.cell class="table-cell-wrap">{{ $rec->state->country->name }}</flux:table.cell>--}}
                    <flux:table.cell>
                        <flux:switch
                            wire:model="statuses.{{ $rec->id }}"
                            wire:click="toggleStatus({{ $rec->id }})"
                        />
                    </flux:table.cell>
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
                                    <flux:heading size="lg">Delete District?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this district. This action cannot be undone.</p>
                                        <p class="mt-2 text-red-500">Note: Districts with related records cannot be deleted.</p>
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