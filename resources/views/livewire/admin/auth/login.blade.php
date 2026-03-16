<?php

use App\Models\Chapter;
use App\Models\TeamFunction;
use App\Models\AppointmentTeams;
use App\Models\PrayerRequestTeam;
use App\Models\BelieversAcademyTeams;
use App\Models\EventTeam;
use App\Models\User;
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

    public array $chapters = [];

    #[Validate('required|exists:chapters,id')]
    public ?int $chapter_id = null;

    public function mount()
    {
        $this->chapters = Chapter::orderBy('name')->get(['id', 'name'])->toArray();
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login()
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (!Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $user = User::where('email', $this->email)->first();
        $chapter = Chapter::find($this->chapter_id);

        if (! $user || ! $chapter) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // Super admins can access all chapters
        if (! $user->hasRole(['super-admin', 'admin', 'team-lead', 'lead_assist'])) {
            Auth::logout();
            Session::invalidate();
            Session::regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Admin access is required for this login.',
            ]);
        }

        // Skip chapter validation for super admins
        if (! $user->hasRole('super-admin')) {
            if ((int) $user->chapter_id !== (int) $this->chapter_id) {
                Auth::logout();
                Session::invalidate();
                Session::regenerateToken();

                throw ValidationException::withMessages([
                    'chapter_id' => 'You do not have access to this chapter.',
                ]);
            }
        }

        RateLimiter::clear($this->throttleKey());
        session()->regenerate();

        $chapterName = $chapter->name;

        // Super admins go to super admin dashboard
        if ($user->hasRole('super-admin')) {
            return redirect()->intended(route('admin.super-admin.dashboard'));
        }

        if ($user->hasRole(['team-lead', 'lead-assist', 'lead_assist'])) {
            $leadersTeam = $user->teams->firstWhere(fn($team) => in_array($team->pivot->role_in_team, ['team-lead', 'lead-assist', 'lead_assist']));
            $teamFunctions = $leadersTeam
                ? TeamFunction::where('team_id', $leadersTeam->id)->first()
                : null;
            $functionMap = $teamFunctions?->function ?? [];

            $relationTeams = [
                'appointments' => AppointmentTeams::where('chapter_id', $chapter->id)->pluck('team_id')->all(),
                'prayer_requests' => PrayerRequestTeam::where('chapter_id', $chapter->id)->pluck('team_id')->all(),
                'believers_academy' => BelieversAcademyTeams::where('chapter_id', $chapter->id)->pluck('team_id')->all(),
                'events' => EventTeam::where('chapter_id', $chapter->id)->pluck('team_id')->all(),
            ];

            $functionRoutes = [
                'transport' => 'admin.dashboard.transport.index',
                'appointments' => 'admin.dashboard.appointments.index',
                'prayer_requests' => 'admin.dashboard.prayer_requests.index',
                'team_settings' => 'admin.dashboard.settings.team-functions',
                'system_settings' => 'admin.dashboard.settings.index',
                'partnerships' => 'admin.dashboard.partnership.intents',
                'believers_academy' => 'admin.dashboard.believers_class.academy',
                'reports' => 'admin.dashboard.reports.index',
                'analytics' => 'admin.dashboard.analytics.index',
                'attendance' => 'admin.dashboard.attendance.manage',
                'events' => 'admin.dashboard.events.index',
                'media' => 'admin.dashboard.sermons.index',
            ];

            $relationKeys = ['appointments', 'prayer_requests', 'believers_academy', 'events'];
            $allowedRoutes = [];

            foreach ($functionRoutes as $key => $routeName) {
                $allowed = false;

                if (in_array($key, $relationKeys, true)) {
                    $allowed = $leadersTeam && in_array($leadersTeam->id, $relationTeams[$key] ?? [], true);
                } else {
                    $allowed = (bool) ($functionMap[$key] ?? false);
                }

                if ($allowed) {
                    $allowedRoutes[] = $routeName;
                }
            }

            $allowedRoutes[] = 'admin.dashboard.members';
            $allowedRoutes[] = 'admin.dashboard.reports.index';

            foreach ($allowedRoutes as $routeName) {
                if (Route::has($routeName)) {
                    return redirect()->intended(route($routeName, ['chapter' => $chapterName]));
                }
            }
        }

        return redirect()->intended(url('/admin/dashboard?chapter=' . $chapterName));
    }



    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
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
        return Str::transliterate(Str::lower($this->email) . '|' . request()->ip());
    }
}; ?>

