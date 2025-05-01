<div>
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-component" class="flex justify-end">
            <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">
                New
            </flux:button>
        </flux:modal.trigger>
    </div>
    <flux:separator class="mt-2 mb-2" />
    <!-- Heading End -->

    <!-- Filters Start -->
    <div class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
            <div class="min-w-[100px] flex justify-end">
                <flux:button variant="filled" class=" px-2 mt-6" tooltip="Cancel Filter" icon="x-circle"
                             wire:click="clearFilters()"></flux:button>
            </div>
        </div>

    </div>
    <!-- Filters End -->

    <!-- Modal Start -->
    <flux:modal name="mdl-component" @cancel="resetForm" position="right" class="max-w-none">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Component @else Add Component @endif
                    </flux:heading>
                    <flux:subheading>
                        @if($isEditing) Update @else Add new @endif component details.
                    </flux:subheading>
                </div>

                <!-- Error Message Section -->
                @if ($errors->any())
                    <div class="rounded-md bg-red-50 p-4 mb-4">
                        <div class="flex">
                        {{-- <div class="flex-shrink-0">
                            <x-heroicon-s-x-circle class="h-5 w-5 text-red-400"/>
                        </div> --}}
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">
                                    There were {{ $errors->count() }} errors with your submission
                                </h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <ul role="list" class="list-disc space-y-1 pl-5">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Grid layout for form fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="@error('formData.name') error @enderror">
                        <flux:input 
                            label="Name" 
                            wire:model="formData.name" 
                            placeholder="Component Name"
                            error="{{ $errors->first('formData.name') }}"
                        />
                    </div>

                    <div class="@error('formData.code') error @enderror">
                        <flux:input 
                            label="Code" 
                            wire:model="formData.code" 
                            placeholder="Component Code"
                            error="{{ $errors->first('formData.code') }}"
                        />
                    </div>

                    <div class="@error('formData.wire') error @enderror">
                        <flux:input 
                            label="Wire" 
                            wire:model="formData.wire" 
                            placeholder="Wire Path"
                            error="{{ $errors->first('formData.wire') }}"
                        />
                    </div>

                    <div class="@error('formData.icon') error @enderror">
                        <flux:input 
                            label="Icon" 
                            wire:model="formData.icon" 
                            placeholder="Icon Class"
                            error="{{ $errors->first('formData.icon') }}"
                        />
                    </div>

                    <div class="@error('formData.color') error @enderror">
                        <flux:input 
                            label="Color" 
                            wire:model="formData.color" 
                            type="color"
                            error="{{ $errors->first('formData.color') }}"
                        />
                    </div>

                    <div class="@error('formData.tooltip') error @enderror">
                        <flux:input 
                            label="Tooltip" 
                            wire:model="formData.tooltip" 
                            placeholder="Tooltip Text"
                            error="{{ $errors->first('formData.tooltip') }}"
                        />
                    </div>

                    <div class="@error('formData.order') error @enderror">
                        <flux:input 
                            label="Order" 
                            wire:model="formData.order" 
                            type="number"
                            error="{{ $errors->first('formData.order') }}"
                        />
                    </div>

                    <div class="@error('formData.badge') error @enderror">
                        <flux:input 
                            label="Badge" 
                            wire:model="formData.badge" 
                            placeholder="Badge Text"
                            error="{{ $errors->first('formData.badge') }}"
                        />
                    </div>

                    <div class="col-span-2 @error('formData.description') error @enderror">
                        <flux:textarea 
                            label="Description" 
                            wire:model="formData.description" 
                            placeholder="Component Description"
                            error="{{ $errors->first('formData.description') }}"
                        />
                    </div>

                    <div class="col-span-2 @error('formData.custom_css') error @enderror">
                        <flux:textarea 
                            label="Custom CSS" 
                            wire:model="formData.custom_css" 
                            placeholder="Custom CSS"
                            error="{{ $errors->first('formData.custom_css') }}"
                        />
                    </div>

                    <div class="col-span-2">
                        <flux:switch 
                            wire:model.live="formData.is_inactive" 
                            label="Mark as Inactive"
                            error="{{ $errors->first('formData.is_inactive') }}"
                        />
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
    <flux:table :paginate="$this->list" class="w-full">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column>Name</flux:table.column>
            <flux:table.column>Code</flux:table.column>
            <flux:table.column>Wire</flux:table.column>
            <flux:table.column>Icon</flux:table.column>
            <flux:table.column>Color</flux:table.column>
            <flux:table.column>Tooltip</flux:table.column>
            <flux:table.column>Order</flux:table.column>
            <flux:table.column>Badge</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->list as $rec)
                <flux:table.row :key="$rec->id" class="border-b">
                    <flux:table.cell class="table-cell-wrap">
                        <div class="flex items-center gap-2">
                            @if($rec->color)
                                <div class="w-3 h-3 rounded-full" style="background-color: {{ $rec->color }}"></div>
                            @endif
                            @if($rec->icon)
                                <i class="{{ $rec->icon }}"></i>
                            @endif
                            {{ $rec->name }}
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>{{ $rec->code }}</flux:table.cell>
                    <flux:table.cell>{{ $rec->wire ?? '-' }}</flux:table.cell>
                    <flux:table.cell>
                        @if($rec->icon)
                            <i class="{{ $rec->icon }}" title="{{ $rec->icon }}"></i>
                        @else
                            -
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($rec->color)
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-4 rounded" style="background-color: {{ $rec->color }}"></div>
                                <span>{{ $rec->color }}</span>
                            </div>
                        @else
                            -
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>{{ $rec->tooltip ?? '-' }}</flux:table.cell>
                    <flux:table.cell>{{ $rec->order ?? '-' }}</flux:table.cell>
                    <flux:table.cell>{{ $rec->badge ?? '-' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:switch
                            wire:model="statuses.{{ $rec->id }}"
                            wire:click="toggleStatus({{ $rec->id }})"
                        />
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex space-x-2">
                            <flux:button variant="primary" size="sm" icon="pencil"
                                wire:click="edit({{ $rec->id }})"/>
                            <flux:modal.trigger name="delete-component-{{ $rec->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"/>
                            </flux:modal.trigger>
                        </div>

                        <!-- Delete Modal -->
                        <flux:modal name="delete-component-{{ $rec->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete Component?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this component. This action cannot be undone.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer/>
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button
                                        type="submit"
                                        variant="danger"
                                        icon="trash"
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