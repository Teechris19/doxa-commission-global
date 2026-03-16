<?php

use App\Models\{PasswordResetRequest, User};
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.tailwind-layout')] class extends Component {
    use Interactions;

    public $token;
    public $email;
    public $password;
    public $password_confirmation;
    public $resetRequest;

    public function mount($token)
    {
        $this->token = $token;

        $this->resetRequest = PasswordResetRequest::where('token', $token)
            ->where('status', 'approved')
            ->first();

        if (!$this->resetRequest) {
            abort(404, 'Invalid or expired reset link');
        }

        $this->email = $this->resetRequest->email;
    }

    public function resetPassword()
    {
        $this->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::where('email', $this->email)->first();

        if (!$user) {
            $this->addError('email', 'User not found');
            return;
        }

        $user->update([
            'password' => Hash::make($this->password),
        ]);

        $this->resetRequest->update([
            'status' => 'used',
            'used_at' => now(),
        ]);

        session()->flash('status', 'Password has been reset successfully! You can now login with your new password.');
        return redirect()->route('home.login');
    }
}; ?>

<div class="mx-auto flex min-h-[70vh] w-full max-w-xl items-center px-4 py-10 sm:px-6 lg:px-8">
    <section class="w-full rounded-3xl border border-blue-100 bg-white p-6 shadow-[0_24px_60px_-40px_rgba(37,99,235,0.45)] sm:p-8">
        <header class="mb-6 text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-blue-600">Account Security</p>
            <h1 class="mt-3 text-3xl font-bold text-slate-900">Reset Your Password</h1>
            <p class="mt-2 text-sm text-slate-600">Set a strong new password for your account.</p>
        </header>

        <form wire:submit="resetPassword" class="space-y-4">
            <div>
                <label for="email" class="mb-2 block text-sm font-medium text-slate-700">Email Address</label>
                <input
                    type="email"
                    id="email"
                    class="w-full rounded-xl border border-blue-100 bg-slate-50 px-4 py-3 text-sm text-slate-500"
                    value="{{ $email }}"
                    readonly
                    disabled
                >
            </div>

            <div>
                <label for="password" class="mb-2 block text-sm font-medium text-slate-700">New Password</label>
                <input
                    type="password"
                    id="password"
                    wire:model="password"
                    placeholder="Enter new password"
                    required
                    class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                >
                @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-slate-500">Minimum 8 characters</p>
            </div>

            <div>
                <label for="password_confirmation" class="mb-2 block text-sm font-medium text-slate-700">Confirm New Password</label>
                <input
                    type="password"
                    id="password_confirmation"
                    wire:model="password_confirmation"
                    placeholder="Confirm new password"
                    required
                    class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                >
                @error('password_confirmation') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700" wire:loading.attr="disabled">
                <span wire:loading.remove>Reset Password</span>
                <span wire:loading>Resetting...</span>
            </button>
        </form>
    </section>
</div>
