<div class="flex-1 p-4">
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
