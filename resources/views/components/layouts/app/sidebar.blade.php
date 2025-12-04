@props(['component' => null, 'title' => null])

@php
    // Get component from props, attributes, or route
    $routeComponent = $component ?? $attributes->get('component') ?? request()->route('component');
    
    // If we have a route component, set the session BEFORE components mount
    if ($routeComponent) {
        // Set the selected wire in session
        session(['selectedWire' => $routeComponent]);
        
        // Find and set the module ID for this wire
        $moduleId = \App\Services\MenuCoordinator::findModuleIdForWire($routeComponent);
        if ($moduleId) {
            session(['selectedModuleId' => $moduleId]);
        }
    }
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800 flex">
@livewire('panel.leftmenu')
<div class="flex-1 p-0 m-0">
    @livewire('panel.topbar')
    @livewire('panel.topmenu')
    <div class="p-0 m-0">
        @livewire('panel.router-wrapper', ['component' => $routeComponent])
    </div>

@fluxScripts
@persist('toast')
<flux:toast position="top right"/>
@endpersist
@stack('scripts')
</body>
</html>
