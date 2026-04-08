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

        $user = Auth::user();
        if (! $user || ! $user->hasRole('super-admin')) {
            Auth::logout();
            Session::invalidate();
            Session::regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Only super admins can sign in here.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        $this->redirectIntended(default: route('admin.super-admin.dashboard', absolute: false), navigate: true);
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

<div class="flex flex-col gap-8">
    <div class="space-y-2 text-center">
        <p class="text-[10px] font-bold uppercase tracking-[0.5em] text-sky-600">Secure Access</p>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">Welcome Back</h1>
        <p class="mx-auto max-w-xs text-sm text-slate-500">Please enter your credentials to access the super admin portal.</p>
    </div>

    @if (session('status'))
        <div class="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 shadow-sm">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" wire:submit="login" class="space-y-6">
        <div class="space-y-4">
            <flux:input
                wire:model="email"
                :label="__('Email address')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="admin@example.com"
                variant="outline"
                class="bg-slate-50/50"
            />

            <div class="space-y-1">
                <flux:input
                    wire:model="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Enter your password')"
                    viewable
                    variant="outline"
                    class="bg-slate-50/50"
                />
            </div>
        </div>

        <div class="flex items-center justify-between">
            <label class="flex cursor-pointer items-center gap-2 group">
                <div class="relative flex items-center">
                    <input type="checkbox" class="peer h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500/20 transition-all cursor-pointer" wire:model="remember" />
                </div>
                <span class="text-sm font-medium text-slate-600 group-hover:text-slate-900 transition-colors">Remember me</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm font-semibold text-sky-600 hover:text-sky-700 transition-colors" wire:navigate>
                    Forgot password?
                </a>
            @endif
        </div>

        <flux:button type="submit" variant="primary" class="w-full h-11 bg-sky-600 hover:bg-sky-700 shadow-lg shadow-sky-600/20 transition-all active:scale-[0.98]">
            <span class="text-sm font-bold">{{ __('Sign In to Dashboard') }}</span>
        </flux:button>
    </form>

    <div class="relative mt-2">
        <div class="absolute inset-0 flex items-center" aria-hidden="true">
            <div class="w-full border-t border-slate-100"></div>
        </div>
        <div class="relative flex justify-center text-xs uppercase tracking-widest">
            <span class="bg-white px-4 text-slate-400 font-medium">Restricted Area</span>
        </div>
    </div>

    <p class="text-center text-[11px] leading-relaxed text-slate-400">
        This is a protected system. Unauthorized access attempts are logged and monitored.
    </p>
</div>
