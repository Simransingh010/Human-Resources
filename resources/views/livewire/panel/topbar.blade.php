<div>
    <style>
        body{
            /*font-family: 'Atlassian Sans';*/
        }
        .cursor-btn{
            cursor: pointer;
        }
        .bg-green-500 {
            background: linear-gradient(90deg, #10b981, #059669) !important;
            /*box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);*/
            position: relative;
            overflow: hidden;
        }

        .bg-green-500::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        /* Sidebar navigation enhancements */
        .flex-shrink-0 {
            background: #fff;
            backdrop-filter: blur(15px);
            border-radius: 20px !important;
            padding: 24px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            /*box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);*/
            position: sticky;
            top: 24px;
            height: fit-content;
        }

        /* Step indicators */
        .relative.pb-4 {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .relative.pb-4:hover {
            transform: translateX(4px);
        }

        /* Step circles - completed */
        .bg-green-500.group-hover\:bg-green-600 {
            background: linear-gradient(135deg, #10b981, #059669) !important;
            /*box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);*/
            border: 2px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }

        .bg-green-500.group-hover\:bg-green-600:hover {
            transform: scale(1.1);
            /*box-shadow: 0 8px 25px rgba(16, 185, 129, 0.6);*/
        }
        button{
            cursor: pointer;
        }
        .block button.bg-transparent{
            border: 1px solid #f64641;
            background: #fff;
            /*background: linear-gradient(to right, rgb(238, 119, 36), rgb(216, 54, 58), rgb(221, 54, 117), rgb(180, 69, 147))!important;*/
            color: #000000;
            margin-bottom: 7px;
            cursor: pointer;
        }
        .top-menu-btn.px-3 button{
            /*background-image: linear-gradient(to right, #FF512F 0%, #DD2476  51%, #FF512F  100%);*/
            border: 1px solid #f64641;
            background: #fff;
            color: #000000;
            cursor: pointer;
        }
        .block button.bg-transparent:hover{
            background: linear-gradient(to right, rgb(238, 119, 36), rgb(216, 54, 58), rgb(221, 54, 117), rgb(180, 69, 147));
            color: #fff;
        }
        .block button.bg-transparent:hover img {
            background: #fff;
            border-radius: 50%;
        }
        .top-menu-btn.px-3 button:hover{
            background-image: linear-gradient(to right, #FF512F 0%, #DD2476  51%, #FF512F  100%);
            border: 1px solid #f64641;
            color: #fff;
            cursor: pointer;
        }
        .\[grid-area\:sidebar\] {
            grid-area: sidebar;
            background: #ffdce8;
        }
        .\[grid-area\:header\] {
            grid-area: header;
            background: #ffdce8;
        }

        .flex-1.m-0.p-0{
            background: #ffe0e52e;
            width: 100%;
            overflow-x: auto;
        }
        tr:nth-child(even){background-color: #f2f2f2;}
        tr:hover {background-color: rgba(255, 234, 234, 0.6);}
    </style>
    {{-- To attain knowledge, add things every day; To attain wisdom, subtract things every day. --}}
    <flux:header class="block! bg-white lg:bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
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
