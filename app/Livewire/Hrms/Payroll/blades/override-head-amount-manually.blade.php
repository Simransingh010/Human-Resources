<div class="p-4">
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
            @foreach ($salcomponentEmployees as $salcomponentEmployee)
                <tr>
                    <td class="border p-2">{{ $salcomponentEmployee->fname }} {{ $salcomponentEmployee->mname }} {{ $salcomponentEmployee->lname }}</td>
                    @foreach ($salcomponents as $salcomponent)
                        <td class="border p-2">
                            <input type="text"
                                   class="w-full border rounded px-2 py-1"
                                   placeholder="0.00"
                                   value="{{ $entries[$salcomponentEmployee->id][$salcomponent->id] ?? '' }}"
                                   wire:keyup.debounce.500ms="saveSingleEntry('{{ $salcomponentEmployee->id }}', '{{ $salcomponent->id }}', $event.target.value)">
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
