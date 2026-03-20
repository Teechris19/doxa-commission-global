<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Sermons;
use App\Models\SermonSeries;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;

new #[Layout('components.layouts.tailwind-layout')] class extends Component {
    use WithPagination;

    #[Url(keep: true)]
    public $page = 1;

    public $search = '';
    public $selectedSeries = null;

    public function with(): array
    {
        $sermons = Sermons::with(['series', 'media'])
            ->when($this->search, fn($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->selectedSeries, fn($q) => $q->where('series_id', $this->selectedSeries))
            ->latest('preached_at')
            ->paginate(12)
            ->withQueryString();

        $series = SermonSeries::withCount('sermons')->latest()->take(6)->get();
        $latestSermon = Sermons::with(['series', 'media'])->latest('preached_at')->first();
        $selectedSeriesModel = $this->selectedSeries ? SermonSeries::find($this->selectedSeries) : null;

        return [
            'sermons' => $sermons,
            'series' => $series,
            'latestSermon' => $latestSermon,
            'selectedSeriesModel' => $selectedSeriesModel,
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedSeries()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->selectedSeries = null;
        $this->resetPage();
    }

    public function resolveMediaUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $normalized = '/' . ltrim($path, '/');

        if (str_starts_with($normalized, '/storage/')) {
            return $normalized;
        }

        return '/storage/' . ltrim($path, '/');
    }
};
?>

<style>
input[type="range"]::-webkit-slider-runnable-track {
    background: #bfdbfe;
    border-radius: 9999px;
    height: 4px;
}

input[type="range"]::-moz-range-track {
    background: #bfdbfe;
    border-radius: 9999px;
    height: 4px;
}

input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    height: 14px;
    width: 14px;
    background-color: #2563eb;
    border-radius: 50%;
    margin-top: -5px;
    box-shadow: 0 0 2px rgba(0, 0, 0, 0.5);
}

input[type="range"]::-moz-range-thumb {
    height: 14px;
    width: 14px;
    background-color: #2563eb;
    border-radius: 50%;
    border: none;
}

#seek-bar,
#seek-bar-mobile {
    -webkit-appearance: none;
    appearance: none;
    background: transparent;
    cursor: pointer;
    width: 100%;
}

#seek-bar::-webkit-slider-runnable-track,
#seek-bar-mobile::-webkit-slider-runnable-track {
    background: #dbeafe;
    border-radius: 9999px;
    height: 6px;
}

#seek-bar::-moz-range-track,
#seek-bar-mobile::-moz-range-track {
    background: #dbeafe;
    border-radius: 9999px;
    height: 6px;
}

#seek-bar::-webkit-slider-thumb,
#seek-bar-mobile::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    height: 14px;
    width: 14px;
    background-color: #2563eb;
    border-radius: 50%;
    margin-top: -4px;
    box-shadow: 0 0 3px rgba(0, 0, 0, 0.5);
}

#seek-bar::-moz-range-thumb,
#seek-bar-mobile::-moz-range-thumb {
    height: 14px;
    width: 14px;
    background-color: #2563eb;
    border-radius: 50%;
    border: none;
}

.audio-player-bar {
    position: fixed;
    bottom: calc(var(--mobile-nav-height, 5.25rem) + env(safe-area-inset-bottom, 0px) + 1rem);
    left: 50%;
    transform: translateX(-50%);
    width: calc(100% - 3rem);
    max-width: 960px;
    border-radius: 26px;
    border: 1px solid rgba(37, 99, 235, 0.25);
    background: rgba(255, 255, 255, 0.97);
    backdrop-filter: blur(24px);
    transition: transform 0.35s ease, opacity 0.35s ease, width 0.35s ease;
    opacity: 0;
    transform: translateX(-50%) translateY(60px);
    z-index: 1050;
    pointer-events: none;
}

.audio-player-bar.visible {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
    pointer-events: auto;
}

.audio-player-bar.pip-mode {
    width: 360px;
    left: 50%;
    transform: translateX(-50%);
}

.audio-player-bar.pip-mode.visible {
    transform: translateX(-50%) translateY(0);
}

/* Hide expanded view when minimized on desktop */
@media (min-width: 768px) {
    .audio-player-bar.pip-mode .expanded-view {
        display: none !important;
    }
    
    /* Show compact view when minimized on desktop */
    .audio-player-bar.pip-mode .compact-view {
        display: block !important;
    }
}

