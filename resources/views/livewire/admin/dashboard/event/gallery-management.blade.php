<?php

use App\Models\Events;
use App\Models\EventGallery;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Livewire\Volt\Component;
use TallStackUi\Traits\Interactions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

new #[Layout('components.layouts.admin')] class extends Component
{
    use WithFileUploads, Interactions;

    public $events = [];
    public $selectedEvent = null;
    public $galleryImages = [];
    public $imageFile;
    public $imageTitle = '';
    public $uploadedCount = 0;
    public $editingImageId = null;
    public $editingTitle = '';

    public function mount()
    {
        $this->loadEvents();
    }

    public function loadEvents()
    {
        $this->events = Events::where('status', 'published')
            ->orderBy('start_at', 'desc')
            ->get();
    }

    public function selectEvent($eventId)
    {
        $this->selectedEvent = Events::findOrFail($eventId);
        $this->loadGalleryImages();
        $this->resetUpload();
    }

    public function loadGalleryImages()
    {
        if (!$this->selectedEvent) {
            return;
        }

        $this->galleryImages = EventGallery::where('event_id', $this->selectedEvent->id)
            ->orderBy('order_column', 'asc')
            ->get();
    }

    public function uploadImages()
    {
        $this->validate([
            'imageFile' => 'required|file|image|max:10240',
            'imageTitle' => 'nullable|string|max:255',
        ]);

        try {
            if (!$this->selectedEvent) {
                $this->toast()->error('Error', 'Please select an event first')->send();
                return;
            }

            $path = $this->imageFile->store('event-galleries', 'public');
            
            // Generate thumbnail
            $thumbnail = $path; // For now, use the same path
            
            EventGallery::create([
                'event_id' => $this->selectedEvent->id,
                'chapter_id' => $this->selectedEvent->chapter_id,
                'title' => $this->imageTitle ?: null,
                'file_path' => $path,
                'thumbnail_path' => $thumbnail,
                'mime_type' => $this->imageFile->getMimeType(),
                'size' => $this->imageFile->getSize(),
                'order_column' => EventGallery::where('event_id', $this->selectedEvent->id)->max('order_column') + 1 ?? 1,
            ]);

            $this->uploadedCount++;
            $this->resetUpload();
            $this->loadGalleryImages();

            $this->toast()
                ->success('Success', 'Image uploaded successfully')
                ->send();

        } catch (\Exception $e) {
            Log::error('Gallery upload failed', [
                'event_id' => $this->selectedEvent->id ?? null,
                'error' => $e->getMessage()
            ]);

            $this->toast()
                ->error('Upload Failed', $e->getMessage())
                ->send();
        }
    }

    public function startEditImage($imageId, $currentTitle)
    {
        $this->editingImageId = $imageId;
        $this->editingTitle = $currentTitle ?? '';
    }

    public function cancelEdit()
    {
        $this->editingImageId = null;
        $this->editingTitle = '';
    }

    public function updateImageTitle()
    {
        try {
            $image = EventGallery::findOrFail($this->editingImageId);
            $image->update(['title' => $this->editingTitle ?: null]);

            $this->cancelEdit();
            $this->loadGalleryImages();

            $this->toast()
                ->success('Success', 'Image title updated')
                ->send();
        } catch (\Exception $e) {
            $this->toast()
                ->error('Error', 'Failed to update image')
                ->send();
        }
    }

    public function deleteImage($imageId)
    {
        try {
            $image = EventGallery::findOrFail($imageId);
            
            // Delete the actual file
            if ($image->file_path && Storage::disk('public')->exists($image->file_path)) {
                Storage::disk('public')->delete($image->file_path);
            }
            
            $image->delete();
            $this->loadGalleryImages();

            $this->toast()
                ->success('Success', 'Image deleted successfully')
                ->send();
        } catch (\Exception $e) {
            $this->toast()
                ->error('Error', 'Failed to delete image')
                ->send();
        }
    }

    public function reorderImages($oldIndex, $newIndex)
    {
        try {
            $images = collect($this->galleryImages)->values()->toArray();
            
            // Move image
            $movedImage = array_splice($images, $oldIndex, 1)[0];
            array_splice($images, $newIndex, 0, [$movedImage]);

            // Update order columns
            foreach ($images as $index => $imageArray) {
                EventGallery::find($imageArray['id'])->update(['order_column' => $index + 1]);
            }

            $this->loadGalleryImages();
            $this->toast()->success('Success', 'Images reordered')->send();
        } catch (\Exception $e) {
            $this->toast()->error('Error', 'Failed to reorder images')->send();
        }
    }

    public function resetUpload()
    {
        $this->reset(['imageFile', 'imageTitle']);
    }
}; ?>

