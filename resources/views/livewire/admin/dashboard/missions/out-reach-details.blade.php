<?php

use App\Models\{MissionOutreachDetail, MissionReport, Chapter};
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

    public ?int $mission_report_id = null;
    public string $location = '';
    public ?string $team_members = null;
    public ?string $materials_used = null;
    public ?string $results = null;

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
            $this->toast()->error('No Branch', 'Select a branch before saving details.')->send();
            return;
        }

        $validated = $this->validate([
            'mission_report_id' => 'nullable|integer|exists:mission_reports,id',
            'location' => 'required|string|max:255',
            'team_members' => 'nullable|string',
            'materials_used' => 'nullable|string',
            'results' => 'nullable|string',
        ]);

        MissionOutreachDetail::create([
            'chapter_id' => $this->chapterId,
            'mission_report_id' => $validated['mission_report_id'] ?? null,
            'location' => $validated['location'],
            'team_members' => $validated['team_members'] ?? null,
            'materials_used' => $validated['materials_used'] ?? null,
            'results' => $validated['results'] ?? null,
        ]);

        $this->toast()->success('Saved', 'Outreach details saved.')->send();
        $this->reset(['mission_report_id', 'location', 'team_members', 'materials_used', 'results']);
    }

    public function getReportsProperty()
    {
        return MissionReport::query()
            ->when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->latest('report_date')
            ->limit(20)
            ->get();
    }

    public function getDetailsProperty()
    {
        return MissionOutreachDetail::query()
            ->when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->latest()
            ->limit(20)
            ->get();
    }
};

?>

<div class="space-y-6">
    <x-fancy-header
        title="Outreach Details"
        subtitle="Detailed breakdown for mission outreaches"
        :breadcrumbs="[
            ['label' => 'Home', 'url' => route('admin.dashboard', request()->query())],
            ['label' => 'Missions', 'url' => route('admin.dashboard.missions.index', request()->query())],
            ['label' => 'Outreach Details']
        ]"
    />

    <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
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

                <div>
                    <label class="mb-1 block text-sm font-medium">Mission Report (Optional)</label>
                    <select wire:model="mission_report_id" class="w-full rounded-lg border px-3 py-2">
                        <option value="">Select report</option>
                        @foreach($this->reports as $report)
                            <option value="{{ $report->id }}">{{ $report->report_date->format('M d, Y') }} • {{ $report->location }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Location</label>
                    <input wire:model.lazy="location" type="text" class="w-full rounded-lg border px-3 py-2" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Team Members</label>
                    <textarea wire:model.lazy="team_members" rows="3" class="w-full rounded-lg border px-3 py-2"></textarea>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Materials Used</label>
                    <textarea wire:model.lazy="materials_used" rows="3" class="w-full rounded-lg border px-3 py-2"></textarea>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Results</label>
                    <textarea wire:model.lazy="results" rows="3" class="w-full rounded-lg border px-3 py-2"></textarea>
                </div>

                <div class="flex gap-3">
                    <a href="{{ route('admin.dashboard.missions.index', request()->query()) }}" wire:navigate class="inline-flex items-center rounded-lg border px-4 py-2 text-sm">Back</a>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Save Details</button>
                </div>
            </form>
        </div>

        <div class="rounded-xl bg-white p-6 shadow">
            <h3 class="text-lg font-semibold text-zinc-900">Recent Outreach Details</h3>
            <div class="mt-4 space-y-3">
                @forelse($this->details as $detail)
                    <div class="rounded-lg border px-3 py-2">
                        <p class="text-sm font-semibold text-zinc-800">{{ $detail->location }}</p>
                        @if($detail->results)
                            <p class="mt-2 text-sm text-zinc-600 line-clamp-2">{{ $detail->results }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">No details yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
