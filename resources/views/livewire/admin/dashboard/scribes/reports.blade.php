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

    public ?string $type = null;
    public ?string $search = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

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

    public function getScribeReportsProperty()
    {
        return ScribeReport::query()
            ->when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->when($this->type, fn($q) => $q->where('type', $this->type))
            ->when($this->dateFrom, fn($q) => $q->whereDate('service_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('service_date', '<=', $this->dateTo))
            ->when($this->search, function ($q) {
                $term = '%' . $this->search . '%';
                $q->where('title', 'like', $term)->orWhere('content', 'like', $term);
            })
            ->latest()
            ->limit(50)
            ->get();
    }

    public function getTeamReportsProperty()
    {
        return Report::query()
            ->when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->when($this->dateFrom, fn($q) => $q->whereDate('report_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('report_date', '<=', $this->dateTo))
            ->when($this->search, function ($q) {
                $term = '%' . $this->search . '%';
                $q->where('title', 'like', $term)->orWhere('description', 'like', $term);
            })
            ->latest()
            ->limit(50)
            ->get();
    }
};

?>

<div class="space-y-6">
    <x-fancy-header
        title="Scribes Reports"
        subtitle="Manage scribe reports and all team reports for your branch"
        :breadcrumbs="[
            ['label' => 'Home', 'url' => route('admin.dashboard', request()->query())],
            ['label' => 'Scribes', 'url' => route('admin.dashboard.scribes.index', request()->query())],
            ['label' => 'Reports']
        ]"
    />

    <div class="rounded-xl bg-white p-4 shadow space-y-4">
        <div class="grid gap-3 md:grid-cols-4">
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
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Type</label>
                <select wire:model="type" class="w-full rounded-lg border px-3 py-2">
                    <option value="">All types</option>
                    <option value="weekly_service">Weekly Service</option>
                    <option value="sunday_summary">Sunday Summary</option>
                    <option value="attendance_summary">Attendance Summary</option>
                    <option value="program_documentation">Program Documentation</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Date From</label>
                <input type="date" wire:model="dateFrom" class="w-full rounded-lg border px-3 py-2" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Date To</label>
                <input type="date" wire:model="dateTo" class="w-full rounded-lg border px-3 py-2" />
            </div>
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Search</label>
            <input type="text" wire:model.debounce.300ms="search" class="w-full rounded-lg border px-3 py-2" placeholder="Search titles, descriptions, content" />
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl bg-white p-5 shadow">
            <h3 class="text-lg font-semibold text-zinc-900">Scribe Reports</h3>
            <div class="mt-4 space-y-3">
                @forelse($this->scribeReports as $report)
                    <div class="rounded-lg border px-3 py-2">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-zinc-800">{{ $report->title }}</p>
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $report->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ ucfirst($report->status) }}
                            </span>
                        </div>
                        <p class="text-xs text-zinc-500">{{ ucfirst(str_replace('_', ' ', $report->type)) }} • {{ $report->service_date?->format('M d, Y') ?? 'No date' }}</p>
                        @if($report->content)
                            <p class="mt-2 text-sm text-zinc-600 line-clamp-2">{{ $report->content }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">No scribe reports found.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl bg-white p-5 shadow">
            <h3 class="text-lg font-semibold text-zinc-900">Team Reports (All Departments)</h3>
            <div class="mt-4 space-y-3">
                @forelse($this->teamReports as $report)
                    <div class="rounded-lg border px-3 py-2">
                        <p class="text-sm font-semibold text-zinc-800">{{ $report->title }}</p>
                        <p class="text-xs text-zinc-500">{{ $report->report_date?->format('M d, Y') ?? 'No date' }} • {{ ucfirst($report->level ?? 'team') }}</p>
                        @if($report->description)
                            <p class="mt-2 text-sm text-zinc-600 line-clamp-2">{{ $report->description }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">No team reports found.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
