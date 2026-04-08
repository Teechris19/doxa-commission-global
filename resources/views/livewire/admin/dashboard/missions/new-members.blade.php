<?php

use App\Models\{MissionNewMember, MissionReport, Chapter};
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
    public string $name = '';
    public ?string $phone = null;
    public ?string $email = null;
    public string $follow_up_status = 'pending';
    public ?string $assigned_leader = null;

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
            $this->toast()->error('No Branch', 'Select a branch before adding a new member.')->send();
            return;
        }

        $validated = $this->validate([
            'mission_report_id' => 'nullable|integer|exists:mission_reports,id',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'follow_up_status' => 'required|string|max:30',
            'assigned_leader' => 'nullable|string|max:255',
        ]);

        MissionNewMember::create([
            'chapter_id' => $this->chapterId,
            'mission_report_id' => $validated['mission_report_id'] ?? null,
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'follow_up_status' => $validated['follow_up_status'],
            'assigned_leader' => $validated['assigned_leader'] ?? null,
        ]);

        $this->toast()->success('Saved', 'New member recorded.')->send();
        $this->reset(['mission_report_id', 'name', 'phone', 'email', 'follow_up_status', 'assigned_leader']);
        $this->follow_up_status = 'pending';
    }

    public function getReportsProperty()
    {
        return MissionReport::query()
            ->when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->latest('report_date')
            ->limit(20)
            ->get();
    }

    public function getMembersProperty()
    {
        return MissionNewMember::query()
            ->when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->latest()
            ->limit(30)
            ->get();
    }
};

?>

<div class="space-y-6">
    <x-fancy-header
        title="New Members"
        subtitle="Track new converts and follow-up status"
        :breadcrumbs="[
            ['label' => 'Home', 'url' => route('admin.dashboard', request()->query())],
            ['label' => 'Missions', 'url' => route('admin.dashboard.missions.index', request()->query())],
            ['label' => 'New Members']
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

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Name</label>
                        <input wire:model.lazy="name" type="text" class="w-full rounded-lg border px-3 py-2" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Phone</label>
                        <input wire:model.lazy="phone" type="text" class="w-full rounded-lg border px-3 py-2" />
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Email</label>
                        <input wire:model.lazy="email" type="email" class="w-full rounded-lg border px-3 py-2" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Assigned Leader</label>
                        <input wire:model.lazy="assigned_leader" type="text" class="w-full rounded-lg border px-3 py-2" />
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Follow-up Status</label>
                    <select wire:model="follow_up_status" class="w-full rounded-lg border px-3 py-2">
                        <option value="pending">Pending</option>
                        <option value="contacted">Contacted</option>
                        <option value="active">Active</option>
                    </select>
                </div>

                <div class="flex gap-3">
                    <a href="{{ route('admin.dashboard.missions.index', request()->query()) }}" wire:navigate class="inline-flex items-center rounded-lg border px-4 py-2 text-sm">Back</a>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Save Member</button>
                </div>
            </form>
        </div>

        <div class="rounded-xl bg-white p-6 shadow">
            <h3 class="text-lg font-semibold text-zinc-900">Recent New Members</h3>
            <div class="mt-4 space-y-3">
                @forelse($this->members as $member)
                    <div class="rounded-lg border px-3 py-2">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-zinc-800">{{ $member->name }}</p>
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $member->follow_up_status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ ucfirst($member->follow_up_status) }}
                            </span>
                        </div>
                        <p class="text-xs text-zinc-500">{{ $member->phone ?? 'No phone' }}</p>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">No members yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
