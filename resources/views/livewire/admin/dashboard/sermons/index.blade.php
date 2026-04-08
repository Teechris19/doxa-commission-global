<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Models\Sermons;
use App\Models\SermonSeries;
use App\Models\SermonMedia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use TallStackUi\Traits\Interactions;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.admin')]  class extends Component {
    use WithFileUploads, WithPagination, Interactions;

    // Sermon form fields
    public $selectedSermon = null;
    public $title = '';
    public $description = '';
    public $preached_at = '';
    public $image;
    public $series_id = '';

    // Media files
    public $audioFile;
    public $videoFile;

    // Series form fields
    public $selectedSeries = null;
    public $seriesTitle = '';
    public $seriesDescription = '';
    public $seriesImage;

    // UI state
    public $editMode = false;
    public $activeTab = 'sermons';
    public ?string $search = null;
    public ?int $quantity = 10;
    public ?string $seriesSearch = null;
    public ?int $seriesQuantity = 10;
    public array $selected = [];

    /**
     * Table headers and data
     */
    public function with(): array
    {
        return [
            'sermonHeaders' => [
                ['index' => 'image_path', 'label' => 'Image'],
                ['index' => 'title', 'label' => 'Title'],
                ['index' => 'series', 'label' => 'Series'],
                ['index' => 'preached_at', 'label' => 'Date'],
                ['index' => 'media', 'label' => 'Media'],
                ['index' => 'action', 'label' => 'Action']
            ],
            'sermonRows' => $this->sermonRows(),
            'seriesHeaders' => [
                ['index' => 'image', 'label' => 'Image'],
                ['index' => 'title', 'label' => 'Title'],
                ['index' => 'description', 'label' => 'Description'],
                ['index' => 'sermons_count', 'label' => 'Sermons'],
                ['index' => 'action', 'label' => 'Action'],
            ],
            'seriesRows' => $this->seriesRows(),
            'series' => SermonSeries::latest()->get(),
        ];
    }

    /**
     * Query sermon rows with filtering + pagination
     */
    public function sermonRows()
    {
        return Sermons::with(['series', 'media'])
            ->when($this->search, fn($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate($this->quantity)
            ->withQueryString();
    }

    public function seriesRows()
    {
        return SermonSeries::withCount('sermons')
            ->when($this->seriesSearch, function ($query) {
                $query->where('title', 'like', "%{$this->seriesSearch}%")
                    ->orWhere('description', 'like', "%{$this->seriesSearch}%");
            })
            ->latest()
            ->paginate($this->seriesQuantity)
            ->withQueryString();
    }

    // Sermon CRUD
    public $speaker_name = '';
    
    public function createSermon()
    {
        $this->resetSermonForm();
        $this->editMode = false;
    }

    public function editSermon($id)
    {
        $this->selectedSermon = Sermons::findOrFail($id);
        $this->title = $this->selectedSermon->title;
        $this->speaker_name = $this->selectedSermon->speaker_name ?? '';
        $this->description = $this->selectedSermon->description;
        $this->preached_at = $this->selectedSermon->preached_at->format('Y-m-d');
        $this->series_id = $this->selectedSermon->series_id;
        $this->editMode = true;
    }

    public function saveSermon()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'speaker_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'preached_at' => 'required|date',
            'series_id' => 'required|exists:series,id',
            'image' => $this->editMode ? 'nullable|image|max:2048' : 'required|image|max:2048',
            'audioFile' => 'nullable|file|mimes:mp3,wav,m4a,aac,ogg,flac|max:512000',
            'videoFile' => 'nullable|file|mimes:mp4,mov,avi,mkv,webm|max:512000',
        ]);

        $imagePath = $this->selectedSermon?->image_path;
        if ($this->image) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = $this->image->store('sermons/images', 'public');
        }

        $sermon = $this->editMode ? $this->selectedSermon : new Sermons();
        $sermon->fill([
            'title' => $this->title,
            'speaker_name' => $this->speaker_name,
            'description' => $this->description,
            'preached_at' => $this->preached_at,
            'series_id' => $this->series_id,
            'image_path' => $imagePath,
        ]);
        $sermon->save();

        // Attach media immediately so playback works even without a queue worker.
        if ($this->audioFile) {
            $this->storeSermonMedia($sermon, $this->audioFile, 'audio');
        }

        if ($this->videoFile) {
            $this->storeSermonMedia($sermon, $this->videoFile, 'video');
        }

        $this->toast()->success('Done!', $this->editMode ? 'Sermon updated successfully!' : 'Sermon created successfully!')->send();
        $this->resetSermonForm();
        $this->dispatch('close-modal', 'sermon-modal');
        $this->dispatch('$refresh');
    }

    public function deleteSermon($id)
    {
        $this->dialog()
            ->error('Are you sure you want to delete this sermon?')
            ->hook([
                'ok' => [
                    'method' => 'confirmDeleteSermon',
                    'params' => [$id],
                ],
            ])
            ->send();
    }

    public function confirmDeleteSermon($id)
    {
        $sermon = Sermons::findOrFail($id);

        if ($sermon->image_path) {
            Storage::disk('public')->delete($sermon->image_path);
        }

        foreach ($sermon->media as $media) {
            Storage::disk('public')->delete($media->file_path);
        }

        $sermon->delete();
        $this->toast()->success('Done!', 'Sermon deleted successfully!')->send();
        $this->dispatch('$refresh');
    }

    public function deleteMedia($mediaId)
    {
        $media = SermonMedia::findOrFail($mediaId);
        Storage::disk('public')->delete($media->file_path);
        $media->delete();

        $this->toast()->success('Done!', 'Media deleted successfully!')->send();
        $this->dispatch('$refresh');
    }

    // Series CRUD
    public function createSeries()
    {
        $this->resetSeriesForm();
        $this->editMode = false;
    }

    public function editSeries($id)
    {
        $this->selectedSeries = SermonSeries::findOrFail($id);
        $this->seriesTitle = $this->selectedSeries->title;
        $this->seriesDescription = $this->selectedSeries->description;
        $this->editMode = true;
    }

    public function saveSeries()
    {
        $this->validate([
            'seriesTitle' => 'required|string|max:255',
            'seriesDescription' => 'nullable|string',
            'seriesImage' => 'nullable|image|max:2048',
        ]);

        $imagePath = $this->selectedSeries?->image;
        if ($this->seriesImage) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = $this->seriesImage->store('series/images', 'public');
        }

        $series = $this->editMode ? $this->selectedSeries : new SermonSeries();
        $series->fill([
            'title' => $this->seriesTitle,
            'description' => $this->seriesDescription,
            'image' => $imagePath,
        ]);
        $series->save();

        $this->toast()->success('Done!', $this->editMode ? 'Series updated successfully!' : 'Series created successfully!')->send();
        $this->resetSeriesForm();
        $this->dispatch('close-modal', 'series-modal');
        $this->dispatch('$refresh');
    }

    public function deleteSeries($id)
    {
        $this->dialog()
            ->error('Are you sure? This will delete all sermons in this series.')
            ->hook([
                'ok' => [
                    'method' => 'confirmDeleteSeries',
                    'params' => [$id],
                ],
            ])
            ->send();
    }

    public function confirmDeleteSeries($id)
    {
        $series = SermonSeries::findOrFail($id);
        $imagePath = $series->getAttribute('image');
        if ($imagePath) {
            Storage::disk('public')->delete($imagePath);
        }
        $series->delete();

        $this->toast()->success('Done!', 'Series deleted successfully!')->send();
        $this->dispatch('$refresh');
    }

    // Helper methods
    private function resetSermonForm()
    {
        $this->reset(['title', 'speaker_name', 'description', 'preached_at', 'series_id', 'image', 'audioFile', 'videoFile', 'selectedSermon']);
    }

    private function resetSeriesForm()
    {
        $this->reset(['seriesTitle', 'seriesDescription', 'seriesImage', 'selectedSeries']);
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

    private function storeSermonMedia(Sermons $sermon, UploadedFile $file, string $type): void
    {
        $sermon->media()
            ->where('type', $type)
            ->get()
            ->each(function (SermonMedia $media): void {
                Storage::disk('public')->delete($media->file_path);
                $media->delete();
            });

        $path = $file->store("sermons/{$type}", 'public');

        $sermon->media()->create([
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'file_size' => $file->getSize() ?: 0,
            'type' => $type,
        ]);
    }
};
?>

