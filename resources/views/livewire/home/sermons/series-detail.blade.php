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
                @if($series->image)
                    <img src="{{ $this->resolveMediaUrl($series->image) }}" alt="{{ $series->title }}" class="h-40 w-40 rounded-2xl border border-white/20 object-cover shadow-lg">
                @endif
            </div>
        </div>

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

                    <div class="overflow-hidden rounded-2xl border border-blue-100 bg-white">
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
                                <div class="rounded-2xl border border-blue-100 bg-blue-50/70 p-4">
                                    <p class="mb-2 text-xs font-semibold uppercase tracking-[0.25em] text-blue-700">Audio</p>
                                    <audio controls preload="metadata" class="w-full">
                                        <source src="{{ $audioUrl }}" type="{{ $audioMedia?->mime_type ?? 'audio/mpeg' }}">
                                        Your browser does not support the audio tag.
                                    </audio>
                                    <a href="{{ $audioUrl }}" download class="mt-3 inline-flex items-center rounded-xl border border-blue-200 bg-white px-4 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-50">
                                        Download Audio
                                    </a>
                                </div>
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
