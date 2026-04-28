<?php

use App\Models\{Chapter, Events};
use Livewire\Attributes\{Layout, Url};
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.tailwind-layout')] class extends Component {
    use WithPagination;

    public $chapters;
    public $selectedEvent;

    #[Url(keep: true)]
    public $sc = 0;

    public function mount()
    {
        $this->chapters = Chapter::orderBy('name')->get();

        if (! is_numeric($this->sc)) {
            $this->sc = 0;
        }
    }

    public function with(): array
    {
        $chapterId = (int) $this->sc;
        $query = Events::with('chapter', 'accounts')->latest('start_at');

        if ($chapterId !== 0) {
            $query->where('chapter_id', $chapterId);
        }

        return [
            'events' => $query->paginate(6),
        ];
    }

    public function filterChapter($id)
    {
        $this->sc = (int) $id;
        $this->resetPage();
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

<div class="bg-white pb-20">
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
                        'rounded-full px-5 py-2.5 text-xs font-semibold uppercase tracking-[0.2em] transition shadow-sm',
                        'bg-blue-600 text-white' => (int) $sc === 0,
                        'bg-white border border-blue-200 text-blue-700 hover:bg-blue-50' => (int) $sc !== 0,
                    ])
                >
                    All Events
                </button>

                @foreach ($chapters as $chapter)
                    <button
                        type="button"
                        wire:click="filterChapter({{ $chapter->id }})"
                        @class([
                            'rounded-full px-5 py-2.5 text-xs font-semibold uppercase tracking-[0.2em] transition shadow-sm',
                            'bg-blue-600 text-white' => (int) $sc === (int) $chapter->id,
                            'bg-white border border-blue-200 text-blue-700 hover:bg-blue-50' => (int) $sc !== (int) $chapter->id,
                        ])
                    >
                        {{ $chapter->name }}
                    </button>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 pt-10 sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-col gap-1 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
            <p class="font-medium">{{ $events->total() }} {{ \Illuminate\Support\Str::plural('event', $events->total()) }} found</p>
            <p wire:loading wire:target="filterChapter" class="text-blue-600 animate-pulse">Updating events...</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @forelse ($events as $event)
                <article class="group flex flex-col overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-sm transition duration-300 hover:shadow-xl hover:-translate-y-1">
                    {{-- Large visible image --}}
                    <div class="relative h-64 w-full overflow-hidden bg-blue-50">
                        @if($event->banner)
                            <img src="{{ Storage::url($event->banner) }}" alt="{{ $event->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                        @else
                            <div class="flex h-full w-full flex-col items-center justify-center text-blue-200">
                                <i class="fas fa-calendar-alt text-5xl mb-3"></i>
                                <span class="text-[0.65rem] font-bold uppercase tracking-[0.2em] text-blue-400">Doxa Event</span>
                            </div>
                        @endif

                        @if($event->chapter)
                            <span class="absolute left-4 top-4 rounded-full bg-blue-600/90 backdrop-blur-sm px-3 py-1.5 text-[0.6rem] font-bold uppercase tracking-widest text-white shadow-sm">
                                <i class="fas fa-map-marker-alt mr-1 text-[0.5rem]"></i>
                                {{ $event->chapter->name }}
                            </span>
                        @endif
                    </div>

                    <div class="flex flex-1 flex-col p-6 lg:p-7">
                        <div class="flex-1">
                            <h2 class="text-xl font-bold text-slate-900 leading-tight group-hover:text-blue-600 transition">{{ $event->title }}</h2>
                            
                            <div class="mt-4 space-y-2.5">
                                <div class="flex items-center text-sm text-slate-600 gap-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                                        <i class="far fa-calendar-alt"></i>
                                    </div>
                                    <span>{{ \Carbon\Carbon::parse($event->start_at)->format('M d, Y') }}</span>
                                </div>
                                <div class="flex items-center text-sm text-slate-600 gap-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                                        <i class="far fa-clock"></i>
                                    </div>
                                    <span>
                                        {{ \Carbon\Carbon::parse($event->start_at)->format('h:i A') }}
                                        @if($event->end_at)
                                            - {{ \Carbon\Carbon::parse($event->end_at)->format('h:i A') }}
                                        @endif
                                    </span>
                                </div>
                                <div class="flex items-center text-sm text-slate-600 gap-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </div>
                                    <span class="truncate">{{ $event->location ?? 'Doxa Cosmos' }}</span>
                                </div>
                            </div>

                            <p class="mt-5 text-sm leading-relaxed text-slate-500 line-clamp-3 italic">
                                "{{ \Illuminate\Support\Str::limit($event->description, 120) }}"
                            </p>
                        </div>

                        <div class="mt-8 flex flex-wrap gap-2 border-t border-slate-50 pt-6">
                            <button
                                type="button"
                                wire:click="openEventModal({{ $event->id }})"
                                class="flex-1 inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-[0.65rem] font-bold uppercase tracking-[0.15em] text-white transition hover:bg-slate-800 shadow-sm"
                            >
                                Details
                            </button>

                            @if($event->registration_required && $event->form_schema)
                                <a
                                    href="{{ route('events.register', ['event_id' => $event->id]) }}"
                                    class="flex-1 inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-[0.65rem] font-bold uppercase tracking-[0.15em] text-white transition hover:bg-blue-700 shadow-sm"
                                >
                                    Register
                                </a>
                            @endif

                            @if($event->requires_partners && $event->isPartnershipOpen())
                                <a
                                    href="{{ route('home.partnership.index') }}?chapter={{ $event->chapter?->name }}"
                                    class="w-full inline-flex items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-[0.65rem] font-bold uppercase tracking-[0.15em] text-emerald-700 transition hover:bg-emerald-100"
                                >
                                    <i class="fas fa-hand-holding-heart mr-2"></i>
                                    Partner
                                </a>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="col-span-full rounded-3xl border-2 border-dashed border-blue-100 bg-blue-50/30 px-6 py-20 text-center">
                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-blue-100 text-blue-500 mb-4">
                        <i class="fas fa-calendar-times text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800">No events found</h3>
                    <p class="mt-2 text-slate-500">Try selecting a different chapter or check back later.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-12">
            {{ $events->links() }}
        </div>

        <section class="mt-20 rounded-[2.5rem] border border-blue-100 bg-blue-600 px-6 py-12 text-center text-white sm:px-12">
            <div class="max-w-2xl mx-auto">
                <h3 class="text-3xl font-bold">Never Miss an Event</h3>
                <p class="mt-3 text-lg text-blue-100 opacity-90 leading-relaxed">Follow our social pages for event reminders and live updates.</p>
                <div class="mt-8 flex flex-wrap justify-center gap-4">
                    <a href="https://www.facebook.com/DoxaCommissionGlobal/" target="_blank" rel="noopener noreferrer" class="rounded-2xl bg-white px-8 py-3 text-sm font-bold uppercase tracking-[0.15em] text-blue-700 transition hover:bg-blue-50 shadow-lg">Facebook</a>
                    <a href="https://x.com" target="_blank" rel="noopener noreferrer" class="rounded-2xl border-2 border-white/30 bg-white/10 px-8 py-3 text-sm font-bold uppercase tracking-[0.15em] text-white transition hover:bg-white/20 backdrop-blur-sm shadow-lg">X / Twitter</a>
                </div>
            </div>
        </section>
    </section>

    @if($selectedEvent)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/70 backdrop-blur-sm p-4" wire:click="closeEventModal">
            <article class="w-full max-w-3xl h-[90vh] flex flex-col overflow-hidden rounded-[2rem] border border-blue-100 bg-white shadow-2xl transition-all" wire:click.stop>
                {{-- Banner stays at the top --}}
                <div class="relative h-64 sm:h-72 w-full flex-none bg-blue-50">
                    @if($selectedEvent->banner)
                        <img src="{{ Storage::url($selectedEvent->banner) }}" alt="{{ $selectedEvent->title }}" class="h-full w-full object-cover">
                    @endif
                    <button
                        type="button"
                        wire:click="closeEventModal"
                        class="absolute right-4 top-4 h-10 w-10 rounded-full bg-black/20 text-white backdrop-blur-md transition hover:bg-black/40 flex items-center justify-center"
                    >
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                {{-- Content area is scrollable --}}
                <div class="flex-1 overflow-y-auto p-8 lg:p-10 custom-scrollbar">
                    <div class="flex flex-wrap items-center gap-3 mb-4">
                        <span class="rounded-full bg-blue-100 px-4 py-1.5 text-[0.65rem] font-bold uppercase tracking-widest text-blue-700">Event Details</span>
                        @if($selectedEvent->chapter)
                            <span class="rounded-full bg-slate-100 px-4 py-1.5 text-[0.65rem] font-bold uppercase tracking-widest text-slate-600">{{ $selectedEvent->chapter->name }}</span>
                        @endif
                    </div>

                    <h3 class="text-3xl font-bold text-slate-900 leading-tight">{{ $selectedEvent->title }}</h3>

                    <div class="mt-8 grid gap-6 text-sm text-slate-600 sm:grid-cols-2">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 text-lg">
                                <i class="far fa-calendar-alt"></i>
                            </div>
                            <div>
                                <p class="text-[0.6rem] font-bold uppercase tracking-widest text-slate-400">Date</p>
                                <p class="text-base font-semibold text-slate-800">{{ \Carbon\Carbon::parse($selectedEvent->start_at)->format('F d, Y') }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 text-lg">
                                <i class="far fa-clock"></i>
                            </div>
                            <div>
                                <p class="text-[0.6rem] font-bold uppercase tracking-widest text-slate-400">Time</p>
                                <p class="text-base font-semibold text-slate-800">
                                    {{ \Carbon\Carbon::parse($selectedEvent->start_at)->format('h:i A') }}
                                    @if($selectedEvent->end_at)
                                        - {{ \Carbon\Carbon::parse($selectedEvent->end_at)->format('h:i A') }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 text-lg">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div>
                                <p class="text-[0.6rem] font-bold uppercase tracking-widest text-slate-400">Location</p>
                                <p class="text-base font-semibold text-slate-800">{{ $selectedEvent->location ?? 'Doxa Cosmos' }}</p>
                            </div>
                        </div>
                        @if($selectedEvent->is_online)
                            <div class="flex items-center gap-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 text-lg">
                                    <i class="fas fa-video"></i>
                                </div>
                                <div>
                                    <p class="text-[0.6rem] font-bold uppercase tracking-widest text-slate-400">Accessibility</p>
                                    <p class="text-base font-semibold text-slate-800">Online Event</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="mt-10 pt-10 border-t border-slate-100">
                        <p class="text-[0.6rem] font-bold uppercase tracking-widest text-blue-600 mb-3">About the Event</p>
                        <p class="text-base leading-relaxed text-slate-600 whitespace-pre-wrap">{{ $selectedEvent->description }}</p>
                    </div>

                    <div class="mt-10 flex flex-wrap gap-4">
                        @if($selectedEvent->registration_required && $selectedEvent->form_schema)
                            <a href="{{ route('events.register', ['event_id' => $selectedEvent->id]) }}" class="flex-1 inline-flex items-center justify-center rounded-2xl bg-blue-600 px-8 py-4 text-sm font-bold uppercase tracking-[0.1em] text-white transition hover:bg-blue-700 shadow-xl shadow-blue-200">
                                Register Now
                            </a>
                        @endif

                        @if($selectedEvent->requires_partners && $selectedEvent->isPartnershipOpen())
                            <a href="{{ route('home.partnership.index') }}?chapter={{ $selectedEvent->chapter?->name }}" class="flex-1 inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-8 py-4 text-sm font-bold uppercase tracking-[0.1em] text-white transition hover:bg-emerald-700 shadow-xl shadow-emerald-200">
                                <i class="fas fa-hand-holding-heart mr-2"></i>
                                Partner
                            </a>
                        @endif
                    </div>
                </div>
            </article>
        </div>
    @endif
</div>
