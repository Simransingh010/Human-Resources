<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();


        $this->panels = auth()->user()->panels()->where('is_inactive', false)->where('panel_type', '2')->get();
        if ($this->panels->isNotEmpty()) {
            $this->currentPanel = session('panel_id', $this->panels->first()->id);
            session(['panel_id' => $this->currentPanel]);
        }


        $this->firms = auth()->user()->firms()->where('is_inactive', false)->get();
        if ($this->firms->isNotEmpty()) {
            $this->currentFirm = session('firm_id', $this->firms->first()->id);
            session(['firm_id' => $this->currentFirm]);
        }

        // Few System Settings  be loaded  and saved in session
        session(['dateFormat' => 'd-M-Y']);
        session(['defaultwire' => 'panel.dashboard']);
          // Set LOP deduction type only for firm ID 2
          $currentFirmId = (int) session('firm_id');
          if ($currentFirmId === 2) {
            session(['LOP_deduction_type'=> 'calculation_wise']);
            // DEBUG: Verify LOP deduction type is set
            \Log::info('LOP DEDUCTION TYPE SET', [
                'firm_id' => $currentFirmId,
                'lop_deduction_type' => session('LOP_deduction_type')
            ]);
        } else {
            session(['LOP_deduction_type'=> '']); // Keep blank for other firms
            \Log::info('LOP DEDUCTION TYPE NOT SET', [
                'firm_id' => $currentFirmId,
                'lop_deduction_type' => session('LOP_deduction_type')
            ]);
        }
        session(['fy_start' => '2025-04-01']);
        session(['fy_end' => '2026-03-31']);
        session(['roundoff_precision' => 0]);
        session(['roundoff_mode' => PHP_ROUND_HALF_UP]); // PHP_ROUND_HALF_UP, PHP_ROUND_HALF_DOWN, PHP_ROUND_HALF_EVEN, PHP_ROUND_HALF_ODD


        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Log in to your account')" :description="__('Enter your email and password below to log in')" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="login" class="flex flex-col gap-6">
        <!-- Email Address -->
        <flux:input
            wire:model="email"
            :label="__('Email address')"
            type="email"
            required
            autofocus
            autocomplete="email"
            placeholder="email@example.com"
        />

        <!-- Password -->
        <div class="relative">
            <flux:input
                wire:model="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="__('Password')"
            />

            @if (Route::has('password.request'))
                <flux:link class="absolute right-0 top-0 text-sm" :href="route('password.request')" wire:navigate>
                    {{ __('Forgot your password?') }}
                </flux:link>
            @endif
        </div>

        <!-- Remember Me -->
        <flux:checkbox wire:model="remember" :label="__('Remember me')" />

        <div class="flex items-center justify-end">
            <flux:button variant="primary" type="submit" class="w-full">{{ __('Log in') }}</flux:button>
        </div>
    </form>

    @if (Route::has('register'))
{{--        <div class="space-x-1 text-center text-sm text-zinc-600 dark:text-zinc-400">--}}
{{--            {{ __('Don\'t have an account?') }}--}}
{{--            <flux:link :href="route('register')" wire:navigate>{{ __('Sign up') }}</flux:link>--}}
{{--        </div>--}}
    @endif
</div>
