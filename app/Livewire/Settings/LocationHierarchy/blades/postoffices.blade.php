<div>
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-postoffice" class="flex justify-end">
            <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">
              New
            </flux:button>
        </flux:modal.trigger>
    </div>
    <flux:separator class="mt-2 mb-2" />
    <!-- Heading End -->

    <!-- Filters Start -->
    <div class="grid lg:grid-cols-4 gap-4 mb-4">
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
        <flux:input
            label="Search by Pincode"
            wire:model.live="filters.search_pincode"
            placeholder="Search by pincode..."
        />
        <div class="relative w-48">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 py-0.5 mb-1">Filter by Country</label>
            <select
                wire:model.live="filters.search_country"
                wire:change="triggerFilterUpdate('countrychanged')"
                class="block w-full rounded-md border-gray-300 px-2 py-2 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
            >
                <option value="">Select Country</option>
                @foreach($filterLists['countrieslist'] ?? [] as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="relative w-48">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 py-0.5 mb-1">Filter by State</label>
            <select
                wire:model.live="filters.search_state"
                wire:change="triggerFilterUpdate('statechanged')"
                class="block w-full rounded-md border-gray-300 px-2 py-2 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
            >
                <option value="">Select State</option>
                @foreach($filterLists['states'] ?? [] as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="relative w-48">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 py-0.5 mb-1">Filter by District</label>
            <select
                wire:model.live="filters.search_district"
                wire:change="triggerFilterUpdate('districtchanged')"
                class="block w-full rounded-md border-gray-300 px-2 py-2 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
            >
                <option value="">Select District</option>
                @foreach($filterLists['districts'] ?? [] as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="relative w-48">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 py-0.5 mb-1">Filter by Subdivision</label>
            <select
                wire:model.live="filters.search_subdivision"
                wire:change="triggerFilterUpdate('subdivisionchanged')"
                class="block w-full rounded-md border-gray-300 px-2 py-2 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
            >
                <option value="">Select Subdivision</option>
                @foreach($filterLists['subdivisions'] ?? [] as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="relative w-48">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 py-0.5 mb-1">Filter by City/Village</label>
            <select
                wire:model.live="filters.search_city"
                class="block w-full rounded-md border-gray-300 px-2 py-2 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
            >
                <option value="">Select City/Village</option>
                @foreach($filterLists['cities'] ?? [] as $id => $name)
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
    <flux:modal name="mdl-postoffice" @cancel="resetForm" class="max-w-none">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Post Office @else Add Post Office @endif
                    </flux:heading>
                    <flux:subheading>
                        @if($isEditing) Update @else Add new @endif post office details.
                    </flux:subheading>
                </div>

                <!-- Grid layout for form fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select label="Select Country"
                                 wire:model.live.debounce.0ms="formData.country_id"
                                 wire:change="triggerUpdate('countrychanged')">
                        <option value="">Select Country</option>
                        @foreach(($isEditing ? $editFormLists['countrieslist'] : $createFormLists['countrieslist']) ?? [] as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select label="Select State"
                                 wire:model.live.debounce.0ms="formData.state_id"
                                 wire:change="triggerUpdate('statechanged')"
                                 searchable>
                        <option value="">Select State</option>
                        @foreach(($isEditing ? $editFormLists['states'] : $createFormLists['states']) ?? [] as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select label="Select District"
                                 wire:model.live.debounce.0ms="formData.district_id"
                                 wire:change="triggerUpdate('districtchanged')"
                                 searchable>
                        <option value="">Select District</option>
                        @foreach(($isEditing ? $editFormLists['districts'] : $createFormLists['districts']) ?? [] as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select label="Select Subdivision"
                                 wire:model.live.debounce.0ms="formData.subdivision_id"
                                 wire:change="triggerUpdate('subdivisionchanged')"
                                 searchable>
                        <option value="">Select Subdivision</option>
                        @foreach(($isEditing ? $editFormLists['subdivisions'] : $createFormLists['subdivisions']) ?? [] as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select label="Select City/Village"
                                 wire:model.live.debounce.0ms="formData.city_or_village_id"
                                 searchable>
                        <option value="">Select City/Village</option>
                        @foreach(($isEditing ? $editFormLists['cities'] : $createFormLists['cities']) ?? [] as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:input label="Name" wire:model.live="formData.name" placeholder="Post Office Name"/>
                    <flux:input label="Code" wire:model.live="formData.code" placeholder="Post Office Code"/>
                    <flux:input label="Pincode" wire:model.live="formData.pincode" placeholder="Post Office Pincode"/>
                    <flux:switch wire:model.live="formData.is_inactive" label="Mark as Inactive"/>
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
            <flux:table.column>Pincode</flux:table.column>
            <flux:table.column>City/Village</flux:table.column>
            <flux:table.column>Subdivision</flux:table.column>
            <flux:table.column>District</flux:table.column>
            <flux:table.column>State</flux:table.column>
            <flux:table.column>Country</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->list as $rec)
                <flux:table.row :key="$rec->id" class="border-b">
                    <flux:table.cell class="table-cell-wrap">{{ $rec->name }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->code }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->pincode }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->cities_or_village->name }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->cities_or_village->subdivision?->name ?? '-' }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->cities_or_village->district->name }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->cities_or_village->district->state->name }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->cities_or_village->district->state->country->name }}</flux:table.cell>
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
                                    <flux:heading size="lg">Delete Post Office?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this post office. This action cannot be undone.</p>
                                        <p class="mt-2 text-red-500">Note: Post offices with related records cannot be deleted.</p>
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