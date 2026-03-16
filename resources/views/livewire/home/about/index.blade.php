<?php

use App\Models\{AboutUs, ChurchLeader, Conclave, Chapter};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.tailwind-layout')] class extends Component {
    public $aboutUs;
    public $leaders;
    public $conclaves;
    public $selectedConclave = null;

    public function mount()
    {
        $chapterName = request()->query('chapter', 'default');
        $chapter = Chapter::where('name', $chapterName)->first();

        if ($chapter) {
            $this->aboutUs = AboutUs::where('chapter_id', $chapter->id)
                ->where('is_active', true)
                ->first();

            $this->leaders = ChurchLeader::where('chapter_id', $chapter->id)
                ->where('is_active', true)
                ->orderBy('order_column')
                ->get();
        } else {
            $this->aboutUs = null;
            $this->leaders = collect();
        }

        $this->conclaves = Conclave::where('is_active', true)->get();
    }

    public function selectConclave($id)
    {
        $this->selectedConclave = Conclave::find($id);
    }
}; ?>

<div class="bg-white pb-10">
    <section class="border-b border-blue-100 bg-gradient-to-b from-blue-50 to-white">
        <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8 lg:py-20">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-blue-600">About Doxa</p>
            <h1 class="mt-3 text-3xl font-semibold text-slate-900 sm:text-4xl">A church family rooted in Christ and mission.</h1>
            <p class="mt-4 max-w-3xl text-base text-slate-600">
                {{ $aboutUs?->title ?: 'Welcome to Doxa Church. We are committed to worship, discipleship, and community impact.' }}
            </p>
        </div>
    </section>

    @if($aboutUs)
        <section class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid gap-8 lg:grid-cols-2 lg:items-center">
                <div class="overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-sm">
                    @if($aboutUs->hero_image)
                        <img src="{{ Storage::url($aboutUs->hero_image) }}" alt="Church" class="h-full w-full object-cover">
                    @else
                        <img src="https://images.unsplash.com/photo-1475721027785-f74eccf877e2?auto=format&fit=crop&w=1200&q=80" alt="Church" class="h-full w-full object-cover">
                    @endif
                </div>
                <div>
                    <h2 class="text-2xl font-semibold text-slate-900">Who We Are</h2>
                    <div class="mt-4 max-h-[26rem] space-y-4 overflow-y-auto pr-2 text-sm leading-7 text-slate-600">
                        {!! nl2br(e($aboutUs->description)) !!}
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if($aboutUs && ($aboutUs->mission || $aboutUs->vision || $aboutUs->core_values))
        <section class="border-y border-blue-100 bg-blue-50/40 py-12">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <h2 class="text-center text-2xl font-semibold text-slate-900">Our Foundation</h2>
                <div class="mt-8 grid gap-4 md:grid-cols-3">
                    @if($aboutUs->mission)
                        <article class="rounded-2xl border border-blue-100 bg-white p-6 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-600">Mission</p>
                            <p class="mt-3 text-sm leading-7 text-slate-600">{{ $aboutUs->mission }}</p>
                        </article>
                    @endif

                    @if($aboutUs->vision)
                        <article class="rounded-2xl border border-blue-100 bg-white p-6 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-600">Vision</p>
                            <p class="mt-3 text-sm leading-7 text-slate-600">{{ $aboutUs->vision }}</p>
                        </article>
                    @endif

                    @if($aboutUs->core_values)
                        <article class="rounded-2xl border border-blue-100 bg-white p-6 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-600">Core Values</p>
                            <p class="mt-3 text-sm leading-7 text-slate-600">{{ $aboutUs->core_values }}</p>
                        </article>
                    @endif
                </div>
            </div>
        </section>
    @endif

    @if($aboutUs && $aboutUs->history_timeline)
        <section class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
            <h2 class="text-center text-2xl font-semibold text-slate-900">Our Journey</h2>
            <div class="mx-auto mt-8 max-w-3xl border-l-2 border-blue-200 pl-6">
                @foreach($aboutUs->history_timeline as $event)
                    <div class="relative mb-8">
                        <span class="absolute -left-[1.95rem] top-1 h-3.5 w-3.5 rounded-full border-2 border-white bg-blue-600"></span>
                        <p class="text-sm font-semibold text-blue-700">{{ $event['year'] }}</p>
                        <p class="mt-1 text-sm leading-7 text-slate-600">{{ $event['event'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if($leaders->count() > 0)
        <section class="border-y border-blue-100 bg-blue-50/40 py-12">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <h2 class="text-center text-2xl font-semibold text-slate-900">Our Leadership</h2>
                <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($leaders as $leader)
                        <article class="overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-sm">
                            @if($leader->photo)
                                <img src="{{ Storage::url($leader->photo) }}" alt="{{ $leader->name }}" class="h-56 w-full object-cover">
                            @else
                                <img src="https://via.placeholder.com/600x400?text={{ urlencode($leader->name) }}" alt="{{ $leader->name }}" class="h-56 w-full object-cover">
                            @endif
                            <div class="p-5">
                                <h3 class="text-lg font-semibold text-slate-900">{{ $leader->name }}</h3>
                                <p class="text-sm text-blue-700">{{ $leader->position }}</p>
                                @if($leader->bio)
                                    <p class="mt-3 text-sm leading-7 text-slate-600">{{ Str::limit($leader->bio, 140) }}</p>
                                @endif
                                <div class="mt-4 flex flex-wrap gap-2 text-xs">
                                    @if($leader->facebook_url)
                                        <a href="{{ $leader->facebook_url }}" target="_blank" class="rounded-full border border-blue-200 px-3 py-1.5 text-blue-700 transition hover:border-blue-300 hover:bg-blue-50">Facebook</a>
                                    @endif
                                    @if($leader->twitter_url)
                                        <a href="{{ $leader->twitter_url }}" target="_blank" class="rounded-full border border-blue-200 px-3 py-1.5 text-blue-700 transition hover:border-blue-300 hover:bg-blue-50">X</a>
                                    @endif
                                    @if($leader->instagram_url)
                                        <a href="{{ $leader->instagram_url }}" target="_blank" class="rounded-full border border-blue-200 px-3 py-1.5 text-blue-700 transition hover:border-blue-300 hover:bg-blue-50">Instagram</a>
                                    @endif
                                    @if($leader->linkedin_url)
                                        <a href="{{ $leader->linkedin_url }}" target="_blank" class="rounded-full border border-blue-200 px-3 py-1.5 text-blue-700 transition hover:border-blue-300 hover:bg-blue-50">LinkedIn</a>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
        <h2 class="text-center text-2xl font-semibold text-slate-900">Service Times</h2>
        <div class="mx-auto mt-8 grid max-w-3xl gap-4">
            <article class="rounded-2xl border border-blue-100 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-600">Sunday Service</p>
                <p class="mt-3 text-sm text-slate-700">First Service: 7:00 AM - 9:00 AM</p>
                <p class="text-sm text-slate-700">Second Service: 9:30 AM - 11:30 AM</p>
            </article>
            <article class="rounded-2xl border border-blue-100 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-600">Thursday Service</p>
                <p class="mt-3 text-sm text-slate-700">Bible Study: 6:00 PM - 8:00 PM</p>
            </article>
        </div>
    </section>

    @if($conclaves->count() > 0)
        <section class="border-y border-blue-100 bg-blue-50/40 py-12">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <h2 class="text-2xl font-semibold text-slate-900">Our Conclaves</h2>
                    <p class="text-sm text-slate-500">Select a conclave to view details.</p>
                </div>
                <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($conclaves as $conclave)
                        <article class="overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-sm">
                            @if($conclave->image)
                                <img src="{{ Storage::url($conclave->image) }}" alt="{{ $conclave->name }}" class="h-48 w-full object-cover">
                            @endif
                            <div class="p-5">
                                <h3 class="text-lg font-semibold text-slate-900">{{ $conclave->name }}</h3>
                                <p class="mt-1 text-sm text-slate-600">{{ $conclave->location }}</p>
                                @if($conclave->description)
                                    <p class="mt-3 text-sm leading-7 text-slate-600">{{ Str::limit($conclave->description, 120) }}</p>
                                @endif
                                <button
                                    type="button"
                                    wire:click="selectConclave({{ $conclave->id }})"
                                    class="mt-4 inline-flex items-center rounded-full bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-white transition hover:bg-blue-700"
                                >
                                    View Details
                                </button>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="rounded-3xl border border-blue-200 bg-blue-600 px-6 py-10 text-center text-white sm:px-10">
            <h2 class="text-2xl font-semibold">Join Our Community</h2>
            <p class="mt-3 text-sm text-blue-100">Experience the love of Christ in a welcoming environment.</p>
            <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route('home') }}" wire:navigate class="rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-blue-700 transition hover:bg-blue-50">Visit Us</a>
                <a href="{{ route('home') }}" wire:navigate class="rounded-full border border-blue-200 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-500">Get Connected</a>
            </div>
        </div>
    </section>

    @if($selectedConclave)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" wire:click="$set('selectedConclave', null)">
            <div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-3xl border border-blue-100 bg-white p-6 shadow-2xl" wire:click.stop>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-2xl font-semibold text-slate-900">{{ $selectedConclave->name }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $selectedConclave->location }}</p>
                    </div>
                    <button type="button" wire:click="$set('selectedConclave', null)" class="rounded-full border border-blue-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 transition hover:text-slate-700">Close</button>
                </div>

                @if($selectedConclave->image)
                    <img src="{{ Storage::url($selectedConclave->image) }}" alt="{{ $selectedConclave->name }}" class="mt-5 h-64 w-full rounded-2xl object-cover">
                @endif

                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    @if($selectedConclave->description)
                        <div class="sm:col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">About</p>
                            <p class="mt-2 text-sm leading-7 text-slate-600">{{ $selectedConclave->description }}</p>
                        </div>
                    @endif

                    @if($selectedConclave->address)
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Address</p>
                            <p class="mt-2 text-sm text-slate-600">{{ $selectedConclave->address }}</p>
                        </div>
                    @endif

                    @if($selectedConclave->phone)
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Phone</p>
                            <a href="tel:{{ $selectedConclave->phone }}" class="mt-2 inline-block text-sm text-slate-600 hover:text-blue-700">{{ $selectedConclave->phone }}</a>
                        </div>
                    @endif

                    @if($selectedConclave->email)
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Email</p>
                            <a href="mailto:{{ $selectedConclave->email }}" class="mt-2 inline-block text-sm text-slate-600 hover:text-blue-700">{{ $selectedConclave->email }}</a>
                        </div>
                    @endif
                </div>

                @if($selectedConclave->latitude && $selectedConclave->longitude)
                    <div class="mt-6 overflow-hidden rounded-2xl border border-blue-100">
                        <iframe
                            width="100%"
                            height="280"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            src="https://maps.google.com/maps?q={{ $selectedConclave->latitude }},{{ $selectedConclave->longitude }}&output=embed"
                        ></iframe>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
