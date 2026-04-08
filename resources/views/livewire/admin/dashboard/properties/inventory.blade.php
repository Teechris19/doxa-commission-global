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

    public ?string $search = null;

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
    }

    public function updatedChapterId(): void
    {
        if ($this->chapterId) {
            $this->chapter = Chapter::find($this->chapterId)?->name;
        }
    }

    public function getAssetsProperty()
    {
        return PropertyAsset::query()
            ->when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->when($this->search, function ($q) {
                $term = '%' . $this->search . '%';
                $q->where('name', 'like', $term)
                    ->orWhere('location', 'like', $term);
            })
            ->orderBy('name')
            ->limit(100)
            ->get();
    }
};

?>

<div class="space-y-6">
    <x-fancy-header
        title="Inventory"
        subtitle="Full asset list"
        :breadcrumbs="[
            ['label' => 'Home', 'url' => route('admin.dashboard', request()->query())],
            ['label' => 'Properties', 'url' => route('admin.dashboard.properties.index', request()->query())],
            ['label' => 'Inventory']
        ]"
    />

    <div class="rounded-xl bg-white p-4 shadow space-y-4">
        <div class="grid gap-3 md:grid-cols-3">
            @if(Auth::user()->hasRole('super-admin'))
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Branch</label>
                    <select wire:model="chapterId" class="w-full rounded-lg border px-3 py-2">
                        <option value="">All branches</option>
                        @foreach($chapters as $chapterOption)
                            <option value="{{ $chapterOption->id }}">{{ $chapterOption->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="md:col-span-2">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Search</label>
                <input type="text" wire:model.debounce.300ms="search" class="w-full rounded-lg border px-3 py-2" placeholder="Search name or location" />
            </div>
        </div>
    </div>

    <div class="rounded-xl bg-white p-5 shadow">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-zinc-500">
                    <tr>
                        <th class="py-2 pr-4">Item</th>
                        <th class="py-2 pr-4">Quantity</th>
                        <th class="py-2 pr-4">Condition</th>
                        <th class="py-2 pr-4">Location</th>
                        <th class="py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->assets as $asset)
                        <tr class="border-t">
                            <td class="py-2 pr-4 font-medium text-zinc-800">{{ $asset->name }}</td>
                            <td class="py-2 pr-4">{{ $asset->quantity }}</td>
                            <td class="py-2 pr-4">{{ ucfirst(str_replace('_', ' ', $asset->condition)) }}</td>
                            <td class="py-2 pr-4">{{ $asset->location ?? 'N/A' }}</td>
                            <td class="py-2">
                                <a href="{{ route('admin.dashboard.properties.edit-inventory', ['id' => $asset->id] + request()->query()) }}" wire:navigate class="text-blue-600 hover:underline">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-zinc-500">No assets found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
