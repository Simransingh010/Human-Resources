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
                            <td class="border p-2">
                                <input
                                    type="text"
                                    class="w-full border rounded px-2 py-1"
                                    placeholder="0.00"
                                    wire:key="cell-{{ $salcomponentEmployee->id }}-{{ $salcomponent->id }}"
                                    wire:model.lazy="entries.{{ $salcomponentEmployee->id }}.{{ $salcomponent->id }}"
                                    wire:change="saveSingleEntry('{{ $salcomponentEmployee->id }}', '{{ $salcomponent->id }}', entries.{{ $salcomponentEmployee->id }}.{{ $salcomponent->id }})"
                                    wire:input="markDirty('{{ $salcomponentEmployee->id }}','{{ $salcomponent->id }}')"
                                >
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Save Entries
            </button>
        </div>
    </form>
</div>