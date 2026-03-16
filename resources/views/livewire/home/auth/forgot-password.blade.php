<?php

use App\Models\User;
use App\Models\PasswordResetRequest;
use App\Notifications\PasswordResetRequestNotification;
use App\Notifications\PasswordResetRequested;
use App\Services\NotificationRecipients;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Illuminate\Support\Str;

new #[Layout('components.layouts.tailwind-layout')] class extends Component {
    #[Validate('required|string|email|exists:users,email')]
    public string $email = '';

    public bool $submitted = false;

    public function sendPasswordReset(): void
    {
        $validated = $this->validate([
            'email' => 'required|string|email|exists:users,email',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if ($user) {
            $existingRequest = PasswordResetRequest::where('email', $validated['email'])
                ->where('status', 'pending')
                ->first();

            if ($existingRequest) {
                $this->addError('email', 'You already have a pending password reset request. Please wait for it to be approved.');
                return;
            }

            $resetRequest = PasswordResetRequest::create([
                'user_id' => $user->id,
                'email' => $validated['email'],
                'token' => Str::random(60),
                'status' => 'pending',
            ]);

            $user->notify(new PasswordResetRequestNotification($resetRequest));

            $recipients = (new NotificationRecipients())
                ->adminsForChapter($user->chapter_id);

            foreach ($recipients as $recipient) {
                $recipient->notify(new PasswordResetRequested($user->id, $user->email));
            }

            $this->submitted = true;
            $this->reset('email');
        }
    }
}; ?>

<div class="mx-auto flex min-h-[70vh] w-full max-w-xl items-center px-4 py-10 sm:px-6 lg:px-8">
    <section class="w-full rounded-3xl border border-blue-100 bg-white p-6 shadow-[0_24px_60px_-40px_rgba(37,99,235,0.45)] sm:p-8">
        @if ($submitted)
            <div class="text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                    <i class="fas fa-check"></i>
                </div>
                <h1 class="mt-4 text-2xl font-bold text-slate-900">Check Your Email</h1>
                <p class="mt-3 text-sm leading-relaxed text-slate-600">
                    We sent a password recovery request. Our team will review it and send your reset link once approved.
                </p>
                <p class="mt-2 text-sm text-slate-500">Please check your inbox and spam folder.</p>
                <a href="{{ route('home.login') }}" wire:navigate class="mt-6 inline-flex rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                    Back to Login
                </a>
            </div>
        @else
            <header class="mb-6 text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-blue-600">Account Recovery</p>
                <h1 class="mt-3 text-3xl font-bold text-slate-900">Reset Password</h1>
                <p class="mt-2 text-sm text-slate-600">Enter your email and we will send a recovery request.</p>
            </header>

            @if ($errors->any())
                <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <p class="font-semibold">Please fix the following:</p>
                    <ul class="mt-2 list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mb-5 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                Your request will be reviewed and approved before password reset.
            </div>

            <form wire:submit="sendPasswordReset" class="space-y-4">
                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-slate-700">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        placeholder="your.email@example.com"
                        wire:model="email"
                        autofocus
                        autocomplete="email"
                        class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                    >
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
                    <span wire:loading.remove>Send Recovery Request</span>
                    <span wire:loading>Sending...</span>
                </button>
            </form>

            <div class="mt-5 text-center">
                <a href="{{ route('home.login') }}" wire:navigate class="text-sm font-semibold text-blue-700 hover:text-blue-800">Back to Login</a>
            </div>
        @endif
    </section>
</div>
