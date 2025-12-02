<div class="flex justify-between">
    <flux:sidebar sticky stashable class="border-r border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark"/>

        <!-- Logo & Firm Name -->
        <a href="#" wire:click="selectApp(null)" class="mr-5 flex items-center space-x-2">
            <span class="flex items-center justify-center rounded-md">
                @if($firmSquareLogo)
                    <img
                        src="{{ $firmSquareLogo }}"
                        alt="{{ $firmShortName ?? $firmName ?? 'Logo' }}"
                        class="h-10 w-auto"
                    />
                @elseif($firmWideLogo)
                    <img
                        src="{{ $firmWideLogo }}"
                        alt="{{ $firmShortName ?? $firmName ?? 'Logo' }}"
                        class="h-10 w-auto"
                    />
                @else
                    <img
                        src="https://iqwing.live/assets/images/logo-iqwing.webp"
                        alt="IQwing Logo"
                        class="h-10 w-auto"
                    />
                @endif
            </span>
            @if($firmShortName || $firmName)
                <span class="font-semibold text-sm text-gray-800 dark:text-white truncate max-w-[120px]">
                    {{ $firmName ?? $firmShortName }}
                </span>
            @endif
        </a>

        <!-- App Selector -->
        <flux:select variant="listbox" wire:model="selectedAppId" wire:change="selectApp($event.target.value)" placeholder="Select role...">
            @foreach ($apps as $app)
                <flux:select.option value="{{ $app['id'] }}">
                    <div class="flex items-center gap-2">
                        @if(!empty($app['icon']))
                            <img src="{{ $app['icon'] }}" class="w-5 h-5" />
                        @endif
                        {{ $app['name'] }}
                    </div>
                </flux:select.option>
            @endforeach
        </flux:select>

        <!-- Module Navigation -->
        <flux:navlist variant="outline">
            <flux:navlist.group :heading="__('Modules')" class="grid">
                @foreach ($modules as $module)
                    <flux:navlist.item
                        wire:click="selectModule({{ $module['id'] }})"
                        @click="$dispatch('navigation-started')"
                        class="{{ $selectedModuleId === $module['id'] ? 'bg-pink-500 !text-white rounded-lg' : 'bg-transparent text-black dark:text-white' }}"
                    >
                        <div class="flex items-center gap-2">
                            @if(!empty($module['icon']))
                            <img src="{{ $module['icon'] }}"  class="w-5 h-5" />
                            @endif
                            {{ $module['name'] }}
                        </div>
                    </flux:navlist.item>
                @endforeach
            </flux:navlist.group>
        </flux:navlist>

        <flux:spacer/>

        <!-- Desktop User Menu -->
        <flux:dropdown position="bottom" align="start">
            <flux:profile
                :name="auth()->user()->name"
                :initials="auth()->user()->initials()"
                icon-trailing="chevrons-up-down"
            />

            <flux:menu class="w-[220px]">
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                            <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                <span
                                    class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                >
                                    {{ auth()->user()->initials() }}
                                </span>
                            </span>
                            <div class="grid flex-1 text-left text-sm leading-tight">
                                <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator/>

                <flux:menu.radio.group>
                    <flux:menu.item wire:click="selectWire('settings.profile')" icon="cog">{{ __('Settings') }}</flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator/>

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                        {{ __('Log Out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:sidebar>
</div>
