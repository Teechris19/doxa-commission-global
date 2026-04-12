<?php

use App\Models\{CellPageSetting};
use Livewire\Attributes\{Layout};
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions, WithFileUploads;

    // Hero Section
    public string $hero_title = 'Join a Cell Group';
    public string $hero_subtitle = 'Connect, grow, and fellowship in small groups';
    public ?string $hero_description = '';
    public $hero_image = null;
    public string $hero_image_path = '';
    public string $hero_button_text = 'Join a Cell';

    // Left/Right Text Section
    public string $left_heading = 'HOME CLOSE TO YOU';
    public ?string $right_description = '';

    // Center Image
    public $center_image = null;
    public string $center_image_path = '';

    // Display Settings
    public int $cells_to_display = 3;

    // FAQs
    public array $faqs = [];

    public function mount(): void
    {
        $this->loadSettings();
    }

    protected function loadSettings(): void
    {
        $settings = CellPageSetting::whereNull('chapter_id')->first();

        if ($settings) {
            $this->hero_title = $settings->hero_title ?? 'Join a Cell Group';
            $this->hero_subtitle = $settings->hero_subtitle ?? 'Connect, grow, and fellowship in small groups';
            $this->hero_description = $settings->hero_description ?? '';
            $this->hero_image_path = $settings->hero_image ?? '';
            $this->hero_button_text = $settings->hero_button_text ?? 'Join a Cell';
            $this->left_heading = $settings->left_heading ?? 'HOME CLOSE TO YOU';
            $this->right_description = $settings->right_description ?? '';
            $this->center_image_path = $settings->center_image ?? '';
            $this->cells_to_display = $settings->cells_to_display ?? 3;
            $this->faqs = $settings->faqs ?? [];
        } else {
            $this->resetForm();
        }
    }

    protected function resetForm(): void
    {
        $this->hero_title = 'Join a Cell Group';
        $this->hero_subtitle = 'Connect, grow, and fellowship in small groups';
        $this->hero_description = '';
        $this->hero_image = null;
        $this->hero_image_path = '';
        $this->hero_button_text = 'Join a Cell';
        $this->left_heading = 'HOME CLOSE TO YOU';
        $this->right_description = '';
        $this->center_image = null;
        $this->center_image_path = '';
        $this->cells_to_display = 3;
        $this->faqs = [];
    }

    public function addFaq(): void
    {
        if (count($this->faqs) >= 20) {
            $this->toast()->warning('Limit Reached', 'Maximum 20 FAQs allowed.')->send();
            return;
        }

        $this->faqs[] = ['question' => '', 'answer' => ''];
    }

    public function removeFaq(int $index): void
    {
        unset($this->faqs[$index]);
        $this->faqs = array_values($this->faqs);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'hero_title' => 'required|string|max:255',
            'hero_subtitle' => 'required|string|max:255',
            'hero_description' => 'nullable|string|max:2000',
            'hero_image' => 'nullable|image|max:5120',
            'hero_button_text' => 'required|string|max:100',
            'left_heading' => 'required|string|max:255',
            'right_description' => 'nullable|string|max:2000',
            'center_image' => 'nullable|image|max:5120',
            'cells_to_display' => 'required|integer|min:1|max:50',
            'faqs' => 'nullable|array',
            'faqs.*.question' => 'required|string|max:255',
            'faqs.*.answer' => 'required|string|max:2000',
        ]);

        // Upload hero image
        if ($this->hero_image) {
            if ($this->hero_image_path) {
                Storage::disk('public')->delete($this->hero_image_path);
            }
            $this->hero_image_path = $this->hero_image->store('cell-page/hero', 'public');
        }

        // Upload center image
        if ($this->center_image) {
            if ($this->center_image_path) {
                Storage::disk('public')->delete($this->center_image_path);
            }
            $this->center_image_path = $this->center_image->store('cell-page/center', 'public');
        }

        // Clean FAQs
        $cleanFaqs = collect($this->faqs)
            ->filter(fn($faq) => !empty($faq['question']) && !empty($faq['answer']))
            ->values()
            ->toArray();

        CellPageSetting::updateOrCreate(
            ['chapter_id' => null],
            [
                'hero_title' => $validated['hero_title'],
                'hero_subtitle' => $validated['hero_subtitle'],
                'hero_description' => $validated['hero_description'] ?: null,
                'hero_image' => $this->hero_image_path ?: null,
                'hero_button_text' => $validated['hero_button_text'],
                'left_heading' => $validated['left_heading'],
                'right_description' => $validated['right_description'] ?: null,
                'center_image' => $this->center_image_path ?: null,
                'cells_to_display' => (int) $validated['cells_to_display'],
                'faqs' => $cleanFaqs,
            ]
        );

        $this->hero_image = null;
        $this->center_image = null;

        $this->toast()->success('Saved', 'Cell page settings updated successfully.')->send();
    }
};

