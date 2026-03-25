<?php
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Url};
use App\Models\{Chapter, PrayerRequest};
use App\Notifications\PrayerRequestSubmitted;
use App\Services\NotificationRecipients;

new #[Layout('components.layouts.tailwind-layout')] class extends Component {
    public ?string $name = null;
    public ?string $email = null;

    #[Url]
    public $chapter;
    public $date,
        $request,
        $user = null,
        $appointmentTeams,
        $chapters,
        $selectedChapter,
        $currentChapter = null;

    public function mount()
    {
        if ($this->chapter != null) {
            $this->currentChapter = Chapter::where('name', $this->chapter)->first();
            if ($this->currentChapter == null) {
                abort(403, 'Invalid Chapter');
            }
            $this->selectedChapter = $this->currentChapter->id;
        }

        $this->chapters = Chapter::all()->toArray();
    }

    public function save()
    {
        $this->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'request' => 'required|string|max:1000',
            'selectedChapter' => 'required|exists:chapters,id',
        ]);

        $prayerRequest = new PrayerRequest();
        $prayerRequest->name = $this->name;
        $prayerRequest->email = $this->email;
        $prayerRequest->request = $this->request;
        $prayerRequest->chapter_id = $this->selectedChapter;
        $prayerRequest->save();

        $recipients = (new NotificationRecipients())
            ->forFunctionAndChapter('prayer_requests', $this->selectedChapter);

        foreach ($recipients as $recipient) {
            $recipient->notify(new PrayerRequestSubmitted($prayerRequest));
        }

        session()->flash('message', 'Prayer request submitted successfully.');
        $this->reset(['name', 'email', 'request', 'selectedChapter']);
    }
}; ?>

<div class="mx-auto w-full max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
    <section class="rounded-3xl border border-blue-100 bg-white p-6 shadow-[0_24px_60px_-40px_rgba(37,99,235,0.5)] sm:p-8">
        <header class="mb-8 text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-blue-600">Prayer Support</p>
            <h1 class="mt-3 text-3xl font-bold text-slate-900">Submit a Prayer Request</h1>
            <p class="mt-2 text-sm text-slate-600">Share your prayer needs and our team will stand with you.</p>
        </header>

        @if (session('message'))
            <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('message') }}
            </div>
        @endif

        <form class="space-y-5" wire:submit.prevent="save">
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label for="name" class="mb-2 block text-sm font-medium text-slate-700">Your Name (Optional)</label>
                    <input type="text" id="name" wire:model.live="name" placeholder="Name" class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-slate-700">Email (Optional)</label>
                    <input type="email" id="email" wire:model="email" placeholder="Email" class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="chapter" class="mb-2 block text-sm font-medium text-slate-700">Pick a Chapter</label>
                @if ($currentChapter != null)
                    <select id="chapter" class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-700" wire:model.live="selectedChapter" disabled>
                        <option value="{{ $currentChapter->id }}" selected>{{ $currentChapter->name }}</option>
                    </select>
                @else
                    <select id="chapter" class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-700" wire:model.live="selectedChapter">
                        <option value="">Select a chapter</option>
                        @foreach ($chapters as $chapter)
                            <option value="{{ $chapter['id'] }}">{{ $chapter['name'] }}</option>
                        @endforeach
                    </select>
                @endif
                @error('selectedChapter') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="request" class="mb-2 block text-sm font-medium text-slate-700">Your Request</label>
                <textarea id="request" rows="6" wire:model.live="request" class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900"></textarea>
                @error('request') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
                Send Request
            </button>
        </form>
    </section>
</div>
