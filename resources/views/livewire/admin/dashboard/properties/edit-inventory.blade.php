<?php

use App\Models\{PropertyAsset, Chapter};
use Livewire\Attributes\{Layout, Url};
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions;

    #[Url]
    public ?int $id = null;

    #[Url(keep: true)]
    public ?string $chapter = null;

    public ?int $chapterId = null;

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
        if (!$this->id) {
            abort(404, 'Asset not found');
        }

        $asset = PropertyAsset::findOrFail($this->id);

        $user = Auth::user();
        if (!$user->hasRole('super-admin') && $user->chapter_id && $asset->chapter_id !== $user->chapter_id) {
            abort(403, 'Unauthorized');
        }

        $this->chapterId = $asset->chapter_id;

        $this->name = $asset->name;
        $this->quantity = $asset->quantity;
        $this->purchase_date = $asset->purchase_date?->format('Y-m-d');
        $this->cost = $asset->cost;
        $this->location = $asset->location;
        $this->condition = $asset->condition;
        $this->low_stock_threshold = $asset->low_stock_threshold;
        $this->is_active = $asset->is_active;
    }

    public function save(): void
    {
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

        $asset = PropertyAsset::findOrFail($this->id);
        $asset->update([
            'name' => $validated['name'],
            'quantity' => (int) $validated['quantity'],
            'purchase_date' => $validated['purchase_date'] ?? null,
            'cost' => $validated['cost'] ?? null,
            'location' => $validated['location'] ?? null,
            'condition' => $validated['condition'],
            'low_stock_threshold' => (int) $validated['low_stock_threshold'],
            'is_active' => (bool) $validated['is_active'],
        ]);

        $this->toast()->success('Updated', 'Inventory item updated.')->send();
        $this->redirect(route('admin.dashboard.properties.inventory', request()->query()), navigate: true);
    }
};

?>

<div class="space-y-6">
    <x-fancy-header
        title="Edit Inventory"
        subtitle="Update asset details"
        :breadcrumbs="[
            ['label' => 'Home', 'url' => route('admin.dashboard', request()->query())],
            ['label' => 'Properties', 'url' => route('admin.dashboard.properties.index', request()->query())],
            ['label' => 'Inventory', 'url' => route('admin.dashboard.properties.inventory', request()->query())],
            ['label' => 'Edit']
        ]"
    />

    <div class="rounded-xl bg-white p-6 shadow">
        <form wire:submit.prevent="save" class="space-y-5">
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
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Save Changes</button>
            </div>
        </form>
    </div>
</div>
