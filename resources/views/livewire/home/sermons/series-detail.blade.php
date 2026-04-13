<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Url};
use App\Models\{SermonSeries, Sermons};
use Illuminate\Support\Facades\Storage;

new #[Layout('components.layouts.tailwind-layout')] class extends Component {

    #[Url]
    public $id;

    public $series;
    public $sermons;
    public $selectedSermon = null;

    public function mount()
    {
        $this->series = SermonSeries::with(['sermons' => function ($q) {
            $q->with('media')->latest('preached_at');
        }])->findOrFail($this->id);

        $this->sermons = $this->series->sermons;
        $this->selectedSermon = $this->sermons->first(function ($sermon) {
            return $sermon->media->isNotEmpty();
        }) ?? $this->sermons->first();
    }

    public function selectSermon($sermonId)
    {
        $this->selectedSermon = Sermons::with('media')->findOrFail($sermonId);
        
        // Dispatch browser event to reload audio player
        $this->dispatch('sermon-changed', sermonId: $this->selectedSermon->id);
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

}; ?>

<div class="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <section class="overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-[0_24px_60px_-40px_rgba(37,99,235,0.45)]">
        @if($series->image)
            <div class="relative overflow-hidden rounded-t-3xl bg-cover bg-center bg-no-repeat px-6 py-16 text-white sm:px-10 sm:py-24"
                 style="background-image: url('{{ $this->resolveMediaUrl($series->image) }}');">
                <div class="absolute inset-0 bg-gradient-to-r from-black/70 via-black/50 to-black/70"></div>
                <div class="relative z-10">
                    <a href="{{ route('sermons.index') }}" wire:navigate class="inline-flex items-center gap-2 rounded-full border border-white/30 bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-white hover:bg-white/20">
                        Back to Sermons
                    </a>
                    <div class="mt-5">
                        <p class="text-xs uppercase tracking-[0.3em] text-blue-200">Series</p>
                        <h1 class="mt-2 text-3xl font-bold sm:text-4xl">{{ $series->title }}</h1>
                        @if($series->description)
                            <p class="mt-3 max-w-3xl text-sm text-gray-200 sm:text-base">{{ $series->description }}</p>
                        @endif
                        <div class="mt-4 flex flex-wrap gap-2 text-xs">
                            <span class="rounded-full bg-white/15 px-3 py-1 font-semibold text-white">{{ $sermons->count() }} Sermons</span>
                            @if($series->created_at)
                                <span class="rounded-full bg-white/15 px-3 py-1 font-semibold text-white">Since {{ $series->created_at->format('M Y') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="bg-gradient-to-r from-blue-600 to-blue-500 px-6 py-10 text-white sm:px-10">
                <a href="{{ route('sermons.index') }}" wire:navigate class="inline-flex items-center gap-2 rounded-full border border-blue-200/50 bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-blue-100 hover:bg-white/20">
                    Back to Sermons
                </a>
                <div class="mt-5 flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-[0.3em] text-blue-100">Series</p>
                        <h1 class="mt-2 text-3xl font-bold sm:text-4xl">{{ $series->title }}</h1>
                        @if($series->description)
                            <p class="mt-3 max-w-3xl text-sm text-blue-100 sm:text-base">{{ $series->description }}</p>
                        @endif
                        <div class="mt-4 flex flex-wrap gap-2 text-xs">
                            <span class="rounded-full bg-white/15 px-3 py-1 font-semibold text-white">{{ $sermons->count() }} Sermons</span>
                            @if($series->created_at)
                                <span class="rounded-full bg-white/15 px-3 py-1 font-semibold text-white">Since {{ $series->created_at->format('M Y') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="grid gap-6 p-6 lg:grid-cols-[1.4fr_0.9fr] lg:p-8">
            <div class="space-y-5">
                @if($selectedSermon)
                    @php
                        $videoMedia = $selectedSermon->media->where('type', 'video')->last();
                        $audioMedia = $selectedSermon->media->where('type', 'audio')->last();
                        $videoPath = $videoMedia?->file_path;
                        $videoUrl = $this->resolveMediaUrl($videoPath);
                        $audioUrl = $this->resolveMediaUrl($audioMedia?->file_path);
                        $youtubeId = null;

                        if ($videoUrl && (str_contains($videoUrl, 'youtube.com') || str_contains($videoUrl, 'youtu.be'))) {
                            if (preg_match('/youtube\.com\/watch\?v=([^&]+)/', $videoUrl, $matches)) {
                                $youtubeId = $matches[1];
                            } elseif (preg_match('/youtu\.be\/([^?]+)/', $videoUrl, $matches)) {
                                $youtubeId = $matches[1];
                            }
                        }
                    @endphp

                    <div wire:key="sermon-player-{{ $selectedSermon->id }}" class="overflow-hidden rounded-2xl border border-blue-100 bg-white">
                        <div class="aspect-video bg-slate-950">
                            @if($videoUrl)
                                @if($youtubeId)
                                    <iframe
                                        class="h-full w-full"
                                        src="https://www.youtube.com/embed/{{ $youtubeId }}"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                        allowfullscreen
                                    ></iframe>
                                @else
                                    <video controls controlsList="nodownload" class="h-full w-full">
                                        <source src="{{ $videoUrl }}" type="{{ $videoMedia?->mime_type ?? 'video/mp4' }}">
                                        Your browser does not support the video tag.
                                    </video>
                                @endif
                            @else
                                <div class="flex h-full items-center justify-center text-sm text-blue-100">
                                    No video available for this sermon yet.
                                </div>
                            @endif
                        </div>

                        <div class="space-y-4 p-5">
                            <h2 class="text-2xl font-semibold text-slate-900">{{ $selectedSermon->title }}</h2>

                            <div class="space-y-1 text-sm text-slate-600">
                                @if($selectedSermon->speaker_name)
                                    <p><span class="font-semibold text-slate-800">Speaker:</span> {{ $selectedSermon->speaker_name }}</p>
                                @endif

                                @if($selectedSermon->preached_at)
                                    <p><span class="font-semibold text-slate-800">Date:</span> {{ $selectedSermon->preached_at->format('F d, Y') }}</p>
                                @endif

                                @if($selectedSermon->scripture_reference)
                                    <p><span class="font-semibold text-slate-800">Scripture:</span> {{ $selectedSermon->scripture_reference }}</p>
                                @endif
                            </div>

                            @if($selectedSermon->description)
                                <p class="text-sm leading-relaxed text-slate-600">{{ $selectedSermon->description }}</p>
                            @endif

                            @if($audioUrl)
                                <div class="rounded-2xl border border-blue-100 bg-blue-50/70 p-4" id="sermon-audio-player" data-audio-url="{{ $audioUrl }}" data-audio-type="{{ $audioMedia?->mime_type ?? 'audio/mpeg' }}" data-sermon-id="{{ $selectedSermon->id }}">
                                    <p class="mb-3 text-xs font-semibold uppercase tracking-[0.25em] text-blue-700">Audio</p>

                                    {{-- Custom Audio Controls --}}
                                    <div class="flex items-center gap-4">
                                        <button type="button" id="audio-play-btn" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-600 text-white transition hover:bg-blue-700">
                                            <i class="fas fa-play"></i>
                                        </button>
                                        <div class="flex min-w-0 flex-1 flex-col gap-1">
                                            <div class="flex items-center gap-2">
                                                <span id="audio-current-time" class="w-10 text-xs font-mono text-slate-500">0:00</span>
                                                <input type="range" id="audio-seek-bar" min="0" max="100" value="0" step="0.1" 
                                                       class="h-1.5 flex-1 cursor-pointer accent-blue-600" 
                                                       style="background: linear-gradient(to right, #2563eb 0%, #2563eb 0%, #e2e8f0 0%, #e2e8f0 100%);">
                                                <span id="audio-duration" class="w-10 text-xs font-mono text-slate-500">0:00</span>
                                            </div>
                                        </div>
                                    </div>

                                    <audio id="sermon-audio" preload="auto" class="hidden">
                                        <source src="{{ $audioUrl }}" type="{{ $audioMedia?->mime_type ?? 'audio/mpeg' }}">
                                    </audio>

                                    <style>
                                    #audio-seek-bar::-webkit-slider-runnable-track {
                                        background: #dbeafe;
                                        border-radius: 9999px;
                                        height: 6px;
                                    }
                                    #audio-seek-bar::-moz-range-track {
                                        background: #dbeafe;
                                        border-radius: 9999px;
                                        height: 6px;
                                    }
                                    #audio-seek-bar::-webkit-slider-thumb {
                                        -webkit-appearance: none;
                                        appearance: none;
                                        height: 16px;
                                        width: 16px;
                                        background-color: #2563eb;
                                        border-radius: 50%;
                                        margin-top: -5px;
                                        box-shadow: 0 0 3px rgba(0, 0, 0, 0.5);
                                        cursor: pointer;
                                        transition: transform 0.1s ease;
                                    }
                                    #audio-seek-bar::-webkit-slider-thumb:hover {
                                        transform: scale(1.2);
                                    }
                                    #audio-seek-bar::-moz-range-thumb {
                                        height: 16px;
                                        width: 16px;
                                        background-color: #2563eb;
                                        border-radius: 50%;
                                        border: none;
                                        cursor: pointer;
                                        transition: transform 0.1s ease;
                                    }
                                    #audio-seek-bar::-moz-range-thumb:hover {
                                        transform: scale(1.2);
                                    }
                                    </style>

                                    <div class="mt-3">
                                        <a href="{{ $audioUrl }}" download class="inline-flex items-center gap-1.5 rounded-xl border border-blue-200 bg-white px-4 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-50">
                                            <i class="fas fa-download text-xs"></i> Download Audio
                                        </a>
                                    </div>
                                </div>

                                <script>
                                (function() {
                                    let audioEl, playBtn, playIcon, seekBar, currentTimeEl, durationEl;

                                    function formatTime(s) {
                                        if (isNaN(s) || !isFinite(s)) return '0:00';
                                        const m = Math.floor(s / 60);
                                        const sec = Math.floor(s % 60);
                                        return m + ':' + (sec < 10 ? '0' : '') + sec;
                                    }

                                    function initAudioPlayer() {
                                        // Get element references
                                        audioEl = document.getElementById('sermon-audio');
                                        playBtn = document.getElementById('audio-play-btn');
                                        seekBar = document.getElementById('audio-seek-bar');
                                        currentTimeEl = document.getElementById('audio-current-time');
                                        durationEl = document.getElementById('audio-duration');

                                        if (!audioEl || !playBtn || !seekBar) {
                                            console.warn('Audio elements not found');
                                            return;
                                        }

                                        playIcon = playBtn.querySelector('i');

                                        // Reset UI
                                        currentTimeEl.textContent = '0:00';
                                        durationEl.textContent = '0:00';
                                        seekBar.value = 0;
                                        seekBar.style.background = 'linear-gradient(to right, #2563eb 0%, #2563eb 0%, #e2e8f0 0%, #e2e8f0 100%)';
                                        if (playIcon) {
                                            playIcon.classList.remove('fa-pause');
                                            playIcon.classList.add('fa-play');
                                        }

                                        // Event: Metadata loaded
                                        audioEl.addEventListener('loadedmetadata', function() {
                                            console.log('Audio loaded metadata, duration:', audioEl.duration);
                                            durationEl.textContent = formatTime(audioEl.duration);
                                            seekBar.value = 0;
                                            currentTimeEl.textContent = '0:00';
                                        });

                                        // Event: Duration changed
                                        audioEl.addEventListener('durationchange', function() {
                                            durationEl.textContent = formatTime(audioEl.duration);
                                        });

                                        // Event: Time update (playback progress)
                                        audioEl.addEventListener('timeupdate', function() {
                                            if (!audioEl.duration) return;
                                            const progress = (audioEl.currentTime / audioEl.duration) * 100;
                                            seekBar.value = progress;
                                            seekBar.style.background = `linear-gradient(to right, #2563eb 0%, #2563eb ${progress}%, #e2e8f0 ${progress}%, #e2e8f0 100%)`;
                                            currentTimeEl.textContent = formatTime(audioEl.currentTime);
                                        });

                                        // Event: Play
                                        audioEl.addEventListener('play', function() {
                                            if (playIcon) {
                                                playIcon.classList.remove('fa-play');
                                                playIcon.classList.add('fa-pause');
                                            }
                                        });

                                        // Event: Pause
                                        audioEl.addEventListener('pause', function() {
                                            if (playIcon) {
                                                playIcon.classList.remove('fa-pause');
                                                playIcon.classList.add('fa-play');
                                            }
                                        });

                                        // Event: Ended
                                        audioEl.addEventListener('ended', function() {
                                            if (playIcon) {
                                                playIcon.classList.remove('fa-pause');
                                                playIcon.classList.add('fa-play');
                                            }
                                            seekBar.value = 0;
                                            seekBar.style.background = 'linear-gradient(to right, #2563eb 0%, #2563eb 0%, #e2e8f0 0%, #e2e8f0 100%)';
                                            currentTimeEl.textContent = '0:00';
                                        });

                                        // Event: Error
                                        audioEl.addEventListener('error', function(e) {
                                            console.error('Audio error:', e);
                                            console.error('Audio src:', audioEl.src);
                                            durationEl.textContent = 'Error';
                                        });

                                        // Play button click
                                        playBtn.addEventListener('click', function() {
                                            if (audioEl.paused) {
                                                audioEl.play().catch(function(err) {
                                                    if (err.name !== 'AbortError') {
                                                        console.error('Playback failed:', err);
                                                    }
                                                });
                                            } else {
                                                audioEl.pause();
                                            }
                                        });

                                        // Seek bar input (while dragging)
                                        let isSeeking = false;
                                        seekBar.addEventListener('input', function() {
                                            isSeeking = true;
                                            const progress = seekBar.value;
                                            seekBar.style.background = `linear-gradient(to right, #2563eb 0%, #2563eb ${progress}%, #e2e8f0 ${progress}%, #e2e8f0 100%)`;
                                        });

                                        // Seek bar change (when released)
                                        seekBar.addEventListener('change', function() {
                                            if (audioEl.duration) {
                                                const time = (parseFloat(seekBar.value) / 100) * audioEl.duration;
                                                audioEl.currentTime = time;
                                                currentTimeEl.textContent = formatTime(time);
                                            }
                                            isSeeking = false;
                                        });

                                        // Force audio to load
                                        audioEl.load();
                                        console.log('Audio player initialized, src:', audioEl.src);
                                    }

                                    // Initial load
                                    if (document.readyState === 'loading') {
                                        document.addEventListener('DOMContentLoaded', function() {
                                            setTimeout(initAudioPlayer, 100);
                                        });
                                    } else {
                                        setTimeout(initAudioPlayer, 100);
                                    }

                                    // When sermon changes via Livewire event
                                    document.addEventListener('livewire:navigated', function() {
                                        setTimeout(initAudioPlayer, 150);
                                    });

                                    // Custom event from Livewire when sermon changes
                                    window.addEventListener('sermon-changed', function(e) {
                                        console.log('Sermon changed event received, reloading audio...');
                                        // Small delay to let Livewire update the DOM
                                        setTimeout(function() {
                                            // Destroy old audio element
                                            if (audioEl) {
                                                audioEl.pause();
                                                audioEl.src = '';
                                                audioEl.load();
                                                var parent = audioEl.parentNode;
                                                if (parent) {
                                                    parent.removeChild(audioEl);
                                                }
                                            }
                                            
                                            // Reinitialize with new audio
                                            setTimeout(initAudioPlayer, 50);
                                        }, 100);
                                    });
                                })();
                                </script>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <aside class="overflow-hidden rounded-2xl border border-blue-100 bg-white">
                <div class="border-b border-blue-100 bg-blue-50 px-4 py-4">
                    <h3 class="text-base font-semibold text-slate-900">All Sermons in this Series</h3>
                    <p class="text-xs text-slate-500">{{ $sermons->count() }} sermon(s)</p>
                </div>

                <div class="max-h-[700px] overflow-y-auto">
                    @forelse($sermons as $sermon)
                        @php
                            $itemIsActive = $selectedSermon && $selectedSermon->id === $sermon->id;
                            $thumb = $this->resolveMediaUrl($sermon->image_path);
                        @endphp
                        <button
                            type="button"
                            wire:click="selectSermon({{ $sermon->id }})"
                            @class([
                                'flex w-full items-center gap-3 border-b border-blue-50 px-4 py-3 text-left transition',
                                'bg-blue-50' => $itemIsActive,
                                'hover:bg-blue-50/60' => !$itemIsActive,
                            ])
                        >
                            @if($thumb)
                                <img src="{{ $thumb }}" alt="{{ $sermon->title }}" class="h-14 w-14 rounded-lg object-cover">
                            @else
                                <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                                    <i class="fas fa-circle-play"></i>
                                </div>
                            @endif

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $sermon->title }}</p>
                                <p class="text-xs text-slate-500">{{ $sermon->speaker_name ?? 'Unknown' }} @if($sermon->preached_at) • {{ $sermon->preached_at->format('M d, Y') }} @endif</p>
                            </div>

                            <span @class(['text-sm font-semibold', 'text-blue-700' => $itemIsActive, 'text-slate-400' => !$itemIsActive])>
                                {{ $itemIsActive ? 'Playing' : 'Open' }}
                            </span>
                        </button>
                    @empty
                        <div class="p-8 text-center text-sm text-slate-500">No sermons in this series yet.</div>
                    @endforelse
                </div>
            </aside>
        </div>
    </section>
</div>
