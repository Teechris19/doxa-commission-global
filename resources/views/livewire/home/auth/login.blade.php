<?php

use App\Models\Chapter;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.tailwind-layout')] class extends Component {

    public string $mode = 'login';
    
    // Login fields
    public string $email = '';
    public string $password = '';
    public string $chapter = '';
    public bool $remember = false;

    // Registration fields
    public string $registerName = '';
    public string $registerEmail = '';
    public string $registerPassword = '';
    public string $registerPasswordConfirmation = '';
    public string $registerChapter = '';
    public string $registerPhone = '';
    public string $registerGender = '';
    public string $registerDob = '';
    public string $registerAddress = '';
    public string $registerCity = '';
    public string $registerState = '';
    public string $registerCountry = '';

    public array $chapters = [];

    public function mount(): void
    {
        $this->chapters = Chapter::orderBy('name')
            ->pluck('name', 'name')
            ->toArray();
    }

    public function switchMode(string $newMode): void
    {
        $this->mode = $newMode;
        $this->resetValidation();
    }

    public function login(): void
    {
        $this->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string|min:6',
            'chapter'  => 'required|string|exists:chapters,name',
        ]);

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        $user = Auth::user()->loadMissing('chapter');

        if (! $user->chapter || $user->chapter->name !== $this->chapter) {
            Auth::logout();
            Session::invalidate();
            Session::regenerateToken();
            throw ValidationException::withMessages(['chapter' => 'You do not have access to this chapter.']);
        }

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        $redirectUrl = route('home', absolute: false);
        if ($this->chapter) {
            $redirectUrl .= '?chapter=' . urlencode($this->chapter);
        }

        $this->redirect($redirectUrl);
    }

    public function register(): void
    {
        $this->validate([
            'registerName' => 'required|string|max:255',
            'registerEmail' => 'required|string|email|max:255|unique:users,email',
            'registerPassword' => 'required|string|min:6',
            'registerPasswordConfirmation' => 'required|string|min:6|same:registerPassword',
            'registerChapter' => 'required|string|exists:chapters,name',
            'registerPhone' => 'required|string|max:20',
            'registerGender' => 'required|string|max:20',
            'registerDob' => 'nullable|date',
            'registerAddress' => 'nullable|string|max:255',
            'registerCity' => 'nullable|string|max:100',
            'registerState' => 'nullable|string|max:100',
            'registerCountry' => 'nullable|string|max:100',
        ]);

        $chapter = Chapter::where('name', $this->registerChapter)->firstOrFail();

        $user = \App\Models\User::create([
            'name' => $this->registerName,
            'email' => $this->registerEmail,
            'password' => Hash::make($this->registerPassword),
            'chapter_id' => $chapter->id,
        ]);

        $user->profile()->create([
            'phone' => $this->registerPhone ?: null,
            'gender' => $this->registerGender ?: null,
            'date_of_birth' => $this->registerDob ?: null,
            'address' => $this->registerAddress ?: null,
            'city' => $this->registerCity ?: null,
            'state' => $this->registerState ?: null,
            'country' => $this->registerCountry ?: null,
            'chapter_id' => $chapter->id,
        ]);

        Auth::login($user);
        Session::regenerate();

        $this->redirect(route('home', ['chapter' => $this->registerChapter]));
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
                
                <!-- Mode Toggle -->
                <div class="mb-8 flex rounded-2xl bg-slate-100 p-1">
                    <button
                        type="button"
                        wire:click="switchMode('login')"
                        @class([
                            'flex-1 rounded-xl px-4 py-2.5 text-sm font-semibold transition',
                            'bg-white text-blue-700 shadow' => $mode === 'login',
                            'text-slate-600 hover:text-slate-900' => $mode !== 'login',
                        ])
                    >
                        Sign In
                    </button>
                    <button
                        type="button"
                        wire:click="switchMode('register')"
                        @class([
                            'flex-1 rounded-xl px-4 py-2.5 text-sm font-semibold transition',
                            'bg-white text-blue-700 shadow' => $mode === 'register',
                            'text-slate-600 hover:text-slate-900' => $mode !== 'register',
                        ])
                    >
                        Create Account
                    </button>
                </div>

                @if($mode === 'login')
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
                @else
                    <div class="mb-8 text-center">
                        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-green-100 text-green-700 shadow-inner">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M12 3a4 4 0 100 8 4 4 0 000-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <p class="text-xs uppercase tracking-[0.3em] text-green-700">Join Us</p>
                        <h1 class="mt-2 text-3xl font-semibold text-slate-900">Create your account</h1>
                        <p class="mt-2 text-sm text-slate-600">Select your chapter and register to continue.</p>
                    </div>
                @endif

                @if (session('status'))
                    <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                @if($mode === 'login')
                    <!-- Login Form -->
                    <form wire:submit="login">
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

                        <button
                            type="submit"
                            class="group inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-blue-600 via-sky-500 to-cyan-500 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-500/30 transition hover:-translate-y-0.5 hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-blue-200 disabled:cursor-not-allowed disabled:opacity-70"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove.delay>Sign In</span>
                            <span wire:loading.delay>Signing in...</span>
                        </button>
                    </form>
                @else
                    <!-- Registration Form -->
                    <form wire:submit="register">
                        <div class="mb-5">
                            <label for="registerChapter" class="block text-sm font-medium text-slate-700">Select Chapter</label>
                            <select
                                id="registerChapter"
                                class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition focus:border-green-400 focus:outline-none focus:ring-4 focus:ring-green-100 @error('registerChapter') border-rose-300 focus:border-rose-400 focus:ring-rose-100 @enderror"
                                wire:model="registerChapter"
                                required
                            >
                                <option value="">-- Select a Chapter --</option>
                                @foreach ($chapters as $name)
                                    <option value="{{ $name }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('registerChapter')
                                <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-5">
                            <label for="registerName" class="block text-sm font-medium text-slate-700">Full Name <span class="text-red-500">*</span></label>
                            <input
                                type="text"
                                id="registerName"
                                class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition focus:border-green-400 focus:outline-none focus:ring-4 focus:ring-green-100 @error('registerName') border-rose-300 focus:border-rose-400 focus:ring-rose-100 @enderror"
                                wire:model="registerName"
                                placeholder="Enter your full name"
                                required
                            >
                            @error('registerName')
                                <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-5">
                            <label for="registerPhone" class="block text-sm font-medium text-slate-700">Phone Number <span class="text-red-500">*</span></label>
                            <input
                                type="tel"
                                id="registerPhone"
                                class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition focus:border-green-400 focus:outline-none focus:ring-4 focus:ring-green-100 @error('registerPhone') border-rose-300 focus:border-rose-400 focus:ring-rose-100 @enderror"
                                wire:model="registerPhone"
                                placeholder="e.g., 0801 234 5678"
                                required
                            >
                            @error('registerPhone')
                                <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div class="mb-5">
                                <label for="registerGender" class="block text-sm font-medium text-slate-700">Gender <span class="text-red-500">*</span></label>
                                <select
                                    id="registerGender"
                                    class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition focus:border-green-400 focus:outline-none focus:ring-4 focus:ring-green-100 @error('registerGender') border-rose-300 focus:border-rose-400 focus:ring-rose-100 @enderror"
                                    wire:model="registerGender"
                                    required
                                >
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                                @error('registerGender')
                                    <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mb-5">
                                <label for="registerDob" class="block text-sm font-medium text-slate-700">Date of Birth</label>
                                <input
                                    type="date"
                                    id="registerDob"
                                    class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition focus:border-green-400 focus:outline-none focus:ring-4 focus:ring-green-100"
                                    wire:model="registerDob"
                                >
                            </div>
                        </div>

                        <div class="mb-5">
                            <label for="registerAddress" class="block text-sm font-medium text-slate-700">Address</label>
                            <input
                                type="text"
                                id="registerAddress"
                                class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition focus:border-green-400 focus:outline-none focus:ring-4 focus:ring-green-100"
                                wire:model="registerAddress"
                                placeholder="Enter your address"
                            >
                        </div>

                        <div class="mb-5">
                            <label for="registerEmail" class="block text-sm font-medium text-slate-700">Email Address <span class="text-red-500">*</span></label>
                            <input
                                type="email"
                                id="registerEmail"
                                class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition focus:border-green-400 focus:outline-none focus:ring-4 focus:ring-green-100 @error('registerEmail') border-rose-300 focus:border-rose-400 focus:ring-rose-100 @enderror"
                                wire:model="registerEmail"
                                placeholder="your.email@example.com"
                                required
                            >
                            @error('registerEmail')
                                <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                            <div class="mb-5">
                                <label for="registerCity" class="block text-sm font-medium text-slate-700">City</label>
                                <input type="text" id="registerCity" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition focus:border-green-400 focus:outline-none focus:ring-4 focus:ring-green-100" wire:model="registerCity" placeholder="City">
                            </div>
                            <div class="mb-5">
                                <label for="registerState" class="block text-sm font-medium text-slate-700">State</label>
                                <input type="text" id="registerState" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition focus:border-green-400 focus:outline-none focus:ring-4 focus:ring-green-100" wire:model="registerState" placeholder="State">
                            </div>
                            <div class="mb-5">
                                <label for="registerCountry" class="block text-sm font-medium text-slate-700">Country</label>
                                <input type="text" id="registerCountry" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition focus:border-green-400 focus:outline-none focus:ring-4 focus:ring-green-100" wire:model="registerCountry" placeholder="Country">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div class="mb-5 relative">
                                <label for="registerPassword" class="block text-sm font-medium text-slate-700">Password</label>
                                <input
                                    type="password"
                                    id="registerPassword"
                                    class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 pr-12 text-sm text-slate-700 shadow-sm transition focus:border-green-400 focus:outline-none focus:ring-4 focus:ring-green-100 @error('registerPassword') border-rose-300 focus:border-rose-400 focus:ring-rose-100 @enderror"
                                    wire:model="registerPassword"
                                    placeholder="Create a password"
                                    required
                                >
                                <button type="button" onclick="togglePassword('registerPassword')" class="absolute right-4 top-9 text-slate-400 hover:text-slate-600">
                                    <svg id="eye-registerPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                                @error('registerPassword')
                                    <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mb-5 relative">
                                <label for="registerPasswordConfirmation" class="block text-sm font-medium text-slate-700">Confirm Password</label>
                                <input
                                    type="password"
                                    id="registerPasswordConfirmation"
                                    class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 pr-12 text-sm text-slate-700 shadow-sm transition focus:border-green-400 focus:outline-none focus:ring-4 focus:ring-green-100 @error('registerPasswordConfirmation') border-rose-300 focus:border-rose-400 focus:ring-rose-100 @enderror"
                                    wire:model="registerPasswordConfirmation"
                                    placeholder="Confirm your password"
                                    required
                                >
                                <button type="button" onclick="togglePassword('registerPasswordConfirmation')" class="absolute right-4 top-9 text-slate-400 hover:text-slate-600">
                                    <svg id="eye-registerPasswordConfirmation" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                                @error('registerPasswordConfirmation')
                                    <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <button
                            type="submit"
                            class="group inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-green-600 via-emerald-500 to-teal-500 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-green-500/30 transition hover:-translate-y-0.5 hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-green-200 disabled:cursor-not-allowed disabled:opacity-70"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove.delay>Create Account</span>
                            <span wire:loading.delay>Creating account...</span>
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const input = document.getElementById(fieldId);
    const eye = document.getElementById('eye-' + fieldId);
    if (input.type === 'password') {
        input.type = 'text';
        eye.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>';
    } else {
        input.type = 'password';
        eye.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
    }
}
</script>
