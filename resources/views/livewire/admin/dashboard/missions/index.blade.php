<?php

use App\Models\{MissionReport, MissionNewMember, Chapter};
use Livewire\Attributes\{Layout, Url};
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.admin')] class extends Component {
    #[Url(keep: true)]
    public ?string $chapter = null;

    public ?int $chapterId = null;
    public array $chapters = [];

    public int $totalOutreaches = 0;
    public int $totalNewConverts = 0;
    public int $activeLocations = 0;
    public int $reportsSummary = 0;

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
        $reportQuery = MissionReport::query()->when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId));

        $this->totalOutreaches = (int) $reportQuery->count();
        $this->totalNewConverts = (int) MissionNewMember::query()
            ->when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->count();
        $this->activeLocations = (int) $reportQuery->distinct('location')->count('location');
        $this->reportsSummary = (int) $reportQuery->whereDate('report_date', '>=', now()->subDays(30))->count();
    }
};

?>

<div class="space-y-6">
    <x-fancy-header
        title="Missions Dashboard"
        subtitle="Evangelism and outreach overview"
        :breadcrumbs="[
            ['label' => 'Home', 'url' => route('admin.dashboard', request()->query())],
            ['label' => 'Missions']
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
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Total Outreaches</p>
            <p class="mt-3 text-3xl font-semibold text-zinc-900">{{ $totalOutreaches }}</p>
        </div>
        <div class="rounded-xl bg-white p-5 shadow">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">New Converts</p>
            <p class="mt-3 text-3xl font-semibold text-zinc-900">{{ $totalNewConverts }}</p>
        </div>
        <div class="rounded-xl bg-white p-5 shadow">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-purple-600">Active Locations</p>
            <p class="mt-3 text-3xl font-semibold text-zinc-900">{{ $activeLocations }}</p>
        </div>
        <div class="rounded-xl bg-white p-5 shadow">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-600">Reports (30 days)</p>
            <p class="mt-3 text-3xl font-semibold text-zinc-900">{{ $reportsSummary }}</p>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <a href="{{ route('admin.dashboard.missions.report', request()->query()) }}" wire:navigate class="rounded-xl border border-blue-100 bg-white p-5 shadow hover:border-blue-200">
            <h3 class="text-lg font-semibold text-zinc-900">Submit Mission Report</h3>
            <p class="mt-2 text-sm text-zinc-600">Document outreach results, testimonies, and expenses.</p>
        </a>
        <a href="{{ route('admin.dashboard.missions.new-members', request()->query()) }}" wire:navigate class="rounded-xl border border-blue-100 bg-white p-5 shadow hover:border-blue-200">
            <h3 class="text-lg font-semibold text-zinc-900">New Members</h3>
            <p class="mt-2 text-sm text-zinc-600">Track new converts and follow-up status.</p>
        </a>
        <a href="{{ route('admin.dashboard.missions.out-reach-details', request()->query()) }}" wire:navigate class="rounded-xl border border-blue-100 bg-white p-5 shadow hover:border-blue-200">
            <h3 class="text-lg font-semibold text-zinc-900">Outreach Details</h3>
            <p class="mt-2 text-sm text-zinc-600">Log team members, materials, and results.</p>
        </a>
        <a href="{{ route('admin.dashboard.missions.outreach-report', request()->query()) }}" wire:navigate class="rounded-xl border border-blue-100 bg-white p-5 shadow hover:border-blue-200">
            <h3 class="text-lg font-semibold text-zinc-900">Outreach Report</h3>
            <p class="mt-2 text-sm text-zinc-600">Aggregate statistics and export summaries.</p>
        </a>
    </div>
</div>
