<div>
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-joblocation" class="flex justify-end">
            <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">
              New
            </flux:button>
        </flux:modal.trigger>
    </div>
    <flux:separator class="mt-2 mb-2" />
    <!-- Heading End -->

    <!-- Filters Start -->
    <div class="flex flex-wrap gap-4 mb-4">
        <flux:input
            label="Search by Name"
            wire:model.live="filters.search_name"
            placeholder="Search by name..."
            class="w-48"
        />
        <flux:input
            label="Search by Code"
            wire:model.live="filters.search_code"
            placeholder="Search by code..."
            class="w-48"
        />
        
        <div class="relative w-48">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 py-0.5 mb-1">Filter by Country</label>
            <select 
                wire:model.live="filters.search_country"
                class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm px-2 py-2"
            >
                <option value="">Select Country</option>
                @foreach($listsForFields['countries'] ?? [] as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="relative w-48">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1 py-0.5">Filter by State</label>
            <select
                wire:model.live="filters.search_state" 
                class="block w-full px-2 py-2 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
            >
                <option value="">Select State</option>
                @foreach($listsForFields['states'] ?? [] as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="relative w-48">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 py-0.5 mb-1">Filter by District</label>
            <select
                wire:model.live="filters.search_district" 
                class="block w-full rounded-md border-gray-300 px-2 py-2 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
            >
                <option value="">Select District</option>
                @foreach($listsForFields['districts'] ?? [] as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end">
            <flux:button variant="filled" class="px-2" tooltip="Cancel Filter" icon="x-circle"
                         wire:click="clearFilters"></flux:button>
        </div>
    </div>
    <!-- Filters End -->

    <!-- Modal Start -->
    <div x-data="{ open: false }" x-show="open" @modal-mdl-joblocation.window="open = true" @close-mdl-joblocation.window="open = false" @cancel="resetForm" class="fixed inset-0 z-50 overflow-y-auto">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>

        <!-- Modal Container -->
        <div class="fixed inset-y-0 right-0 flex max-w-full">
            <div class="relative w-96">
                <!-- Modal Content -->
                <div class="h-full transform bg-white dark:bg-gray-900 shadow-xl transition-all overflow-y-auto">
                    <form wire:submit.prevent="store" class="p-6">
                        <div class="space-y-6">
                            <!-- Header -->
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                                    @if($isEditing) Edit Job Location @else Add Job Location @endif
                                </h2>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    @if($isEditing) Update @else Add new @endif job location details.
                                </p>
                            </div>

                            <!-- Form Fields -->
                            <div class="grid grid-cols-1 gap-4">
                                <!-- Name -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Name</label>
                                    <input type="text" wire:model="formData.name" placeholder="Job Location Name"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm px-3 py-2 @error('formData.name') border-red-500 @enderror">
                                    @error('formData.name')
                                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Code -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Code</label>
                                    <input type="text" wire:model="formData.code" placeholder="Job Location Code"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm px-3 py-2 @error('formData.code') border-red-500 @enderror">
                                    @error('formData.code')
                                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Description -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Description</label>
                                    <textarea wire:model="formData.description" placeholder="Description"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm px-3 py-2 @error('formData.description') border-red-500 @enderror"></textarea>
                                    @error('formData.description')
                                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Parent Location -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Parent Location</label>
                                    <select wire:model="formData.parent_joblocation_id"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm px-3 py-2">
                                        <option value="">Select Parent Location</option>
                                        @foreach($listsForFields['joblocations'] ?? [] as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Country -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Country</label>
                                    <select wire:model="formData.country_id" wire:change="triggerUpdate('countrychanged')"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm px-3 py-2">
                                        <option value="">Select Country</option>
                                        @foreach($listsForFields['countries'] ?? [] as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- State -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">State</label>
                                    <select wire:model="formData.state_id" wire:change="triggerUpdate('statechanged')"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm px-3 py-2">
                                        <option value="">Select State</option>
                                        @foreach($listsForFields['states'] ?? [] as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- District -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">District</label>
                                    <select wire:model="formData.district_id" wire:change="triggerUpdate('districtchanged')"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm px-3 py-2">
                                        <option value="">Select District</option>
                                        @foreach($listsForFields['districts'] ?? [] as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Subdivision -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Subdivision</label>
                                    <select wire:model="formData.subdivision_id" wire:change="triggerUpdate('subdivisionchanged')"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm px-3 py-2">
                                        <option value="">Select Subdivision</option>
                                        @foreach($listsForFields['subdivisions'] ?? [] as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- City/Village -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">City/Village</label>
                                    <select wire:model="formData.city_or_village_id" wire:change="triggerUpdate('citychanged')"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm px-3 py-2">
                                        <option value="">Select City/Village</option>
                                        @foreach($listsForFields['cities'] ?? [] as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Post Office -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Post Office</label>
                                    <select wire:model="formData.postoffice_id"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm px-3 py-2">
                                        <option value="">Select Post Office</option>
                                        @foreach($listsForFields['postoffices'] ?? [] as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Is Inactive Switch -->
                                <div class="flex items-center">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" wire:model="formData.is_inactive" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                                        <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-200">Mark as Inactive</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end pt-4">
                                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                    Save
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal End -->

    <!-- Table Start-->
    <flux:table :paginate="$this->list" class="">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column>Name</flux:table.column>
            <flux:table.column>Code</flux:table.column>
            <flux:table.column>Description</flux:table.column>
            <flux:table.column>Parent Location</flux:table.column>
            <flux:table.column>Post Office</flux:table.column>
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
                    <flux:table.cell class="table-cell-wrap">{{ $rec->description }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->joblocation?->name ?? '-' }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->postoffice?->name ?? '-' }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->cities_or_village?->name ?? '-' }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->subdivision?->name ?? '-' }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->district?->name ?? '-' }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->state?->name ?? '-' }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->country?->name ?? '-' }}</flux:table.cell>
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
                                    <flux:heading size="lg">Delete Job Location?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this job location. This action cannot be undone.</p>
                                        <p class="mt-2 text-red-500">Note: Job locations with related records cannot be deleted.</p>
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
    <script>
        window.addEventListener('open-joblocation-modal', () => {
          // use Flux's JS helper to show the modal
          Flux.modal('mdl-joblocation').show();
        });
      </script> 
</div>

