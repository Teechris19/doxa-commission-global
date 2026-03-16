<?php

use App\Models\{PropertyAsset, Chapter};
use Livewire\Attributes\{Layout, Url};
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.admin')] class extends Component {
    #[Url(keep: true)]
    public ?string $chapter = null;

    public ?int $chapterId = null;
    public array $chapters = [];

    public int $totalAssets = 0;
    public int $assetsInUse = 0;
    public int $damagedItems = 0;
    public int $lowStockAlerts = 0;

    public function mount(): void
    {
        $user = Auth::user();

        if ($user->hasRole('super-admin')) {
            $this->chapters = Chapter::orderBy('name')->get()->all();
            if ($this->chapter) {
                $this->chapterId = Chapter::where('name', $this->chapter)->value('id');
            }
        } else {
            $this->chapterId = $user?->chapter_id;
        }

        $this->loadStats();
    }

    public function updatedChapterId(): void
    {
        if ($this->chapterId) {
            $this->chapter = Chapter::find($this->chapterId)?->name;
        }
        $this->loadStats();
    }

    private function loadStats(): void
    {
        $query = PropertyAsset::query()->when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId));

        $this->totalAssets = (int) $query->count();
        $this->assetsInUse = (int) PropertyAsset::query()
            ->when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->where('condition', 'in_use')
            ->count();
        $this->damagedItems = (int) PropertyAsset::query()
            ->when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->where('condition', 'damaged')
            ->count();
        $this->lowStockAlerts = (int) PropertyAsset::query()
            ->when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->where('low_stock_threshold', '>', 0)
            ->whereColumn('quantity', '<=', 'low_stock_threshold')
            ->count();
    }
};

?>

<div class="space-y-6">
    <x-fancy-header
        title="Properties Dashboard"
        subtitle="Asset management overview"
        :breadcrumbs="[
            ['label' => 'Home', 'url' => route('admin.dashboard', request()->query())],
            ['label' => 'Properties']
        ]"
    />

    @if(Auth::user()->hasRole('super-admin'))
        <div class="rounded-xl bg-white p-4 shadow">
            <label class="mb-1 block text-sm font-medium">Branch</label>
            <select wire:model="chapterId" class="w-full rounded-lg border px-3 py-2">
                <option value="">All branches</option>
                @foreach($chapters as $chapterOption)
                    <option value="{{ $chapterOption->id }}">{{ $chapterOption->name }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-xl bg-white p-5 shadow">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Total Assets</p>
            <p class="mt-3 text-3xl font-semibold text-zinc-900">{{ $totalAssets }}</p>
        </div>
        <div class="rounded-xl bg-white p-5 shadow">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">Assets In Use</p>
            <p class="mt-3 text-3xl font-semibold text-zinc-900">{{ $assetsInUse }}</p>
        </div>
        <div class="rounded-xl bg-white p-5 shadow">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-rose-600">Damaged Items</p>
            <p class="mt-3 text-3xl font-semibold text-zinc-900">{{ $damagedItems }}</p>
        </div>
        <div class="rounded-xl bg-white p-5 shadow">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-600">Low Stock Alerts</p>
            <p class="mt-3 text-3xl font-semibold text-zinc-900">{{ $lowStockAlerts }}</p>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <a href="{{ route('admin.dashboard.properties.inventory', request()->query()) }}" wire:navigate class="rounded-xl border border-blue-100 bg-white p-5 shadow hover:border-blue-200">
            <h3 class="text-lg font-semibold text-zinc-900">Inventory</h3>
            <p class="mt-2 text-sm text-zinc-600">View all assets, search inventory, and track stock levels.</p>
        </a>
        <a href="{{ route('admin.dashboard.properties.add-inventory', request()->query()) }}" wire:navigate class="rounded-xl border border-blue-100 bg-white p-5 shadow hover:border-blue-200">
            <h3 class="text-lg font-semibold text-zinc-900">Add Inventory</h3>
            <p class="mt-2 text-sm text-zinc-600">Register new church assets and supplies.</p>
        </a>
    </div>
</div>