<div>
    <x-fancy-header
        title="Sermon Manager"
        subtitle="Manage sermons, series, and media"
        :breadcrumbs="[
            ['label' => 'Home', 'url' => route('admin.dashboard', request()->query())],
            ['label' => 'Sermons']
        ]"
    />

    <!-- Sermon Edit Modal -->
    <x-modal id="sermon-modal" :title="$editMode ? 'Edit Sermon' : 'Create Sermon'" size="2xl">
        <form wire:submit.prevent="saveSermon" class="space-y-6 bg-zinc-50 dark:bg-zinc-800 p-6">
            <div>
                <label class="block text-sm font-medium mb-1 dark:text-gray-200">Title *</label>
                <input wire:model="title" type="text"
                    class="w-full px-3 py-2 rounded-lg bg-white dark:bg-zinc-900 border dark:border-zinc-700 dark:text-gray-200" />
                @error('title') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 dark:text-gray-200">Speaker Name</label>
                <input wire:model="speaker_name" type="text"
                    class="w-full px-3 py-2 rounded-lg bg-white dark:bg-zinc-900 border dark:border-zinc-700 dark:text-gray-200"
                    placeholder="e.g. Pastor John Doe" />
                @error('speaker_name') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 dark:text-gray-200">Description</label>
                <textarea wire:model="description" rows="4"
                    class="w-full px-3 py-2 rounded-lg bg-white dark:bg-zinc-900 border dark:border-zinc-700 dark:text-gray-200"></textarea>
                @error('description') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1 dark:text-gray-200">Preached Date *</label>
                    <input wire:model="preached_at" type="date"
                        class="w-full px-3 py-2 rounded-lg bg-white dark:bg-zinc-900 border dark:border-zinc-700 dark:text-gray-200" />
                    @error('preached_at') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1 dark:text-gray-200">Series *</label>
                    <select wire:model="series_id"
                        class="w-full px-3 py-2 rounded-lg bg-white dark:bg-zinc-900 border dark:border-zinc-700 dark:text-gray-200">
                        <option value="">Select Series</option>
                        @foreach($series as $item)
                            <option value="{{ $item->id }}">{{ $item->title }}</option>
                        @endforeach
                    </select>
                    @error('series_id') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 dark:text-gray-200">Image {{ $editMode ? '' : '*' }}</label>
                <input wire:model="image" type="file" accept="image/*"
                    class="w-full px-3 py-2 rounded-lg bg-white dark:bg-zinc-900 border dark:border-zinc-700 dark:text-gray-200" />
                @error('image') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
                @if ($image)
                    <img src="{{ $image->temporaryUrl() }}" class="mt-2 h-32 w-32 object-cover rounded">
                @elseif($editMode && $selectedSermon?->image_path)
                    <img src="{{ $this->resolveMediaUrl($selectedSermon->image_path) }}" class="mt-2 h-32 w-32 object-cover rounded">
                @endif
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 dark:text-gray-200">Audio File</label>
                <input wire:model="audioFile" type="file" accept="audio/*"
                    class="w-full px-3 py-2 rounded-lg bg-white dark:bg-zinc-900 border dark:border-zinc-700 dark:text-gray-200" />
                @error('audioFile') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
                <div wire:loading wire:target="audioFile" class="text-xs text-blue-500 mt-1">
                    Uploading audio file...
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Max 500MB (server upload limit may be lower)</p>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 dark:text-gray-200">Video File</label>
                <input wire:model="videoFile" type="file" accept="video/*"
                    class="w-full px-3 py-2 rounded-lg bg-white dark:bg-zinc-900 border dark:border-zinc-700 dark:text-gray-200" />
                @error('videoFile') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
                <div wire:loading wire:target="videoFile" class="text-xs text-blue-500 mt-1">
                    Uploading video file...
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Max 500MB (server upload limit may be lower)</p>
            </div>

            @if($editMode && $selectedSermon && $selectedSermon->media->count() > 0)
                <div>
                    <label class="block text-sm font-medium mb-2 dark:text-gray-200">Existing Media</label>
                    <div class="space-y-2">
                        @foreach($selectedSermon->media as $media)
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-zinc-700 rounded">
                                <div>
                                    <span class="text-sm font-medium dark:text-gray-200">{{ $media->file_name }}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">({{ number_format($media->file_size / 1024 / 1024, 2) }} MB)</span>
                                </div>
                                <button wire:click="deleteMedia({{ $media->id }})" type="button" class="text-red-600 hover:text-red-900 text-sm">Delete</button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="flex justify-end space-x-3">
                <button type="button" x-on:click="$modalClose('sermon-modal')"
                    class="px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-zinc-700">
                    Cancel
                </button>
                <button type="submit"
                    class="px-4 py-2 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white">
                    {{ $editMode ? 'Update' : 'Create' }} Sermon
                </button>
            </div>
        </form>
    </x-modal>

    <!-- Series Modal -->
    <x-modal id="series-modal" :title="$editMode ? 'Edit Series' : 'Create Series'" size="xl">
        <form wire:submit.prevent="saveSeries" class="space-y-6 bg-zinc-50 dark:bg-zinc-800 p-6">
            <div>
                <label class="block text-sm font-medium mb-1 dark:text-gray-200">Title *</label>
                <input wire:model="seriesTitle" type="text"
                    class="w-full px-3 py-2 rounded-lg bg-white dark:bg-zinc-900 border dark:border-zinc-700 dark:text-gray-200" />
                @error('seriesTitle') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 dark:text-gray-200">Description</label>
                <textarea wire:model="seriesDescription" rows="4"
                    class="w-full px-3 py-2 rounded-lg bg-white dark:bg-zinc-900 border dark:border-zinc-700 dark:text-gray-200"></textarea>
                @error('seriesDescription') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 dark:text-gray-200">Cover Image</label>
                <input wire:model="seriesImage" type="file" accept="image/*"
                    class="w-full px-3 py-2 rounded-lg bg-white dark:bg-zinc-900 border dark:border-zinc-700 dark:text-gray-200" />
                @error('seriesImage') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
                @if ($seriesImage)
                    <img src="{{ $seriesImage->temporaryUrl() }}" class="mt-2 h-32 w-32 object-cover rounded">
                @elseif($editMode && $selectedSeries?->image)
                    <img src="{{ $this->resolveMediaUrl($selectedSeries->image) }}" class="mt-2 h-32 w-32 object-cover rounded">
                @endif
            </div>

            <div class="flex justify-end space-x-3">
                <button type="button" x-on:click="$modalClose('series-modal')"
                    class="px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-zinc-700">
                    Cancel
                </button>
                <button type="submit"
                    class="px-4 py-2 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white">
                    {{ $editMode ? 'Update' : 'Create' }} Series
                </button>
            </div>
        </form>
    </x-modal>

    <!-- Tabs -->
    <div class="mb-6 border-b border-gray-200 dark:border-zinc-700">
        <nav class="-mb-px flex space-x-8">
            <button
                wire:click="$set('activeTab', 'sermons')"
                class="pb-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'sermons' ? 'border-indigo-500 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                Sermons
            </button>
            <button
                wire:click="$set('activeTab', 'series')"
                class="pb-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'series' ? 'border-indigo-500 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                Series
            </button>
        </nav>
    </div>

    <!-- Sermons Tab -->
    @if($activeTab === 'sermons')
        <x-card class="relative dark:bg-dark-800">
            <x-table
                :headers="$sermonHeaders"
                :rows="$sermonRows"
                :filter="['quantity' => 'quantity', 'search' => 'search']"
                :quantity="[5, 10, 15, 25, 50]"
                paginate
                persistent
                wire:model.live="selected">

                <x-slot:header>
                    <div class="flex justify-end mb-4">
                        <x-button color="green" icon="plus" x-on:click="$wire.call('createSermon').then(() => $modalOpen('sermon-modal'))">
                            Add Sermon
                        </x-button>
                    </div>
                </x-slot:header>

                @interact('column_image_path', $row)
                    @if($row->image_path)
                        <img src="{{ $this->resolveMediaUrl($row->image_path) }}" alt="{{ $row->title }}" class="h-12 w-12 rounded object-cover">
                    @else
                        <div class="h-12 w-12 rounded bg-gray-200 dark:bg-zinc-700 flex items-center justify-center">
                            <span class="text-xs text-gray-400">No image</span>
                        </div>
                    @endif
                @endinteract

                @interact('column_title', $row)
                    <div>
                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $row->title }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($row->description, 50) }}</div>
                    </div>
                @endinteract

                @interact('column_series', $row)
                    <span class="text-sm text-gray-900 dark:text-gray-100">{{ $row->series->title }}</span>
                @endinteract

                @interact('column_preached_at', $row)
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $row->preached_at->format('M d, Y') }}</span>
                @endinteract

                @interact('column_media', $row)
                    @foreach($row->media as $media)
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $media->type === 'audio' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' }} mr-1">
                            {{ $media->type }}
                        </span>
                    @endforeach
                @endinteract

                @interact('column_action', $row)
                    <div class="flex items-center space-x-2">
                        <x-button.circle color="blue" icon="pencil"
                            x-on:click="$wire.call('editSermon', {{ $row->id }}).then(() => $modalOpen('sermon-modal'))" />
                        <x-button.circle color="red" icon="trash"
                            wire:click="deleteSermon({{ $row->id }})" />
                    </div>
                @endinteract
            </x-table>
        </x-card>
    @endif

    <!-- Series Tab -->
    @if($activeTab === 'series')
        <x-card class="relative dark:bg-dark-800">
            <x-table
                :headers="$seriesHeaders"
                :rows="$seriesRows"
                :filter="['quantity' => 'seriesQuantity', 'search' => 'seriesSearch']"
                :quantity="[5, 10, 15, 25, 50]"
                paginate
                persistent>

                <x-slot:header>
                    <div class="mb-4 flex justify-end">
                        <x-button color="green" icon="plus" x-on:click="$wire.call('createSeries').then(() => $modalOpen('series-modal'))">
                            Add Series
                        </x-button>
                    </div>
                </x-slot:header>

                @interact('column_image', $row)
                    @if($row->image)
                        <img src="{{ $this->resolveMediaUrl($row->image) }}" alt="{{ $row->title }}" class="h-12 w-12 rounded object-cover">
                    @else
                        <div class="h-12 w-12 rounded bg-gray-200 dark:bg-zinc-700 flex items-center justify-center">
                            <span class="text-xs text-gray-400">No image</span>
                        </div>
                    @endif
                @endinteract

                @interact('column_title', $row)
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $row->title }}</span>
                @endinteract

                @interact('column_description', $row)
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ Str::limit($row->description, 90) }}</span>
                @endinteract

                @interact('column_sermons_count', $row)
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $row->sermons_count }}</span>
                @endinteract

                @interact('column_action', $row)
                    <div class="flex items-center space-x-2">
                        <x-button.circle color="blue" icon="pencil"
                            x-on:click="$wire.call('editSeries', {{ $row->id }}).then(() => $modalOpen('series-modal'))" />
                        <x-button.circle color="red" icon="trash"
                            wire:click="deleteSeries({{ $row->id }})" />
                    </div>
                @endinteract
            </x-table>
        </x-card>
    @endif
</div>
