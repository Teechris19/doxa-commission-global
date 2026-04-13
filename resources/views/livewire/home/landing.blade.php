<?php

use App\Models\{Chapter, LandingPageSetting, Testimony, PastorSetting, Conclave};
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.tailwind-layout')] class extends Component {
    use WithFileUploads;

    public $landing = null;
    public ?Chapter $activeChapter = null;
    public array $heroSection = [];
    public array $services = [];
    public $testimonies;
    public $pastorSettings = null;
    public $conclaves;
    public $selectedConclave = null;

    // Testimony form fields
    public $name = '';
    public $email = '';
    public $testimony = '';
    public $image;

    public function selectConclave($id): void
    {
        $this->selectedConclave = \App\Models\Conclave::find($id);
    }

    public function mount(): void
    {
        $this->activeChapter = $this->resolveChapterContext();
        $this->landing = $this->resolveLandingSetting($this->activeChapter?->id);

        $this->heroSection = $this->buildHeroSection($this->landing?->hero_section ?? []);
        $this->services = $this->buildServices($this->landing?->services ?? []);

        $limit = max(1, (int) ($this->landing?->number_of_testimonies ?? 5));
        $this->testimonies = Testimony::where('status', 'approved')
            ->latest()
            ->take($limit)
            ->get();

        $conclaveLimit = max(0, (int) ($this->landing?->number_of_conclaves ?? 3));
        if ($conclaveLimit > 0) {
            $this->conclaves = Conclave::active()
                ->latest()
                ->take($conclaveLimit)
                ->get();
        } else {
            $this->conclaves = collect();
        }

        $this->pastorSettings = PastorSetting::where('chapter_id', $this->activeChapter?->id)
            ->orWhereNull('chapter_id')
            ->where('is_active', true)
            ->first();
    }

    protected function resolveChapterContext(): ?Chapter
    {
        $requestedChapter = request()->query('chapter');

        if ($requestedChapter) {
            $chapter = Chapter::where('name', $requestedChapter)->first();
            if ($chapter) {
                return $chapter;
            }
        }

        $user = auth()->user();
        if ($user?->chapter_id) {
            return Chapter::find($user->chapter_id);
        }

        return null;
    }

    protected function resolveLandingSetting(?int $chapterId)
    {
        if (!Schema::hasColumn('landing_page_settings', 'chapter_id')) {
            return LandingPageSetting::first();
        }

        // Get global settings (chapter_id = null)
        $globalSetting = LandingPageSetting::whereNull('chapter_id')->first();

        // If no chapter context, return global
        if (!$chapterId) {
            return $globalSetting ?? LandingPageSetting::first();
        }

        // Get chapter-specific settings
        $chapterSetting = LandingPageSetting::where('chapter_id', $chapterId)->first();

        // If no chapter setting exists, return global
        if (!$chapterSetting) {
            return $globalSetting;
        }

        // If both exist, merge with field-level fallback
        // Chapter values take precedence, but empty/null falls back to global
        $merged = $this->mergeLandingSettings($globalSetting, $chapterSetting);

        return $merged;
    }

    /**
     * Merge global and chapter settings with field-level fallback.
     * Chapter values override global, but empty/null values fall back to global.
     */
    protected function mergeLandingSettings(?LandingPageSetting $global, LandingPageSetting $chapter)
    {
        // If no global, just return chapter
        if (!$global) {
            return $chapter;
        }

        // Start with global values
        $mergedData = [
            'chapter_id' => $chapter->chapter_id,
            'id' => $chapter->id,
        ];

        // For each field, use chapter value if set, otherwise fallback to global
        $mergedData['services'] = $this->mergeField($chapter->services, $global->services);
        $mergedData['number_of_testimonies'] = $this->mergeField($chapter->number_of_testimonies, $global->number_of_testimonies) ?? 5;
        $mergedData['number_of_conclaves'] = $this->mergeField($chapter->number_of_conclaves, $global->number_of_conclaves) ?? 3;
        $mergedData['hero_section'] = $this->mergeHeroSection($chapter->hero_section, $global->hero_section);

        // Create a simple stdClass object with merged values
        $merged = new \stdClass();
        foreach ($mergedData as $key => $value) {
            $merged->$key = $value;
        }

        return $merged;
    }

    /**
     * Merge a single field: use chapter value if not empty, fallback to global
     */
    protected function mergeField($chapterValue, $globalValue)
    {
        // If chapter has a value (not null, not empty array, not empty string), use it
        if ($chapterValue !== null && $chapterValue !== [] && $chapterValue !== '') {
            return $chapterValue;
        }

        // Otherwise fallback to global
        return $globalValue;
    }

    /**
     * Merge hero section with field-level fallback
     */
    protected function mergeHeroSection($chapterHero, $globalHero)
    {
        $chapterHero = is_array($chapterHero) ? $chapterHero : [];
        $globalHero = is_array($globalHero) ? $globalHero : [];

        // Merge with global as base, chapter values override
        return array_merge($globalHero, $chapterHero);
    }

    protected function buildHeroSection(array $hero): array
    {
        if (!isset($hero['media_path']) && !empty($hero['image'])) {
            $hero['media_path'] = $hero['image'];
            $hero['media_type'] = 'image';
        }

        $default = [
            'title' => 'Welcome to Doxa Commission Global',
            'subtitle' => 'A place where faith, hope, and love come together.',
            'cta_text' => 'Get Connected',
            'cta_url' => route('appointment', request()->query()),
            'media_type' => 'image',
            'media_path' => null,
        ];

        $hero = array_merge($default, $hero);
        $hero['media_url'] = $this->resolveMediaUrl($hero['media_path'] ?? null);

        return $hero;
    }

    protected function buildServices(array $settingsServices): array
    {
        $services = collect($settingsServices)
            ->filter(fn ($service) => !empty($service['name']))
            ->map(function ($service) {
                return [
                    'name' => (string) ($service['name'] ?? ''),
                    'day_of_week' => (string) ($service['day_of_week'] ?? ''),
                    'start_time' => $this->normalizeTime($service['start_time'] ?? null),
                    'end_time' => $this->normalizeTime($service['end_time'] ?? null),
                    'location' => (string) ($service['location'] ?? ''),
                    'livestream_url' => (string) ($service['livestream_url'] ?? ''),
                ];
            })
            ->values();

        return $services->all();
    }

    protected function normalizeTime($value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->format('H:i');
        } catch (\Throwable $e) {
            return is_string($value) ? $value : null;
        }
    }

    protected function resolveMediaUrl($path): ?string
    {
        if (!$path || !is_string($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // Generate URL directly without checking existence (Laravel will handle 404 if file doesn't exist)
        try {
            return Storage::disk('public')->url($path);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function submitTestimony()
    {
        $this->validate([
            'email' => 'required|email',
            'testimony' => 'required|min:10',
            'name' => 'nullable|string|max:255',
            'image' => 'nullable|image|max:2048',
        ]);

        $imagePath = null;
        if ($this->image) {
            $imagePath = $this->image->store('testimonies', 'public');
        }

        Testimony::create([
            'name' => $this->name,
            'email' => $this->email,
            'testimony' => $this->testimony,
            'image' => $imagePath,
            'status' => 'pending',
        ]);

        $this->reset(['name', 'email', 'testimony', 'image']);
        $this->dispatch('testimony-submitted');
    }
}; ?>

<div class="min-h-screen bg-white">
    @php
        $fallbackHero = asset('/Img/IMG-20250101-WA0021.jpg');
        $heroType = $heroSection['media_type'] ?? 'image';
        $heroMedia = $heroSection['media_url'] ?? null;
        $chapterQuery = request()->query();

        $formatServiceTime = function (?string $time): ?string {
            if (!$time) {
                return null;
            }

            try {
                return \Carbon\Carbon::createFromFormat('H:i', $time)->format('g:i A');
            } catch (\Throwable $e) {
                return $time;
            }
        };
    @endphp

    <section class="relative overflow-hidden">
        <div class="relative h-screen min-h-[600px] w-full">
            @if($heroType === 'video' && $heroMedia)
                <video autoplay muted loop playsinline class="absolute inset-0 h-full w-full object-cover">
                    <source src="{{ $heroMedia }}" type="video/mp4">
                </video>
            @else
                <div class="absolute inset-0 h-full w-full bg-cover bg-center bg-no-repeat" style="background-image: url('{{ $heroMedia ?: $fallbackHero }}');"></div>
            @endif

            <div class="absolute inset-0 bg-gradient-to-b from-slate-950/70 via-slate-950/50 to-slate-950/60"></div>

            <div class="relative z-10 flex h-full w-full items-center justify-center px-4 sm:px-6 lg:px-8">
                <div class="max-w-5xl text-center text-white">
                    @if($activeChapter)
                        <p class="mx-auto inline-block rounded-full bg-white/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.3em] text-blue-200 backdrop-blur-sm">{{ $activeChapter->name }}</p>
                    @endif
                    <h1 class="mt-6 text-4xl font-semibold leading-tight sm:text-5xl md:text-6xl lg:text-7xl">{{ $heroSection['title'] }}</h1>
                    <p class="mx-auto mt-6 max-w-3xl text-base leading-7 text-blue-50 sm:text-lg md:text-xl">{{ $heroSection['subtitle'] }}</p>

                    @if(!empty($heroSection['cta_text']) && !empty($heroSection['cta_url']))
                        <div class="mt-10">
                            <a href="{{ $heroSection['cta_url'] }}" wire:navigate class="inline-flex rounded-full bg-blue-600 px-8 py-4 text-sm font-semibold text-white transition-all hover:bg-blue-700 hover:shadow-lg hover:shadow-blue-500/30">
                                {{ $heroSection['cta_text'] }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <div class="absolute bottom-8 left-1/2 z-10 -translate-x-1/2 animate-bounce">
                <svg class="h-8 w-8 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                </svg>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-600">Service Schedule</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900">Worship with us this week</h2>
            </div>
        </div>

        @if(!empty($services))
            <div class="grid gap-4 md:grid-cols-2">
                @foreach($services as $service)
                    <article
                        class="service-card rounded-2xl border border-blue-100 bg-white p-5 shadow-sm transition-colors duration-200"
                        data-service-day="{{ strtolower((string) ($service['day_of_week'] ?? '')) }}"
                        data-service-start="{{ $service['start_time'] ?? '' }}"
                        data-service-end="{{ $service['end_time'] ?? '' }}"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">{{ $service['day_of_week'] ?: 'Service' }}</p>
                            <span class="service-status rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-[0.65rem] font-semibold uppercase tracking-[0.12em] text-blue-700">
                                Checking
                            </span>
                        </div>

                        <h3 class="mt-2 text-lg font-semibold text-slate-900">{{ $service['name'] }}</h3>

                        <p class="mt-2 text-sm text-slate-600">
                            @php
                                $start = $formatServiceTime($service['start_time'] ?? null);
                                $end = $formatServiceTime($service['end_time'] ?? null);
                            @endphp
                            @if($start && $end)
                                {{ $start }} - {{ $end }}
                            @elseif($start)
                                {{ $start }}
                            @else
                                Time TBD
                            @endif
                        </p>

                        @if(!empty($service['location']))
                            <p class="mt-1 text-sm text-slate-500">{{ $service['location'] }}</p>
                        @endif

                        @if(!empty($service['livestream_url']))
                            <a href="{{ $service['livestream_url'] }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex rounded-full border border-blue-200 px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-blue-700 transition hover:bg-blue-50">Join Live</a>
                        @endif
                    </article>
                @endforeach
            </div>
        @else
            <div class="rounded-2xl border border-dashed border-blue-200 bg-blue-50/40 px-6 py-12 text-center">
                <p class="text-sm text-slate-600">No services published for this chapter yet. Add services in Landing Page Settings.</p>
            </div>
        @endif
    </section>

    @if($conclaves && $conclaves->count() > 0)
    <section class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-600">Our Conclaves</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900">Discover our communities</h2>
            </div>
        </div>

        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach($conclaves as $conclave)
                <article class="group flex flex-col overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-sm transition hover:border-blue-300 hover:shadow-lg">
                    @if($conclave->image)
                        <div class="relative h-48 w-full overflow-hidden">
                            <img src="{{ Storage::url($conclave->image) }}" alt="{{ $conclave->name }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105" />
                        </div>
                    @else
                        <div class="flex h-48 w-full items-center justify-center bg-gradient-to-br from-blue-50 to-blue-100">
                            <svg class="h-16 w-16 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                    @endif

                    <div class="flex flex-1 flex-col p-5">
                        <h3 class="text-lg font-bold text-slate-900">{{ $conclave->name }}</h3>

                        @if($conclave->location)
                            <p class="mt-2 flex items-center text-sm text-slate-600">
                                <svg class="mr-1.5 h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                {{ $conclave->location }}
                            </p>
                        @endif

                        @if($conclave->description)
                            <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ \Illuminate\Support\Str::limit($conclave->description, 120) }}</p>
                        @endif

                        <div class="mt-auto pt-4">
                            <button type="button" wire:click="selectConclave({{ $conclave->id }})" class="inline-flex w-full items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                                View Details
                                <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-8 text-center">
            <a href="{{ route('conclaves.index', request()->query()) }}" wire:navigate class="inline-flex items-center rounded-full border border-blue-200 bg-white px-6 py-3 text-sm font-semibold text-blue-700 transition hover:bg-blue-50 hover:border-blue-300">
                View All Conclaves
                <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
        </div>
    </section>
    @endif

    <section class="mx-auto max-w-6xl px-4 pb-12 sm:px-6 lg:px-8">
        <header class="mb-6 text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-600">Get Connected</p>
            <h2 class="mt-2 text-2xl font-semibold text-slate-900">Take your next step</h2>
        </header>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            {{-- Book an Appointment Card --}}
            <a href="{{ route('appointment', $chapterQuery) }}" wire:navigate class="group flex flex-col items-center rounded-2xl border border-blue-100 bg-white p-6 text-center shadow-sm transition hover:border-blue-300 hover:shadow-lg">
                <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-blue-50 transition group-hover:bg-blue-100">
                    <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900">Book an Appointment</h3>
                <p class="mt-2 text-sm text-slate-600">Schedule time with our pastor or counselors</p>
                <button class="mt-4 w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                    Book Now
                </button>
            </a>

            {{-- Prayer Request Card --}}
            <a href="{{ route('prayer.request', $chapterQuery) }}" wire:navigate class="group flex flex-col items-center rounded-2xl border border-blue-100 bg-white p-6 text-center shadow-sm transition hover:border-blue-300 hover:shadow-lg">
                <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-blue-50 transition group-hover:bg-blue-100">
                    <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900">Prayer Request</h3>
                <p class="mt-2 text-sm text-slate-600">Submit your prayer needs to our community</p>
                <button class="mt-4 w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                    Submit Request
                </button>
            </a>

            {{-- Upcoming Events Card --}}
            <a href="{{ route('events.index', $chapterQuery) }}" wire:navigate class="group flex flex-col items-center rounded-2xl border border-blue-100 bg-white p-6 text-center shadow-sm transition hover:border-blue-300 hover:shadow-lg">
                <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-blue-50 transition group-hover:bg-blue-100">
                    <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900">Upcoming Events</h3>
                <p class="mt-2 text-sm text-slate-600">Register for upcoming conferences and retreats</p>
                <button class="mt-4 w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                    Register Now
                </button>
            </a>

            {{-- Believers Academy Card --}}
            <a href="{{ route('believers.academy', $chapterQuery) }}" wire:navigate class="group flex flex-col items-center rounded-2xl border border-blue-100 bg-white p-6 text-center shadow-sm transition hover:border-blue-300 hover:shadow-lg">
                <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-blue-50 transition group-hover:bg-blue-100">
                    <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900">Believers Academy</h3>
                <p class="mt-2 text-sm text-slate-600">Grow in doctrine and discipleship, join our class today</p>
                <button class="mt-4 w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                    Learn More
                </button>
            </a>

            {{-- Partnership and Givings Card --}}
            <a href="{{ route('home.partnership.index', $chapterQuery) }}" wire:navigate class="group flex flex-col items-center rounded-2xl border border-blue-100 bg-white p-6 text-center shadow-sm transition hover:border-blue-300 hover:shadow-lg">
                <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-blue-50 transition group-hover:bg-blue-100">
                    <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900">Partnership and Givings</h3>
                <p class="mt-2 text-sm text-slate-600">Learn more on being a partner in Doxa</p>
                <button class="mt-4 w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                    Be a Partner
                </button>
            </a>

            {{-- Listen to Previous Messages Card --}}
            <a href="{{ route('sermons.index', $chapterQuery) }}" wire:navigate class="group flex flex-col items-center rounded-2xl border border-blue-100 bg-white p-6 text-center shadow-sm transition hover:border-blue-300 hover:shadow-lg">
                <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-blue-50 transition group-hover:bg-blue-100">
                    <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900">Listen to Previous Messages</h3>
                <p class="mt-2 text-sm text-slate-600">Catch up on recent messages</p>
                <button class="mt-4 w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                    Listen Now
                </button>
            </a>
        </div>
    </section>

    @if($pastorSettings && ($pastorSettings->pastor_name || $pastorSettings->pastor_description || $pastorSettings->pastor_image))
        <!-- Meet Our Pastor Section -->
        <section class="relative flex min-h-screen items-center" style="background-color: #f9fbff;">
            <div class="absolute inset-0 overflow-hidden">
                @if($pastorSettings->pastor_image)
                    <img src="{{ Storage::url($pastorSettings->pastor_image) }}" alt="{{ $pastorSettings->pastor_name ?? 'Pastor' }}" class="h-full w-full object-cover" />
                    <div class="absolute inset-0 bg-gradient-to-r from-[#f9fbff] via-[#f9fbff]/95 to-[#f9fbff]/80"></div>
                @endif
            </div>

            <div class="relative mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <div class="grid min-h-[80vh] items-center gap-12 lg:grid-cols-2">
                    <!-- Left Content -->
                    <div class="order-2 lg:order-1">
                        <!-- Church Identity -->
                        <div class="mb-8 flex items-center gap-4">
                            @php
                                $globalSettings = \App\Models\GlobalSetting::first();
                                $churchLogo = $globalSettings?->logo ? asset('storage/' . $globalSettings->logo) : null;
                                $churchName = $globalSettings?->church_name ?? config('app.name', 'Doxa Commission Global');
                            @endphp
                            @if($churchLogo)
                                <img src="{{ $churchLogo }}" alt="{{ $churchName }}" class="h-12 w-auto object-contain" />
                            @endif
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">{{ $churchName }}</p>
                            </div>
                        </div>

                        <!-- Heading -->
                        <h2 class="text-4xl font-bold uppercase tracking-[0.15em] text-slate-900 sm:text-5xl lg:text-6xl">
                            MEET OUR PASTOR
                        </h2>

                        <!-- Pastor Name and Title -->
                        @if($pastorSettings->pastor_name)
                            <div class="mt-6">
                                <h3 class="text-2xl font-bold text-slate-900 sm:text-3xl">
                                    {{ $pastorSettings->pastor_name }}
                                </h3>
                                @if($pastorSettings->pastor_title)
                                    <p class="mt-2 text-lg font-medium text-blue-600">{{ $pastorSettings->pastor_title }}</p>
                                @endif
                            </div>
                        @endif

                        <!-- Pastor Description -->
                        @if($pastorSettings->pastor_description)
                            <div class="mt-6 max-w-2xl">
                                <p class="text-base leading-relaxed text-slate-600 sm:text-lg">
                                    {{ $pastorSettings->pastor_description }}
                                </p>
                            </div>
                        @endif

                        <!-- CTA Button -->
                        @if($pastorSettings->cta_button_url)
                            <div class="mt-8">
                                <a href="{{ $pastorSettings->cta_button_url }}" class="inline-flex items-center rounded-full bg-blue-600 px-8 py-3.5 text-sm font-semibold uppercase tracking-[0.2em] text-white transition hover:bg-blue-700">
                                    {{ $pastorSettings->cta_button_text ?? 'Learn More' }}
                                    <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                                    </svg>
                                </a>
                            </div>
                        @endif

                        <!-- Social Media Links -->
                        @if($pastorSettings->facebook_url || $pastorSettings->instagram_url || $pastorSettings->x_url || $pastorSettings->youtube_url || $pastorSettings->tiktok_url || $pastorSettings->telegram_url || $pastorSettings->whatsapp_url)
                            <div class="mt-10 flex items-center gap-4">
                                <span class="text-sm font-medium text-slate-500">Follow:</span>
                                <div class="flex gap-3">
                                    @if($pastorSettings->facebook_url)
                                        <a href="{{ $pastorSettings->facebook_url }}" target="_blank" rel="noopener noreferrer" aria-label="Facebook" class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-900/50 text-blue-200 transition-all duration-300 hover:bg-white hover:text-blue-600">
                                            <i class="fab fa-facebook-f"></i>
                                        </a>
                                    @endif
                                    @if($pastorSettings->instagram_url)
                                        <a href="{{ $pastorSettings->instagram_url }}" target="_blank" rel="noopener noreferrer" aria-label="Instagram" class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-900/50 text-blue-200 transition-all duration-300 hover:bg-white hover:text-pink-500">
                                            <i class="fab fa-instagram"></i>
                                        </a>
                                    @endif
                                    @if($pastorSettings->x_url)
                                        <a href="{{ $pastorSettings->x_url }}" target="_blank" rel="noopener noreferrer" aria-label="X / Twitter" class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-900/50 text-blue-200 transition-all duration-300 hover:bg-white hover:text-black">
                                            <i class="fab fa-x-twitter"></i>
                                        </a>
                                    @endif
                                    @if($pastorSettings->youtube_url)
                                        <a href="{{ $pastorSettings->youtube_url }}" target="_blank" rel="noopener noreferrer" aria-label="YouTube" class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-900/50 text-blue-200 transition-all duration-300 hover:bg-white hover:text-red-600">
                                            <i class="fab fa-youtube"></i>
                                        </a>
                                    @endif
                                    @if($pastorSettings->tiktok_url)
                                        <a href="{{ $pastorSettings->tiktok_url }}" target="_blank" rel="noopener noreferrer" aria-label="TikTok" class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-900/50 text-blue-200 transition-all duration-300 hover:bg-white hover:text-black">
                                            <i class="fab fa-tiktok"></i>
                                        </a>
                                    @endif
                                    @if($pastorSettings->telegram_url)
                                        <a href="{{ $pastorSettings->telegram_url }}" target="_blank" rel="noopener noreferrer" aria-label="Telegram" class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-900/50 text-blue-200 transition-all duration-300 hover:bg-white hover:text-blue-500">
                                            <i class="fab fa-telegram-plane"></i>
                                        </a>
                                    @endif
                                    @if($pastorSettings->whatsapp_url)
                                        <a href="{{ $pastorSettings->whatsapp_url }}" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp" class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-900/50 text-blue-200 transition-all duration-300 hover:bg-white hover:text-green-500">
                                            <i class="fab fa-whatsapp"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Right Content - Pastor Image (visible on desktop, hidden on mobile) -->
                    @if($pastorSettings->pastor_image)
                        <div class="order-1 hidden lg:order-2 lg:block">
                            <div class="relative">
                                <div class="absolute -inset-4 rounded-3xl bg-gradient-to-br from-blue-100 to-blue-50 opacity-50 blur-2xl"></div>
                                <img src="{{ Storage::url($pastorSettings->pastor_image) }}" alt="{{ $pastorSettings->pastor_name ?? 'Pastor' }}" class="relative mx-auto h-[600px] w-full max-w-md rounded-3xl object-cover shadow-2xl" />
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    <section class="border-y border-blue-100 bg-blue-50/40 py-12">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-600">Testimonies</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">What God is doing</h2>
                </div>
                <button type="button" class="rounded-full bg-blue-600 px-5 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-white transition hover:bg-blue-700" onclick="openTestimonyModal()">
                    Share Testimony
                </button>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                @forelse($testimonies as $item)
                    <article class="rounded-2xl border border-blue-100 bg-white p-5 shadow-sm">
                        <p class="text-sm italic leading-7 text-slate-600">"{{ \Illuminate\Support\Str::limit($item->testimony, 240) }}"</p>
                        <p class="mt-3 text-sm font-semibold text-slate-800">- {{ $item->name ?: 'Anonymous' }}</p>
                    </article>
                @empty
                    <div class="md:col-span-2 rounded-2xl border border-dashed border-blue-200 bg-white px-6 py-12 text-center">
                        <p class="text-sm text-slate-500">No approved testimonies yet.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <div id="testimony-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/55 p-4">
        <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-xl font-semibold text-slate-900">Share Your Testimony</h3>
                <button type="button" class="rounded border border-blue-100 px-3 py-1 text-xs font-semibold text-slate-500 hover:text-slate-700" onclick="closeTestimonyModal()">Close</button>
            </div>

            <form wire:submit.prevent="submitTestimony" class="space-y-4">
                <div>
                    <label for="testimonyName" class="block text-sm font-medium text-slate-700">Your Name (Optional)</label>
                    <input id="testimonyName" type="text" wire:model="name" placeholder="Your name" class="mt-2 w-full rounded-xl border border-blue-100 px-4 py-2.5 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    @error('name')
                        <span class="text-xs text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label for="testimonyEmail" class="block text-sm font-medium text-slate-700">Your Email</label>
                    <input id="testimonyEmail" type="email" wire:model="email" placeholder="you@example.com" class="mt-2 w-full rounded-xl border border-blue-100 px-4 py-2.5 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100" required>
                    @error('email')
                        <span class="text-xs text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label for="testimonyText" class="block text-sm font-medium text-slate-700">Testimony</label>
                    <textarea id="testimonyText" rows="5" wire:model="testimony" placeholder="Share your testimony..." class="mt-2 w-full rounded-xl border border-blue-100 px-4 py-2.5 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100" required></textarea>
                    @error('testimony')
                        <span class="text-xs text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label for="testimonyImage" class="block text-sm font-medium text-slate-700">Image (Optional)</label>
                    <input id="testimonyImage" type="file" wire:model="image" accept="image/*" class="mt-2 w-full rounded-xl border border-blue-100 px-4 py-2 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-blue-700 hover:file:bg-blue-100">
                    @error('image')
                        <span class="text-xs text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="rounded-xl border border-blue-100 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-blue-50" onclick="closeTestimonyModal()">Cancel</button>
                    <button type="submit" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function parseServiceWindow(dayName, startTime, endTime) {
            if (!dayName || !startTime || !endTime) {
                return null;
            }

            const dayMap = {
                sunday: 0,
                monday: 1,
                tuesday: 2,
                wednesday: 3,
                thursday: 4,
                friday: 5,
                saturday: 6,
            };

            const targetDay = dayMap[dayName.toLowerCase()];
            if (typeof targetDay === 'undefined') {
                return null;
            }

            const now = new Date();
            const [startHour, startMinute] = startTime.split(':').map(Number);
            const [endHour, endMinute] = endTime.split(':').map(Number);

            const start = new Date(now);
            const dayOffset = (targetDay - now.getDay() + 7) % 7;
            start.setDate(now.getDate() + dayOffset);
            start.setHours(startHour, startMinute, 0, 0);

            const end = new Date(start);
            end.setHours(endHour, endMinute, 0, 0);
            if (end <= start) {
                end.setDate(end.getDate() + 1);
            }

            if (now > end) {
                start.setDate(start.getDate() + 7);
                end.setDate(end.getDate() + 7);
            }

            return { start, end };
        }

        function formatDuration(ms) {
            if (ms <= 0) {
                return 'now';
            }

            const totalMinutes = Math.floor(ms / 60000);
            const days = Math.floor(totalMinutes / (60 * 24));
            const hours = Math.floor((totalMinutes % (60 * 24)) / 60);
            const minutes = totalMinutes % 60;

            if (days > 0) {
                return `${days}d ${hours}h`;
            }

            if (hours > 0) {
                return `${hours}h ${minutes}m`;
            }

            return `${Math.max(1, minutes)}m`;
        }

        function updateServiceStatuses() {
            const cards = document.querySelectorAll('.service-card');
            const now = new Date();

            cards.forEach((card) => {
                const day = card.dataset.serviceDay;
                const startTime = card.dataset.serviceStart;
                const endTime = card.dataset.serviceEnd;
                const chip = card.querySelector('.service-status');
                if (!chip) {
                    return;
                }

                const window = parseServiceWindow(day, startTime, endTime);
                if (!window) {
                    chip.textContent = 'Schedule needed';
                    chip.className = 'service-status rounded-full border border-slate-200 bg-slate-100 px-3 py-1 text-[0.65rem] font-semibold uppercase tracking-[0.12em] text-slate-600';
                    card.classList.remove('border-green-300', 'bg-green-50/40');
                    return;
                }

                if (now >= window.start && now < window.end) {
                    const remaining = formatDuration(window.end - now);
                    chip.textContent = `Ongoing • ${remaining} left`;
                    chip.className = 'service-status rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[0.65rem] font-semibold uppercase tracking-[0.12em] text-emerald-700';
                    card.classList.add('border-emerald-200', 'bg-emerald-50/30');
                    return;
                }

                const untilStart = formatDuration(window.start - now);
                chip.textContent = `Starts in ${untilStart}`;
                chip.className = 'service-status rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-[0.65rem] font-semibold uppercase tracking-[0.12em] text-blue-700';
                card.classList.remove('border-emerald-200', 'bg-emerald-50/30');
            });
        }

        function openTestimonyModal() {
            const modal = document.getElementById('testimony-modal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        }

        function closeTestimonyModal() {
            const modal = document.getElementById('testimony-modal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }

        const testimonyModal = document.getElementById('testimony-modal');
        if (testimonyModal) {
            testimonyModal.addEventListener('click', function (e) {
                if (e.target === testimonyModal) {
                    closeTestimonyModal();
                }
            });
        }

        updateServiceStatuses();
        setInterval(updateServiceStatuses, 60000);
        document.addEventListener('livewire:navigated', updateServiceStatuses);
    </script>

    {{-- Conclave Details Modal --}}
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
