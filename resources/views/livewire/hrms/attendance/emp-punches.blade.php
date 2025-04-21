<div>
    <flux:modal.trigger name="mdl-emp-punch">
        <flux:button variant="primary" class="bg-blue-500 mb-4 text-white px-4 py-2 rounded-md">
            @if($isEditing)
                Edit Punch Record
            @else
                Add Punch Record
            @endif
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="mdl-emp-punch" @close="resetForm" class="max-w-none min-w-[360px] bg-neutral-100">
        <form wire:submit.prevent="savePunch">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Punch Record @else Add Punch Record @endif
                    </flux:heading>
                    <flux:subheading>
                        Configure employee punch record details.
                    </flux:subheading>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <flux:select
                        label="Employee"
                        wire:model="punchData.employee_id"
                    >
                        <option value="">Select Employee</option>
                        @foreach($this->employeesList as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>

                    <flux:input
                        type="date"
                        label="Work Date"
                        wire:model="punchData.work_date"
                    />

                    <flux:input
                        type="datetime-local"
                        label="Punch Date & Time"
                        wire:model="punchData.punch_datetime"
                    />

                    <flux:select
                        label="Punch Type"
                        wire:model="punchData.in_out"
                    >
                        <option value="">Select Type</option>
                        <option value="IN">IN</option>
                        <option value="OUT">OUT</option>
                    </flux:select>

                    <flux:input
                        label="Location"
                        wire:model="punchData.punch_location"
                        placeholder="Enter location"
                    />

                    <flux:input
                        label="Device ID"
                        wire:model="punchData.device_id"
                        placeholder="Enter device ID"
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <flux:input
                            type="file"
                            label="Punch Photo"
                            wire:model="photo"
                        />
                        <div class="text-sm text-gray-500 mt-1">
                            Max file size: 1MB. Allowed types: jpg, png, jpeg
                        </div>
                    </div>

                    <div class="flex items-center">
                        <flux:checkbox
                            label="Mark as Final"
                            wire:model="punchData.is_final"
                        />
                    </div>
                </div>

                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary">
                        Save Changes
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:table :paginate="$this->punchesList" class="">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column>ID</flux:table.column>
            <flux:table.column>Employee</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'work_date'" :direction="$sortDirection"
                             wire:click="sort('work_date')">Work Date</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'punch_datetime'" :direction="$sortDirection"
                             wire:click="sort('punch_datetime')">Punch Time</flux:table.column>
            <flux:table.column>Type</flux:table.column>
            <flux:table.column>Location</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->punchesList as $punch)
                <flux:table.row :key="$punch->id" class="border-b">
                    <flux:table.cell>{{ $punch->id }}</flux:table.cell>
                    <flux:table.cell>{{ $punch->employee->fname }} {{ $punch->employee->lname }}</flux:table.cell>
                    <flux:table.cell>{{ $punch->work_date->format('Y-m-d') }}</flux:table.cell>
                    <flux:table.cell>{{ $punch->punch_datetime->format('Y-m-d H:i:s') }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$punch->in_out === 'IN' ? 'green' : 'red'">
                            {{ $punch->in_out }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $punch->punch_location ?? '-' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$punch->is_final ? 'blue' : 'yellow'">
                            {{ $punch->is_final ? 'Final' : 'Pending' }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-center space-x-2">
                            <flux:button variant="outline" size="sm" icon="pencil"
                                       wire:click="fetchPunch({{ $punch->id }})"></flux:button>
                            <flux:modal.trigger name="delete-punch-{{ $punch->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>

                        <flux:modal name="delete-punch-{{ $punch->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete Punch Record?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this punch record.</p>
                                        <p>This action cannot be reversed.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                               wire:click="deletePunch({{ $punch->id }})"></flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div> 