<?php

use App\Models\{MissionReport, Chapter};
use Livewire\Attributes\{Layout, Url};
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.admin')] class extends Component {
    #[Url(keep: true)]
    public ?string $chapter = null;

    public ?int $chapterId = null;
    public array $chapters = [];

    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    public int $totalOutreaches = 0;
    public int $totalReached = 0;
    public string $totalExpenses = '0.00';

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

        $this->refreshStats();
    }

    public function updated($property): void
    {
        if (in_array($property, ['chapterId', 'dateFrom', 'dateTo'], true)) {
            if ($property === 'chapterId' && $this->chapterId) {
                $this->chapter = Chapter::find($this->chapterId)?->name;
            }
            $this->refreshStats();
        }
    }

    private function refreshStats(): void
    {
        $query = MissionReport::query()
            ->when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->when($this->dateFrom, fn($q) => $q->whereDate('report_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('report_date', '<=', $this->dateTo));

        $this->totalOutreaches = (int) $query->count();
        $this->totalReached = (int) $query->sum('number_reached');
        $this->totalExpenses = number_format((float) $query->sum('expenses'), 2, '.', '');
    }

    public function getReportsProperty()
    {
        return MissionReport::query()
            ->when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->when($this->dateFrom, fn($q) => $q->whereDate('report_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('report_date', '<=', $this->dateTo))
            ->latest('report_date')
            ->limit(50)
            ->get();
    }
};

?>

<div class="space-y-6">
    <x-fancy-header
        title="Outreach Report"
        subtitle="Aggregate outreach statistics and summaries"
        :breadcrumbs="[
            ['label' => 'Home', 'url' => route('admin.dashboard', request()->query())],
            ['label' => 'Missions', 'url' => route('admin.dashboard.missions.index', request()->query())],
            ['label' => 'Outreach Report']
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
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Date From</label>
                <input type="date" wire:model="dateFrom" class="w-full rounded-lg border px-3 py-2" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Date To</label>
                <input type="date" wire:model="dateTo" class="w-full rounded-lg border px-3 py-2" />
            </div>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl bg-white p-5 shadow">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Total Outreaches</p>
            <p class="mt-3 text-3xl font-semibold text-zinc-900">{{ $totalOutreaches }}</p>
        </div>
        <div class="rounded-xl bg-white p-5 shadow">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">Total Reached</p>
            <p class="mt-3 text-3xl font-semibold text-zinc-900">{{ $totalReached }}</p>
        </div>
        <div class="rounded-xl bg-white p-5 shadow">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-600">Total Expenses</p>
            <p class="mt-3 text-3xl font-semibold text-zinc-900">{{ $totalExpenses }}</p>
        </div>
    </div>

    <div class="rounded-xl bg-white p-5 shadow">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-zinc-500">
                    <tr>
                        <th class="py-2 pr-4">Date</th>
                        <th class="py-2 pr-4">Location</th>
                        <th class="py-2 pr-4">Reached</th>
                        <th class="py-2">Expenses</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->reports as $report)
                        <tr class="border-t">
                            <td class="py-2 pr-4">{{ $report->report_date->format('M d, Y') }}</td>
                            <td class="py-2 pr-4">{{ $report->location }}</td>
                            <td class="py-2 pr-4">{{ $report->number_reached }}</td>
                            <td class="py-2">{{ number_format((float) $report->expenses, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-zinc-500">No reports found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
