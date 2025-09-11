<div class="p-4">
    <div class="mb-4">
        <flux:input
            class="w-64"
            placeholder="Search Employee Name or Code..."
            wire:model.live.debounce.200ms="searchName"
        />
    </div>
    <form wire:submit.prevent="save">
        <table class="min-w-full border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border p-2 text-left">Employee Name</th>
                    @foreach ($salcomponents as $salcomponent)
                        <th class="border p-2 text-left">{{ $salcomponent->title }} <br> [ {{$salcomponent->nature }}]</th>
                    @endforeach
{{--                    <th class="border p-2 text-left">Actions</th>--}}
                </tr>
            </thead>
            <tbody>
                @foreach ($this->filteredSalcomponentEmployees as $salcomponentEmployee)
                    <tr wire:key="emp-{{ $salcomponentEmployee->id }}">
                        <td class="border p-2">{{ $salcomponentEmployee->fname }} {{ $salcomponentEmployee->mname }}
                            {{ $salcomponentEmployee->lname }}
                            @if(optional($salcomponentEmployee->emp_job_profile)->employee_code)
                                ({{ optional($salcomponentEmployee->emp_job_profile)->employee_code }})
                            @endif
                        </td>
                        @foreach ($salcomponents as $salcomponent)
                            @php
                                $isAssigned = $assignedMatrix[$salcomponentEmployee->id][$salcomponent->id] ?? false;
                            @endphp
                            <td class="border p-2">
                                <div class="flex items-center space-x-2">
                                    <input type="text" class="w-full border rounded px-2 py-1 {{ $isAssigned ? '' : 'bg-gray-100 cursor-not-allowed' }}" placeholder="0.00"
                                        wire:key="cell-{{ $salcomponentEmployee->id }}-{{ $salcomponent->id }}"
                                        value="{{ $entries[$salcomponentEmployee->id][$salcomponent->id] ?? '' }}"
                                        @if($isAssigned)
                                            wire:keyup.debounce.500ms="saveSingleEntry('{{ $salcomponentEmployee->id }}', '{{ $salcomponent->id }}', $event.target.value)"
                                        @else disabled title="Head not assigned to this employee" @endif>
                                    <flux:modal.trigger
                                        name="mdl-remarks-{{ $salcomponentEmployee->id }}-{{ $salcomponent->id }}">
                                        <flux:button variant="primary" size="sm" icon="pencil-square"
                                            tooltip="Add/Edit Remarks" @if(!$isAssigned) disabled @endif />
                                    </flux:modal.trigger>
                                </div>
                            </td>
                        @endforeach
{{--                        <td class="border p-2">--}}
{{--                            <div class="flex space-x-2">--}}
{{--                                <flux:button variant="primary" size="sm" icon="eye" tooltip="View All Remarks"--}}
{{--                                    wire:click="viewAllRemarks('{{ $salcomponentEmployee->id }}')" />--}}
{{--                            </div>--}}
{{--                        </td>--}}
                    </tr>

                    <!-- Individual Component Remarks Modal -->
                    @foreach ($salcomponents as $salcomponent)
                        <flux:modal name="mdl-remarks-{{ $salcomponentEmployee->id }}-{{ $salcomponent->id }}" class="max-w-lg">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Add/Edit Remarks</flux:heading>
                                    <flux:subheading>
                                        Employee: {{ $salcomponentEmployee->fname }} {{ $salcomponentEmployee->mname }}
                                        {{ $salcomponentEmployee->lname }}
                                        @if(optional($salcomponentEmployee->emp_job_profile)->employee_code)
                                            ({{ optional($salcomponentEmployee->emp_job_profile)->employee_code }})
                                        @endif
                                        <br>
                                        Component: {{ $salcomponent->title }} [{{ $salcomponent->nature }}]
                                    </flux:subheading>
                                </div>

                                <div class="space-y-4">
                                    <flux:textarea label="Remarks" placeholder="Enter remarks for this component..."
                                        wire:model="componentRemarks.{{ $salcomponentEmployee->id }}.{{ $salcomponent->id }}"
                                        rows="4" />
                                </div>

                                <div class="flex justify-end space-x-2">
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button variant="primary"
                                        wire:click="saveRemarks('{{ $salcomponentEmployee->id }}', '{{ $salcomponent->id }}')">
                                        Save Remarks
                                    </flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    @endforeach
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Save Entries
            </button>
        </div>
    </form>

    <!-- View All Remarks Modal -->
    <flux:modal name="mdl-view-all-remarks" class="max-w-2xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">All Component Remarks</flux:heading>
                <flux:subheading>
                    Employee:
                    {{ $currentEmployee ? $currentEmployee->fname . ' ' . $currentEmployee->mname . ' ' . $currentEmployee->lname : '' }}
                </flux:subheading>
            </div>

            <div class="space-y-4">
                @if(isset($allRemarks) && count($allRemarks))
                    @foreach($allRemarks as $componentId => $remark)
                        <div class="border-b pb-4">
                            <flux:heading size="sm">{{ $salcomponents->find($componentId)->title }}</flux:heading>
                            <flux:text class="mt-2">{{ $remark ?: 'No remarks added' }}</flux:text>
                        </div>
                    @endforeach
                @else
                    <flux:text>No remarks found for any components.</flux:text>
                @endif
            </div>

            <div class="flex justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost">Close</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>