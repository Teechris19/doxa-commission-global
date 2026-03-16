<?php

use App\Models\{AboutUs, Chapter};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions, WithFileUploads;

    public $aboutUs;
    public $chapter_id;
    public $title;
    public $description;
    public $mission;
    public $vision;
    public $core_values;
    public $hero_image;
    public $history_timeline = [];
    public $is_active = true;

    // For timeline management
    public $newYear;
    public $newEvent;

    public function mount()
    {
        $chapterName = request()->query('chapter', 'default');
        $chapter = Chapter::where('name', $chapterName)->first();

        if ($chapter) {
            $this->chapter_id = $chapter->id;
            $this->aboutUs = AboutUs::where('chapter_id', $chapter->id)->first();

            if ($this->aboutUs) {
                $this->fill([
                    'title' => $this->aboutUs->title,
                    'description' => $this->aboutUs->description,
                    'mission' => $this->aboutUs->mission,
                    'vision' => $this->aboutUs->vision,
                    'core_values' => $this->aboutUs->core_values,
                    'history_timeline' => $this->aboutUs->history_timeline ?? [],
                    'is_active' => $this->aboutUs->is_active,
                ]);
            }
        }
    }

    public function addTimelineEvent()
    {
        $this->validate([
            'newYear' => 'required|integer|min:1900|max:' . date('Y'),
            'newEvent' => 'required|string|max:500',
        ]);

        $this->history_timeline[] = [
            'year' => $this->newYear,
            'event' => $this->newEvent,
        ];

        // Sort by year descending
        usort($this->history_timeline, fn($a, $b) => $b['year'] <=> $a['year']);

        $this->reset(['newYear', 'newEvent']);
        $this->toast()->success('Timeline event added')->send();
    }

    public function removeTimelineEvent($index)
    {
        unset($this->history_timeline[$index]);
        $this->history_timeline = array_values($this->history_timeline);
        $this->toast()->success('Timeline event removed')->send();
    }

    public function save()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'mission' => 'nullable|string',
            'vision' => 'nullable|string',
            'core_values' => 'nullable|string',
            'hero_image' => 'nullable|image|max:2048',
        ]);

        $data = [
            'chapter_id' => $this->chapter_id,
            'title' => $this->title,
            'description' => $this->description,
            'mission' => $this->mission,
            'vision' => $this->vision,
            'core_values' => $this->core_values,
            'history_timeline' => $this->history_timeline,
            'is_active' => $this->is_active,
        ];

        if ($this->hero_image && is_object($this->hero_image)) {
            $path = $this->hero_image->store('about-us', 'public');
            $data['hero_image'] = $path;
        }

        if ($this->aboutUs) {
            $this->aboutUs->update($data);
            $this->toast()->success('About Us updated successfully')->send();
        } else {
            AboutUs::create($data);
            $this->toast()->success('About Us created successfully')->send();
        }

        $this->dispatch('$refresh');
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <x-fancy-header
        title="About Us Management"
        subtitle="Manage church information, mission, vision, and history"
        :breadcrumbs="[
            ['label' => 'Home', 'url' => route('admin.dashboard', request()->query())],
            ['label' => 'About Us']
        ]"
    />

    <x-card>
        <form wire:submit.prevent="save" class="space-y-6">
            <!-- Title -->
            <div>
                <label class="block text-sm font-medium mb-2 dark:text-zinc-200">Page Title</label>
                <input type="text" wire:model="title"
                    class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                    placeholder="e.g., Welcome to Doxa Church">
                @error('title') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <!-- Description -->
            <div>
                <label class="block text-sm font-medium mb-2 dark:text-zinc-200">Church Description</label>
                <textarea wire:model="description" rows="6"
                    class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                    placeholder="Tell your church's story..."></textarea>
                @error('description') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <!-- Mission, Vision, Core Values -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-2 dark:text-zinc-200">
                        <i class="bi bi-bullseye text-blue-500"></i> Mission
                    </label>
                    <textarea wire:model="mission" rows="4"
                        class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                        placeholder="Our mission..."></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2 dark:text-zinc-200">
                        <i class="bi bi-eye text-purple-500"></i> Vision
                    </label>
                    <textarea wire:model="vision" rows="4"
                        class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                        placeholder="Our vision..."></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2 dark:text-zinc-200">
                        <i class="bi bi-heart text-red-500"></i> Core Values
                    </label>
                    <textarea wire:model="core_values" rows="4"
                        class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                        placeholder="Our values..."></textarea>
                </div>
            </div>

            <!-- Hero Image -->
            <div>
                <label class="block text-sm font-medium mb-2 dark:text-zinc-200">Hero Image</label>
                <input type="file" wire:model="hero_image" accept="image/*"
                    class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                @error('hero_image') <span class="text-xs text-red-500">{{ $message }}</span> @enderror

                @if($hero_image && is_object($hero_image))
                    <div class="mt-2">
                        <img src="{{ $hero_image->temporaryUrl() }}" class="h-32 rounded-lg">
                    </div>
                @elseif($aboutUs && $aboutUs->hero_image)
                    <div class="mt-2">
                        <img src="{{ Storage::url($aboutUs->hero_image) }}" class="h-32 rounded-lg">
                    </div>
                @endif
            </div>

            <!-- History Timeline -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-semibold mb-4 dark:text-zinc-200">
                    <i class="bi bi-calendar-event text-orange-500"></i> History Timeline
                </h3>

                <!-- Add Timeline Event -->
                <div class="bg-zinc-50 dark:bg-zinc-800 p-4 rounded-lg mb-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2 dark:text-zinc-200">Year</label>
                            <input type="number" wire:model="newYear"
                                class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                                placeholder="1995">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-2 dark:text-zinc-200">Event</label>
                            <div class="flex gap-2">
                                <input type="text" wire:model="newEvent"
                                    class="flex-1 px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                                    placeholder="Church founded...">
                                <button type="button" wire:click="addTimelineEvent"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                                    <i class="bi bi-plus-lg"></i> Add
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Timeline Events List -->
                @if(count($history_timeline) > 0)
                    <div class="space-y-2">
                        @foreach($history_timeline as $index => $event)
                            <div class="flex items-center gap-4 bg-white dark:bg-zinc-700 p-3 rounded-lg border border-zinc-200 dark:border-zinc-600">
                                <div class="font-bold text-blue-600 dark:text-blue-400 min-w-[80px]">
                                    {{ $event['year'] }}
                                </div>
                                <div class="flex-1 dark:text-zinc-200">
                                    {{ $event['event'] }}
                                </div>
                                <button type="button" wire:click="removeTimelineEvent({{ $index }})"
                                    class="text-red-600 hover:text-red-700">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-zinc-500 dark:text-zinc-400 text-center py-4">No timeline events yet. Add your first milestone above.</p>
                @endif
            </div>

            <!-- Active Status -->
            <div class="flex items-center gap-3">
                <input type="checkbox" wire:model="is_active" id="is_active"
                    class="w-4 h-4 text-blue-600 rounded">
                <label for="is_active" class="text-sm font-medium dark:text-zinc-200">
                    Active (visible on public website)
                </label>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end gap-3 pt-4 border-t">
                <button type="submit"
                    class="px-6 py-2 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-lg font-medium transition">
                    <i class="bi bi-save"></i> Save About Us
                </button>
            </div>
        </form>
    </x-card>
</div>