<style>
    :root {
        --primary: #667eea;
        --primary-dark: #764ba2;
        --shadow-soft: 0 10px 40px rgba(0, 0, 0, 0.1);
    }

    .gallery-container {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        box-shadow: var(--shadow-soft);
    }

    .event-selector {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .event-card {
        padding: 1.5rem;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
    }

    .event-card:hover {
        border-color: var(--primary);
        background: rgba(102, 126, 234, 0.05);
    }

    .event-card.selected {
        border-color: var(--primary);
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
    }

    .event-card h6 {
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: #1f2937;
    }

    .event-card small {
        color: #6b7280;
    }

    .upload-section {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
        border: 2px dashed var(--primary);
        border-radius: 12px;
        padding: 2rem;
        margin-bottom: 2rem;
        text-align: center;
    }

    .upload-section.disabled {
        opacity: 0.6;
        cursor: not-allowed;
        border-color: #d1d5db;
    }

    .upload-icon {
        font-size: 3rem;
        color: var(--primary);
        margin-bottom: 1rem;
    }

    .upload-input {
        display: none;
    }

    .upload-btn {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        border: none;
        color: white;
        padding: 0.8rem 2rem;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 1rem;
    }

    .upload-btn:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
    }

    .upload-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .gallery-item-wrapper {
        position: relative;
    }

    .gallery-item {
        position: relative;
        border-radius: 12px;
        overflow: hidden;
        background: white;
        border: 1px solid #e5e7eb;
        transition: all 0.3s ease;
    }

    .gallery-item:hover {
        box-shadow: var(--shadow-soft);
    }

    .gallery-item-image {
        width: 100%;
        height: 220px;
        object-fit: cover;
    }

    .gallery-item-info {
        padding: 1rem;
    }

    .gallery-item-title {
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #1f2937;
        word-break: break-word;
    }

    .gallery-item-meta {
        font-size: 0.85rem;
        color: #6b7280;
    }

    .gallery-item-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    .action-btn {
        flex: 1;
        padding: 0.5rem;
        border: 1px solid #e5e7eb;
        background: white;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.85rem;
        transition: all 0.2s ease;
    }

    .action-btn:hover {
        border-color: var(--primary);
        color: var(--primary);
    }

    .action-btn-delete:hover {
        border-color: #ef4444;
        color: #ef4444;
    }

    .edit-form {
        padding: 1rem;
        background: #f3f4f6;
        border-radius: 8px;
        margin-top: 0.5rem;
    }

    .edit-input {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }

    .edit-actions {
        display: flex;
        gap: 0.5rem;
    }

    .edit-actions button {
        flex: 1;
        padding: 0.5rem;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.85rem;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .edit-btn-save {
        background: var(--primary);
        color: white;
    }

    .edit-btn-save:hover {
        background: var(--primary-dark);
    }

    .edit-btn-cancel {
        background: #e5e7eb;
        color: #1f2937;
    }

    .edit-btn-cancel:hover {
        background: #d1d5db;
    }

    .empty-state {
        text-align: center;
        padding: 3rem 2rem;
        color: #6b7280;
    }

    .empty-state-icon {
        font-size: 3rem;
        color: #d1d5db;
        margin-bottom: 1rem;
    }

    .empty-state h5 {
        color: #1f2937;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .gallery-grid {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
        }

        .event-selector {
            grid-template-columns: 1fr;
        }

        .upload-section {
            padding: 1.5rem;
        }
    }
</style>

