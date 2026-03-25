<?php

use App\Models\Chapter;
use App\Models\PastorSetting;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions, WithFileUploads;

    public ?int $chapterId = null;
    public ?string $chapterName = null;

    // Form fields
    public ?string $pastorName = null;
    public ?string $pastorTitle = 'Lead Pastor';
    public ?string $pastorDescription = null;
    public $pastorImage = null;
    public ?string $existingPastorImage = null;
    public ?string $ctaButtonText = 'Learn More';
    public ?string $ctaButtonUrl = null;
    public ?string $facebookUrl = null;
    public ?string $instagramUrl = null;
    public ?string $xUrl = null;
    public ?string $youtubeUrl = null;
    public ?string $tiktokUrl = null;
    public bool $isActive = true;

    public function mount(): void
    {
        $user = auth()->user();
        $this->chapterId = $user->chapter_id;
        $chapter = Chapter::find($this->chapterId);
        $this->chapterName = $chapter?->name ?? 'Not set';

        $this->loadPastorSettings();
    }

    public function loadPastorSettings(): void
    {
        $settings = PastorSetting::where('chapter_id', $this->chapterId)
            ->orWhereNull('chapter_id')
            ->first();

        if ($settings) {
            $this->pastorName = $settings->pastor_name;
            $this->pastorTitle = $settings->pastor_title ?? 'Lead Pastor';
            $this->pastorDescription = $settings->pastor_description;
            $this->existingPastorImage = $settings->pastor_image;
            $this->ctaButtonText = $settings->cta_button_text ?? 'Learn More';
            $this->ctaButtonUrl = $settings->cta_button_url;
            $this->facebookUrl = $settings->facebook_url;
            $this->instagramUrl = $settings->instagram_url;
            $this->xUrl = $settings->x_url;
            $this->youtubeUrl = $settings->youtube_url;
            $this->tiktokUrl = $settings->tiktok_url;
            $this->isActive = $settings->is_active;
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'pastorName' => 'nullable|string|max:255',
            'pastorTitle' => 'nullable|string|max:255',
            'pastorDescription' => 'nullable|string',
            'pastorImage' => 'nullable|image|max:5120', // 5MB
            'ctaButtonText' => 'nullable|string|max:100',
            'ctaButtonUrl' => 'nullable|url|max:255',
            'facebookUrl' => 'nullable|url|max:255',
            'instagramUrl' => 'nullable|url|max:255',
            'xUrl' => 'nullable|url|max:255',
            'youtubeUrl' => 'nullable|url|max:255',
            'tiktokUrl' => 'nullable|url|max:255',
        ]);

        $settings = PastorSetting::where('chapter_id', $this->chapterId)->first();

        if (!$settings) {
            $settings = new PastorSetting();
            $settings->chapter_id = $this->chapterId;
        }

        $settings->pastor_name = $validated['pastorName'];
        $settings->pastor_title = $validated['pastorTitle'];
        $settings->pastor_description = $validated['pastorDescription'];
        $settings->cta_button_text = $validated['ctaButtonText'];
        $settings->cta_button_url = $validated['ctaButtonUrl'];
        $settings->facebook_url = $validated['facebookUrl'];
        $settings->instagram_url = $validated['instagramUrl'];
        $settings->x_url = $validated['xUrl'];
        $settings->youtube_url = $validated['youtubeUrl'];
        $settings->tiktok_url = $validated['tiktokUrl'];
        $settings->is_active = $this->isActive;

        // Handle image upload
        if ($this->pastorImage) {
            // Delete old image if exists
            if ($settings->pastor_image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($settings->pastor_image);
            }

            $path = $this->pastorImage->store('pastor/images', 'public');
            $settings->pastor_image = $path;
        }

        $settings->save();

        $this->toast()->success('Saved', 'Pastor settings saved successfully.')->send();
        $this->loadPastorSettings();
    }

    public function removeImage(): void
    {
        $settings = PastorSetting::where('chapter_id', $this->chapterId)->first();

        if ($settings && $settings->pastor_image) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($settings->pastor_image);
            $settings->update(['pastor_image' => null]);
            $this->existingPastorImage = null;
            $this->toast()->success('Removed', 'Pastor image removed successfully.')->send();
        }
    }
}; ?>

