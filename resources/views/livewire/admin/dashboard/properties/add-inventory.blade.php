<?php

use App\Models\{PropertyAsset, Chapter};
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
    public int $quantity = 0;
    public ?string $purchase_date = null;
    public ?string $cost = null;
    public ?string $location = null;
    public string $condition = 'good';
    public int $low_stock_threshold = 0;
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
        if ($this->chapterId) {
            $this->chapter = Chapter::find($this->chapterId)?->name;
        }
    }

    public function save(): void
    {
        if (!$this->chapterId) {
            $this->toast()->error('No Branch', 'Select a branch before adding inventory.')->send();
            return;
        }

        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'purchase_date' => 'nullable|date',
            'cost' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'condition' => 'required|string|max:30',
            'low_stock_threshold' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        PropertyAsset::create([
            'chapter_id' => $this->chapterId,
            'name' => $validated['name'],
            'quantity' => (int) $validated['quantity'],
            'purchase_date' => $validated['purchase_date'] ?? null,
            'cost' => $validated['cost'] ?? null,
            'location' => $validated['location'] ?? null,
            'condition' => $validated['condition'],
            'low_stock_threshold' => (int) $validated['low_stock_threshold'],
            'is_active' => (bool) $validated['is_active'],
        ]);

        $this->toast()->success('Saved', 'Inventory item added.')->send();
        $this->reset(['name', 'quantity', 'purchase_date', 'cost', 'location', 'condition', 'low_stock_threshold', 'is_active']);
        $this->condition = 'good';
        $this->quantity = 0;
        $this->low_stock_threshold = 0;
        $this->is_active = true;
    }
};

?>

<div class="space-y-6">
    <x-fancy-header
        title="Add Inventory"
        subtitle="Register a new asset"
        :breadcrumbs="[
            ['label' => 'Home', 'url' => route('admin.dashboard', request()->query())],
            ['label' => 'Properties', 'url' => route('admin.dashboard.properties.index', request()->query())],
            ['label' => 'Add Inventory']
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
                    <label class="mb-1 block text-sm font-medium">Item Name</label>
                    <input wire:model.lazy="name" type="text" class="w-full rounded-lg border px-3 py-2" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Quantity</label>
                    <input wire:model.lazy="quantity" type="number" min="0" class="w-full rounded-lg border px-3 py-2" />
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Purchase Date</label>
                    <input wire:model.lazy="purchase_date" type="date" class="w-full rounded-lg border px-3 py-2" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Cost</label>
                    <input wire:model.lazy="cost" type="number" step="0.01" min="0" class="w-full rounded-lg border px-3 py-2" />
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Location</label>
                    <input wire:model.lazy="location" type="text" class="w-full rounded-lg border px-3 py-2" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Condition</label>
                    <select wire:model="condition" class="w-full rounded-lg border px-3 py-2">
                        <option value="good">Good</option>
                        <option value="in_use">In Use</option>
                        <option value="damaged">Damaged</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Low Stock Threshold</label>
                    <input wire:model.lazy="low_stock_threshold" type="number" min="0" class="w-full rounded-lg border px-3 py-2" />
                </div>
                <div class="flex items-center gap-3 pt-6">
                    <input id="is_active" type="checkbox" wire:model="is_active" class="rounded border-zinc-300" />
                    <label for="is_active" class="text-sm">Active</label>
                </div>
            </div>

            <div class="flex gap-3">
                <a href="{{ route('admin.dashboard.properties.inventory', request()->query()) }}" wire:navigate class="inline-flex items-center rounded-lg border px-4 py-2 text-sm">Back</a>
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Save Item</button>
            </div>
        </form>
    </div>
</div>