/* Show compact view always on mobile */
@media (max-width: 767px) {
    .audio-player-bar {
        width: calc(100% - 1.5rem);
    }

    .audio-player-bar.pip-mode {
        width: calc(100% - 1.5rem);
    }
}

@media (min-width: 1024px) {
    .audio-player-bar {
        bottom: 1.25rem;
    }
}

.play-overlay {
    align-items: center;
    background: rgba(10, 10, 10, 0.4);
    border-radius: 9999px;
    display: flex;
    height: 100%;
    justify-content: center;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s ease;
    width: 100%;
}

.sermon-card:hover .play-overlay {
    opacity: 1;
}

.sermon-card {
    position: relative;
}

.image-wrapper img {
    transition: transform 0.5s ease;
}

.sermon-card:hover .image-wrapper img {
    transform: scale(1.06);
}
</style>

<div class="min-h-screen bg-white text-slate-900">
    <section class="relative overflow-hidden border-b border-blue-100">
        <div class="pointer-events-none absolute inset-0">
            <div class="pointer-events-none absolute inset-0 -z-10 bg-gradient-to-b from-blue-100/90 via-white to-white opacity-90"></div>
            <div class="pointer-events-none absolute -left-40 top-16 h-72 w-[110%] -z-10 rounded-full bg-blue-200/50 blur-3xl opacity-60"></div>
        </div>
        <div class="relative mx-auto max-w-6xl px-6 py-20">
            <div class="grid items-center gap-12 lg:grid-cols-[1.1fr_0.9fr]">
                <div class="space-y-6">
                    <p class="text-xs uppercase tracking-[0.5em] text-blue-600">Doxa Messages</p>
                    <h1 class="text-4xl font-semibold leading-tight text-slate-900 md:text-5xl">Life-transforming sermons, anywhere you go.</h1>
                    <p class="text-lg text-slate-600">Explore curated series, search by speaker or theme, and keep every message on hand with the built-in player.</p>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                        <label class="sr-only" for="sermon-search">Search sermons</label>
                        <div class="relative flex-1">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="16.65" y1="16.65" x2="21" y2="21"/></svg>
                            </span>
                            <input wire:model.live.debounce.300ms="search" id="sermon-search" type="text" placeholder="Search message, series, or speaker" class="w-full rounded-2xl border border-blue-100 bg-white px-12 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200" />
                        </div>
                        <a href="#sermons" class="inline-flex items-center justify-center rounded-2xl bg-blue-600 px-6 py-3 text-xs font-semibold uppercase tracking-[0.2em] text-white transition hover:bg-blue-700">Browse Sermons</a>
                    </div>
                    <div class="flex flex-wrap gap-6 text-xs uppercase tracking-[0.3em] text-slate-400">
                        <span class="flex items-center gap-2 text-blue-600"><span class="h-1.5 w-1.5 rounded-full bg-blue-600"></span>{{ $sermons->total() }} sermons</span>
                        <span class="flex items-center gap-2">{{ $series->count() }} curated series</span>
                        @if($selectedSeriesModel)
                            <span class="flex items-center gap-2 text-blue-700">Showing: {{ $selectedSeriesModel->title }}</span>
                        @endif
                    </div>
                </div>
                <div class="space-y-6 text-sm text-slate-600">
                    @if($latestSermon)
                        <div class="relative overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-[0_20px_60px_-30px_rgba(37,99,235,0.35)]">
                            <div class="relative overflow-hidden rounded-[22px]">
                                <img src="{{ $latestSermon->image_path ? $this->resolveMediaUrl($latestSermon->image_path) : 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=1200&q=80' }}" alt="{{ $latestSermon->title }}" class="h-64 w-full object-cover transition duration-500 group-hover:scale-105" />
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-900/70 via-transparent to-transparent"></div>
                                <div class="absolute inset-x-5 bottom-5">
                                    <p class="text-[0.6rem] uppercase tracking-[0.4em] text-blue-100">Latest release</p>
                                    <h3 class="text-2xl font-semibold text-white">{{ $latestSermon->title }}</h3>
                                    <p class="text-sm text-blue-100">{{ $latestSermon->series->title ?? 'Standalone message' }}</p>
                                </div>
                            </div>
                            <span class="absolute top-4 right-4 rounded-full border border-blue-100/80 bg-blue-600 px-3 py-1 text-[0.65rem] font-semibold uppercase tracking-[0.4em] text-white">Must Listen</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section id="series" class="border-b border-blue-100 py-16">
        <div class="mx-auto max-w-6xl px-6 space-y-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.4em] text-blue-600">Series</p>
                    <h2 class="text-3xl font-semibold text-slate-900">Curated Collections</h2>
                </div>
                <div class="flex flex-wrap items-center gap-3 text-xs uppercase tracking-[0.3em] text-slate-400">
                    <span class="text-xs text-slate-500">Open a series to view all messages</span>
                </div>
            </div>
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @forelse($series as $item)
                    <a href="{{ route('sermons.series-detail', array_merge(['id' => $item->id], request()->query())) }}" wire:navigate class="group flex h-full flex-col overflow-hidden rounded-3xl border border-blue-100 bg-white px-4 py-5 text-left transition hover:border-blue-300 hover:shadow-[0_12px_34px_-18px_rgba(37,99,235,0.45)]">
                        <div class="mb-4 h-44 w-full overflow-hidden rounded-2xl bg-blue-50">
                            <img src="{{ $item->image ? $this->resolveMediaUrl($item->image) : 'https://images.unsplash.com/photo-1497215842964-222b430dc094?auto=format&fit=crop&w=900&q=80' }}" alt="{{ $item->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-110" />
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-slate-900">{{ $item->title }}</h3>
                                <span class="text-xs font-semibold uppercase tracking-[0.4em] text-blue-600">{{ $item->sermons_count }}x</span>
                            </div>
                            <p class="text-sm text-slate-600">{{ Str::limit($item->description, 120) }}</p>
                            <p class="text-xs uppercase tracking-[0.4em] text-blue-600">Open series</p>
                        </div>
                    </a>
                @empty
                    <div class="rounded-3xl border border-dashed border-blue-200 p-10 text-center text-slate-500">No series yet</div>
                @endforelse
            </div>
        </div>
    </section>

    <section id="sermons" class="py-16">
        <div class="mx-auto max-w-6xl px-6 space-y-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.4em] text-blue-600">Messages</p>
                    <h2 class="text-3xl font-semibold text-slate-900">{{ $selectedSeriesModel ? 'Filtered Sermons' : 'Latest Sermons' }}</h2>
                    @if($selectedSeriesModel)
                        <p class="text-sm text-slate-400">Series: {{ $selectedSeriesModel->title }}</p>
                    @endif
                </div>
                <div class="flex items-center gap-4 text-xs uppercase tracking-[0.3em] text-slate-400">
                    <span>{{ $sermons->total() }} sermons</span>
                    @if($selectedSeries)
                        <button wire:click="clearFilters" class="rounded-full border border-blue-200 px-4 py-2 text-xs tracking-[0.3em] text-blue-700 transition hover:border-blue-300">Clear filter</button>
                    @endif
                </div>
            </div>
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @forelse($sermons as $sermon)
                    @php
                        $audioMedia = $sermon->media->where('type', 'audio')->last();
                    @endphp
                    <article class="sermon-card group relative flex flex-col overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-[0_25px_60px_-30px_rgba(37,99,235,0.25)] transition hover:border-blue-300 hover:shadow-[0_30px_60px_-20px_rgba(37,99,235,0.35)] cursor-pointer"
                        data-id="{{ $sermon->id }}"
                        data-title="{{ $sermon->title }}"
                        data-artist="{{ $sermon->speaker_name ?? ($sermon->series->title ?? 'Unknown') }}"
                        data-audio-src="{{ $audioMedia ? $this->resolveMediaUrl($audioMedia->file_path) : '' }}"
                        data-image-src="{{ $sermon->image_path ? $this->resolveMediaUrl($sermon->image_path) : 'https://images.unsplash.com/photo-1489515217757-5fd1be406fef?auto=format&fit=crop&w=900&q=80' }}">
                        <div class="relative h-56 w-full overflow-hidden">
                            <img src="{{ $sermon->image_path ? $this->resolveMediaUrl($sermon->image_path) : 'https://images.unsplash.com/photo-1489515217757-5fd1be406fef?auto=format&fit=crop&w=900&q=80' }}"
                                 alt="{{ $sermon->title }}"
                                 class="h-full w-full object-cover transition duration-500 group-hover:scale-110" />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent"></div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="play-overlay">
                                    @if($audioMedia)
                                        <i class="fas fa-play-circle fa-4x text-white"></i>
                                    @else
                                        <span class="rounded-full bg-amber-500/90 px-3 py-1 text-[0.6rem] font-semibold uppercase tracking-[0.3em] text-slate-900">No audio</span>
                                    @endif
                                </div>
                            </div>
                            <div class="absolute top-4 left-4 rounded-full border border-blue-100/70 bg-blue-50/90 px-3 py-1 text-[0.65rem] font-semibold uppercase tracking-[0.3em] text-blue-700">{{ $sermon->series->title ?? 'Standalone' }}</div>
                        </div>
                        <div class="flex flex-1 flex-col gap-3 px-5 py-6">
                            <h3 class="text-xl font-semibold text-slate-900">{{ $sermon->title }}</h3>
                            <p class="text-sm text-slate-600">{{ Str::limit($sermon->description, 140) }}</p>
                            <div class="mt-auto flex items-center justify-between text-xs uppercase tracking-[0.3em] text-slate-400">
                                <span>{{ $sermon->preached_at->format('M d, Y') }}</span>
                                <span>{{ $audioMedia ? 'Play now' : 'Awaiting audio' }}</span>
                            </div>
                            @if($sermon->speaker_name)
                                <p class="text-xs text-slate-500">Speaker: {{ $sermon->speaker_name }}</p>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="rounded-3xl border border-dashed border-blue-200 p-10 text-center text-slate-500">No sermons matched your search.</div>
                @endforelse
            </div>
            <div class="mt-10 flex justify-center">
                {{ $sermons->links() }}
            </div>
        </div>
    </section>

    <div id="audio-player-bar" class="audio-player-bar hidden">
        <!-- Desktop Expanded View -->
        <div class="expanded-view hidden md:flex items-center gap-6 px-6 py-4">
            <div class="flex items-center gap-4 min-w-[260px]">
                <div id="player-album-art" class="h-20 w-20 overflow-hidden rounded-[18px] bg-blue-50">
                    <img src="" class="h-full w-full object-cover" alt="Album art" />
                </div>
                <div>
                    <p id="player-title" class="text-sm font-semibold text-slate-900">Select a Sermon</p>
                    <p id="player-artist" class="text-xs text-slate-500">--</p>
                </div>
            </div>
            <div class="flex flex-1 flex-col items-center gap-3">
                <div class="flex items-center gap-3">
                    <button id="prev-btn" class="rounded-full border border-blue-100 bg-blue-50 px-3 py-2 text-blue-700 transition hover:border-blue-300">
                        <i class="fas fa-step-backward"></i>
                    </button>
                    <button id="play-pause-btn" class="rounded-full bg-blue-600 px-4 py-2 text-2xl text-white transition hover:bg-blue-700">
                        <i id="play-pause-icon" class="fas fa-play fa-2x"></i>
                    </button>
                    <button id="next-btn" class="rounded-full border border-blue-100 bg-blue-50 px-3 py-2 text-blue-700 transition hover:border-blue-300">
                        <i class="fas fa-step-forward"></i>
                    </button>
                </div>
                <div class="flex w-full items-center gap-3 text-xs text-slate-400">
                    <span id="current-time" class="w-12 text-left">0:00</span>
                    <input id="seek-bar" type="range" min="0" max="100" value="0" step="0.1" class="h-1 flex-1 accent-blue-600" />
                    <span id="duration" class="w-12 text-right">0:00</span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2">
                    <i class="fas fa-volume-up text-blue-700"></i>
                    <input id="volume-slider" type="range" min="0" max="1" step="0.01" value="1" class="h-1 w-28 accent-blue-600" />
                </div>
                <button id="pip-toggle-btn" class="rounded-full border border-blue-100 bg-blue-50 px-3 py-2 text-blue-700 transition hover:border-blue-300">
                    <i class="fas fa-compress-alt"></i>
                </button>
                <button id="close-player-btn" class="rounded-full border border-blue-100 bg-blue-50 px-3 py-2 text-blue-700 transition hover:border-red-400 hover:text-red-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <!-- Compact View (Mobile & Minimized Desktop) -->
        <div class="compact-view block md:hidden space-y-3 px-5 py-4">
            <div class="flex items-center gap-3">
                <div id="player-album-art-mobile" class="h-12 w-12 overflow-hidden rounded-2xl bg-blue-50">
                    <img src="" class="h-full w-full object-cover" alt="Album art" />
                </div>
                <div class="flex-1 min-w-0">
                    <p id="player-title-mobile" class="text-sm font-semibold text-slate-900 truncate">Select a Sermon</p>
                    <p id="player-artist-mobile" class="text-xs text-slate-500 truncate">--</p>
                </div>
                <button id="close-player-btn-mobile" class="rounded-full border border-blue-100 bg-blue-50 p-2 text-blue-700 transition hover:border-red-400 hover:text-red-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="flex items-center justify-center gap-6">
                <button id="prev-btn-mobile" class="rounded-full border border-blue-100 bg-blue-50 px-3 py-2 text-blue-700 transition hover:border-blue-300">
                    <i class="fas fa-step-backward"></i>
                </button>
                <button id="play-pause-btn-mobile" class="rounded-full bg-blue-600 px-4 py-2 text-2xl text-white transition hover:bg-blue-700">
                    <i id="play-pause-icon-mobile" class="fas fa-play fa-2x"></i>
                </button>
                <button id="next-btn-mobile" class="rounded-full border border-blue-100 bg-blue-50 px-3 py-2 text-blue-700 transition hover:border-blue-300">
                    <i class="fas fa-step-forward"></i>
                </button>
            </div>
            <div class="flex items-center gap-3">
                <span id="current-time-mobile" class="w-12 text-left text-xs text-slate-400">0:00</span>
                <input id="seek-bar-mobile" type="range" min="0" max="100" value="0" step="0.1" class="h-1 flex-1 accent-blue-600" />
                <span id="duration-mobile" class="w-12 text-right text-xs text-slate-400">0:00</span>
            </div>
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <i class="fas fa-volume-up text-blue-700"></i>
                    <input id="volume-slider-mobile" type="range" min="0" max="1" step="0.01" value="1" class="h-1 w-24 accent-blue-600" />
                </div>
                <button id="pip-toggle-btn-mobile" class="rounded-full border border-blue-100 bg-blue-50 px-3 py-2 text-blue-700 transition hover:border-blue-300">
                    <i class="fas fa-compress-alt"></i>
                </button>
            </div>
        </div>
    </div>

    <audio id="player-audio" src="" preload="metadata"></audio>

