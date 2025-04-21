<div>
    {{-- To attain knowledge, add things every day; To attain wisdom, subtract things every day. --}}
    <flux:header class="block! bg-white lg:bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">

        <flux:navbar scrollable>

            <flux:select variant="listbox" placeholder="Choose Panel" wire:model="currentPanel"
                         wire:change="changePanel($event.target.value)">
                @foreach($panels as $panel)
                    <flux:select.option value="{{ $panel->id }}">{{ $panel->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select
                variant="listbox"
                placeholder="Choose Firm"
                wire:model="currentFirm"
                wire:change="changefirm($event.target.value)"
                class="appearance-none bg-transparent text-gray-800 font-medium px-2 py-1 pr-6 focus:outline-none relative"
            >
                @foreach($firms as $firm)
                    <flux:select.option value="{{ $firm->id }}">
                        {{ $firm->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>



            {{--            <flux:select variant="listbox" placeholder="Choose Firm" wire:model="currentFirm"--}}
{{--                         wire:change="changefirm($event.target.value)">--}}
{{--                @foreach($firms as $firm)--}}
{{--                    <flux:select.option value="{{ $firm->id }}">{{ $firm->name }}</flux:select.option>--}}
{{--                @endforeach--}}
{{--            </flux:select>--}}
        </flux:navbar>

    </flux:header>


</div>
