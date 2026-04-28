<?php

use App\Models\{AboutUs, ChurchLeader, Conclave, Chapter, Pastor, ServiceTime, CtaSection};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.tailwind-layout')] class extends Component {
    public $aboutUs;
    public $leaders;
    public $pastors;
    public $sundayServices;
    public $thursdayServices;
    public $ctaSection;
    public $conclaves;
    public $conclavesPreviewCount = 6;
    public $selectedConclave = null;

    public function mount()
    {
        $chapterName = request()->query('chapter', 'Calabar Branch');
        $chapter = Chapter::where('name', $chapterName)->first();
        
        // If chapter not found by name, try to get the first chapter
        if (!$chapter) {
            $chapter = Chapter::first();
        }

        if ($chapter) {
            // Load About Us
            $this->aboutUs = AboutUs::where('chapter_id', $chapter->id)
                ->where('is_active', true)
                ->first();
            
            // Debug: Log what we found
            \Log::info('About Page Load', [
                'chapter' => $chapter->name,
                'chapter_id' => $chapter->id,
                'aboutUs' => $this->aboutUs ? $this->aboutUs->toArray() : null,
            ]);

            // Load Church Leaders (legacy - keep for backward compatibility)
            $this->leaders = ChurchLeader::where('chapter_id', $chapter->id)
                ->where('is_active', true)
                ->orderBy('order_column')
                ->get();

            // Load Pastors (new system)
            $this->pastors = Pastor::where('chapter_id', $chapter->id)
                ->where('is_active', true)
                ->orderBy('order_column')
                ->get();

            // Load Service Times
            $services = ServiceTime::where('chapter_id', $chapter->id)
                ->where('is_active', true)
                ->orderBy('order_column')
                ->get();

            $this->sundayServices = $services->where('category', 'sunday');
            $this->thursdayServices = $services->where('category', 'thursday');

            // Load CTA Section
            $this->ctaSection = CtaSection::where('chapter_id', $chapter->id)
                ->where('is_active', true)
                ->first();

            // Load Conclaves preview count
            if ($this->aboutUs) {
                $this->conclavesPreviewCount = $this->aboutUs->conclaves_preview_count ?? 6;
            }
        } else {
            $this->aboutUs = null;
            $this->leaders = collect();
            $this->pastors = collect();
            $this->sundayServices = collect();
            $this->thursdayServices = collect();
            $this->ctaSection = null;
        }

        // Load all active conclaves
        $this->conclaves = Conclave::where('is_active', true)->get();
    }

    public function selectConclave($id)
    {
        $this->selectedConclave = Conclave::find($id);
    }
}; ?>

