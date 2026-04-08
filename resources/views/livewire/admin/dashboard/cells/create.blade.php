<?php

use App\Models\{CellGroup, Chapter};
use Livewire\Attributes\{Layout, Url};
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions, WithFileUploads;

    #[Url(keep: true)]
    public ?string $chapter = null;

    public ?int $chapterId = null;
    public array $chapters = [];

    public string $name = '';
    public ?string $description = null;
    public ?string $meeting_day = null;
    public ?string $meeting_time = null;
    public ?string $location = null;
    public ?string $address = null;
    public ?string $latitude = null;
    public ?string $longitude = null;
    public ?string $phone = null;
    public ?string $whatsapp_link = null;
    public $image = null;
    public bool $is_active = true;

    public function mount(): void
    {
        $user = Auth::user();

        if ($user->hasRole('super-admin')) {
            $this->chapters = Chapter::orderBy('name')->get()->all();
            if ($this->chapter) {
                $this->chapterId = Chapter::where('name', $this->chapter)->value('id');
            }

            if (!$this->chapterId && !empty($this->chapters)) {
                $this->chapterId = $this->chapters[0]->id;
            }
        } else {
            $this->chapterId = $user?->chapter_id;
        }
    }

    public function updatedChapterId(): void
    {
        if (!$this->chapterId) {
            return;
        }

        $selected = Chapter::find($this->chapterId);
        $this->chapter = $selected?->name;
    }

    public function save(): void
    {
        if (!$this->chapterId) {
            $this->toast()->error('No Branch', 'Select a branch before creating a cell group.')->send();
            return;
        }

        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'meeting_day' => 'nullable|string|max:20',
            'meeting_time' => 'nullable|date_format:H:i',
            'location' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'phone' => 'nullable|string|max:30',
            'whatsapp_link' => 'nullable|url|max:255',
            'image' => 'nullable|image|max:5120',
            'is_active' => 'boolean',
        ]);

        $imagePath = null;
        if ($this->image) {
            $imagePath = $this->image->store('cells', 'public');
        }

        CellGroup::create([
            'chapter_id' => $this->chapterId,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'meeting_day' => $validated['meeting_day'] ?? null,
            'meeting_time' => $validated['meeting_time'] ?? null,
            'location' => $validated['location'] ?? null,
            'address' => $validated['address'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'whatsapp_link' => $validated['whatsapp_link'] ?? null,
            'image' => $imagePath,
            'is_active' => (bool) $validated['is_active'],
        ]);

        $this->toast()->success('Created', 'Cell group created successfully.')->send();
        $this->redirect(route('admin.dashboard.cells.index', request()->query()), navigate: true);
    }
};

?>

<div class="space-y-6">
    <x-fancy-header
        title="Create Cell Group"
        subtitle="Add a new cell group for a branch"
        :breadcrumbs="[
            ['label' => 'Home', 'url' => route('admin.dashboard', request()->query())],
            ['label' => 'Cell Groups', 'url' => route('admin.dashboard.cells.index', request()->query())],
            ['label' => 'Create']
        ]"
    />

    <div class="rounded-xl bg-white p-6 shadow dark:bg-slate-800">
        <form wire:submit.prevent="save" class="space-y-5">
            @if(Auth::user()->hasRole('super-admin'))
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Branch</label>
                    <select wire:model="chapterId" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 dark:focus:border-blue-400">
                        <option value="">Select branch</option>
                        @foreach($chapters as $chapterOption)
                            <option value="{{ $chapterOption->id }}">{{ $chapterOption->name }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700 dark:border-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                    Branch: {{ Auth::user()->chapter?->name ?? 'Assigned branch' }}
                </div>
            @endif

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Cell Name</label>
                    <input wire:model.lazy="name" type="text" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 dark:placeholder-slate-500 dark:focus:border-blue-400" required />
                    @error('name') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Cell Image</label>
                    <input wire:model="image" type="file" accept="image/*" class="w-full text-sm text-slate-600 dark:text-slate-300" />
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Upload an image for this cell group</p>
                    @error('image') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Description</label>
                <textarea wire:model.lazy="description" rows="3" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 dark:placeholder-slate-500 dark:focus:border-blue-400"></textarea>
                @error('description') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Meeting Day</label>
                    <input wire:model.lazy="meeting_day" type="text" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 dark:placeholder-slate-500 dark:focus:border-blue-400" placeholder="e.g. Tuesday" />
                    @error('meeting_day') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Meeting Time</label>
                    <input wire:model.lazy="meeting_time" type="time" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 dark:focus:border-blue-400" />
                    @error('meeting_time') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Location Name</label>
                    <input wire:model.lazy="location" type="text" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 dark:placeholder-slate-500 dark:focus:border-blue-400" placeholder="Area / landmark" />
                    @error('location') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Phone</label>
                    <input wire:model.lazy="phone" type="text" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 dark:placeholder-slate-500 dark:focus:border-blue-400" />
                    @error('phone') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">WhatsApp Group Link</label>
                <input wire:model.lazy="whatsapp_link" type="url" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 dark:placeholder-slate-500 dark:focus:border-blue-400" placeholder="https://chat.whatsapp.com/..." />
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Members will be redirected here after joining</p>
                @error('whatsapp_link') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Address</label>
                <input wire:model.lazy="address" type="text" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 dark:placeholder-slate-500 dark:focus:border-blue-400" />
                @error('address') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Latitude</label>
                    <input wire:model.lazy="latitude" type="text" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 dark:placeholder-slate-500 dark:focus:border-blue-400" />
                    @error('latitude') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Longitude</label>
                    <input wire:model.lazy="longitude" type="text" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 dark:placeholder-slate-500 dark:focus:border-blue-400" />
                    @error('longitude') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex items-center gap-3">
                <input id="is_active" type="checkbox" wire:model="is_active" class="h-4 w-4 rounded border-slate-300 text-blue-600 transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-700" />
                <label for="is_active" class="text-sm font-medium text-slate-700 dark:text-slate-300">Active</label>
            </div>

            <div class="flex gap-3">
                <a href="{{ route('admin.dashboard.cells.index', request()->query()) }}" wire:navigate class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600">
                    Cancel
                </a>
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                    Create Cell Group
                </button>
            </div>
        </form>
    </div>
</div>