<div class="min-h-screen bg-[#f5f2ec]">
    <div class="relative overflow-hidden">
        <div class="absolute inset-0">
            <div class="absolute -left-40 top-[-9rem] h-[26rem] w-[26rem] rounded-full bg-gradient-to-br from-emerald-200 via-emerald-100 to-transparent opacity-60 blur-3xl"></div>
            <div class="absolute right-[-11rem] top-[7rem] h-[24rem] w-[24rem] rounded-full bg-gradient-to-tr from-amber-200 via-amber-100 to-transparent opacity-60 blur-3xl"></div>
        </div>
        <div class="relative mx-auto flex min-h-screen w-full max-w-6xl items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
            <div class="w-full max-w-xl rounded-3xl border border-emerald-200/60 bg-white/85 p-8 shadow-[0_25px_60px_-35px_rgba(15,23,42,0.45)] backdrop-blur sm:p-10">
                <div class="mb-8 text-center">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 shadow-inner">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 3v18M6 9h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <p class="text-xs uppercase tracking-[0.3em] text-emerald-700">Admin Access</p>
                    <h1 class="mt-2 text-3xl font-semibold text-slate-900">Chapter Admin Sign In</h1>
                    <p class="mt-2 text-sm text-slate-600">Select your chapter and continue to the admin dashboard.</p>
                </div>

                @if (session('status'))
                    <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" wire:submit="login" class="space-y-5">
                    @csrf
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700">Email Address</label>
                        <input
                            id="email"
                            type="email"
                            class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition focus:border-emerald-400 focus:outline-none focus:ring-4 focus:ring-emerald-100 @error('email') border-rose-300 focus:border-rose-400 focus:ring-rose-100 @enderror"
                            wire:model="email"
                            required
                            autofocus
                            autocomplete="email"
                            placeholder="admin@example.com"
                        />
                        @error('email')
                            <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                        <input
                            id="password"
                            type="password"
                            class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition focus:border-emerald-400 focus:outline-none focus:ring-4 focus:ring-emerald-100 @error('password') border-rose-300 focus:border-rose-400 focus:ring-rose-100 @enderror"
                            wire:model="password"
                            required
                            autocomplete="current-password"
                            placeholder="Enter your password"
                        />
                        @error('password')
                            <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="chapter_id" class="block text-sm font-medium text-slate-700">Chapter</label>
                        <select
                            id="chapter_id"
                            class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition focus:border-emerald-400 focus:outline-none focus:ring-4 focus:ring-emerald-100 @error('chapter_id') border-rose-300 focus:border-rose-400 focus:ring-rose-100 @enderror"
                            wire:model="chapter_id"
                            required
                        >
                            <option value="">Select a chapter</option>
                            @foreach ($chapters as $chapter)
                                <option value="{{ $chapter['id'] }}">{{ $chapter['name'] }}</option>
                            @endforeach
                        </select>
                        @error('chapter_id')
                            <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-4 text-sm text-slate-600">
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-200" wire:model="remember" />
                            <span>Remember me</span>
                        </label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="font-medium text-emerald-700 transition hover:text-emerald-800" wire:navigate>
                                Forgot Password?
                            </a>
                        @endif
                    </div>

                    <button type="submit" class="w-full rounded-2xl bg-gradient-to-r from-emerald-600 via-emerald-500 to-lime-500 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-500/30 transition hover:-translate-y-0.5 hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-emerald-200" wire:loading.attr="disabled">
                        <span wire:loading.remove.delay>Sign In</span>
                        <span wire:loading.delay>Signing in...</span>
                    </button>
                </form>

                <div class="mt-8 border-t border-slate-200/80 pt-6 text-center text-xs text-slate-500">
                    Super admins can sign in here or at the main portal.
                </div>
            </div>
        </div>
    </div>
</div>
