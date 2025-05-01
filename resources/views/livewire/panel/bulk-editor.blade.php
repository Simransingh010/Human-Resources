<div class="space-y-4">
    <table class="w-full table-auto border border-gray-200 text-sm">
        <thead>
        <tr class="bg-gray-100">
            {{-- read-only label column --}}
            <th class="p-2 text-left">{{ $labelHeader }}</th>

            {{-- editable columns --}}
            @foreach($fields as $field)
                <th class="p-2 text-left">{{ Str::title(str_replace('_',' ',$field)) }}</th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        @foreach($this->items as $item)
            <tr class="border-t">
                {{-- display the labelFields concatenated --}}
                <td class="p-2">
                    {{ collect($labelFields)
                        ->map(fn($f) => data_get($item, $f))
                        ->filter()
                        ->implode(' ')
                    }}
                </td>

                {{-- the dynamic inputs as before --}}
                @foreach($fields as $field)
                    <td class="p-2">
                        @if(isset($listsForFields[$field]))
                            <select
                                wire:model.defer="updateData.{{ $item->id }}.{{ $field }}"
                                wire:change="saveField({{ $item->id }}, '{{ $field }}')"
                                class="border rounded px-2 py-1 w-full"
                            >
                                <option value="">— select —</option>
                                @foreach($listsForFields[$field] as $val => $label)
                                    <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        @else
                            <input
                                type="text"
                                wire:model.defer="updateData.{{ $item->id }}.{{ $field }}"
                                wire:change="saveField({{ $item->id }}, '{{ $field }}')"
                                class="border rounded px-2 py-1 w-full"
                            />
                        @endif
                    </td>
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="mt-4">
        {{ $this->items->links() }}
    </div>
</div>
