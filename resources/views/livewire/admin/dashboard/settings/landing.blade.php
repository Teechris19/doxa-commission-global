<?php

use App\Models\Chapter;
use App\Models\LandingPageSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component {
    use WithFileUploads, Interactions;

    public LandingPageSetting $landing;
    public array $services = [];
    public int $number_of_testimonies = 5;
    public int $number_of_conclaves = 3;
    public array $hero_section = [];
    public bool $isSaving = false;
    public ?Chapter $activeChapter = null;
    public bool $isSuperAdmin = false;
    public array $chapterNames = [];

    public function mount(): void
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('super-admin')) {
            abort(403, 'Unauthorized access. System settings are only accessible by super admins.');
        }
        
        $this->isSuperAdmin = true;
        if ($this->isSuperAdmin) {
            $this->chapterNames = Chapter::orderBy('name')->pluck('name')->all();
        }

        $this->activeChapter = $this->resolveChapter();
        $this->landing = $this->resolveLandingSetting();

        $this->services = $this->normalizeServices($this->landing->services ?? []);
        $this->number_of_testimonies = (int) ($this->landing->number_of_testimonies ?? 5);
        $this->number_of_conclaves = (int) ($this->landing->number_of_conclaves ?? 3);
        $this->hero_section = $this->normalizeHeroSection($this->landing->hero_section ?? []);
    }

    protected function resolveChapter(): ?Chapter
    {
        if ($this->isSuperAdmin) {
            $requested = request()->query('chapter');

            if ($requested === '__global__') {
                return null;
            }

            if ($requested) {
                $chapter = Chapter::where('name', $requested)->first();
                if ($chapter) {
                    return $chapter;
                }
            }

            return Chapter::orderBy('name')->first();
        }

        $user = auth()->user();
        if ($user?->chapter_id) {
            return Chapter::find($user->chapter_id);
        }

        return Chapter::orderBy('name')->first();
    }

    protected function resolveLandingSetting(): LandingPageSetting
    {
        if (!$this->hasChapterColumn()) {
            return LandingPageSetting::firstOrCreate([], [
                'services' => [],
                'number_of_testimonies' => 5,
                'hero_section' => $this->defaultHeroSection(),
            ]);
        }

        $chapterId = $this->activeChapter?->id;

        $query = LandingPageSetting::query();
        if ($chapterId) {
            $query->where('chapter_id', $chapterId);
        } else {
            $query->whereNull('chapter_id');
        }

        $record = $query->first();
        if ($record) {
            return $record;
        }

        return LandingPageSetting::create([
            'chapter_id' => $chapterId,
            'services' => [],
            'number_of_testimonies' => 5,
            'hero_section' => $this->defaultHeroSection(),
        ]);
    }

    protected function defaultHeroSection(): array
    {
        return [
            'title' => 'Welcome to Doxa Commission Global',
            'subtitle' => 'A place where faith, hope, and love come together.',
            'cta_text' => 'Get Connected',
            'cta_url' => route('home'),
            'media_type' => 'image',
            'media_path' => null,
        ];
    }

    protected function normalizeHeroSection(array $hero): array
    {
        if (!isset($hero['media_path']) && !empty($hero['image'])) {
            $hero['media_path'] = $hero['image'];
            $hero['media_type'] = 'image';
        }

        $hero = array_merge($this->defaultHeroSection(), $hero);
        $hero['temp_media'] = null;
        $hero['preview'] = null;

        if (!empty($hero['media_path']) && is_string($hero['media_path']) && Storage::disk('public')->exists($hero['media_path'])) {
            $hero['preview'] = Storage::disk('public')->url($hero['media_path']);
        }

        return $hero;
    }

    protected function defaultServices(): array
    {
        return [];
    }

    protected function normalizeServices(array $services): array
    {
        return array_map(function ($service) {
            return [
                'name' => $service['name'] ?? '',
                'day_of_week' => $service['day_of_week'] ?? '',
                'start_time' => $service['start_time'] ?? '09:00',
                'end_time' => $service['end_time'] ?? '10:00',
                'location' => $service['location'] ?? '',
                'livestream_url' => $service['livestream_url'] ?? '',
            ];
        }, $services);
    }

    public function addService(): void
    {
        $this->services[] = [
            'name' => '',
            'day_of_week' => '',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'location' => '',
            'livestream_url' => '',
        ];
    }

    public function removeService(int $index): void
    {
        if (isset($this->services[$index])) {
            unset($this->services[$index]);
            $this->services = array_values($this->services);
        }
    }

    public function updatedHeroSectionTempMedia(): void
    {
        $file = data_get($this->hero_section, 'temp_media');
        if ($file) {
            $this->storeHeroMedia($file);
        }
    }

    protected function storeHeroMedia($file): void
    {
        $mediaType = data_get($this->hero_section, 'media_type', 'image');

        if ($mediaType === 'video') {
            $this->validate([
                'hero_section.temp_media' => 'required|file|mimetypes:video/mp4,video/webm,video/quicktime|max:25600',
            ]);
        } else {
            $this->validate([
                'hero_section.temp_media' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
            ]);
        }

        $oldPath = data_get($this->hero_section, 'media_path');
        if ($oldPath && is_string($oldPath) && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $folder = $mediaType === 'video' ? 'uploads/hero/video' : 'uploads/hero/image';
        $path = $file->store($folder, 'public');

        $this->hero_section['media_path'] = $path;
        $this->hero_section['preview'] = Storage::disk('public')->url($path);
        $this->hero_section['temp_media'] = null;
    }

    public function removeHeroMedia(): void
    {
        $oldPath = data_get($this->hero_section, 'media_path');

        if ($oldPath && is_string($oldPath) && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $this->hero_section['media_path'] = null;
        $this->hero_section['preview'] = null;
        $this->hero_section['temp_media'] = null;
    }

    public function save(): void
    {
        $this->isSaving = true;

        try {
            $this->validate([
                'services' => 'array',
                'services.*.name' => 'required|string|max:255',
                'services.*.day_of_week' => 'required|in:Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
                'services.*.start_time' => 'required|date_format:H:i',
                'services.*.end_time' => 'required|date_format:H:i',
                'services.*.location' => 'nullable|string|max:255',
                'services.*.livestream_url' => 'nullable|url|max:255',
                'number_of_testimonies' => 'required|integer|min:1|max:20',
                'number_of_conclaves' => 'required|integer|min:0|max:20',
                'hero_section.title' => 'required|string|max:255',
                'hero_section.subtitle' => 'nullable|string|max:500',
                'hero_section.cta_text' => 'nullable|string|max:100',
                'hero_section.cta_url' => 'nullable|url|max:255',
                'hero_section.media_type' => 'required|in:image,video',
            ]);

            if (data_get($this->hero_section, 'temp_media')) {
                $this->storeHeroMedia($this->hero_section['temp_media']);
            }

            $this->persist();

            $this->toast()->success('Saved!', 'Landing page settings updated')->send();
            $this->dispatch('saved');
        } finally {
            $this->isSaving = false;
        }
    }

    protected function persist(): void
    {
        $cleanedHeroSection = Arr::only($this->hero_section, [
            'title',
            'subtitle',
            'cta_text',
            'cta_url',
            'media_type',
            'media_path',
        ]);

        $cleanedServices = array_map(function ($service) {
            return [
                'name' => trim((string) ($service['name'] ?? '')),
                'day_of_week' => trim((string) ($service['day_of_week'] ?? '')),
                'start_time' => $service['start_time'] ?? null,
                'end_time' => $service['end_time'] ?? null,
                'location' => trim((string) ($service['location'] ?? '')),
                'livestream_url' => trim((string) ($service['livestream_url'] ?? '')),
            ];
        }, $this->services);

        $payload = [
            'services' => $cleanedServices,
            'number_of_testimonies' => $this->number_of_testimonies,
            'number_of_conclaves' => $this->number_of_conclaves,
            'hero_section' => $cleanedHeroSection,
        ];

        if ($this->hasChapterColumn()) {
            $payload['chapter_id'] = $this->activeChapter?->id;
        }

        $this->landing->update($payload);
    }

    protected function hasChapterColumn(): bool
    {
        return Schema::hasColumn('landing_page_settings', 'chapter_id');
    }
};
?>

<div>
    <x-fancy-header title="Landing Page Settings" subtitle="Manage homepage hero and service blocks" :breadcrumbs="[
        ['label' => 'Home', 'url' => route('admin.dashboard')],
        ['label' => 'Settings'],
        ['label' => 'Landing Page']
    ]">
    </x-fancy-header>

    <x-card class="bg-white text-gray-800">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-blue-600">Active Scope</p>
                <p class="text-sm font-semibold text-gray-800">{{ $activeChapter?->name ?? 'Global (All Branches)' }}</p>
            </div>
            <x-button wire:click="save" wire:loading.attr="disabled" class="bg-blue-600 hover:bg-blue-700">
                <span wire:loading wire:target="save" class="animate-spin">↻</span>
                <span>{{ $isSaving ? 'Saving...' : 'Save Changes' }}</span>
            </x-button>
        </div>

        @if($isSuperAdmin)
            <div class="mt-4 max-w-sm">
                <form method="GET" action="{{ route('admin.dashboard.settings.landing') }}" class="space-y-2">
                    <label for="scope-chapter" class="block text-xs font-semibold uppercase tracking-[0.15em] text-gray-600">Choose Scope</label>
                    <div class="flex gap-2">
                        <select id="scope-chapter" name="chapter" class="flex-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                            <option value="__global__" @selected(!$activeChapter)>Global (All Branches)</option>
                            @foreach($chapterNames as $chapterName)
                                <option value="{{ $chapterName }}" @selected($activeChapter?->name === $chapterName)>
                                    {{ $chapterName }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">
                            Apply
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </x-card>

    <!-- Meet Our Pastor Settings Card -->
    <x-card class="mt-6 bg-white text-gray-800">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-xl font-bold text-gray-800">Meet Our Pastor Section</h3>
                <p class="mt-1 text-sm text-gray-500">Configure the pastor section displayed on the homepage</p>
            </div>
            <a href="{{ route('admin.dashboard.settings.pastor', request()->query()) }}" wire:navigate class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                Configure Pastor Section
            </a>
        </div>
    </x-card>

    <x-card class="mt-6 bg-white text-gray-800 border-2 border-blue-200">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-xl font-bold text-gray-800">📄 About Page Settings</h3>
                <p class="mt-1 text-sm text-gray-500">Manage all sections of the public About page including Hero, Pastor, Services, Conclaves preview, and CTA</p>
            </div>
            <a href="{{ route('admin.dashboard.settings.about-page', request()->query()) }}" wire:navigate class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                Configure About Page
            </a>
        </div>
    </x-card>

    <x-card class="mt-6 bg-white text-gray-800">
        <h3 class="text-xl font-bold text-gray-800">Hero Section</h3>
        <p class="mt-1 text-sm text-gray-500">This is the top section on the public homepage.</p>

        <div class="mt-5 space-y-4">
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">Hero Title</label>
                <input type="text" wire:model.defer="hero_section.title" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                @error('hero_section.title')
                    <span class="text-xs text-red-500">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">Hero Subtitle</label>
                <textarea rows="3" wire:model.defer="hero_section.subtitle" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"></textarea>
                @error('hero_section.subtitle')
                    <span class="text-xs text-red-500">{{ $message }}</span>
                @enderror
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">CTA Text</label>
                    <input type="text" wire:model.defer="hero_section.cta_text" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @error('hero_section.cta_text')
                        <span class="text-xs text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">CTA URL</label>
                    <input type="url" wire:model.defer="hero_section.cta_url" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @error('hero_section.cta_url')
                        <span class="text-xs text-red-500">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Background Type</label>
                    <select wire:model.defer="hero_section.media_type" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <option value="image">Image</option>
                        <option value="video">Short Video</option>
                    </select>
                    @error('hero_section.media_type')
                        <span class="text-xs text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Background Media</label>
                    <input
                        type="file"
                        wire:model.live="hero_section.temp_media"
                        accept="image/*,video/mp4,video/webm,video/quicktime"
                        class="block w-full rounded-md border border-gray-300 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-blue-600 file:px-4 file:py-2 file:text-white hover:file:bg-blue-700"
                    >
                    @error('hero_section.temp_media')
                        <span class="text-xs text-red-500">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            @if (!empty($hero_section['preview']) || !empty($hero_section['media_path']))
                <div class="rounded-lg border border-gray-200 p-3">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-[0.15em] text-gray-500">Preview</p>

                    @if (($hero_section['media_type'] ?? 'image') === 'video' && !empty($hero_section['preview']))
                        <video src="{{ $hero_section['preview'] }}" controls class="h-52 w-full rounded object-cover"></video>
                    @elseif (($hero_section['media_type'] ?? 'image') === 'video' && !empty($hero_section['media_path']))
                        <video src="{{ Storage::disk('public')->url($hero_section['media_path']) }}" controls class="h-52 w-full rounded object-cover"></video>
                    @elseif (!empty($hero_section['preview']))
                        <img src="{{ $hero_section['preview'] }}" alt="Hero preview" class="h-52 w-full rounded object-cover">
                    @elseif (!empty($hero_section['media_path']))
                        <img src="{{ Storage::disk('public')->url($hero_section['media_path']) }}" alt="Hero preview" class="h-52 w-full rounded object-cover">
                    @endif

                    <button type="button" wire:click="removeHeroMedia" class="mt-3 rounded bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700">
                        Remove Media
                    </button>
                </div>
            @endif
        </div>
    </x-card>

    <x-card class="mt-6 bg-white text-gray-800">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-xl font-bold text-gray-800">Services</h3>
                <p class="mt-1 text-sm text-gray-500">These entries are shown on the homepage service section.</p>
            </div>
            <x-button wire:click="addService" class="bg-green-600 hover:bg-green-700">+ Add Service</x-button>
        </div>

        <div class="mt-5 space-y-4">
            @if (empty($services))
                <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-6 text-center text-sm text-gray-500">
                    No services added yet. Click <span class="font-semibold text-gray-700">Add Service</span> to create one.
                </div>
            @endif

            @foreach ($services as $index => $service)
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4" wire:key="service-{{ $index }}">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Service Name</label>
                            <input type="text" wire:model.defer="services.{{ $index }}.name" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                            @error("services.$index.name")
                                <span class="text-xs text-red-500">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Day</label>
                            <select wire:model.defer="services.{{ $index }}.day_of_week" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                                <option value="">Select day</option>
                                <option value="Sunday">Sunday</option>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                            </select>
                            @error("services.$index.day_of_week")
                                <span class="text-xs text-red-500">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Start Time</label>
                            <input type="time" wire:model.defer="services.{{ $index }}.start_time" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                            @error("services.$index.start_time")
                                <span class="text-xs text-red-500">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">End Time</label>
                            <input type="time" wire:model.defer="services.{{ $index }}.end_time" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                            @error("services.$index.end_time")
                                <span class="text-xs text-red-500">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Location</label>
                            <input type="text" wire:model.defer="services.{{ $index }}.location" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                            @error("services.$index.location")
                                <span class="text-xs text-red-500">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Livestream URL</label>
                            <input type="url" wire:model.defer="services.{{ $index }}.livestream_url" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                            @error("services.$index.livestream_url")
                                <span class="text-xs text-red-500">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <button type="button" wire:click="removeService({{ $index }})" class="mt-3 rounded bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700">
                        Remove Service
                    </button>
                </div>
            @endforeach
        </div>
    </x-card>

    <x-card class="mt-6 bg-white text-gray-800">
        <h3 class="text-xl font-bold text-gray-800">Testimonies</h3>
        <p class="mt-1 text-sm text-gray-500">Control how many approved testimonies are shown on the homepage.</p>

        <div class="mt-4 max-w-xs">
            <label class="mb-1 block text-sm font-medium text-gray-700">Number to display</label>
            <input type="number" min="1" max="20" wire:model.defer="number_of_testimonies" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
            @error('number_of_testimonies')
                <span class="text-xs text-red-500">{{ $message }}</span>
            @enderror
        </div>
    </x-card>

    <x-card class="mt-6 bg-white text-gray-800">
        <h3 class="text-xl font-bold text-gray-800">Conclaves</h3>
        <p class="mt-1 text-sm text-gray-500">Control how many active conclaves are shown on the homepage.</p>

        <div class="mt-4 max-w-xs">
            <label class="mb-1 block text-sm font-medium text-gray-700">Number to display</label>
            <input type="number" min="0" max="20" wire:model.defer="number_of_conclaves" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
            @error('number_of_conclaves')
                <span class="text-xs text-red-500">{{ $message }}</span>
            @enderror
            <p class="mt-2 text-xs text-gray-500">Set to 0 to hide the conclaves section on the homepage.</p>
        </div>
    </x-card>

    <div class="mt-6">
        <x-button wire:click="save" wire:loading.attr="disabled" class="bg-blue-600 hover:bg-blue-700">
            <span wire:loading wire:target="save" class="animate-spin">↻</span>
            <span>{{ $isSaving ? 'Saving...' : 'Save Landing Settings' }}</span>
        </x-button>
    </div>
</div>
