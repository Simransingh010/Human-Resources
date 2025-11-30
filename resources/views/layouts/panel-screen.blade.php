@php
    $componentValue = isset($component) ? $component : null;
@endphp

<x-layouts.app.sidebar :component="$componentValue">
    {{-- RouterWrapper receives component via the component prop --}}
</x-layouts.app.sidebar>
https:// start.iqdigit.com/hrms/onboard/employees