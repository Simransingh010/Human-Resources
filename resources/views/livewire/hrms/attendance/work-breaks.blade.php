<div>
    <flux:modal.trigger name="mdl-break">
        <flux:button variant="primary" class="bg-blue-500 text-white px-4 mb-4 py-2 rounded-md">
            @if($isEditing)
                Edit Break
            @else
                Add Break
            @endif
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="mdl-break" position="right" @close="resetForm" class="max-w-none min-w-[360px] bg-neutral-100">
        <form wire:submit.prevent="saveBreak">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Break @else Add Break @endif
                    </flux:heading>
                    <flux:subheading>
                        Configure work break settings.
                    </flux:subheading>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <flux:input
                            label="Break Title"
                            wire:model="breakData.break_title"
                            placeholder="Enter break title"
                    />
                    <div class="col-span-1">
                        <flux:input
                                label="Start Time"
                                type="time"
                                wire:model="breakData.start_time"
                        />
                    </div>
                    <div class="col-span-1">
                        <flux:input
                                label="End Time"
                                type="time"
                                wire:model="breakData.end_time"
                        />
                    </div>
                </div>
                <flux:textarea
                        label="Description"
                        wire:model="breakData.break_desc"
                        placeholder="Enter break description"
                />
                <div class="flex">
                    <flux:spacer/>
                    <flux:button type="submit" variant="primary">
                        Save Changes
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:table :paginate="$this->breaksList" class="w-full">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column class="w-12">ID</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'break_title'" :direction="$sortDirection"
                               wire:click="sort('break_title')">Break Title
            </flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'start_time'" :direction="$sortDirection"
                               wire:click="sort('start_time')">Start Time
            </flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'end_time'" :direction="$sortDirection"
                               wire:click="sort('end_time')">End Time
            </flux:table.column>
            <flux:table.column>Description</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->breaksList as $break)
                <flux:table.row :key="$break->id" class="border-b">
                    <flux:table.cell class="">{{ $break->id }}</flux:table.cell>
                    <flux:table.cell>{{ $break->break_title }}</flux:table.cell>
                    <flux:table.cell>{{ \Carbon\Carbon::parse($break->start_time)->format('H:i') }}</flux:table.cell>
                    <flux:table.cell>{{ \Carbon\Carbon::parse($break->end_time)->format('H:i') }}</flux:table.cell>
                    <flux:table.cell class="">{{ $break->break_desc }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:switch
                                :value="!$break->is_inactive"
                                wire:click="toggleStatus({{ $break->id }})" :checked="!$break->is_inactive"
                        />
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-center space-x-2">
                        <flux:button variant="ghost" size="sm" icon="pencil"
                                     wire:click="fetchBreak({{ $break->id }})"></flux:button>
                            <flux:modal.trigger name="delete-profile-{{ $break->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>
                        <flux:modal name="delete-profile-{{ $break->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete project?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this project.</p>
                                        <p>This action cannot be reversed.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash" wire:click="deleteWorkBreak({{ $break->id }})"></flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div> 