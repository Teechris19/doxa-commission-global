<?php

use App\Models\{ChurchLeader, Chapter};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions, WithFileUploads, WithPagination;

    public $chapter_id;
    public $leaders;
    public $showModal = false;
    public $editingId = null;

    // Form fields
    public $name;
    public $position;
    public $bio;
    public $photo;
    public $facebook_url;
    public $twitter_url;
    public $instagram_url;
    public $linkedin_url;
    public $order_column = 0;
    public $is_active = true;

    public function mount()
    {
        $chapterName = request()->query('chapter', 'default');
        $chapter = Chapter::where('name', $chapterName)->first();

        if ($chapter) {
            $this->chapter_id = $chapter->id;
        }

        $this->loadLeaders();
    }

    public function loadLeaders()
    {
        $this->leaders = ChurchLeader::where('chapter_id', $this->chapter_id)
            ->orderBy('order_column')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function openModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit($id)
    {
        $leader = ChurchLeader::findOrFail($id);
        $this->editingId = $id;

        $this->fill([
            'name' => $leader->name,
            'position' => $leader->position,
            'bio' => $leader->bio,
            'facebook_url' => $leader->facebook_url,
            'twitter_url' => $leader->twitter_url,
            'instagram_url' => $leader->instagram_url,
            'linkedin_url' => $leader->linkedin_url,
            'order_column' => $leader->order_column,
            'is_active' => $leader->is_active,
        ]);

        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'bio' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
            'facebook_url' => 'nullable|url',
            'twitter_url' => 'nullable|url',
            'instagram_url' => 'nullable|url',
            'linkedin_url' => 'nullable|url',
            'order_column' => 'integer|min:0',
        ]);

        $data = [
            'chapter_id' => $this->chapter_id,
            'name' => $this->name,
            'position' => $this->position,
            'bio' => $this->bio,
            'facebook_url' => $this->facebook_url,
            'twitter_url' => $this->twitter_url,
            'instagram_url' => $this->instagram_url,
            'linkedin_url' => $this->linkedin_url,
            'order_column' => $this->order_column,
            'is_active' => $this->is_active,
        ];

        if ($this->photo && is_object($this->photo)) {
            $path = $this->photo->store('church-leaders', 'public');
            $data['photo'] = $path;
        }

        if ($this->editingId) {
            $leader = ChurchLeader::findOrFail($this->editingId);
            $leader->update($data);
            $this->toast()->success('Leader updated successfully')->send();
        } else {
            ChurchLeader::create($data);
            $this->toast()->success('Leader added successfully')->send();
        }

        $this->showModal = false;
        $this->resetForm();
        $this->loadLeaders();
    }

    public function delete($id)
    {
        ChurchLeader::findOrFail($id)->delete();
        $this->toast()->success('Leader deleted successfully')->send();
        $this->loadLeaders();
    }

    public function resetForm()
    {
        $this->reset(['editingId', 'name', 'position', 'bio', 'photo', 'facebook_url', 'twitter_url', 'instagram_url', 'linkedin_url', 'order_column']);
        $this->is_active = true;
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <x-fancy-header
        title="Church Leaders"
        subtitle="Manage church leadership team"
        :breadcrumbs="[
            ['label' => 'Home', 'url' => route('admin.dashboard', request()->query())],
            ['label' => 'Church Leaders']
        ]"
    />

    <!-- Add Leader Button -->
    <div class="flex justify-end">
        <button wire:click="openModal"
            class="px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-lg font-medium transition">
            <i class="bi bi-plus-lg"></i> Add Leader
        </button>
    </div>

    <!-- Leaders Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($leaders as $leader)
            <x-card class="relative">
                @if(!$leader->is_active)
                    <div class="absolute top-2 right-2 bg-red-500 text-white text-xs px-2 py-1 rounded">
                        Inactive
                    </div>
                @endif

                <div class="text-center">
                    @if($leader->photo)
                        <img src="{{ Storage::url($leader->photo) }}" alt="{{ $leader->name }}"
                            class="w-32 h-32 rounded-full mx-auto object-cover mb-4">
                    @else
                        <div class="w-32 h-32 rounded-full mx-auto bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white text-4xl font-bold mb-4">
                            {{ substr($leader->name, 0, 1) }}
                        </div>
                    @endif

                    <h3 class="font-bold text-lg dark:text-zinc-100">{{ $leader->name }}</h3>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-2">{{ $leader->position }}</p>

                    @if($leader->bio)
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-3">{{ Str::limit($leader->bio, 100) }}</p>
                    @endif

                    <!-- Social Links -->
                    @if($leader->facebook_url || $leader->twitter_url || $leader->instagram_url || $leader->linkedin_url)
                        <div class="flex justify-center gap-2 mb-3">
                            @if($leader->facebook_url)
                                <a href="{{ $leader->facebook_url }}" target="_blank" class="text-blue-600 hover:text-blue-700">
                                    <i class="bi bi-facebook"></i>
                                </a>
                            @endif
                            @if($leader->twitter_url)
                                <a href="{{ $leader->twitter_url }}" target="_blank" class="text-sky-500 hover:text-sky-600">
                                    <i class="bi bi-twitter"></i>
                                </a>
                            @endif
                            @if($leader->instagram_url)
                                <a href="{{ $leader->instagram_url }}" target="_blank" class="text-pink-600 hover:text-pink-700">
                                    <i class="bi bi-instagram"></i>
                                </a>
                            @endif
                            @if($leader->linkedin_url)
                                <a href="{{ $leader->linkedin_url }}" target="_blank" class="text-blue-700 hover:text-blue-800">
                                    <i class="bi bi-linkedin"></i>
                                </a>
                            @endif
                        </div>
                    @endif

                    <div class="text-xs text-zinc-500 dark:text-zinc-400 mb-3">
                        Order: {{ $leader->order_column }}
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2 justify-center">
                        <button wire:click="edit({{ $leader->id }})"
                            class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-sm">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                        <button wire:click="delete({{ $leader->id }})"
                            class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-sm">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </x-card>
        @empty
            <div class="col-span-full">
                <x-card>
                    <div class="text-center py-8">
                        <i class="bi bi-people text-6xl text-zinc-300 dark:text-zinc-600 mb-4"></i>
                        <p class="text-zinc-500 dark:text-zinc-400">No leaders added yet. Click "Add Leader" to get started.</p>
                    </div>
                </x-card>
            </div>
        @endforelse
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-zinc-800 rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold dark:text-zinc-100">
                            {{ $editingId ? 'Edit Leader' : 'Add Leader' }}
                        </h3>
                        <button wire:click="$set('showModal', false)" class="text-zinc-500 hover:text-zinc-700">
                            <i class="bi bi-x-lg text-2xl"></i>
                        </button>
                    </div>

                    <form wire:submit.prevent="save" class="space-y-4">
                        <!-- Name & Position -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-2 dark:text-zinc-200">Name *</label>
                                <input type="text" wire:model="name"
                                    class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                                @error('name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2 dark:text-zinc-200">Position *</label>
                                <input type="text" wire:model="position"
                                    class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                                    placeholder="e.g., Senior Pastor">
                                @error('position') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Bio -->
                        <div>
                            <label class="block text-sm font-medium mb-2 dark:text-zinc-200">Biography</label>
                            <textarea wire:model="bio" rows="3"
                                class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                                placeholder="Brief bio..."></textarea>
                        </div>

                        <!-- Photo -->
                        <div>
                            <label class="block text-sm font-medium mb-2 dark:text-zinc-200">Photo</label>
                            <input type="file" wire:model="photo" accept="image/*"
                                class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            @if($photo && is_object($photo))
                                <img src="{{ $photo->temporaryUrl() }}" class="mt-2 h-20 rounded">
                            @endif
                        </div>

                        <!-- Social Media -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-2 dark:text-zinc-200">
                                    <i class="bi bi-facebook text-blue-600"></i> Facebook URL
                                </label>
                                <input type="url" wire:model="facebook_url"
                                    class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2 dark:text-zinc-200">
                                    <i class="bi bi-twitter text-sky-500"></i> Twitter URL
                                </label>
                                <input type="url" wire:model="twitter_url"
                                    class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2 dark:text-zinc-200">
                                    <i class="bi bi-instagram text-pink-600"></i> Instagram URL
                                </label>
                                <input type="url" wire:model="instagram_url"
                                    class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2 dark:text-zinc-200">
                                    <i class="bi bi-linkedin text-blue-700"></i> LinkedIn URL
                                </label>
                                <input type="url" wire:model="linkedin_url"
                                    class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            </div>
                        </div>

                        <!-- Order & Active -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-2 dark:text-zinc-200">Display Order</label>
                                <input type="number" wire:model="order_column"
                                    class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" wire:model="is_active" id="leader_active" class="w-4 h-4 mr-2">
                                <label for="leader_active" class="text-sm font-medium dark:text-zinc-200">Active</label>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end gap-3 pt-4">
                            <button type="button" wire:click="$set('showModal', false)"
                                class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 dark:text-zinc-200">
                                Cancel
                            </button>
                            <button type="submit"
                                class="px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-lg">
                                <i class="bi bi-save"></i> Save Leader
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
