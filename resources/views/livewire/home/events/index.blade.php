<?php

use App\Models\{Chapter, Events};
use Livewire\Attributes\{Layout, Url};
use Livewire\Volt\Component;

new #[Layout('components.layouts.tailwind-layout')] class extends Component {
    public $chapters;
    public $events;
    public $selectedEvent;

    #[Url(keep: true)]
    public $sc = 0;

    public function mount()
    {
        $this->chapters = Chapter::orderBy('name')->get();

        if (! is_numeric($this->sc)) {
            $this->sc = 0;
        }

        $this->loadEvents((int) $this->sc);
    }

    private function loadEvents(int $chapterId = 0): void
    {
        $query = Events::with('chapter', 'accounts')->latest('start_at');

        if ($chapterId !== 0) {
            $query->where('chapter_id', $chapterId);
        }

        $this->events = $query->get();
    }

    public function filterChapter($id)
    {
        $this->sc = (int) $id;
        $this->loadEvents($this->sc);
    }

    public function openEventModal($id)
    {
        $this->selectedEvent = Events::with('chapter')->findOrFail($id);
    }

    public function closeEventModal()
    {
        $this->selectedEvent = null;
    }
}; ?>

<div class="bg-white pb-12">
    <section class="border-b border-blue-100 bg-gradient-to-b from-blue-50 to-white">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8 lg:py-16">
            <div class="max-w-3xl">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-blue-600">Doxa Events</p>
                <h1 class="mt-3 text-3xl font-semibold text-slate-900 sm:text-4xl">Discover and join upcoming church events.</h1>
                <p class="mt-4 text-sm leading-7 text-slate-600">Stay connected with services, conferences, and community gatherings across all chapters.</p>
            </div>

            <div class="mt-8 flex flex-wrap gap-2">
                <button
                    type="button"
                    wire:click="filterChapter(0)"
                    @class([
                        'rounded-full px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] transition',
                        'bg-blue-600 text-white' => (int) $sc === 0,
                        'border border-blue-200 text-blue-700 hover:bg-blue-50' => (int) $sc !== 0,
                    ])
                >
                    All Events
                </button>

                @foreach ($chapters as $chapter)
                    <button
                        type="button"
                        wire:click="filterChapter({{ $chapter->id }})"
                        @class([
                            'rounded-full px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] transition',
                            'bg-blue-600 text-white' => (int) $sc === (int) $chapter->id,
                            'border border-blue-200 text-blue-700 hover:bg-blue-50' => (int) $sc !== (int) $chapter->id,
                        ])
                    >
                        {{ $chapter->name }}
                    </button>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
        <div class="mb-4 flex flex-col gap-1 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
            <p>{{ $events->count() }} {{ \Illuminate\Support\Str::plural('event', $events->count()) }} found</p>
            <p wire:loading wire:target="filterChapter" class="text-blue-600">Loading events...</p>
        </div>

        <div class="space-y-3">
            @forelse ($events as $event)
                <article class="overflow-hidden rounded-xl border border-blue-100 bg-white shadow-sm transition hover:shadow-md">
                    <div class="flex items-start">
                        <div class="relative w-24 flex-none self-start sm:w-28 md:w-40">
                            @if($event->chapter)
                                <span class="absolute right-1 top-1 hidden rounded-full bg-blue-600 px-2 py-1 text-[0.55rem] font-semibold uppercase tracking-[0.12em] text-white sm:inline-flex">{{ $event->chapter->name }}</span>
                            @endif

                        @if($event->banner)
                                <img src="{{ Storage::url($event->banner) }}" alt="{{ $event->title }}" class="h-24 w-full object-cover sm:h-28 md:h-32">
                        @else
                                <div class="flex h-24 w-full items-center justify-center bg-blue-50 sm:h-28 md:h-32">
                                    <span class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-blue-600">Doxa Event</span>
                                </div>
                        @endif
                        </div>

                        <div class="flex-1 space-y-2.5 p-3 sm:p-4">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <h2 class="text-base font-semibold text-slate-900 md:text-lg">{{ $event->title }}</h2>
                                <button
                                    type="button"
                                    wire:click="openEventModal({{ $event->id }})"
                                    class="inline-flex items-center rounded-full bg-blue-600 px-3 py-1.5 text-[0.65rem] font-semibold uppercase tracking-[0.18em] text-white transition hover:bg-blue-700"
                                >
                                    View Details
                                </button>
                            </div>

                            <div class="grid gap-1.5 text-xs text-slate-600 sm:grid-cols-2">
                                <p>
                                    <span class="font-medium text-slate-800">Date:</span>
                                    {{ \Carbon\Carbon::parse($event->start_at)->format('M d, Y') }}
                                </p>
                                <p>
                                    <span class="font-medium text-slate-800">Time:</span>
                                    {{ \Carbon\Carbon::parse($event->start_at)->format('h:i A') }}
                                    @if($event->end_at)
                                        - {{ \Carbon\Carbon::parse($event->end_at)->format('h:i A') }}
                                    @endif
                                    @if($event->timezone)
                                        ({{ $event->timezone }})
                                    @endif
                                </p>
                                <p class="sm:col-span-2">
                                    <span class="font-medium text-slate-800">Location:</span>
                                    {{ $event->location ?? 'Doxa Cosmos' }}
                                </p>
                            </div>

                            <p class="text-xs leading-5 text-slate-600">{{ \Illuminate\Support\Str::limit($event->description, 110) }}</p>

                            <div class="flex flex-wrap gap-1.5">
                                @if($event->registration_required && $event->form_schema)
                                    <a
                                        href="{{ route('events.register', ['event_id' => $event->id]) }}"
                                        class="inline-flex items-center rounded-full border border-blue-200 px-3 py-1.5 text-[0.65rem] font-semibold uppercase tracking-[0.18em] text-blue-700 transition hover:bg-blue-50"
                                    >
                                        Register
                                    </a>
                                @endif

                                @if($event->requires_partners && $event->isPartnershipOpen())
                                    <a
                                        href="{{ route('home.partnership.index') }}?chapter={{ $event->chapter?->name }}"
                                        class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-[0.65rem] font-semibold uppercase tracking-[0.18em] text-emerald-700 transition hover:bg-emerald-100"
                                    >
                                        <i class="fas fa-hand-holding-heart mr-1"></i>
                                        Partner
                                    </a>
                                @endif

                                @if($event->hasStarted())
                                    <a
                                        href="{{ route('events.gallery', ['event' => $event->slug ?? $event->id]) }}"
                                        class="inline-flex items-center rounded-full border border-blue-200 px-3 py-1.5 text-[0.65rem] font-semibold uppercase tracking-[0.18em] text-blue-700 transition hover:bg-blue-50"
                                    >
                                        Gallery
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-blue-200 bg-blue-50/40 px-6 py-14 text-center">
                    <h3 class="text-lg font-semibold text-slate-800">No events available</h3>
                    <p class="mt-2 text-sm text-slate-500">Try another chapter or check back soon.</p>
                </div>
            @endforelse
        </div>

        <section class="mt-10 rounded-3xl border border-blue-100 bg-blue-600 px-6 py-9 text-center text-white sm:px-10">
            <h3 class="text-2xl font-semibold">Never Miss an Event</h3>
            <p class="mt-2 text-sm text-blue-100">Follow our social pages for event reminders and live updates.</p>
            <div class="mt-5 flex flex-wrap justify-center gap-3">
                <a href="https://www.facebook.com/DoxaCommissionGlobal/" target="_blank" rel="noopener noreferrer" class="rounded-full bg-white px-5 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-blue-700 transition hover:bg-blue-50">Facebook</a>
                <a href="https://x.com" target="_blank" rel="noopener noreferrer" class="rounded-full border border-blue-200 px-5 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-white transition hover:bg-blue-500">X / Twitter</a>
            </div>
        </section>
    </section>

    @if($selectedEvent)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" wire:click="closeEventModal">
            <article class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-3xl border border-blue-100 bg-white p-6 shadow-2xl" wire:click.stop>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Event Details</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ $selectedEvent->title }}</h3>
                    </div>
                    <button
                        type="button"
                        wire:click="closeEventModal"
                        class="rounded-full border border-blue-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 transition hover:text-slate-700"
                    >
                        Close
                    </button>
                </div>

                @if($selectedEvent->banner)
                    <img src="{{ Storage::url($selectedEvent->banner) }}" alt="{{ $selectedEvent->title }}" class="mt-5 h-64 w-full rounded-2xl object-cover">
                @endif

                <div class="mt-6 grid gap-4 text-sm text-slate-600 sm:grid-cols-2">
                    <p><span class="font-semibold text-slate-800">Date:</span> {{ \Carbon\Carbon::parse($selectedEvent->start_at)->format('F d, Y') }}</p>
                    <p>
                        <span class="font-semibold text-slate-800">Time:</span>
                        {{ \Carbon\Carbon::parse($selectedEvent->start_at)->format('h:i A') }}
                        @if($selectedEvent->end_at)
                            - {{ \Carbon\Carbon::parse($selectedEvent->end_at)->format('h:i A') }}
                        @endif
                        @if($selectedEvent->timezone)
                            ({{ $selectedEvent->timezone }})
                        @endif
                    </p>
                    <p><span class="font-semibold text-slate-800">Location:</span> {{ $selectedEvent->location ?? 'Doxa Cosmos' }}</p>
                    <p><span class="font-semibold text-slate-800">Chapter:</span> {{ $selectedEvent->chapter->name ?? 'N/A' }}</p>
                    @if($selectedEvent->is_online)
                        <p class="sm:col-span-2"><span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">Online Event</span></p>
                    @endif
                    @if($selectedEvent->capacity)
                        <p class="sm:col-span-2"><span class="font-semibold text-slate-800">Capacity:</span> {{ $selectedEvent->capacity }} attendees</p>
                    @endif
                </div>

                <div class="mt-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Description</p>
                    <p class="mt-2 text-sm leading-7 text-slate-600">{{ $selectedEvent->description }}</p>
                </div>

                <div class="mt-6 flex flex-wrap gap-2">
                    @if($selectedEvent->registration_required && $selectedEvent->form_schema)
                        <a href="{{ route('events.register', ['event_id' => $selectedEvent->id]) }}" class="inline-flex items-center rounded-full bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-white transition hover:bg-blue-700">
                            Register Now
                        </a>
                    @endif

                    @if($selectedEvent->requires_partners && $selectedEvent->isPartnershipOpen())
                        <a href="{{ route('home.partnership.index') }}?chapter={{ $selectedEvent->chapter?->name }}" class="inline-flex items-center rounded-full bg-emerald-600 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-white transition hover:bg-emerald-700">
                            <i class="fas fa-hand-holding-heart mr-2"></i>
                            Partner for this Event
                        </a>
                        @if($selectedEvent->partnership_description)
                            <p class="mt-3 w-full rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-800">
                                <i class="fas fa-info-circle mr-1"></i>
                                {{ $selectedEvent->partnership_description }}
                            </p>
                        @endif
                        @if($selectedEvent->partnership_deadline)
                            <p class="mt-2 w-full text-xs text-emerald-700">
                                <i class="fas fa-clock mr-1"></i>
                                Partnership deadline: {{ $selectedEvent->partnership_deadline->format('F d, Y h:i A') }}
                            </p>
                        @endif
                    @endif

                    @if($selectedEvent->hasStarted())
                        <a href="{{ route('events.gallery', ['event' => $selectedEvent->slug ?? $selectedEvent->id]) }}" class="inline-flex items-center rounded-full border border-blue-200 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-blue-700 transition hover:bg-blue-50">
                            View Gallery
                        </a>
                    @endif
                </div>
            </article>
        </div>
    @endif
</div>