<div>
    <x-fancy-header
        title="Meet Our Pastor"
        subtitle="Configure the pastor section displayed on the homepage"
        :breadcrumbs="[
            ['label' => 'Home', 'url' => route('admin.dashboard')],
            ['label' => 'Settings', 'url' => route('admin.dashboard.settings.index')],
            ['label' => 'Meet Our Pastor']
        ]"
    >
    </x-fancy-header>

    <form wire:submit.prevent="save" class="space-y-6">
        <!-- Main Settings Card -->
        <x-card>
            <div class="mb-6">
                <h3 class="text-lg font-semibold">Pastor Information</h3>
                <p class="text-sm text-slate-500">Enter the details that will be displayed on the homepage</p>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Pastor Name</label>
                    <input type="text" wire:model="pastorName" class="w-full rounded-lg border px-3 py-2" placeholder="e.g., Dr. John Doe" />
                    @error('pastorName') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Pastor Title</label>
                    <input type="text" wire:model="pastorTitle" class="w-full rounded-lg border px-3 py-2" placeholder="e.g., Lead Pastor" />
                    @error('pastorTitle') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="mt-4">
                <label class="mb-1 block text-sm font-medium">Pastor Description</label>
                <textarea wire:model="pastorDescription" rows="5" class="w-full rounded-lg border px-3 py-2" placeholder="Write a brief description about the pastor..."></textarea>
                <p class="mt-1 text-xs text-slate-500">This will be displayed on the homepage. Keep it concise and engaging.</p>
                @error('pastorDescription') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <div class="mt-4">
                <label class="mb-1 block text-sm font-medium">Pastor Image</label>
                <div class="mt-2 flex items-center gap-4">
                    @if($existingPastorImage)
                        <div class="relative">
                            <img src="{{ Storage::url($existingPastorImage) }}" alt="Pastor" class="h-32 w-32 rounded-lg object-cover" />
                            <button type="button" wire:click="removeImage" class="absolute -right-2 -top-2 rounded-full bg-red-500 p-1 text-white hover:bg-red-600">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    @endif
                    <div class="flex-1">
                        <input type="file" wire:model="pastorImage" accept="image/*" class="w-full rounded-lg border px-3 py-2" />
                        <p class="mt-1 text-xs text-slate-500">Recommended size: 600x800px. Max file size: 5MB</p>
                        @error('pastorImage') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </x-card>

        <!-- Call to Action Card -->
        <x-card>
            <div class="mb-6">
                <h3 class="text-lg font-semibold">Call-to-Action Button</h3>
                <p class="text-sm text-slate-500">Configure the CTA button that appears below the pastor description</p>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Button Text</label>
                    <input type="text" wire:model="ctaButtonText" class="w-full rounded-lg border px-3 py-2" placeholder="e.g., Learn More" />
                    @error('ctaButtonText') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Button URL</label>
                    <input type="url" wire:model="ctaButtonUrl" class="w-full rounded-lg border px-3 py-2" placeholder="https://example.com/about" />
                    @error('ctaButtonUrl') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>
        </x-card>

        <!-- Social Media Links Card -->
        <x-card>
            <div class="mb-6">
                <h3 class="text-lg font-semibold">Social Media Links</h3>
                <p class="text-sm text-slate-500">Add links to the pastor's or church's social media profiles</p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">
                        <svg class="mr-1 inline h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        Facebook
                    </label>
                    <input type="url" wire:model="facebookUrl" class="w-full rounded-lg border px-3 py-2" placeholder="https://facebook.com/yourpage" />
                    @error('facebookUrl') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        <svg class="mr-1 inline h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                        Instagram
                    </label>
                    <input type="url" wire:model="instagramUrl" class="w-full rounded-lg border px-3 py-2" placeholder="https://instagram.com/yourprofile" />
                    @error('instagramUrl') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        <svg class="mr-1 inline h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        X (Twitter)
                    </label>
                    <input type="url" wire:model="xUrl" class="w-full rounded-lg border px-3 py-2" placeholder="https://x.com/yourprofile" />
                    @error('xUrl') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        <svg class="mr-1 inline h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                        YouTube
                    </label>
                    <input type="url" wire:model="youtubeUrl" class="w-full rounded-lg border px-3 py-2" placeholder="https://youtube.com/yourchannel" />
                    @error('youtubeUrl') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        <svg class="mr-1 inline h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                        TikTok
                    </label>
                    <input type="url" wire:model="tiktokUrl" class="w-full rounded-lg border px-3 py-2" placeholder="https://tiktok.com/@yourprofile" />
                    @error('tiktokUrl') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>
        </x-card>

        <!-- Status Card -->
        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold">Section Status</h3>
                    <p class="text-sm text-slate-500">Enable or disable the "Meet Our Pastor" section on the homepage</p>
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-sm font-medium">Active</label>
                    <input type="checkbox" wire:model="isActive" class="h-5 w-5 rounded border-zinc-300 text-blue-600 focus:ring-blue-500" />
                </div>
            </div>
        </x-card>

        <!-- Save Button -->
        <div class="flex justify-end">
            <button type="submit" class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                Save Settings
            </button>
        </div>
    </form>
</div>