<div class="bg-white pb-10">
    {{-- Hero Section --}}
    <section class="border-b border-blue-100" 
        @if($aboutUs?->hero_background_image)
            style="background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('{{ Storage::url($aboutUs->hero_background_image) }}'); background-size: cover; background-position: center;"
        @else
            style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"
        @endif
    >
        <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8 lg:py-20">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-blue-200">About Doxa</p>
            <h1 class="mt-3 text-3xl font-semibold text-white sm:text-4xl">
                {{ $aboutUs?->hero_title ?: 'Welcome to Doxa Church' }}
            </h1>
            <p class="mt-4 max-w-3xl text-base text-blue-100">
                {{ $aboutUs?->hero_subtitle ?: 'A place where faith, hope, and love come together.' }}
            </p>
        </div>
    </section>

    @if($aboutUs && ($aboutUs->hero_image || $aboutUs->description))
        {{-- Who We Are Section --}}
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
        {{-- Our Foundation Section --}}
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


    {{-- Our Pastor Section (New System) --}}
    @if($pastors && $pastors->count() > 0)
        <section class="border-y border-blue-100 bg-blue-50/40 py-12">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <h2 class="text-center text-2xl font-semibold text-slate-900">Our Pastor</h2>
                <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($pastors as $pastor)
                        <article class="overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-sm">
                            @if($pastor->image)
                                <img src="{{ Storage::url($pastor->image) }}" alt="{{ $pastor->name }}" class="h-56 w-full object-cover">
                            @else
                                <img src="https://via.placeholder.com/600x400?text={{ urlencode($pastor->name) }}" alt="{{ $pastor->name }}" class="h-56 w-full object-cover">
                            @endif
                            <div class="p-5">
                                <h3 class="text-lg font-semibold text-slate-900">{{ $pastor->name }}</h3>
                                <p class="text-sm text-blue-700">{{ $pastor->title ?? 'Pastor' }}</p>
                                @if($pastor->description)
                                    <p class="mt-3 text-sm leading-7 text-slate-600">{{ Str::limit($pastor->description, 140) }}</p>
                                @endif
                                <div class="mt-4 flex flex-wrap gap-2 text-xs">
                                    @if($pastor->facebook_url)
                                        <a href="{{ $pastor->facebook_url }}" target="_blank" class="rounded-full border border-blue-200 px-3 py-1.5 text-blue-700 transition hover:border-blue-300 hover:bg-blue-50">Facebook</a>
                                    @endif
                                    @if($pastor->twitter_url)
                                        <a href="{{ $pastor->twitter_url }}" target="_blank" class="rounded-full border border-blue-200 px-3 py-1.5 text-blue-700 transition hover:border-blue-300 hover:bg-blue-50">X</a>
                                    @endif
                                    @if($pastor->instagram_url)
                                        <a href="{{ $pastor->instagram_url }}" target="_blank" class="rounded-full border border-blue-200 px-3 py-1.5 text-blue-700 transition hover:border-blue-300 hover:bg-blue-50">Instagram</a>
                                    @endif
                                    @if($pastor->youtube_url)
                                        <a href="{{ $pastor->youtube_url }}" target="_blank" class="rounded-full border border-blue-200 px-3 py-1.5 text-blue-700 transition hover:border-blue-300 hover:bg-blue-50">YouTube</a>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Service Times Section --}}
    @if($sundayServices->count() > 0 || $thursdayServices->count() > 0)
        <section class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
            <h2 class="text-center text-2xl font-semibold text-slate-900">Service Times</h2>
            <div class="mx-auto mt-8 grid max-w-3xl gap-4">
                @if($sundayServices->count() > 0)
                    <article class="rounded-2xl border border-blue-100 bg-white p-6 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-600">Sunday Services</p>
                        <div class="mt-3 space-y-2">
                            @foreach($sundayServices as $service)
                                <p class="text-sm text-slate-700">{{ $service->service_name }}: {{ $service->time }}</p>
                            @endforeach
                        </div>
                    </article>
                @endif
                @if($thursdayServices->count() > 0)
                    <article class="rounded-2xl border border-blue-100 bg-white p-6 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-600">Thursday Services</p>
                        <div class="mt-3 space-y-2">
                            @foreach($thursdayServices as $service)
                                <p class="text-sm text-slate-700">{{ $service->service_name }}: {{ $service->time }}</p>
                            @endforeach
                        </div>
                    </article>
                @endif
            </div>
        </section>
    @endif

    {{-- Conclaves Preview Section --}}
    @if($conclaves->count() > 0)
        <section class="border-y border-blue-100 bg-blue-50/40 py-12">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <h2 class="text-2xl font-semibold text-slate-900">Our Conclaves</h2>
                    <p class="text-sm text-slate-500">Select a conclave to view details.</p>
                </div>
                <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($conclaves->take($conclavesPreviewCount) as $conclave)
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
                @if($conclaves->count() > $conclavesPreviewCount)
                    <div class="mt-8 text-center">
                        <a href="{{ route('conclaves.index') }}" class="inline-flex items-center rounded-full bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
                            View All Conclaves
                        </a>
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- Join Community CTA Section --}}
    @if($ctaSection)
        <section class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="rounded-3xl border border-blue-200 bg-blue-600 px-6 py-10 text-center text-white sm:px-10">
                <h2 class="text-2xl font-semibold">{{ $ctaSection->title }}</h2>
                <p class="mt-3 text-sm text-blue-100">{{ $ctaSection->description }}</p>
                <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                    <a href="{{ $ctaSection->button_link }}" class="rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-blue-700 transition hover:bg-blue-50">{{ $ctaSection->button_text }}</a>
                    <a href="{{ route('home') }}" wire:navigate class="rounded-full border border-blue-200 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-500">Get Connected</a>
                </div>
            </div>
        </section>
    @else
        {{-- Default CTA if none configured --}}
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
    @endif

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

                    @if($selectedConclave->whatsapp_link)
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">WhatsApp</p>
                            <a href="{{ $selectedConclave->whatsapp_link }}" target="_blank" class="mt-2 inline-flex items-center gap-1 text-sm text-green-600 hover:text-green-700">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                                Join WhatsApp Group
                            </a>
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
