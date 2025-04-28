<div>
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-leave-allocation" class="flex justify-end">
            <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">
                New
            </flux:button>
        </flux:modal.trigger>
    </div>
    <flux:separator class="mt-2 mb-2" />
    <!-- Heading End -->

    <!-- Filters Start -->
    <form wire:submit.prevent="applyFilters">
        <flux:heading level="3" size="lg">Filter Records</flux:heading>
        <flux:card size="sm" class="sm:p-2 !rounded-xl rounded-xl! mb-1 rounded-t-lg p-0 bg-zinc-50 hover:bg-zinc-50 dark:hover:bg-zinc-700">
            <div class="grid md:grid-cols-3 gap-2 items-end">
                <div>
                    <flux:select
                        variant="listbox"
                        searchable
                        multiple
                        placeholder="Employees"
                        wire:model="filters.employees"
                        wire:key="employees-filter"
                    >
                        @foreach($listsForFields['employees'] as $key => $value)
                            <flux:select.option value="{{ $key }}">{{ $value }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div>
                    <flux:select
                        variant="listbox"
                        searchable
                        multiple
                        placeholder="Leave Types"
                        wire:model="filters.leave_types"
                        wire:key="leave-types-filter"
                    >
                        @foreach($listsForFields['leave_types'] as $key => $value)
                            <flux:select.option value="{{ $key }}">{{ $value }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="flex flex-wrap gap-2">
                    <flux:input type="text" placeholder="Search" wire:model="filters.search" />
                    <div class="min-w-[100px]">
                        <flux:button type="submit" variant="primary" class="w-full">Go</flux:button>
                    </div>
                    <div class="min-w-[100px]">
                        <flux:button variant="filled" class="w-full px-2" tooltip="Cancel Filter" icon="x-circle"
                            wire:click="clearFilters()"></flux:button>
                    </div>
                </div>
            </div>
        </flux:card>
    </form>
    <!-- Filters End -->

    <!-- Modal Start -->
    <flux:modal name="mdl-leave-allocation" @cancel="resetForm" class="max-w-none">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Leave Allocation @else Add Leave Allocation @endif
                    </flux:heading>
                    <flux:subheading>
                        @if($isEditing) Update @else Add new @endif employee leave allocation details.
                    </flux:subheading>
                </div>

                <!-- Grid layout for form fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select label="Employee" wire:model.live="formData.employee_id" required>
                        <option value="">Select Employee</option>
                        @foreach($listsForFields['employees'] as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select label="Leave Type" wire:model.live="formData.leave_type_id" required>
                        <option value="">Select Leave Type</option>
                        @foreach($listsForFields['leave_types'] as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select label="Quota Template" wire:model.live="formData.leaves_quota_template_id">
                        <option value="">Select Template</option>
                        @foreach($listsForFields['templates'] as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:input label="Days Assigned" type="number" wire:model.live="formData.days_assigned" placeholder="Enter days assigned" required/>
                    <flux:input label="Start Date" type="date" wire:model.live="formData.start_date" required/>
                    <flux:input label="End Date" type="date" wire:model.live="formData.end_date" required/>
                    <flux:input label="Days Balance" type="number" wire:model.live="formData.days_balance" placeholder="Enter days balance" required/>
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
            <flux:table.column>Employee</flux:table.column>
            <flux:table.column>Leave Type</flux:table.column>
            <flux:table.column>Template</flux:table.column>
            <flux:table.column>Days Assigned</flux:table.column>
            <flux:table.column>Start Date</flux:table.column>
            <flux:table.column>End Date</flux:table.column>
            <flux:table.column>Days Balance</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->list as $rec)
                <flux:table.row :key="$rec->id" class="border-b">
                    <flux:table.cell>{{ $rec->employee->fname }}</flux:table.cell>
                    <flux:table.cell>{{ $rec->leave_type->leave_title }}</flux:table.cell>
                    <flux:table.cell>{{ $rec->leaves_quota_template?->name ?? 'N/A' }}</flux:table.cell>
                    <flux:table.cell>{{ $rec->days_assigned }}</flux:table.cell>
                    <flux:table.cell>{{ $rec->start_date?->format('Y-m-d') }}</flux:table.cell>
                    <flux:table.cell>{{ $rec->end_date?->format('Y-m-d') }}</flux:table.cell>
                    <flux:table.cell>{{ $rec->days_balance }}</flux:table.cell>
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
                                    <flux:heading size="lg">Delete Allocation?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this leave allocation. This action cannot be undone.</p>
                                        <p class="mt-2 text-red-500">Note: Allocations with related records cannot be deleted.</p>
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