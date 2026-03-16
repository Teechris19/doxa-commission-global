<?php

use App\Models\ProfileChangeRequest;
use App\Models\User;
use App\Notifications\ProfileChangeRequestSubmitted;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.user')] class extends Component {
    use Interactions;

    public string $requested_changes = '';

    public function submitRequest(): void
    {
        $this->validate([
            'requested_changes' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $user = Auth::user();

        $changeRequest = ProfileChangeRequest::create([
            'user_id' => $user->id,
            'chapter_id' => $user->chapter_id,
            'requested_changes' => $this->requested_changes,
            'status' => 'pending',
        ]);

        $admins = User::role(['admin'])
            ->when($user->chapter_id, fn($q) => $q->where('chapter_id', $user->chapter_id))
            ->get()
            ->merge(User::role(['super-admin'])->get())
            ->unique('id');

        foreach ($admins as $admin) {
            $admin->notify(new ProfileChangeRequestSubmitted($changeRequest));
        }

        $this->requested_changes = '';

        $this->toast()
            ->success('Request sent', 'Your change request has been submitted.')
            ->send();
    }

    public function with(): array
    {
        $user = Auth::user();

        return [
            'user' => $user,
            'profile' => $user->profile,
            'requests' => ProfileChangeRequest::where('user_id', $user->id)
                ->latest()
                ->take(6)
                ->get(),
        ];
    }
}; ?>

<div class="bg-gradient-to-b from-slate-50 via-white to-slate-100">
    <div class="mx-auto w-full max-w-6xl px-6 pb-16 pt-10">
        <div class="mb-10 flex flex-col gap-2">
            <p class="text-xs font-semibold uppercase tracking-[0.35em] text-sky-600">Your Account</p>
            <h1 class="text-3xl font-semibold text-slate-900 sm:text-4xl">Profile Overview</h1>
            <p class="text-sm text-slate-600">Review your information and request changes if anything is incorrect.</p>
        </div>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-[0_20px_40px_-30px_rgba(15,23,42,0.45)]">
                    <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-4">
                            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-sky-100 text-sky-700 text-xl font-semibold">
                                {{ $user->initials() }}
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold text-slate-900">{{ $user->name }}</h2>
                                <p class="text-sm text-slate-500">{{ $user->email }}</p>
                            </div>
                        </div>
                        <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-sky-700">
                            {{ $user->chapter?->name ?? 'No chapter' }}
                        </div>
                    </div>

                    <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">First Name</p>
                            <p class="mt-2 text-sm font-medium text-slate-900">{{ $profile?->first_name ?? 'Not set' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Last Name</p>
                            <p class="mt-2 text-sm font-medium text-slate-900">{{ $profile?->last_name ?? 'Not set' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Phone</p>
                            <p class="mt-2 text-sm font-medium text-slate-900">{{ $profile?->phone ?? 'Not set' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Secondary Phone</p>
                            <p class="mt-2 text-sm font-medium text-slate-900">{{ $profile?->secondary_phone ?? 'Not set' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Address</p>
                            <p class="mt-2 text-sm font-medium text-slate-900">{{ $profile?->address ?? 'Not set' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">City / State</p>
                            <p class="mt-2 text-sm font-medium text-slate-900">
                                {{ trim(($profile?->city ?? '') . ' ' . ($profile?->state ?? '')) ?: 'Not set' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Country</p>
                            <p class="mt-2 text-sm font-medium text-slate-900">{{ $profile?->country ?? 'Not set' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Occupation</p>
                            <p class="mt-2 text-sm font-medium text-slate-900">{{ $profile?->occupation ?? 'Not set' }}</p>
                        </div>
                    </div>
                </div>

                <div class="mt-8 rounded-3xl border border-slate-200 bg-white p-8 shadow-[0_20px_40px_-30px_rgba(15,23,42,0.45)]">
                    <h3 class="text-lg font-semibold text-slate-900">Request a Profile Change</h3>
                    <p class="mt-2 text-sm text-slate-600">
                        List any corrections you need. A team will review and update your record.
                    </p>

                    <form wire:submit="submitRequest" class="mt-6 space-y-4">
                        <div>
                            <label class="text-sm font-medium text-slate-700" for="requested_changes">Describe the changes</label>
                            <textarea
                                id="requested_changes"
                                class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                                rows="5"
                                wire:model.defer="requested_changes"
                                placeholder="Example: Update phone number to (555) 555-0123 and change address to 24 Garden Way."
                                required
                            ></textarea>
                            @error('requested_changes')
                                <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center rounded-2xl bg-gradient-to-r from-sky-600 via-sky-500 to-blue-500 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-sky-500/30 transition hover:-translate-y-0.5 hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-sky-200"
                        >
                            Submit Request
                        </button>
                    </form>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-[0_20px_40px_-30px_rgba(15,23,42,0.45)]">
                    <h3 class="text-lg font-semibold text-slate-900">Recent Requests</h3>
                    <p class="mt-2 text-sm text-slate-600">Track the latest changes you have requested.</p>

                    <div class="mt-5 space-y-4">
                        @forelse ($requests as $request)
                            <div class="rounded-2xl border border-slate-200 px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    {{ $request->created_at->toFormattedDateString() }}
                                </p>
                                <p class="mt-2 text-sm text-slate-700">
                                    {{ \Illuminate\Support\Str::limit($request->requested_changes, 140) }}
                                </p>
                                <span class="mt-3 inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                    {{ ucfirst($request->status) }}
                                </span>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-500">
                                No change requests yet.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-3xl border border-sky-200 bg-sky-50 p-6 text-sm text-sky-700">
                    Need to update sensitive details? Submit a request and your chapter admin will follow up.
                </div>
            </div>
        </div>
    </div>
</div>
