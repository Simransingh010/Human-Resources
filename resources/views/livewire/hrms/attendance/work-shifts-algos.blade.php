<div>
    <flux:modal.trigger name="mdl-shift-algo">
        <flux:button variant="primary" class="bg-blue-500 mb-4 text-white px-4 py-2 rounded-md">
            @if($isEditing)
                Edit Shift Algorithm
            @else
                Add Shift Algorithm
            @endif
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="mdl-shift-algo" @close="resetForm" class="max-w-none min-w-[360px] bg-neutral-100">
        <form wire:submit.prevent="saveAlgo">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Shift Algorithm @else Add Shift Algorithm @endif
                    </flux:heading>
                    <flux:subheading>
                        Configure work shift algorithm settings.
                    </flux:subheading>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <flux:select
                            label="Work Shift"
                            wire:model="algoData.work_shift_id"
                    >
                        <option value="">Select Work Shift</option>
                        @foreach($this->workShiftsList as $id => $title)
                            <option value="{{ $id }}">{{ $title }}</option>
                        @endforeach
                    </flux:select>

                    <flux:input
                            label="Week Off Pattern"
                            wire:model="algoData.week_off_pattern"
                            placeholder="Enter week off pattern"
                    />

                    <flux:input
                            label="Holiday Calendar ID"
                            type="number"
                            wire:model="algoData.holiday_calendar_id"
                    />
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
                    <flux:textarea
                            label="Half Day Rule"
                            wire:model="algoData.half_day_rule"
                            placeholder="Enter half day rules"
                    />

                    <flux:textarea
                            label="Overtime Rule"
                            wire:model="algoData.overtime_rule"
                            placeholder="Enter overtime rules"
                    />

                    <flux:textarea
                            label="Rules Configuration"
                            wire:model="algoData.rules_config"
                            placeholder="Enter rules configuration"
                    />
                    <div class="mt-auto">
                        <flux:checkbox
                                label="Allow Work From Home" class="mt-auto flex"
                                wire:model="algoData.allow_wfh"
                        />
                    </div>
                </div>
                {{--                <flux:checkbox --}}
                {{--                    label="Active" --}}
                {{--                    wire:model="algoData.is_active"--}}
                {{--                />--}}

                <div class="flex">
                    <flux:spacer/>
                    <flux:button type="submit" variant="primary">
                        Save Changes
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:table :paginate="$this->algosList" class="">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column>ID</flux:table.column>
            <flux:table.column>Work Shift</flux:table.column>
            <flux:table.column>Week Off Pattern</flux:table.column>
            <flux:table.column>WFH</flux:table.column>
            <flux:table.column>Holiday Calendar ID</flux:table.column>
            <flux:table.column>Half Day Rule</flux:table.column>
            <flux:table.column>Overtime Rule</flux:table.column>
            <flux:table.column>Rules Configuration</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->algosList as $algo)
                <flux:table.row :key="$algo->id" class="border-b">
                    <flux:table.cell>{{ $algo->id }}</flux:table.cell>
                    <flux:table.cell>{{ $algo->work_shift->shift_title }}</flux:table.cell>
                    <flux:table.cell>{{ $algo->week_off_pattern }}</flux:table.cell>
                    <flux:table.cell>
                        @if($algo->allow_wfh)
                            <flux:badge size="sm" color="green">Allowed</flux:badge>
                        @else
                            <flux:badge size="sm" color="red">Not Allowed</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>{{ $algo->holiday_calendar_id }}</flux:table.cell>
                    <flux:table.cell>{{ $algo->half_day_rule }}</flux:table.cell>
                    <flux:table.cell>{{ $algo->overtime_rule }}</flux:table.cell>
                    <flux:table.cell>{{ $algo->rules_config }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:switch
                                :value="!$algo->is_active"
                                wire:click="toggleStatus({{ $algo->id }})" :checked="!$algo->is_active"
                        />
                    </flux:table.cell>
                    <flux:table.cell>

                        <div class="flex justify-center space-x-2">
                            <flux:button variant="outline" size="sm" icon="pencil"
                                         wire:click="fetchAlgo({{ $algo->id }})"></flux:button>
                            <flux:modal.trigger name="delete-profile-{{ $algo->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>
                        <flux:modal name="delete-profile-{{ $algo->id }}" class="min-w-[22rem]">
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
                                    <flux:button type="submit" variant="danger" icon="trash" wire:click="deleteBreak({{ $algo->id }})"></flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div> 