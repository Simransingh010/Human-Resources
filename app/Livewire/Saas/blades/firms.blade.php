<div>
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-firm" class="flex justify-end">
            <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">
              New
            </flux:button>
        </flux:modal.trigger>
    </div>
    <!-- Heading End -->

    <!-- Modal Start -->
    <flux:modal name="mdl-firm" @cancel="resetForm" position="right" class="max-w-none" variant="flyout">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Firm @else Add Firm @endif
                    </flux:heading>
                    <flux:subheading>
                        @if($isEditing) Update  @else Add new @endif  firm details.
                    </flux:subheading>
                </div>

                <!-- Grid layout for form fields -->
                <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
                    <flux:input label="Name" wire:model="formData.name" placeholder="Firm Name"/>
                    <flux:input label="Short Name" wire:model="formData.short_name" placeholder="Short Name"/>
                    <flux:select label="Firm Type" wire:model="formData.firm_type" >
                        <flux:select.option value="">-- Select Firm Type --</flux:select.option>
                        <!-- static placeholder -->
                        @foreach($this->listsForFields['firm_type'] as $key => $value)
                            <flux:select.option value="{{ $key }}">{{$value}}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select label="Related Agency" variant="listbox" searchable wire:model="formData.agency_id"
                                 placeholder="Related Agency">
                        <flux:select.option value="">-- Select Agency --</flux:select.option>
                        <!-- static placeholder -->
                        @foreach($this->listsForFields['agencylist'] as $key => $value)
                            <flux:select.option value="{{ $key }}">{{$value}}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select label="Parent Firm" variant="listbox" searchable wire:model="formData.parent_firm_id"
                                 placeholder="Parent Firm">
                        <flux:select.option value="">-- Select Parent Firm --</flux:select.option>
                        <!-- static placeholder -->
                        @foreach($this->listsForFields['firmlist'] as $key => $value)
                            <flux:select.option value="{{ $key }}">{{$value}}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:switch wire:model.live="formData.is_master_firm" label="Set as Master"/>
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
    <flux:table :paginate="$this->list" class="w-full">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column>Name</flux:table.column>
            <flux:table.column>Short Name</flux:table.column>
            <flux:table.column>Firm Type</flux:table.column>
            <flux:table.column>Agency</flux:table.column>
            <flux:table.column>Parent Firm</flux:table.column>
            <flux:table.column>Set as Master</flux:table.column>
            <flux:table.column>Mark as Inactive</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->list as $firm)
                <flux:table.row :key="$firm->id" class="border-b">
                    <flux:table.cell class="table-cell-wrap">{{ $firm->name }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $firm->short_name }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $firm->firm_type_label }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $firm->agency?->name ?? '-' }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $firm->firm?->name ?? '-' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:switch wire:model="setMasterStatuses.{{ $firm->id }}"
                                     wire:click="toggleSetMasterStatus({{ $firm->id }})"/>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:switch wire:model="statuses.{{ $firm->id }}"
                                     wire:click="toggleStatus({{ $firm->id }})"/>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex space-x-2">
                            <flux:button
                                    wire:click="showAppAccess({{ $firm->id }})"
                                    color="zinc"
                                    size="sm"
                                    icon="key"
                                    tooltip="App Access"
                            />
                            <flux:button variant="primary" size="sm" icon="pencil"
                                         wire:click="edit({{ $firm->id }})"/>
                            <flux:modal.trigger name="delete-firm-{{ $firm->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"/>
                            </flux:modal.trigger>
                        </div>

                        <!-- Delete Modal -->
                        <flux:modal name="delete-firm-{{ $firm->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete Firm?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this firm. This action cannot be undone.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer/>
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                                 wire:click="delete({{ $firm->id }})"/>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
    <!-- Table End-->

    <!-- Related Component Calls in Modal Start-->
    <flux:modal name="app-access" title="App Access" class="p-10">
        @if($selectedId)
            <livewire:saas.firm-meta.app-access :firm-id="$selectedId" :wire:key="'app-access-'.$selectedId"/>
        @endif
    </flux:modal>
    <!-- Related Component Calls in Modal Over-->

</div>