<script>
        // Use immediate execution and also listen for Livewire load
        (function() {
            let initialized = false;

            function initAudioPlayer() {
                if (initialized) {
                    console.log('Audio player already initialized, skipping...');
                    return;
                }
                console.log('Initializing audio player...');
                initialized = true;

                // Get all relevant DOM elements
                const audioPlayer = document.getElementById('player-audio');
                const audioPlayerBar = document.getElementById('audio-player-bar');

                if (!audioPlayer || !audioPlayerBar) {
                    console.error('Audio player elements not found!');
                    return;
                }

                // Add error handling for audio loading
                audioPlayer.addEventListener('error', function(e) {
                    console.error('Audio loading error:', e);
                    console.error('Audio source:', audioPlayer.src);
                    console.error('Error code:', audioPlayer.error ? audioPlayer.error.code : 'unknown');
                });


                // Desktop elements
            const playPauseBtn = document.getElementById('play-pause-btn');
            const playPauseIcon = document.getElementById('play-pause-icon');
            const playerTitle = document.getElementById('player-title');
            const playerArtist = document.getElementById('player-artist');
            const playerAlbumArt = document.getElementById('player-album-art').querySelector('img');
            const volumeSlider = document.getElementById('volume-slider');
            const currentTimeEl = document.getElementById('current-time');
            const durationEl = document.getElementById('duration');
            const seekBar = document.getElementById('seek-bar');
            const closePlayerBtn = document.getElementById('close-player-btn');
            const prevBtn = document.getElementById('prev-btn');
            const nextBtn = document.getElementById('next-btn');
            const pipToggleBtn = document.getElementById('pip-toggle-btn');

            // Mobile elements
            const playPauseBtnMobile = document.getElementById('play-pause-btn-mobile');
            const playPauseIconMobile = document.getElementById('play-pause-icon-mobile');
            const playerTitleMobile = document.getElementById('player-title-mobile');
            const playerArtistMobile = document.getElementById('player-artist-mobile');
            const playerAlbumArtMobile = document.getElementById('player-album-art-mobile').querySelector('img');
            const volumeSliderMobile = document.getElementById('volume-slider-mobile');
            const currentTimeElMobile = document.getElementById('current-time-mobile');
            const durationElMobile = document.getElementById('duration-mobile');
            const seekBarMobile = document.getElementById('seek-bar-mobile');
            const closePlayerBtnMobile = document.getElementById('close-player-btn-mobile');
            const prevBtnMobile = document.getElementById('prev-btn-mobile');
            const nextBtnMobile = document.getElementById('next-btn-mobile');
            const pipToggleBtnMobile = document.getElementById('pip-toggle-btn-mobile');

            const sermonCards = document.querySelectorAll('.sermon-card');
            console.log('Found sermon cards:', sermonCards.length);

            let currentSermonIndex = -1;
            let isPipMode = false;

            if (sermonCards.length === 0) {
                console.warn('No sermon cards found on the page');
            }

            // Function to format time in minutes:seconds
            function formatTime(seconds) {
                if (isNaN(seconds)) return '0:00';
                const min = Math.floor(seconds / 60);
                const sec = Math.floor(seconds % 60);
                return `${min}:${sec < 10 ? '0' : ''}${sec}`;
            }

            // Update both desktop and mobile displays
            function updateTimeDisplays() {
                const currentTime = formatTime(audioPlayer.currentTime);
                const duration = formatTime(audioPlayer.duration);

                currentTimeEl.textContent = currentTime;
                durationEl.textContent = duration;
                currentTimeElMobile.textContent = currentTime;
                durationElMobile.textContent = duration;
            }

            function updateSeekBars() {
                if (!isSeeking) {
                    const progress = (audioPlayer.currentTime / audioPlayer.duration) * 100;
                    seekBar.value = progress;
                    seekBarMobile.value = progress;
                }
            }

            // Play/Pause functionality (desktop and mobile)
            function togglePlayPause() {
                if (audioPlayer.paused) {
                    if (audioPlayer.src) {
                        audioPlayer.play();
                    }
                } else {
                    audioPlayer.pause();
                }
            }

            playPauseBtn.addEventListener('click', togglePlayPause);
            playPauseBtnMobile.addEventListener('click', togglePlayPause);

            // Update icons on play/pause events - single icon toggle
            audioPlayer.addEventListener('play', () => {
                playPauseIcon.classList.remove('fa-play');
                playPauseIcon.classList.add('fa-pause');
                playPauseIconMobile.classList.remove('fa-play');
                playPauseIconMobile.classList.add('fa-pause');
            });

            audioPlayer.addEventListener('pause', () => {
                playPauseIcon.classList.remove('fa-pause');
                playPauseIcon.classList.add('fa-play');
                playPauseIconMobile.classList.remove('fa-pause');
                playPauseIconMobile.classList.add('fa-play');
            });
            
            // Also handle ended event to reset icon
            audioPlayer.addEventListener('ended', () => {
                playPauseIcon.classList.remove('fa-pause');
                playPauseIcon.classList.add('fa-play');
                playPauseIconMobile.classList.remove('fa-pause');
                playPauseIconMobile.classList.add('fa-play');
            });

            // Update the current time display and seek bar
            audioPlayer.addEventListener('timeupdate', () => {
                updateTimeDisplays();
                updateSeekBars();
            });

            // Update the total duration when the audio loads metadata
            audioPlayer.addEventListener('loadedmetadata', () => {
                updateTimeDisplays();
                seekBar.max = 100;
                seekBarMobile.max = 100;
            });

            // Also handle duration change event for better compatibility
            audioPlayer.addEventListener('durationchange', () => {
                updateTimeDisplays();
            });

            // Click event listeners for sermon cards
            sermonCards.forEach((card, index) => {
                card.addEventListener('click', (e) => {
                    try {
                        const audioSrc = card.dataset.audioSrc;

                        if (!audioSrc || audioSrc.trim() === '') {
                            console.warn('No audio source for this sermon');
                            return;
                        }

                        const title = card.dataset.title;
                        const artist = card.dataset.artist;
                        const imageSrc = card.dataset.imageSrc;

                        // Show the audio player bar
                        audioPlayerBar.classList.remove('hidden');
                        audioPlayerBar.classList.add('visible');

                        // Update player info (both desktop and mobile)
                        playerTitle.textContent = title;
                        playerArtist.textContent = artist;
                        playerAlbumArt.src = imageSrc;

                        playerTitleMobile.textContent = title;
                        playerArtistMobile.textContent = artist;
                        playerAlbumArtMobile.src = imageSrc;

                        // Update audio source and play
                        audioPlayer.src = audioSrc;
                        audioPlayer.load(); // Explicitly load the audio

                        // Play with error handling (silence AbortError as it's expected when switching tracks)
                        audioPlayer.play().catch(error => {
                            if (error.name !== 'AbortError') {
                                console.error('Playback failed:', error);
                            }
                        });

                        currentSermonIndex = index;
                    } catch (error) {
                        console.error('Error in click handler:', error);
                    }
                });
            });

            // Next button functionality
            function playNext() {
                if (currentSermonIndex !== -1 && currentSermonIndex < sermonCards.length - 1) {
                    sermonCards[currentSermonIndex + 1].click();
                } else if (currentSermonIndex === sermonCards.length - 1) {
                    sermonCards[0].click();
                }
            }

            nextBtn.addEventListener('click', playNext);
            nextBtnMobile.addEventListener('click', playNext);

            // Previous button functionality
            function playPrev() {
                if (currentSermonIndex > 0) {
                    sermonCards[currentSermonIndex - 1].click();
                } else if (currentSermonIndex === 0) {
                    sermonCards[sermonCards.length - 1].click();
                }
            }

            prevBtn.addEventListener('click', playPrev);
            prevBtnMobile.addEventListener('click', playPrev);

            // Volume slider functionality (sync both)
            volumeSlider.addEventListener('input', (event) => {
                audioPlayer.volume = event.target.value;
                volumeSliderMobile.value = event.target.value;
            });

            volumeSliderMobile.addEventListener('input', (event) => {
                audioPlayer.volume = event.target.value;
                volumeSlider.value = event.target.value;
            });

            // Close button functionality
            function closePlayer() {
                audioPlayer.pause();
                audioPlayerBar.classList.remove('visible', 'pip-mode');
                audioPlayerBar.classList.add('hidden');
                isPipMode = false;
            }

            closePlayerBtn.addEventListener('click', closePlayer);
            closePlayerBtnMobile.addEventListener('click', closePlayer);

            // PIP Toggle function
            function togglePipMode() {
                isPipMode = !isPipMode;
                if (isPipMode) {
                    audioPlayerBar.classList.add('pip-mode');
                    pipToggleBtn.innerHTML = '<i class="fas fa-expand-alt"></i>';
                    if (pipToggleBtnMobile) {
                        pipToggleBtnMobile.innerHTML = '<i class="fas fa-expand-alt"></i>';
                    }
                } else {
                    audioPlayerBar.classList.remove('pip-mode');
                    pipToggleBtn.innerHTML = '<i class="fas fa-compress-alt"></i>';
                    if (pipToggleBtnMobile) {
                        pipToggleBtnMobile.innerHTML = '<i class="fas fa-compress-alt"></i>';
                    }
                }
            }

            // PIP Toggle
            pipToggleBtn.addEventListener('click', togglePipMode);
            if (pipToggleBtnMobile) {
                pipToggleBtnMobile.addEventListener('click', togglePipMode);
            }

            // Seek bar functionality
            let isSeeking = false;

            function setupSeekBar(seekBarElement) {
                seekBarElement.addEventListener('mousedown', () => {
                    isSeeking = true;
                });

                seekBarElement.addEventListener('touchstart', () => {
                    isSeeking = true;
                });

                seekBarElement.addEventListener('mouseup', () => {
                    isSeeking = false;
                });

                seekBarElement.addEventListener('touchend', () => {
                    isSeeking = false;
                });

                seekBarElement.addEventListener('input', () => {
                    const time = (seekBarElement.value / 100) * audioPlayer.duration;
                    audioPlayer.currentTime = time;
                    // Sync the other seek bar
                    if (seekBarElement === seekBar) {
                        seekBarMobile.value = seekBarElement.value;
                    } else {
                        seekBar.value = seekBarElement.value;
                    }
                });
            }

            setupSeekBar(seekBar);
            setupSeekBar(seekBarMobile);
        }

        // Initialize on DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initAudioPlayer);
        } else {
            initAudioPlayer();
        }

        // Also reinitialize after Livewire navigation
        document.addEventListener('livewire:navigated', initAudioPlayer);
    })();
    </script>
</div>
