<div class="flex-1 p-4 relative" x-data="{ loading: false }" 
     @navigation-started.window="loading = true"
     @navigation-ended.window="loading = false"
     x-on:livewire:navigated.window="loading = false">
    
    {{-- Loading Overlay --}}
    <div x-show="loading" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="absolute inset-0 bg-white/80 dark:bg-zinc-800/80 z-50 flex items-center justify-center backdrop-blur-sm"
         style="display: none;">
        <div class="flex flex-col items-center gap-3">
            <svg class="animate-spin h-8 w-8 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm text-gray-600 dark:text-gray-300 font-medium">Loading...</span>
        </div>
    </div>
    
    {{-- Livewire loading indicator for wire:click actions --}}
    <div wire:loading.delay class="absolute inset-0 bg-white/80 dark:bg-zinc-800/80 z-50 flex items-center justify-center backdrop-blur-sm">
        <div class="flex flex-col items-center gap-3">
            <svg class="animate-spin h-8 w-8 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm text-gray-600 dark:text-gray-300 font-medium">Loading...</span>
        </div>
    </div>

    @if($componentToRender)
        @livewire($componentToRender, [], key($componentKey))
    @elseif($errorMessage ?? false)
        <div class="flex items-center justify-center h-64 text-center">
            <div>
                <div class="text-red-500 mb-2">{{ $errorMessage }}</div>
                <a href="{{ route('dashboard') }}" class="text-blue-500 hover:underline">Back to Dashboard</a>
            </div>
        </div>
    @else
        <div class="text-gray-500 text-center py-16">No content selected</div>
    @endif
</div>
