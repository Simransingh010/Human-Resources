<div>
    <div class="flex justify-between mt-2">
        <div>
            <flux:heading size="lg" class="flex">
                <flux:icon.clock />
                Work Shift Breaks
            </flux:heading>
            <flux:subheading>
                Configure work shift break settings.
            </flux:subheading>
        </div>
        <div>
            <flux:modal.trigger name="mdl-break">
                <flux:button icon="plus" variant="primary" icon="plus" wire:click="resetForm()"
                             class="bg-blue-500 text-white mt-2 rounded-md">
                    New
                </flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    <flux:modal name="mdl-break" @close="resetForm" position="right" class="max-w-none">
        <form wire:submit.prevent="saveBreak">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg" class="flex">
                        <flux:icon.clock />
                        @if($isEditing) Edit Break @else New Break @endif
                    </flux:heading>
                    <flux:subheading>
                        Configure break details.
                    </flux:subheading>
                </div>
                <flux:separator/>

                <!-- Form Fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <flux:input label="Break Title" wire:model="breakData.break_title" placeholder="Enter break title" />
                    <flux:textarea label="Description" wire:model="breakData.break_desc" placeholder="Enter break description" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input type="time" label="Start Time" wire:model="breakData.start_time" />
                    <flux:input type="time" label="End Time" wire:model="breakData.end_time" />
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end pt-4">
                    <flux:button type="submit" variant="primary">
                        Save Changes
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:separator class="mb-3 mt-3" />

    <flux:table>
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column sortable :sorted="$sortBy === 'break_title'" :direction="$sortDirection"
                wire:click="sort('break_title')">Break Title</flux:table.column>
            <flux:table.column>Description</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'start_time'" :direction="$sortDirection"
                wire:click="sort('start_time')">Start Time</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'end_time'" :direction="$sortDirection"
                wire:click="sort('end_time')">End Time</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->breaksList as $break)
                <flux:table.row :key="$break->id" class="border-b">
                    <flux:table.cell>
                        <flux:badge size="sm" color="blue" inset="top bottom">
                            {{ $break->break_title }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $break->break_desc ?? 'N/A' }}</flux:table.cell>
                    <flux:table.cell>{{ $break->start_time ? date('H:i', strtotime($break->start_time)) : 'N/A' }}</flux:table.cell>
                    <flux:table.cell>{{ $break->end_time ? date('H:i', strtotime($break->end_time)) : 'N/A' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:switch
                            :value="!$break->is_inactive"
                            wire:click="toggleStatus({{ $break->id }})"
                            :checked="!$break->is_inactive"
                        />
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-center space-x-2">
                            <flux:button variant="primary" size="sm" icon="pencil"
                                wire:click="fetchBreak({{ $break->id }})"></flux:button>
                            <flux:modal.trigger name="delete-break-{{ $break->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>

                        <flux:modal name="delete-break-{{ $break->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete break?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this break.</p>
                                        <p>This action cannot be reversed.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                        wire:click="deleteBreak({{ $break->id }})"></flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div> 