<x-fancy-header title="Event Gallery Management" subtitle="Manage event photos and galleries" :breadcrumbs="[['label' => 'Home', 'url' => route('admin.dashboard', request()->query())], ['label' => 'Events', 'url' => route('admin.dashboard.events.index', request()->query())], ['label' => 'Gallery']]">
        <x-slot:actions>
            <a href="{{ route('admin.dashboard.events.index', request()->query()) }}">
                <x-button color="secondary" icon="arrow-left" label="Back to Events" />
            </a>
        </x-slot:actions>
    </x-fancy-header>

    <div class="gallery-container">
        <h2 class="h4 mb-4">
            <i class="bi bi-images me-2" style="color: var(--primary);"></i>
            Select Event
        </h2>

        <!-- Event Selection -->
        <div class="mb-4">
            <h6 class="mb-3 text-muted">Select an Event</h6>
            <div class="event-selector">
                @forelse($events as $event)
                    <div 
                        class="event-card @if($selectedEvent && $selectedEvent->id === $event->id) selected @endif"
                        wire:click="selectEvent({{ $event->id }})"
                    >
                        <h6 class="mb-2">{{ Str::limit($event->title, 20) }}</h6>
                        <small>{{ $event->start_at->format('M d, Y') }}</small>
                        <br>
                        <small class="text-muted">
                            {{ $event->galleries()->count() }} images
                        </small>
                    </div>
                @empty
                    <div class="alert alert-info w-100" role="alert">
                        <i class="bi bi-info-circle me-2"></i>
                        No published events found
                    </div>
                @endforelse
            </div>
        </div>

        @if($selectedEvent)
            <!-- Upload Section -->
            <div class="upload-section @if(!$selectedEvent) disabled @endif">
                <div class="upload-icon">
                    <i class="bi bi-cloud-arrow-up"></i>
                </div>
                <h5>Upload Event Images</h5>
                <p class="text-muted mb-3">Drag and drop your images or click to browse</p>

                <input 
                    type="file"
                    class="upload-input"
                    id="imageFile"
                    wire:model="imageFile"
                    accept="image/*"
                    @if(!$selectedEvent) disabled @endif
                >

                <input 
                    type="text"
                    class="form-control mb-3"
                    placeholder="Image title (optional)"
                    wire:model="imageTitle"
                    maxlength="255"
                >

                <label for="imageFile" class="upload-btn">
                    <i class="bi bi-plus-circle me-2"></i>
                    Choose Images
                </label>

                @if($imageFile)
                    <div class="mt-3">
                        <p class="text-muted small">
                            <i class="bi bi-file-image me-2"></i>
                            Selected: {{ $imageFile->getClientOriginalName() }}
                        </p>
                        <button 
                            type="button"
                            class="upload-btn"
                            wire:click="uploadImages"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove>
                                <i class="bi bi-upload me-2"></i>
                                Upload Image
                            </span>
                            <span wire:loading>
                                <i class="bi bi-hourglass-split me-2"></i>
                                Uploading...
                            </span>
                        </button>
                    </div>
                @endif

                @error('imageFile') 
                    <div class="alert alert-danger mt-3 mb-0">{{ $message }}</div> 
                @enderror
                @error('imageTitle') 
                    <div class="alert alert-danger mt-3 mb-0">{{ $message }}</div> 
                @enderror
            </div>

            <!-- Gallery Grid -->
            @if($galleryImages->count() > 0)
                <div>
                    <h6 class="mb-3 text-muted">Gallery Images ({{ $galleryImages->count() }})</h6>
                    <div class="gallery-grid">
                        @foreach($galleryImages as $image)
                            <div class="gallery-item-wrapper">
                                <div class="gallery-item">
                                    <img 
                                        src="{{ Storage::url($image->file_path) }}" 
                                        alt="{{ $image->title ?? 'Gallery Image' }}"
                                        class="gallery-item-image"

                                    >
                                    <div class="gallery-item-info">
                                        @if($editingImageId === $image->id)
                                            <div class="edit-form">
                                                <input 
                                                    type="text"
                                                    class="edit-input"
                                                    wire:model="editingTitle"
                                                    placeholder="Image title"
                                                >
                                                <div class="edit-actions">
                                                    <button 
                                                        class="edit-btn-save"
                                                        wire:click="updateImageTitle"
                                                    >
                                                        Save
                                                    </button>
                                                    <button 
                                                        class="edit-btn-cancel"
                                                        wire:click="cancelEdit"
                                                    >
                                                        Cancel
                                                    </button>
                                                </div>
                                            </div>
                                        @else
                                            <p class="gallery-item-title">
                                                {{ $image->title ?? 'Untitled Image' }}
                                            </p>
                                            <p class="gallery-item-meta">
                                                <i class="bi bi-calendar"></i> 
                                                {{ $image->created_at->format('M d, Y') }}
                                            </p>
                                            <div class="gallery-item-actions">
                                                <button 
                                                    class="action-btn"
                                                    wire:click="startEditImage({{ $image->id }}, {{ json_encode($image->title ?? '') }})"
                                                >
                                                    <i class="bi bi-pencil-square me-1"></i> Edit
                                                </button>
                                                <button 
                                                    class="action-btn action-btn-delete"
                                                    wire:click="deleteImage({{ $image->id }})"
                                                    wire:confirm="Are you sure you want to delete this image?"
                                                >
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="bi bi-image"></i>
                    </div>
                    <h5>No Images Yet</h5>
                    <p>Upload images above to create a gallery for this event</p>
                </div>
            @endif
        @else
            <div class="alert alert-info" role="alert">
                <i class="bi bi-info-circle me-2"></i>
                Select an event above to manage its gallery
            </div>
        @endif
    </div>
</div>
