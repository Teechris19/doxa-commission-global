<?php

use App\Models\AboutUs;
use App\Models\Chapter;
use App\Models\Conclave;
use App\Models\CtaSection;
use App\Models\Pastor;
use App\Models\ServiceTime;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions, WithFileUploads;

    #[Url]
    public ?string $chapter = null;

    public ?Chapter $activeChapter = null;
    
    // About Us Section
    public ?AboutUs $aboutUs = null;
    public ?string $heroTitle = null;
    public ?string $heroSubtitle = null;
    public $heroBackgroundImage = null;
    public ?string $existingHeroImage = null;
    public ?string $whoWeAreImage = null;
    public ?string $existingWhoWeAreImage = null;
    public ?string $whoWeAreDescription = null;
    public ?string $mission = null;
    public ?string $vision = null;
    public ?string $coreValues = null;
    public array $historyTimeline = [];
    public int $conclavesPreviewCount = 6;
    
    // Pastor Section
    public array $pastors = [];
    
    // Service Times
    public array $sundayServices = [];
    public array $thursdayServices = [];
    
    // CTA Section
    public ?CtaSection $ctaSection = null;
    public ?string $ctaTitle = null;
    public ?string $ctaDescription = null;
    public ?string $ctaButtonText = null;
    public ?string $ctaButtonLink = null;

    public function mount(): void
    {
        if (!auth()->user()->hasRole('super-admin')) {
            abort(403, 'Unauthorized access.');
        }

        $this->resolveChapter();
        $this->loadAboutUs();
        $this->loadPastors();
        $this->loadServiceTimes();
        $this->loadCtaSection();
    }

    protected function resolveChapter(): void
    {
        if ($this->chapter) {
            $this->activeChapter = Chapter::where('name', $this->chapter)->first();
        }
        
        if (!$this->activeChapter) {
            $this->activeChapter = Chapter::orderBy('name')->first();
            $this->chapter = $this->activeChapter?->name;
        }
    }

    protected function loadAboutUs(): void
    {
        $this->aboutUs = AboutUs::where('chapter_id', $this->activeChapter?->id)
            ->where('is_active', true)
            ->first();

        if (!$this->aboutUs) {
            $this->aboutUs = new AboutUs(['chapter_id' => $this->activeChapter?->id]);
        }

        $this->heroTitle = $this->aboutUs->hero_title;
        $this->heroSubtitle = $this->aboutUs->hero_subtitle;
        $this->existingHeroImage = $this->aboutUs->hero_background_image;
        $this->whoWeAreDescription = $this->aboutUs->description;
        $this->existingWhoWeAreImage = $this->aboutUs->hero_image;
        $this->mission = $this->aboutUs->mission;
        $this->vision = $this->aboutUs->vision;
        $this->coreValues = $this->aboutUs->core_values;
        $this->historyTimeline = $this->aboutUs->history_timeline ?? [];
        $this->conclavesPreviewCount = $this->aboutUs->conclaves_preview_count ?? 6;
    }

    protected function loadPastors(): void
    {
        $this->pastors = Pastor::where('chapter_id', $this->activeChapter?->id)
            ->orderBy('order_column')
            ->get()
            ->map(function ($pastor) {
                return [
                    'id' => $pastor->id,
                    'name' => $pastor->name,
                    'title' => $pastor->title,
                    'description' => $pastor->description,
                    'image' => $pastor->image,
                    'facebook_url' => $pastor->facebook_url,
                    'instagram_url' => $pastor->instagram_url,
                    'twitter_url' => $pastor->twitter_url,
                    'youtube_url' => $pastor->youtube_url,
                    'is_active' => $pastor->is_active,
                ];
            })
            ->toArray();
    }

    protected function loadServiceTimes(): void
    {
        $services = ServiceTime::where('chapter_id', $this->activeChapter?->id)
            ->where('is_active', true)
            ->orderBy('order_column')
            ->get();

        $this->sundayServices = $services->where('category', 'sunday')->map(function ($service) {
            return [
                'id' => $service->id,
                'service_name' => $service->service_name,
                'time' => $service->time,
                'order_column' => $service->order_column,
            ];
        })->values()->toArray();

        $this->thursdayServices = $services->where('category', 'thursday')->map(function ($service) {
            return [
                'id' => $service->id,
                'service_name' => $service->service_name,
                'time' => $service->time,
                'order_column' => $service->order_column,
            ];
        })->values()->toArray();
    }

    protected function loadCtaSection(): void
    {
        $this->ctaSection = CtaSection::where('chapter_id', $this->activeChapter?->id)
            ->where('is_active', true)
            ->first();

        if ($this->ctaSection) {
            $this->ctaTitle = $this->ctaSection->title;
            $this->ctaDescription = $this->ctaSection->description;
            $this->ctaButtonText = $this->ctaSection->button_text;
            $this->ctaButtonLink = $this->ctaSection->button_link;
        }
    }

    public function saveAboutUs(): void
    {
        try {
            // Validate text fields only - images are handled separately
            $validated = $this->validate([
                'heroTitle' => 'nullable|string|max:255',
                'heroSubtitle' => 'nullable|string|max:500',
                'whoWeAreDescription' => 'nullable|string',
                'mission' => 'nullable|string',
                'vision' => 'nullable|string',
                'coreValues' => 'nullable|string',
                'conclavesPreviewCount' => 'nullable|integer|min:1|max:20',
            ]);

            // Create AboutUs record if it doesn't exist
            if (!$this->aboutUs || !$this->aboutUs->exists) {
                $this->aboutUs = AboutUs::firstOrCreate(
                    ['chapter_id' => $this->activeChapter?->id],
                    [
                        'is_active' => true,
                        'title' => 'About Doxa Church',
                        'hero_title' => $validated['heroTitle'] ?? 'Welcome to Doxa Church',
                        'hero_subtitle' => $validated['heroSubtitle'] ?? 'A place where faith, hope, and love come together.',
                        'conclaves_preview_count' => $validated['conclavesPreviewCount'] ?? 6,
                    ]
                );
            }

            $this->aboutUs->hero_title = $validated['heroTitle'] ?? $this->aboutUs->hero_title;
            $this->aboutUs->hero_subtitle = $validated['heroSubtitle'] ?? $this->aboutUs->hero_subtitle;
            $this->aboutUs->description = $validated['whoWeAreDescription'] ?? $this->aboutUs->description;
            $this->aboutUs->mission = $validated['mission'] ?? $this->aboutUs->mission;
            $this->aboutUs->vision = $validated['vision'] ?? $this->aboutUs->vision;
            $this->aboutUs->core_values = $validated['coreValues'] ?? $this->aboutUs->core_values;
            $this->aboutUs->conclaves_preview_count = $validated['conclavesPreviewCount'] ?? $this->aboutUs->conclaves_preview_count;

            // Handle hero background image upload
            if ($this->heroBackgroundImage instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                if ($this->aboutUs->hero_background_image) {
                    Storage::disk('public')->delete($this->aboutUs->hero_background_image);
                }
                $path = $this->heroBackgroundImage->store('about/hero', 'public');
                $this->aboutUs->hero_background_image = $path;
            }

            // Handle who we are image upload
            if ($this->whoWeAreImage instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                if ($this->aboutUs->hero_image) {
                    Storage::disk('public')->delete($this->aboutUs->hero_image);
                }
                $path = $this->whoWeAreImage->store('about/who-we-are', 'public');
                $this->aboutUs->hero_image = $path;
            }

            $this->aboutUs->save();

            $this->toast()->success('Saved', 'About Us section updated successfully.')->send();
            $this->loadAboutUs();
        } catch (\Exception $e) {
            $this->toast()->error('Error', 'Failed to save: ' . $e->getMessage())->send();
        }
    }

    public function addHistoryEvent(): void
    {
        $this->historyTimeline[] = [
            'year' => '',
            'event' => '',
        ];
    }

    public function removeHistoryEvent(int $index): void
    {
        unset($this->historyTimeline[$index]);
        $this->historyTimeline = array_values($this->historyTimeline);
    }

    public function saveHistoryTimeline(): void
    {
        try {
            if (!$this->aboutUs || !$this->aboutUs->exists) {
                $this->toast()->error('Error', 'Please save the About Us section first before adding timeline events.')->send();
                return;
            }
            
            $this->aboutUs->history_timeline = array_values($this->historyTimeline);
            $this->aboutUs->save();
            $this->toast()->success('Saved', 'History timeline updated successfully.')->send();
        } catch (\Exception $e) {
            $this->toast()->error('Error', 'Failed to save timeline: ' . $e->getMessage())->send();
        }
    }

    public function addPastor(): void
    {
        $this->pastors[] = [
            'name' => '',
            'title' => 'Lead Pastor',
            'description' => '',
            'image' => null,
            'facebook_url' => '',
            'instagram_url' => '',
            'twitter_url' => '',
            'youtube_url' => '',
            'is_active' => true,
        ];
    }

    public function removePastor(int $index): void
    {
        unset($this->pastors[$index]);
        $this->pastors = array_values($this->pastors);
    }

    public function savePastors(): void
    {
        try {
            foreach ($this->pastors as $index => $pastorData) {
                // Skip empty pastor entries
                if (empty($pastorData['name'])) {
                    continue;
                }

                if (isset($pastorData['id']) && $pastorData['id']) {
                    $pastor = Pastor::find($pastorData['id']);
                } else {
                    $pastor = new Pastor();
                    $pastor->chapter_id = $this->activeChapter?->id;
                    $pastor->order_column = $index;
                }

                $pastor->name = $pastorData['name'] ?? '';
                $pastor->title = $pastorData['title'] ?? 'Lead Pastor';
                $pastor->description = $pastorData['description'] ?? '';
                $pastor->facebook_url = $pastorData['facebook_url'] ?? '';
                $pastor->instagram_url = $pastorData['instagram_url'] ?? '';
                $pastor->twitter_url = $pastorData['twitter_url'] ?? '';
                $pastor->youtube_url = $pastorData['youtube_url'] ?? '';
                $pastor->is_active = $pastorData['is_active'] ?? true;

                // Handle image upload
                if (isset($pastorData['temp_image']) && $pastorData['temp_image']) {
                    if ($pastor->image) {
                        Storage::disk('public')->delete($pastor->image);
                    }
                    $path = $pastorData['temp_image']->store('pastors', 'public');
                    $pastor->image = $path;
                }

                $pastor->save();
            }

            $this->toast()->success('Saved', 'Pastor section updated successfully.')->send();
            $this->loadPastors();
        } catch (\Exception $e) {
            $this->toast()->error('Error', 'Failed to save pastors: ' . $e->getMessage())->send();
        }
    }

    public function addSundayService(): void
    {
        $this->sundayServices[] = [
            'service_name' => '',
            'time' => '',
        ];
    }

    public function removeSundayService(int $index): void
    {
        unset($this->sundayServices[$index]);
        $this->sundayServices = array_values($this->sundayServices);
    }

    public function addThursdayService(): void
    {
        $this->thursdayServices[] = [
            'service_name' => '',
            'time' => '',
        ];
    }

    public function removeThursdayService(int $index): void
    {
        unset($this->thursdayServices[$index]);
        $this->thursdayServices = array_values($this->thursdayServices);
    }

    public function saveServiceTimes(): void
    {
        try {
            // Delete existing services for this chapter first
            ServiceTime::where('chapter_id', $this->activeChapter?->id)->delete();
            
            // Save Sunday services
            foreach ($this->sundayServices as $index => $serviceData) {
                if (empty($serviceData['service_name'])) {
                    continue;
                }
                
                $service = new ServiceTime();
                $service->chapter_id = $this->activeChapter?->id;
                $service->category = 'sunday';
                $service->service_name = $serviceData['service_name'] ?? '';
                $service->time = $serviceData['time'] ?? '';
                $service->order_column = $index;
                $service->is_active = true;
                $service->save();
            }

            // Save Thursday services
            foreach ($this->thursdayServices as $index => $serviceData) {
                if (empty($serviceData['service_name'])) {
                    continue;
                }
                
                $service = new ServiceTime();
                $service->chapter_id = $this->activeChapter?->id;
                $service->category = 'thursday';
                $service->service_name = $serviceData['service_name'] ?? '';
                $service->time = $serviceData['time'] ?? '';
                $service->order_column = $index;
                $service->is_active = true;
                $service->save();
            }

            $this->toast()->success('Saved', 'Service times updated successfully.')->send();
            $this->loadServiceTimes();
        } catch (\Exception $e) {
            $this->toast()->error('Error', 'Failed to save service times: ' . $e->getMessage())->send();
        }
    }

    public function saveCtaSection(): void
    {
        try {
            $validated = $this->validate([
                'ctaTitle' => 'required|string|max:255',
                'ctaDescription' => 'nullable|string',
                'ctaButtonText' => 'required|string|max:100',
                'ctaButtonLink' => 'required|url|max:255',
            ]);

            $this->ctaSection = CtaSection::updateOrCreate(
                ['chapter_id' => $this->activeChapter?->id],
                [
                    'title' => $validated['ctaTitle'],
                    'description' => $validated['ctaDescription'] ?? '',
                    'button_text' => $validated['ctaButtonText'],
                    'button_link' => $validated['ctaButtonLink'],
                    'is_active' => true,
                ]
            );

            $this->toast()->success('Saved', 'CTA section updated successfully.')->send();
            $this->loadCtaSection();
        } catch (\Exception $e) {
            $this->toast()->error('Error', 'Failed to save CTA: ' . $e->getMessage())->send();
        }
    }

    public function updatedChapter(): void
    {
        $this->resolveChapter();
        $this->loadAboutUs();
        $this->loadPastors();
        $this->loadServiceTimes();
        $this->loadCtaSection();
    }
}; ?>