?>

<div class="space-y-6">
    <x-fancy-header
        title="Cell Page Settings"
        subtitle="Configure the public cell groups page appearance (global settings apply to all chapters)"
        :breadcrumbs="[
            ['label' => 'Home', 'url' => route('admin.dashboard', request()->query())],
            ['label' => 'Cells', 'url' => route('admin.dashboard.cells.index', request()->query())],
            ['label' => 'Settings']
        ]"
    />

    <form wire:submit.prevent="save" class="space-y-6">
        {{-- Hero Section --}}
        <div class="rounded-xl bg-white p-6 shadow dark:bg-slate-800">
            <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-4">Hero Section</h3>
            
            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Hero Title</label>
                    <input wire:model.lazy="hero_title" type="text" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100" />
                    @error('hero_title') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Hero Subtitle</label>
                    <input wire:model.lazy="hero_subtitle" type="text" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100" />
                    @error('hero_subtitle') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Hero Description</label>
                    <textarea wire:model.lazy="hero_description" rows="3" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100"></textarea>
                    @error('hero_description') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Button Text</label>
                    <input wire:model.lazy="hero_button_text" type="text" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100" />
                    @error('hero_button_text') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Hero Background Image</label>
                    @if(!empty($hero_image_path))
                        <div class="mb-2">
                            <img src="{{ Storage::url($hero_image_path) }}" alt="Current hero image" class="h-32 w-full rounded-lg object-cover" />
                        </div>
                    @endif
                    <input wire:model="hero_image" type="file" accept="image/*" class="w-full text-sm text-slate-600 dark:text-slate-300" />
                    @error('hero_image') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        {{-- Left/Right Text Section --}}
        <div class="rounded-xl bg-white p-6 shadow dark:bg-slate-800">
            <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-4">Left/Right Text Section</h3>
            
            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Left Heading</label>
                    <input wire:model.lazy="left_heading" type="text" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100" placeholder="e.g. HOME CLOSE TO YOU" />
                    @error('left_heading') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Right Description</label>
                    <textarea wire:model.lazy="right_description" rows="4" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100" placeholder="Think about Doxa Cell as a small gathering..."></textarea>
                    @error('right_description') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        {{-- Center Image --}}
        <div class="rounded-xl bg-white p-6 shadow dark:bg-slate-800">
            <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-4">Center Image</h3>
            
            <div>
                @if(!empty($center_image_path))
                    <div class="mb-2">
                        <img src="{{ Storage::url($center_image_path) }}" alt="Current center image" class="h-40 w-full rounded-lg object-cover" />
                    </div>
                @endif
                <input wire:model="center_image" type="file" accept="image/*" class="w-full text-sm text-slate-600 dark:text-slate-300" />
                @error('center_image') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
        </div>

        {{-- Display Settings --}}
        <div class="rounded-xl bg-white p-6 shadow dark:bg-slate-800">
            <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-4">Display Settings</h3>
            
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Number of Cells to Display</label>
                <input wire:model.lazy="cells_to_display" type="number" min="1" max="50" class="w-full max-w-xs rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100" />
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Set how many cell cards show before "View All Cells" button</p>
                @error('cells_to_display') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
        </div>

        {{-- FAQs Section --}}
        <div class="rounded-xl bg-white p-6 shadow dark:bg-slate-800">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100">Frequently Asked Questions</h3>
                <button type="button" wire:click="addFaq" class="rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-blue-700">
                    Add FAQ
                </button>
            </div>
            
            <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">Add up to 20 FAQs for the public page</p>

            <div class="space-y-4">
                @php $faqsList = $this->faqs ?? []; @endphp
                @forelse($faqsList as $index => $faq)
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-700/50">
                        <div class="mb-2 flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">FAQ #{{ $index + 1 }}</span>
                            <button type="button" wire:click="removeFaq({{ $index }})" class="text-xs text-red-500 hover:text-red-700">Remove</button>
                        </div>
                        <div class="space-y-2">
                            <input wire:model.lazy="faqs.{{ $index }}.question" type="text" placeholder="Question" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100" />
                            <textarea wire:model.lazy="faqs.{{ $index }}.answer" rows="2" placeholder="Answer" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100"></textarea>
                        </div>
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-6 text-center dark:border-slate-600 dark:bg-slate-700/30">
                        <p class="text-sm text-slate-500 dark:text-slate-400">No FAQs added yet. Click "Add FAQ" to create one.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Save Button --}}
        <div class="flex gap-3">
            <button type="submit" class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                Save Settings
            </button>
        </div>
    </form>
</div>
