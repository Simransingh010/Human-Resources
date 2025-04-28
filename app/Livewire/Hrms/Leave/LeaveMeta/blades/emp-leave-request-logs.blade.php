<div>
    <!-- Heading Start -->
    <div class="flex justify-between">
        <flux:heading level="3" size="lg">Leave Request Logs</flux:heading>
        <flux:modal.trigger name="mdl-leave-request-log" class="flex justify-end">
            <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">
                Add Log
            </flux:button>
        </flux:modal.trigger>
    </div>
    <flux:separator class="mt-2 mb-2" />
    <!-- Heading End -->

    <!-- Table Start-->
    <flux:table :paginate="$this->list" class="">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Date & Time</flux:table.column>
            <flux:table.column>Remarks</flux:table.column>
            <flux:table.column>Action By</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->list as $log)
                <flux:table.row :key="$log->id" class="border-b">
                    <flux:table.cell>{{ $listsForFields['statuses'][$log->status] ?? $log->status }}</flux:table.cell>
                    <flux:table.cell>{{ $log->status_datetime?->format('Y-m-d H:i:s') }}</flux:table.cell>
                    <flux:table.cell>{{ $log->remarks }}</flux:table.cell>
                    <flux:table.cell>{{ $actionByNames[$log->action_by] ?? 'N/A' }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex space-x-2">
                            <flux:button
                                variant="primary"
                                size="sm"
                                icon="pencil"
                                wire:click="edit({{ $log->id }})"
                            />
                            <flux:modal.trigger name="delete-{{ $log->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"/>
                            </flux:modal.trigger>
                        </div>

                        <!-- Delete Modal -->
                        <flux:modal name="delete-{{ $log->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete Log Entry?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this log entry. This action cannot be undone.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer/>
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                        wire:click="delete({{ $log->id }})"/>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
    <!-- Table End-->

    <!-- Modal Start -->
    <flux:modal name="mdl-leave-request-log" @cancel="resetForm" class="max-w-none">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Log Entry @else Add Log Entry @endif
                    </flux:heading>
                    <flux:subheading>
                        @if($isEditing) Update @else Add new @endif leave request log details.
                    </flux:subheading>
                </div>

                <!-- Grid layout for form fields -->
                <div class="grid grid-cols-1 gap-4">
                    <flux:select label="Status" wire:model.live="formData.status" required>
                        <option value="">Select Status</option>
                        @foreach($listsForFields['statuses'] as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                    </flux:select>
                    <flux:input label="Date & Time" type="datetime-local" wire:model.live="formData.status_datetime" required/>
                    <flux:input label="Remarks" type="text" wire:model.live="formData.remarks" placeholder="Enter remarks"/>
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
</div>
