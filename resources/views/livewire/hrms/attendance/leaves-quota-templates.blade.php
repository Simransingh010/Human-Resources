<div>
    <flux:modal.trigger name="mdl-quota-template">
        <flux:button variant="primary" class="bg-blue-500 text-white dark:text-primary px-4 py-2 mb-4 rounded-md">
            @if($isEditing)
                Edit Leave Quota Template
            @else
                Add Leave Quota Template
            @endif
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="mdl-quota-template" position="right" @close="resetForm"
        class="max-w-none min-w-[360px] bg-neutral-100">
        <form wire:submit.prevent="saveTemplate">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Leave Quota Template @else Add Leave Quota Template @endif
                    </flux:heading>
                    <flux:subheading>
                        Configure leave quota template details.
                    </flux:subheading>
                </div>

                <div class="grid grid-cols-1 gap-4">
                    <flux:input label="Template Name" wire:model="templateData.name" placeholder="Enter template name"
                        required />

                    <div class="col-span-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                            Description
                        </label>
                        <textarea wire:model="templateData.desc" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                            placeholder="Enter template description"></textarea>
                    </div>

                    {{-- <div class="flex items-center">--}}
                        {{-- <flux:checkbox--}} {{-- label="Inactive" --}} {{-- wire:model="templateData.is_inactive"
                            --}} {{-- />--}}
                        {{-- </div>--}}
                </div>

                <div class="flex justify-end pt-4">
                    <flux:button type="submit" variant="primary">
                        Save Changes
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:table :paginate="$this->templatesList" class="w-full">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column class="w-12">ID</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection"
                wire:click="sort('name')">Template Name</flux:table.column>
            <flux:table.column>Description</flux:table.column>
            <flux:table.column>Allocations</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->templatesList as $template)
                <flux:table.row :key="$template->id" class="border-b">
                    <flux:table.cell>{{ $template->id }}</flux:table.cell>
                    <flux:table.cell>{{ $template->name }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="max-w-xs truncate">{{ $template->desc ?? '-' }}</div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" color="blue" inset="top bottom">
                            {{ $template->emp_leave_allocations_count ?? 0 }} Allocations
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:switch :value="!$template->is_inactive" wire:click="toggleStatus({{ $template->id }})" />
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-center space-x-2">
                            <flux:dropdown>
                                <flux:button icon="cog" size="sm"></flux:button>

                                <flux:menu>
                                    <flux:modal.trigger wire:click="showmodal_template_setup({{ $template->id }})">
                                        <flux:menu.item icon="calendar-days" class="mt-0.5">
                                            Quota Setup
                                        </flux:menu.item>
                                    </flux:modal.trigger>
                                </flux:menu>
                            </flux:dropdown>

                            <flux:button variant="outline" size="sm" icon="pencil"
                                wire:click="fetchTemplate({{ $template->id }})">
                            </flux:button>
                            <flux:modal.trigger name="delete-template-{{ $template->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>

                        <flux:modal name="delete-template-{{ $template->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete Template?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this leave quota template.</p>
                                        <p>This action cannot be reversed.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                        wire:click="deleteTemplate({{ $template->id }})">
                                    </flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal name="add-template-setup" title="Template Setup" class="p-10 max-w-none">
        @if ($selectedTemplateId)
            <livewire:hrms.attendance.leaves-quota-template-setups :templateId="$selectedTemplateId"
                :wire:key="'add-template-setup-'.$selectedTemplateId" />
        @endif
    </flux:modal>
</div>