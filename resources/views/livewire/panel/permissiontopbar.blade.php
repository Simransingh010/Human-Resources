<div class="px-8 pt-2 pb-0">
    <flux:button.group>
        @foreach ($permissions as $permission)
            <flux:button href="{{ route($permission['route']) }}">
                {{ $permission['name'] }}
            </flux:button>
        @endforeach
    </flux:button.group>
</div>


