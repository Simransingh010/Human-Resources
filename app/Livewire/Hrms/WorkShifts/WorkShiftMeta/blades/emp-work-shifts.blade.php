<div>
    <!-- Heading Start -->
    <div class="flex justify-between">
        <div>
            @if($workShift)
                <flux:heading>Employee Assignments - {{ $workShift->shift_title }}</flux:heading>
            @else
                @livewire('panel.component-heading')
            @endif
        </div>
{{--        <flux:modal.trigger name="mdl-emp-shift" class="flex justify-end">--}}
{{--            <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">--}}
{{--                New Assignment--}}
{{--            </flux:button>--}}
{{--        </flux:modal.trigger>--}}
    </div>
    <flux:separator class="mt-2 mb-2" />
    <!-- Heading End -->

    <!-- Filters Start -->
    <flux:card class="p-2">
        <flux:heading>Filters</flux:heading>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
            <!-- Search -->
            <div>
                <flux:input type="search" placeholder="Search by employee name..."
                    wire:model.live="filters.search_employee" class="w-full">
                    <x-slot:prefix>
                        <flux:icon name="magnifying-glass" class="w-5 h-5 text-gray-400" />
                    </x-slot:prefix>
                    @if($filters['search_employee'])
                        <x-slot:suffix>
                            <flux:button wire:click="$set('filters.search_employee', '')" variant="ghost" size="xs"
                                icon="x-mark" class="text-gray-400 hover:text-gray-600" />
                        </x-slot:suffix>
                    @endif
                </flux:input>
            </div>
            <!-- Date Filter -->
            <div>
                <flux:input type="date" placeholder="Filter by date..." wire:model.live="filters.search_date"
                    class="w-full" />
            </div>
            <div class="mt-1 flex justify-end">
                <flux:button wire:click="clearFilters" variant="outline" size="sm" icon="x-circle">
                </flux:button>
            </div>
        </div>
        <!-- Clear Filters Button -->

    </flux:card>
    <!-- Filters End -->

    <!-- Modal Start -->
    <flux:modal name="mdl-emp-shift" @cancel="resetForm" position="right" class="max-w-none" variant="flyout">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Work Shift Assignment @else New Work Shift Assignment @endif
                    </flux:heading>
                    <flux:subheading>
                        @if($workShift)
                            {{ $workShift->shift_title }}
                        @endif
                    </flux:subheading>
                </div>

                <!-- Grid layout for form fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if(!$workShift)
                        <div>
                            <flux:select label="Work Shift" wire:model="formData.work_shift_id" required>
                                <flux:select.option value="">Select Work Shift</flux:select.option>
                                @foreach($listsForFields['work_shifts'] ?? [] as $id => $title)
                                    <flux:select.option value="{{ $id }}">{{ $title }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                    @endif
                    <div>
                        <flux:input label="Start Date" type="date" wire:model="formData.start_date" required />
                    </div>
                    <div>
                        <flux:input label="End Date" type="date" wire:model="formData.end_date" />
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
        <flux:table.columns>
            <flux:table.column>Employee</flux:table.column>
            @if(!$workShift)
                <flux:table.column>Work Shift</flux:table.column>
            @endif
            <flux:table.column>Start Date</flux:table.column>
            <flux:table.column>End Date</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->list as $rec)
                <flux:table.row :key="$rec->id" class="border-b">
                    <flux:table.cell class="table-cell-wrap">
                        {{ trim($rec->employee->fname . ' ' . ($rec->employee->mname ? $rec->employee->mname . ' ' : '') . $rec->employee->lname) }}
                    </flux:table.cell>
                    @if(!$workShift)
                        <flux:table.cell class="table-cell-wrap">{{ $rec->work_shift->shift_title }}</flux:table.cell>
                    @endif
                    <flux:table.cell class="table-cell-wrap">{{ $rec->start_date->format('jS M Y ') }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->end_date ? $rec->end_date->format('jS M Y') : '-' }}
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex space-x-2">
                            <flux:button variant="primary" size="sm" icon="pencil" wire:click="edit({{ $rec->id }})" />
                            <flux:modal.trigger name="delete-{{ $rec->id }}">
                                <flux:button variant="danger" size="sm" icon="trash" />
                            </flux:modal.trigger>
                        </div>

                        <!-- Delete Modal -->
                        <flux:modal name="delete-{{ $rec->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete Work Shift Assignment?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this work shift assignment. This action cannot be undone.
                                        </p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                        wire:click="delete({{ $rec->id }})" />
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