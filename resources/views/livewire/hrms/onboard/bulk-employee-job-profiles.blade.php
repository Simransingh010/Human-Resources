<div class="space-y-4">
    <form wire:submit.prevent="saveBreak">
        <table class="w-full table-auto border border-gray-200 text-sm">
            <thead>
            <tr class="bg-gray-100">
                <th class="p-2 text-left">Employees
                </th>
                <th class="p-2 text-left">Department</th>
                <th class="p-2 text-left">Designation</th>
                <th class="p-2 text-left">Employment Type</th>
            </tr>
            </thead>
            <tbody>
            @foreach($employees as $employee)
                <tr class="border-t">


                    <td class="p-2">
                        {{ $employee->fname }} {{ $employee->lname }}
                    </td>
                    <td class="p-2">

                        <flux:select
                            wire:model.live="bulkupdate.{{ $employee->id }}.department_id"
                            wire:click="triggerUpdate({{ $employee->id }}, 'department_id')"
                        >
                            <option value="">Select..</option>
                            @foreach($departments as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </flux:select>

                    </td>
                    <td class="p-2">

                        <flux:select
                            wire:model.live="bulkupdate.{{ $employee->id }}.designation_id"
                            wire:click="triggerUpdate({{ $employee->id }}, 'designation_id')"
                        >
                            <option value="">Select..</option>
                            @foreach($designations as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </flux:select>

                    </td>
                    <td class="p-2">

                        <flux:select
                            wire:model.live="bulkupdate.{{ $employee->id }}.employment_type"
                            wire:click="triggerUpdate({{ $employee->id }}, 'employment_type')"
                        >
                            <option value="">Select..</option>
                            @foreach($employmentTypes as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </flux:select>


                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </form>

    <div class="mt-4">
        {{ $employees->links() }}
    </div>
</div>
