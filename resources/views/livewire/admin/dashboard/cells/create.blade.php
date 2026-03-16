<?php

use App\Models\{CellGroup, Chapter};
use Livewire\Attributes\{Layout, Url};
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions;

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
    public int $max_members = 15;
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
            'max_members' => 'required|integer|min:1|max:1000',
            'is_active' => 'boolean',
        ]);

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
            'max_members' => (int) $validated['max_members'],
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

    <div class="rounded-xl bg-white p-6 shadow">
        <form wire:submit.prevent="save" class="space-y-5">
            @if(Auth::user()->hasRole('super-admin'))
                <div>
                    <label class="mb-1 block text-sm font-medium">Branch</label>
                    <select wire:model="chapterId" class="w-full rounded-lg border px-3 py-2">
                        <option value="">Select branch</option>
                        @foreach($chapters as $chapterOption)
                            <option value="{{ $chapterOption->id }}">{{ $chapterOption->name }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                    Branch: {{ Auth::user()->chapter?->name ?? 'Assigned branch' }}
                </div>
            @endif

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Cell Name</label>
                    <input wire:model.lazy="name" type="text" class="w-full rounded-lg border px-3 py-2" required />
                    @error('name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Max Members</label>
                    <input wire:model.lazy="max_members" type="number" min="1" class="w-full rounded-lg border px-3 py-2" />
                    @error('max_members') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Description</label>
                <textarea wire:model.lazy="description" rows="3" class="w-full rounded-lg border px-3 py-2"></textarea>
                @error('description') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Meeting Day</label>
                    <input wire:model.lazy="meeting_day" type="text" class="w-full rounded-lg border px-3 py-2" placeholder="e.g. Tuesday" />
                    @error('meeting_day') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Meeting Time</label>
                    <input wire:model.lazy="meeting_time" type="time" class="w-full rounded-lg border px-3 py-2" />
                    @error('meeting_time') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Location Name</label>
                    <input wire:model.lazy="location" type="text" class="w-full rounded-lg border px-3 py-2" placeholder="Area / landmark" />
                    @error('location') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Phone</label>
                    <input wire:model.lazy="phone" type="text" class="w-full rounded-lg border px-3 py-2" />
                    @error('phone') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Address</label>
                <input wire:model.lazy="address" type="text" class="w-full rounded-lg border px-3 py-2" />
                @error('address') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Latitude</label>
                    <input wire:model.lazy="latitude" type="text" class="w-full rounded-lg border px-3 py-2" />
                    @error('latitude') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Longitude</label>
                    <input wire:model.lazy="longitude" type="text" class="w-full rounded-lg border px-3 py-2" />
                    @error('longitude') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex items-center gap-3">
                <input id="is_active" type="checkbox" wire:model="is_active" class="rounded border-zinc-300" />
                <label for="is_active" class="text-sm">Active</label>
            </div>

            <div class="flex gap-3">
                <a href="{{ route('admin.dashboard.cells.index', request()->query()) }}" wire:navigate class="inline-flex items-center rounded-lg border px-4 py-2 text-sm">
                    Cancel
                </a>
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Create Cell Group
                </button>
            </div>
        </form>
    </div>
</div>
