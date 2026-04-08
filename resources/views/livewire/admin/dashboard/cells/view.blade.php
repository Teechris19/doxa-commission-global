<?php

use App\Models\CellGroup;
use Livewire\Attributes\{Layout, Url};
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions;

    #[Url]
    public ?int $id = null;

    public $cell;

    public function mount(): void
    {
        if (!$this->id) {
            abort(404, 'Cell group not found');
        }

        $this->cell = CellGroup::with(['chapter', 'leaders', 'members', 'primaryLeader'])
            ->withCount(['members', 'activeMembers'])
            ->findOrFail($this->id);

        $user = Auth::user();
        if (!$user->hasRole('super-admin') && $user->chapter_id && $this->cell->chapter_id !== $user->chapter_id) {
            abort(403, 'Unauthorized');
        }
    }

    public function goBack(): void
    {
        $this->redirect(route('admin.dashboard.cells.index', request()->query()), navigate: true);
    }
};

?>

<div class="space-y-6">
    <x-fancy-header
        title="Cell Group Details"
        subtitle="View cell group information"
        :breadcrumbs="[
            ['label' => 'Home', 'url' => route('admin.dashboard', request()->query())],
            ['label' => 'Cell Groups', 'url' => route('admin.dashboard.cells.index', request()->query())],
            ['label' => $cell->name ?? 'Details']
        ]"
    />

    <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
        <div class="rounded-xl bg-white p-6 shadow">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-semibold text-zinc-900">{{ $cell->name }}</h2>
                    <p class="mt-1 text-sm text-zinc-500">Branch: {{ $cell->chapter?->name ?? 'N/A' }}</p>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $cell->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-zinc-200 text-zinc-600' }}">
                    {{ $cell->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>

            @if($cell->description)
                <p class="mt-4 text-sm text-zinc-600 leading-6">{{ $cell->description }}</p>
            @endif

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Meetings</p>
                    <p class="mt-2 text-sm text-zinc-700">
                        {{ $cell->meeting_day ? $cell->meeting_day . 's' : 'Not set' }}
                        @if($cell->meeting_time)
                            at {{ \Carbon\Carbon::parse($cell->meeting_time)->format('g:i A') }}
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Members</p>
                    <p class="mt-2 text-sm text-zinc-700">{{ $cell->active_members_count }} / {{ $cell->max_members }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Location</p>
                    <p class="mt-2 text-sm text-zinc-700">{{ $cell->location ?? 'Not set' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Address</p>
                    <p class="mt-2 text-sm text-zinc-700">{{ $cell->address ?? 'Not set' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Phone</p>
                    <p class="mt-2 text-sm text-zinc-700">{{ $cell->phone ?? 'Not set' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Coordinates</p>
                    <p class="mt-2 text-sm text-zinc-700">
                        @if($cell->latitude && $cell->longitude)
                            {{ $cell->latitude }}, {{ $cell->longitude }}
                        @else
                            Not set
                        @endif
                    </p>
                </div>
            </div>

            <div class="mt-6 flex gap-3">
                <button type="button" wire:click="goBack" class="rounded-lg border px-4 py-2 text-sm">Back to Cells</button>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-xl bg-white p-6 shadow">
                <h3 class="text-lg font-semibold text-zinc-900">Leaders</h3>
                <div class="mt-4 space-y-3">
                    @forelse($cell->leaders as $leader)
                        <div class="rounded-lg border px-3 py-2">
                            <p class="text-sm font-semibold text-zinc-800">{{ $leader->name }}</p>
                            <p class="text-xs text-zinc-500">{{ $leader->phone }}</p>
                            @if($leader->email)
                                <p class="text-xs text-zinc-500">{{ $leader->email }}</p>
                            @endif
                            @if($leader->is_primary)
                                <span class="mt-2 inline-flex rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700">Primary</span>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500">No leaders assigned.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl bg-white p-6 shadow">
                <h3 class="text-lg font-semibold text-zinc-900">Members</h3>
                <div class="mt-4 space-y-2">
                    @forelse($cell->members as $member)
                        <div class="flex items-center justify-between rounded-lg border px-3 py-2 text-sm">
                            <div>
                                <p class="font-medium text-zinc-800">{{ $member->name }}</p>
                                <p class="text-xs text-zinc-500">{{ $member->phone ?? 'No phone' }}</p>
                            </div>
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $member->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-zinc-200 text-zinc-600' }}">
                                {{ ucfirst($member->status) }}
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500">No members yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
