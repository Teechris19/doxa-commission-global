<?php

use App\Models\{Chapter, LandingPageSetting, Testimony};
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

    <section class="relative overflow-hidden border-b border-blue-100">
        <div class="relative h-[68vh] min-h-[460px]">
            @if($heroType === 'video' && $heroMedia)
                <video autoplay muted loop playsinline class="absolute inset-0 h-full w-full object-cover">
                    <source src="{{ $heroMedia }}" type="video/mp4">
                </video>
            @else
                <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $heroMedia ?: $fallbackHero }}');"></div>
            @endif

            <div class="absolute inset-0 bg-slate-950/55"></div>

            <div class="relative z-10 mx-auto flex h-full w-full max-w-6xl items-center px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl text-white">
                    @if($activeChapter)
                        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-blue-200">{{ $activeChapter->name }}</p>
                    @endif
                    <h1 class="mt-3 text-4xl font-semibold leading-tight sm:text-5xl">{{ $heroSection['title'] }}</h1>
                    <p class="mt-5 max-w-2xl text-base leading-7 text-blue-50 sm:text-lg">{{ $heroSection['subtitle'] }}</p>

                    @if(!empty($heroSection['cta_text']) && !empty($heroSection['cta_url']))
                        <a href="{{ $heroSection['cta_url'] }}" wire:navigate class="mt-8 inline-flex rounded-full bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
                            {{ $heroSection['cta_text'] }}
                        </a>
                    @endif
                </div>
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
