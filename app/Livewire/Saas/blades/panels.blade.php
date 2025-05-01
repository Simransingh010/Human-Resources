<div class="w-full p-0 m-0">
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-panel" class="flex justify-end">
            <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">
                New
            </flux:button>
        </flux:modal.trigger>
    </div>
    <flux:separator class="mt-2 mb-2" />
    <!-- Heading End -->

    <!-- Modal Start -->
    <flux:modal name="mdl-panel" @cancel="resetForm" position="right" class="max-w-none">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Panel @else Add Panel @endif
                    </flux:heading>
                    <flux:subheading>
                        @if($isEditing) Update @else Add new @endif panel details.
                    </flux:subheading>
                </div>

                <!-- Grid layout for form fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input
                            label="Name"
                            wire:model="formData.name"
                            placeholder="Panel Name"
                    />
                    <flux:input
                            label="Code"
                            wire:model="formData.code"
                            placeholder="Panel Code"
                    />
                    <flux:textarea
                            label="Description"
                            wire:model="formData.description"
                            placeholder="Panel Description"
                    />
                    <flux:select
                            label="Panel Type"
                            wire:model="formData.panel_type"
                    >
                        <option value="">Select Type</option>
                        @foreach($this->listsForFields['panel_type'] as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                    </flux:select>
                    <flux:switch
                            wire:model.live="formData.is_inactive"
                            label="Mark as Inactive"
                    />
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
            <flux:table.column>Description</flux:table.column>
            <flux:table.column>Panel Type</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->list as $rec)
                <flux:table.row :key="$rec->id" class="border-b">
                    <flux:table.cell class="table-cell-wrap">{{ $rec->name }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->code }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ $rec->description }}</flux:table.cell>
                    <flux:table.cell class="table-cell-wrap">{{ ucfirst($rec->panel_type_label) }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:switch
                                wire:model="statuses.{{ $rec->id }}"
                                wire:click="toggleStatus({{ $rec->id }})"
                        />
                    </flux:table.cell>
                    <!-- In your actions cell -->
                    <flux:table.cell>
                        <div class="flex space-x-2">
                            <flux:button wire:click="showComponentSync({{ $rec->id }})" color="zinc" size="xs">Components
                            </flux:button>
                            <flux:button wire:click="showAppSync({{ $rec->id }})" color="zinc" size="xs">App
                            </flux:button>
                            <flux:button wire:click="showModuleSync({{ $rec->id }})" color="zinc" size="xs">Module
                            </flux:button>
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
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
    <!-- Table End-->
    <!-- Shared Modal -->
    <flux:modal name="app-sync" title="Manage Panels" class="p-10">
        @if($selectedPanelId)
            <livewire:saas.panel-meta.app-sync :panelId="$selectedPanelId" :wire:key="'app-sync-'.$selectedPanelId"/>
        @endif
    </flux:modal>

    <flux:modal name="module-sync" title="Manage Modules" class="p-10">
        @if($selectedPanelId)
            <livewire:saas.panel-meta.module-sync :panelId="$selectedPanelId"
                                                  :wire:key="'module-sync-'.$selectedPanelId"/>
        @endif
    </flux:modal>

{{--    <flux:modal name="component-sync" variant="flyout" title="Manage Components" class="p-10" class="min-h-[60vh] max-h-[90vh] overflow-y-auto">--}}
    <flux:modal name="component-sync"  variant="flyout" class="max-w-5xl min-h-[70vh] max-h-[85vh] overflow-y-auto">
        @if($selectedPanelId)
            <livewire:saas.panel-meta.component-sync :panelId="$selectedPanelId"
                                                  :wire:key="'component-sync-'.$selectedPanelId"/>
        @endif
    </flux:modal>
</div>
