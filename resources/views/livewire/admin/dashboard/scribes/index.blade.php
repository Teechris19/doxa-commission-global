<?php

use App\Models\{Report, ScribeReport, Chapter};
use Livewire\Attributes\{Layout, Url};
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.admin')] class extends Component {
    #[Url(keep: true)]
    public ?string $chapter = null;

    public ?int $chapterId = null;
    public array $chapters = [];

    public int $totalReports = 0;
    public int $eventsRecorded = 0;
    public int $pendingSubmissions = 0;

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
        $query = Report::query()->when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId));

        $this->totalReports = (int) $query->count();
        $this->eventsRecorded = (int) $query->whereNotNull('event_type')->count();
        $this->pendingSubmissions = (int) Report::query()
            ->when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->where('status', 'pending')
            ->count();
    }
};

?>

<div class="space-y-6">
    <x-fancy-header
        title="Scribes Dashboard"
        subtitle="Documentation and reporting overview"
        :breadcrumbs="[
            ['label' => 'Home', 'url' => route('admin.dashboard', request()->query())],
            ['label' => 'Scribes']
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

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl bg-white p-5 shadow">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Total Reports</p>
            <p class="mt-3 text-3xl font-semibold text-zinc-900">{{ $totalReports }}</p>
        </div>
        <div class="rounded-xl bg-white p-5 shadow">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">Events Recorded</p>
            <p class="mt-3 text-3xl font-semibold text-zinc-900">{{ $eventsRecorded }}</p>
        </div>
        <div class="rounded-xl bg-white p-5 shadow">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-600">Pending Submissions</p>
            <p class="mt-3 text-3xl font-semibold text-zinc-900">{{ $pendingSubmissions }}</p>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <a href="{{ route('admin.dashboard.scribes.general-report', request()->query()) }}" wire:navigate class="rounded-xl border border-blue-100 bg-white p-5 shadow hover:border-blue-200">
            <h3 class="text-lg font-semibold text-zinc-900">Create General Report</h3>
            <p class="mt-2 text-sm text-zinc-600">Weekly service reports, Sunday summaries, attendance summaries, and program documentation.</p>
        </a>
        <a href="{{ route('admin.dashboard.scribes.reports', request()->query()) }}" wire:navigate class="rounded-xl border border-blue-100 bg-white p-5 shadow hover:border-blue-200">
            <h3 class="text-lg font-semibold text-zinc-900">Reports Archive</h3>
            <p class="mt-2 text-sm text-zinc-600">Browse all reports from teams in your chapter and export documentation.</p>
        </a>
        <a href="{{ route('admin.dashboard.scribes.doxa-update', request()->query()) }}" wire:navigate class="rounded-xl border border-blue-100 bg-white p-5 shadow hover:border-blue-200">
            <h3 class="text-lg font-semibold text-zinc-900">Doxa Update</h3>
            <p class="mt-2 text-sm text-zinc-600">Manage website content updates, announcements, event summaries, and newsletters.</p>
        </a>
    </div>
</div>