<div class="space-y-6" x-data="{ activeTab: 'Hero' }">
    <x-fancy-header title="About Page Settings" subtitle="Manage all sections of the public about page" :breadcrumbs="[
        ['label' => 'Home', 'url' => route('admin.dashboard')],
        ['label' => 'Settings'],
        ['label' => 'About Page']
    ]">
        <div class="mt-4 max-w-sm">
            <form method="GET" action="{{ route('admin.dashboard.settings.about-page') }}" class="space-y-2">
                <label for="scope-chapter" class="block text-xs font-semibold uppercase tracking-[0.15em] text-gray-600">Choose Chapter</label>
                <div class="flex gap-2">
                    <select id="scope-chapter" name="chapter" class="flex-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        @foreach(Chapter::orderBy('name')->pluck('name') as $chapterName)
                            <option value="{{ $chapterName }}" @selected($chapter === $chapterName)>
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
    </x-fancy-header>

    <x-card class="dark:bg-zinc-800 text-white overflow-hidden">
        {{-- Custom Responsive Tabs --}}
        <div class="border-b border-zinc-700">
            <nav class="flex overflow-x-auto no-scrollbar" aria-label="Tabs">
                @foreach(['Hero', 'Who We Are', 'Our Foundation', 'Our Pastor', 'Service Time', 'Conclave Preview', 'Join Community (CTA)'] as $tabName)
                    <button 
                        @click="activeTab = '{{ $tabName }}'"
                        :class="{ 'border-blue-500 text-blue-500': activeTab === '{{ $tabName }}', 'border-transparent text-gray-400 hover:text-gray-200 hover:border-gray-600': activeTab !== '{{ $tabName }}' }"
                        class="whitespace-nowrap border-b-2 py-4 px-4 text-sm font-medium transition-all duration-200 focus:outline-none"
                    >
                        {{ $tabName }}
                    </button>
                @endforeach
            </nav>
        </div>

        <div class="mt-6">
            <!-- Hero Tab -->
            <div x-show="activeTab === 'Hero'" x-cloak>
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold">Hero Section</h3>
                        <p class="text-sm text-slate-400">Top banner section of the about page</p>
                    </div>
                    <x-button wire:click="saveAboutUs" class="bg-blue-600 hover:bg-blue-700">Save Hero</x-button>
                </div>
                <div class="space-y-4">
                    <flux:input wire:model="heroTitle" label="Hero Title" type="text" placeholder="Welcome to Doxa Church" />
                    <flux:textarea wire:model="heroSubtitle" label="Hero Subtitle" rows="2" placeholder="A place where faith, hope, and love come together." />
                    <div>
                        <label class="mb-1 block text-sm font-medium">Hero Background Image</label>
                        @if($existingHeroImage)
                            <div class="mb-2">
                                <img src="{{ Storage::url($existingHeroImage) }}" alt="Hero" class="h-32 rounded object-cover" />
                            </div>
                        @endif
                        <input type="file" wire:model="heroBackgroundImage" accept="image/*" class="w-full rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2" />
                    </div>
                </div>
            </div>

            <!-- Who We Are Tab -->
            <div x-show="activeTab === 'Who We Are'" x-cloak>
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold">Who We Are</h3>
                        <p class="text-sm text-slate-400">Main description section</p>
                    </div>
                    <x-button wire:click="saveAboutUs" class="bg-blue-600 hover:bg-blue-700">Save Section</x-button>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Section Image</label>
                        @if($existingWhoWeAreImage)
                            <div class="mb-2">
                                <img src="{{ Storage::url($existingWhoWeAreImage) }}" alt="Who We Are" class="h-32 rounded object-cover" />
                            </div>
                        @endif
                        <input type="file" wire:model="whoWeAreImage" accept="image/*" class="w-full rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2" />
                    </div>
                    <flux:textarea wire:model="whoWeAreDescription" label="Description" rows="5" placeholder="Enter the main description..." />
                </div>
            </div>

            <!-- Our Foundation Tab -->
            <div x-show="activeTab === 'Our Foundation'" x-cloak>
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold">Our Foundation</h3>
                        <p class="text-sm text-slate-400">Mission, Vision, and Core Values</p>
                    </div>
                    <x-button wire:click="saveAboutUs" class="bg-blue-600 hover:bg-blue-700">Save Section</x-button>
                </div>
                <div class="space-y-4">
                    <flux:textarea wire:model="mission" label="Mission" rows="3" placeholder="Our mission..." />
                    <flux:textarea wire:model="vision" label="Vision" rows="3" placeholder="Our vision..." />
                    <flux:textarea wire:model="coreValues" label="Core Values" rows="3" placeholder="Our core values..." />
                </div>
            </div>

            <!-- Our Pastor Tab -->
            <div x-show="activeTab === 'Our Pastor'" x-cloak>
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold">Our Pastor</h3>
                        <p class="text-sm text-slate-400">Pastor information and social links</p>
                    </div>
                    <div class="flex gap-2">
                        <x-button wire:click="addPastor" class="bg-green-600 hover:bg-green-700">+ Add Pastor</x-button>
                        <x-button wire:click="savePastors" class="bg-blue-600 hover:bg-blue-700">Save Pastors</x-button>
                    </div>
                </div>
                <div class="space-y-4">
                    @foreach($pastors as $index => $pastor)
                        <div class="rounded-lg border border-zinc-700 bg-zinc-900 p-4">
                            <div class="mb-3 flex items-center justify-between">
                                <h4 class="font-medium">Pastor #{{ $index + 1 }}</h4>
                                <x-button wire:click="removePastor({{ $index }})" class="bg-red-600 hover:bg-red-700 text-xs px-2 py-1">Remove</x-button>
                            </div>
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 text-white">
                                <flux:input wire:model="pastors.{{ $index }}.name" label="Name" type="text" />
                                <flux:input wire:model="pastors.{{ $index }}.title" label="Title" type="text" />
                                <flux:textarea wire:model="pastors.{{ $index }}.description" label="Description" rows="3" class="md:col-span-2" />
                                <div class="md:col-span-2">
                                    <label class="mb-1 block text-sm font-medium">Image</label>
                                    @if(isset($pastor['image']))
                                        <div class="mb-2">
                                            <img src="{{ Storage::url($pastor['image']) }}" alt="Pastor" class="h-24 w-24 rounded object-cover" />
                                        </div>
                                    @endif
                                    <input type="file" wire:model="pastors.{{ $index }}.temp_image" accept="image/*" class="w-full rounded-lg border border-zinc-700 bg-zinc-800 px-3 py-2" />
                                </div>
                                <flux:input wire:model="pastors.{{ $index }}.facebook_url" label="Facebook URL" type="url" />
                                <flux:input wire:model="pastors.{{ $index }}.instagram_url" label="Instagram URL" type="url" />
                                <flux:input wire:model="pastors.{{ $index }}.twitter_url" label="Twitter/X URL" type="url" />
                                <flux:input wire:model="pastors.{{ $index }}.youtube_url" label="YouTube URL" type="url" />
                                <div class="flex items-center gap-2">
                                    <flux:checkbox wire:model="pastors.{{ $index }}.is_active" label="Active" />
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Service Time Tab -->
            <div x-show="activeTab === 'Service Time'" x-cloak>
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold">Service Times</h3>
                        <p class="text-sm text-slate-400">Sunday and Thursday service schedules</p>
                    </div>
                    <x-button wire:click="saveServiceTimes" class="bg-blue-600 hover:bg-blue-700">Save Services</x-button>
                </div>
                <div class="space-y-6">
                    <!-- Sunday Services -->
                    <div>
                        <div class="mb-3 flex items-center justify-between">
                            <h4 class="font-medium text-blue-400">Sunday Services</h4>
                            <x-button wire:click="addSundayService" class="bg-green-600 hover:bg-green-700">+ Add Service</x-button>
                        </div>
                        @foreach($sundayServices as $index => $service)
                            <div class="mb-2 flex items-center gap-3 rounded-lg border border-zinc-700 bg-zinc-900 p-3">
                                <flux:input wire:model="sundayServices.{{ $index }}.service_name" label="Service Name" type="text" placeholder="e.g., 1st Service" class="flex-1" />
                                <flux:input wire:model="sundayServices.{{ $index }}.time" label="Time" type="text" placeholder="e.g., 7:00 AM - 9:00 AM" class="w-48" />
                                <x-button wire:click="removeSundayService({{ $index }})" class="bg-red-600 hover:bg-red-700 text-xs">Remove</x-button>
                            </div>
                        @endforeach
                    </div>
                    <!-- Thursday Services -->
                    <div>
                        <div class="mb-3 flex items-center justify-between">
                            <h4 class="font-medium text-blue-400">Thursday Services</h4>
                            <x-button wire:click="addThursdayService" class="bg-green-600 hover:bg-green-700">+ Add Service</x-button>
                        </div>
                        @foreach($thursdayServices as $index => $service)
                            <div class="mb-2 flex items-center gap-3 rounded-lg border border-zinc-700 bg-zinc-900 p-3">
                                <flux:input wire:model="thursdayServices.{{ $index }}.service_name" label="Service Name" type="text" placeholder="e.g., Bible Study" class="flex-1" />
                                <flux:input wire:model="thursdayServices.{{ $index }}.time" label="Time" type="text" placeholder="e.g., 6:00 PM - 8:00 PM" class="w-48" />
                                <x-button wire:click="removeThursdayService({{ $index }})" class="bg-red-600 hover:bg-red-700 text-xs">Remove</x-button>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Conclave Preview Tab -->
            <div x-show="activeTab === 'Conclave Preview'" x-cloak>
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold">Conclaves Preview</h3>
                        <p class="text-sm text-slate-400">Number of conclaves to show on about page</p>
                    </div>
                    <x-button wire:click="saveAboutUs" class="bg-blue-600 hover:bg-blue-700">Save Setting</x-button>
                </div>
                <flux:input wire:model="conclavesPreviewCount" label="Number of Conclaves to Display" type="number" min="1" max="20" />
            </div>

            <!-- Join Community (CTA) Tab -->
            <div x-show="activeTab === 'Join Community (CTA)'" x-cloak>
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold">Join Community (CTA)</h3>
                        <p class="text-sm text-slate-400">Call-to-action section at the bottom</p>
                    </div>
                    <x-button wire:click="saveCtaSection" class="bg-blue-600 hover:bg-blue-700">Save CTA</x-button>
                </div>
                <div class="space-y-4">
                    <flux:input wire:model="ctaTitle" label="Title" type="text" placeholder="Join Our Community" />
                    <flux:textarea wire:model="ctaDescription" label="Description" rows="2" placeholder="Experience the love of Christ..." />
                    <flux:input wire:model="ctaButtonText" label="Button Text" type="text" placeholder="Visit Us" />
                    <flux:input wire:model="ctaButtonLink" label="Button Link" type="url" placeholder="/contact" />
                </div>
            </div>
        </div>
    </x-card>

    <style>
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</div>
