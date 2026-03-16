<?php

use App\Models\EventGallery;
use App\Models\Events;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.tailwind-layout')] class extends Component
{
    public $event;
    public $event_id;
    public $galleryImages = [];
    public $selectedImage = null;

    public function mount($event)
    {
        if (is_numeric($event)) {
            $this->event = Events::findOrFail($event);
        } else {
            $this->event = Events::where('slug', $event)->firstOrFail();
        }

        if (! $this->event->hasStarted()) {
            abort(403, 'The event gallery will be available once the event starts.');
        }

        $this->loadGalleryImages();
    }

    public function loadGalleryImages()
    {
        $this->galleryImages = EventGallery::where('event_id', $this->event->id)
            ->orderBy('order_column', 'asc')
            ->get();
    }

    public function openImageModal($imageId)
    {
        $this->selectedImage = EventGallery::findOrFail($imageId);
    }

    public function closeImageModal()
    {
        $this->selectedImage = null;
    }
}; ?>

<div class="bg-white py-10 sm:py-14">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <section class="rounded-3xl border border-blue-100 bg-gradient-to-b from-blue-50 to-white p-8 shadow-sm sm:p-10">
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-600">Event Gallery</p>
            <h1 class="mt-3 text-3xl font-semibold text-slate-900 sm:text-4xl">{{ $event->title }}</h1>
            <p class="mt-2 text-sm text-slate-600">Moments from {{ $event->start_at->format('F d, Y') }}</p>
        </section>

        @if($galleryImages->count() > 0)
            <section class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($galleryImages as $image)
                    <article
                        class="group cursor-pointer overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-md"
                        wire:click="openImageModal({{ $image->id }})"
                    >
                        <div class="relative overflow-hidden">
                            <img
                                src="{{ Storage::url($image->file_path) }}"
                                alt="{{ $image->title ?? 'Gallery Image' }}"
                                class="h-64 w-full object-cover transition duration-300 group-hover:scale-105"
                                onerror="this.src='{{ asset('images/placeholder.jpg') }}'"
                            >
                        </div>

                        @if($image->title)
                            <div class="p-4">
                                <h3 class="text-base font-semibold text-slate-900">{{ $image->title }}</h3>
                                <p class="mt-1 text-xs text-slate-500">{{ $image->created_at->format('M d, Y') }}</p>
                            </div>
                        @endif
                    </article>
                @endforeach
            </section>
        @else
            <section class="mt-8 rounded-2xl border border-dashed border-blue-200 bg-blue-50/40 px-6 py-14 text-center">
                <h3 class="text-lg font-semibold text-slate-800">No Images Yet</h3>
                <p class="mt-2 text-sm text-slate-500">The event gallery is currently empty. Check back soon for photos from the event.</p>
            </section>
        @endif

        <div class="mt-8 text-center">
            <a href="{{ route('events.index') }}" wire:navigate class="inline-flex items-center rounded-full bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                Back to Events
            </a>
        </div>
    </div>

    @if($selectedImage)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/70 p-4" wire:click="closeImageModal">
            <div class="w-full max-w-4xl overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-2xl" wire:click.stop>
                <div class="relative bg-slate-900">
                    <button
                        type="button"
                        wire:click="closeImageModal"
                        class="absolute right-4 top-4 z-10 rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-slate-700 transition hover:bg-white"
                    >
                        Close
                    </button>
                    <img
                        src="{{ Storage::url($selectedImage->file_path) }}"
                        alt="{{ $selectedImage->title ?? 'Gallery Image' }}"
                        class="max-h-[70vh] w-full object-contain"
                        onerror="this.src='{{ asset('images/placeholder.jpg') }}'"
                    >
                </div>

                <div class="space-y-2 p-5">
                    @if($selectedImage->title)
                        <h4 class="text-lg font-semibold text-slate-900">{{ $selectedImage->title }}</h4>
                    @endif
                    <p class="text-sm text-slate-500">{{ $selectedImage->created_at->format('F d, Y \a\t h:i A') }}</p>
                    <p class="text-xs text-slate-500">{{ number_format($selectedImage->size / 1024 / 1024, 2) }} MB</p>
                </div>
            </div>
        </div>
    @endif
</div>
