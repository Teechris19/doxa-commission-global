<?php

use App\Models\{Chapter, LandingPageSetting, Testimony, PastorSetting};
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.tailwind-layout')] class extends Component {
    use WithFileUploads;

    public ?LandingPageSetting $landing = null;
    public ?Chapter $activeChapter = null;
    public array $heroSection = [];
    public array $services = [];
    public $testimonies;
    public $pastorSettings = null;

    // Testimony form fields
    public $name = '';
    public $email = '';
    public $testimony = '';
    public $image;

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

    protected function resolveLandingSetting(?int $chapterId): ?LandingPageSetting
    {
        if (!Schema::hasColumn('landing_page_settings', 'chapter_id')) {
            return LandingPageSetting::first();
        }

        if ($chapterId) {
            $chapterSetting = LandingPageSetting::where('chapter_id', $chapterId)->first();
            if ($chapterSetting) {
                return $chapterSetting;
            }
        }

        return LandingPageSetting::whereNull('chapter_id')->first() ?? LandingPageSetting::first();
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

    <section class="mx-auto max-w-6xl px-4 pb-12 sm:px-6 lg:px-8">
        <header class="mb-6 text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-600">Get Connected</p>
            <h2 class="mt-2 text-2xl font-semibold text-slate-900">Take your next step</h2>
        </header>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            <a href="{{ route('appointment', $chapterQuery) }}" wire:navigate class="rounded-2xl border border-blue-100 bg-white p-5 shadow-sm transition hover:border-blue-300 hover:shadow-md">
                <h3 class="text-base font-semibold text-slate-900">Book Appointment</h3>
                <p class="mt-1 text-sm text-slate-600">Talk to our pastors or counselors.</p>
            </a>
            <a href="{{ route('prayer.request', $chapterQuery) }}" wire:navigate class="rounded-2xl border border-blue-100 bg-white p-5 shadow-sm transition hover:border-blue-300 hover:shadow-md">
                <h3 class="text-base font-semibold text-slate-900">Prayer Request</h3>
                <p class="mt-1 text-sm text-slate-600">Submit your prayer needs.</p>
            </a>
            <a href="{{ route('events.index', $chapterQuery) }}" wire:navigate class="rounded-2xl border border-blue-100 bg-white p-5 shadow-sm transition hover:border-blue-300 hover:shadow-md">
                <h3 class="text-base font-semibold text-slate-900">Upcoming Events</h3>
                <p class="mt-1 text-sm text-slate-600">Discover and register for events.</p>
            </a>
            <a href="{{ route('believers.academy', $chapterQuery) }}" wire:navigate class="rounded-2xl border border-blue-100 bg-white p-5 shadow-sm transition hover:border-blue-300 hover:shadow-md">
                <h3 class="text-base font-semibold text-slate-900">Believers Academy</h3>
                <p class="mt-1 text-sm text-slate-600">Grow in doctrine and discipleship.</p>
            </a>
            <a href="{{ route('home.partnership.index', $chapterQuery) }}" wire:navigate class="rounded-2xl border border-blue-100 bg-white p-5 shadow-sm transition hover:border-blue-300 hover:shadow-md">
                <h3 class="text-base font-semibold text-slate-900">Partnership</h3>
                <p class="mt-1 text-sm text-slate-600">Explore ways to partner with us.</p>
            </a>
            <a href="{{ route('sermons.index', $chapterQuery) }}" wire:navigate class="rounded-2xl border border-blue-100 bg-white p-5 shadow-sm transition hover:border-blue-300 hover:shadow-md">
                <h3 class="text-base font-semibold text-slate-900">Watch Sermons</h3>
                <p class="mt-1 text-sm text-slate-600">Catch up on recent messages.</p>
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
                                        <a href="{{ $pastorSettings->facebook_url }}" target="_blank" rel="noopener noreferrer" class="rounded-full bg-blue-600 p-2.5 text-white transition hover:bg-blue-700">
                                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                        </a>
                                    @endif
                                    @if($pastorSettings->instagram_url)
                                        <a href="{{ $pastorSettings->instagram_url }}" target="_blank" rel="noopener noreferrer" class="rounded-full bg-gradient-to-r from-purple-500 to-pink-500 p-2.5 text-white transition hover:from-purple-600 hover:to-pink-600">
                                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                                        </a>
                                    @endif
                                    @if($pastorSettings->x_url)
                                        <a href="{{ $pastorSettings->x_url }}" target="_blank" rel="noopener noreferrer" class="rounded-full bg-slate-900 p-2.5 text-white transition hover:bg-slate-800">
                                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                        </a>
                                    @endif
                                    @if($pastorSettings->youtube_url)
                                        <a href="{{ $pastorSettings->youtube_url }}" target="_blank" rel="noopener noreferrer" class="rounded-full bg-red-600 p-2.5 text-white transition hover:bg-red-700">
                                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                                        </a>
                                    @endif
                                    @if($pastorSettings->tiktok_url)
                                        <a href="{{ $pastorSettings->tiktok_url }}" target="_blank" rel="noopener noreferrer" class="rounded-full bg-black p-2.5 text-white transition hover:bg-gray-900">
                                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                                        </a>
                                    @endif
                                    @if($pastorSettings->telegram_url)
                                        <a href="{{ $pastorSettings->telegram_url }}" target="_blank" rel="noopener noreferrer" class="rounded-full bg-sky-500 p-2.5 text-white transition hover:bg-sky-600">
                                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.779 9.28c-.131.659-.52.817-1.05.517l-2.906-2.14-1.401 1.352c-.156.155-.286.285-.587.285l.213-3.054 5.56-5.022c.242-.213-.054-.332-.373-.121l-6.866 4.326-2.96-.924c-.642-.203-.658-.64.135-.954l11.566-4.458c.538-.196 1.006.128.848.939z"/></svg>
                                        </a>
                                    @endif
                                    @if($pastorSettings->whatsapp_url)
                                        <a href="{{ $pastorSettings->whatsapp_url }}" target="_blank" rel="noopener noreferrer" class="rounded-full bg-green-500 p-2.5 text-white transition hover:bg-green-600">
                                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
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
</div>
