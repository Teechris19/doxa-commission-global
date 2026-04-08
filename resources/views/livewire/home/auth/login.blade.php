<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.tailwind-layout')] class extends Component {

    public string $email = '';
    public string $password = '';
    public string $chapter = '';
    public bool $remember = false;

    public array $chapters = [];

    public function mount(): void
    {
        // Load all chapter names
        $this->chapters = \App\Models\Chapter::orderBy('name')
            ->pluck('name', 'name')
            ->toArray();
    }

    public function login(): void
    {
        // 1. Validate inputs
        $this->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string|min:6',
            'chapter'  => 'required|string|exists:chapters,name',
        ]);

        // 2. Rate limiting
        $this->ensureIsNotRateLimited();

        // 3. Attempt authentication
        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // 4. Load user with chapter relationship
        $user = Auth::user()->loadMissing('chapter');

        // 5. Validate user belongs to selected chapter
        if (! $user->chapter || $user->chapter->name !== $this->chapter) {
            Auth::logout();
            Session::invalidate();
            Session::regenerateToken();

            throw ValidationException::withMessages([
                'chapter' => 'You do not have access to this chapter.',
            ]);
        }

        // 6. Success: clear rate limiter
        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        // 7. Redirect with chapter param
        $redirectUrl = route('home', absolute: false);
        if ($this->chapter) {
            $redirectUrl .= '?chapter=' . urlencode($this->chapter);
        }

        $this->redirect($redirectUrl);
    }

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

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email) . '|' . request()->ip());
    }
};
?>

<div class="min-h-screen bg-[#f3f6ff]">
    <div class="relative overflow-hidden">
        <div class="absolute inset-0">
            <div class="absolute -left-40 top-[-8rem] h-[26rem] w-[26rem] rounded-full bg-gradient-to-br from-sky-300 via-sky-200 to-transparent opacity-70 blur-3xl"></div>
            <div class="absolute right-[-10rem] top-[6rem] h-[24rem] w-[24rem] rounded-full bg-gradient-to-tr from-blue-300 via-cyan-200 to-transparent opacity-70 blur-3xl"></div>
        </div>
        <div class="relative mx-auto flex min-h-screen w-full max-w-6xl items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
            <div class="w-full max-w-2xl rounded-3xl border border-blue-200/60 bg-white/85 p-8 shadow-[0_25px_60px_-35px_rgba(15,23,42,0.45)] backdrop-blur sm:p-10">
                <div class="mb-8 text-center">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-100 text-blue-700 shadow-inner">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 3v18M6 9h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <p class="text-xs uppercase tracking-[0.3em] text-blue-700">Welcome Back</p>
                    <h1 class="mt-2 text-3xl font-semibold text-slate-900">Sign in to your church account</h1>
                    <p class="mt-2 text-sm text-slate-600">Choose your chapter and continue to your dashboard.</p>
                </div>

            <!-- Header -->
            @if (session('status'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            <!-- Login Form -->
            <form wire:submit="login">
                {{-- @csrf --}}

                <!-- Chapter Select -->
                <div class="mb-5">
                    <label for="chapter" class="block text-sm font-medium text-slate-700">Select Chapter</label>
                    <select
                        id="chapter"
                        class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition focus:border-blue-400 focus:outline-none focus:ring-4 focus:ring-blue-100 @error('chapter') border-rose-300 focus:border-rose-400 focus:ring-rose-100 @enderror"
                        wire:model.live="chapter"
                        required
                        autofocus
                    >
                        <option value="">-- Select a Chapter --</option>
                        @foreach ($chapters as $name)
                            <option value="{{ $name }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('chapter')
                        <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div class="mb-5">
                    <label for="email" class="block text-sm font-medium text-slate-700">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition focus:border-blue-400 focus:outline-none focus:ring-4 focus:ring-blue-100 @error('email') border-rose-300 focus:border-rose-400 focus:ring-rose-100 @enderror"
                        wire:model.live="email"
                        placeholder="your.email@example.com"
                        autocomplete="email"
                        required
                    >
                    @error('email')
                        <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div class="mb-5">
                    <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                    <input
                        type="password"
                        id="password"
                        class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition focus:border-blue-400 focus:outline-none focus:ring-4 focus:ring-blue-100 @error('password') border-rose-300 focus:border-rose-400 focus:ring-rose-100 @enderror"
                        wire:model.live="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >
                    @error('password')
                        <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remember & Forgot -->
                <div class="mb-6 flex flex-wrap items-center justify-between gap-4 text-sm text-slate-600">
                    <label class="flex cursor-pointer items-center gap-2">
                        <input
                            type="checkbox"
                            class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-200"
                            id="remember"
                            wire:model="remember"
                        >
                        <span>Remember me</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="font-medium text-blue-700 transition hover:text-blue-800" wire:navigate>
                            Forgot Password?
                        </a>
                    @endif
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    class="group inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-blue-600 via-sky-500 to-cyan-500 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-500/30 transition hover:-translate-y-0.5 hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-blue-200 disabled:cursor-not-allowed disabled:opacity-70"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove.delay>Sign In</span>
                    <span wire:loading.delay>Signing in...</span>
                </button>
            </form>

                <div class="mt-8 border-t border-slate-200/80 pt-6 text-center text-xs text-slate-500">
                    Need help? Contact your chapter admin for access.
                </div>
            </div>
        </div>
    </div>
</div>
