<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800 flex">
@livewire('panel.leftmenu')
<div class="flex-1">
    @livewire('panel.topbar')
    @livewire('panel.topmenu')
    <div class="p-0 m-0">
        @livewire('panel.main-content')
    </div>
</div>

@fluxScripts
@persist('toast')
<flux:toast position="top right"/>
@endpersist
@livewireScripts
@stack('scripts')
</body>
</html>
