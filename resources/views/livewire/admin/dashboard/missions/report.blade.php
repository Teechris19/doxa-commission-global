<?php

use App\Models\{MissionReport, Chapter};
use Livewire\Attributes\{Layout, Url};
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions, WithFileUploads;

    #[Url(keep: true)]
    public ?string $chapter = null;

    public ?int $chapterId = null;
    public array $chapters = [];

    public string $report_date = '';
    public string $location = '';
    public int $number_reached = 0;
    public ?string $testimonies = null;
    public ?string $expenses = null;
    public $images = [];

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

        $this->report_date = now()->format('Y-m-d');
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
            $this->toast()->error('No Branch', 'Select a branch before submitting a report.')->send();
            return;
        }

        $validated = $this->validate([
            'report_date' => 'required|date',
            'location' => 'required|string|max:255',
            'number_reached' => 'required|integer|min:0',
            'testimonies' => 'nullable|string',
            'expenses' => 'nullable|numeric|min:0',
            'images.*' => 'nullable|image|max:2048',
        ]);

        $imagePaths = [];
        if ($this->images) {
            foreach ($this->images as $image) {
                $imagePaths[] = $image->store('missions', 'public');
            }
        }

        MissionReport::create([
            'chapter_id' => $this->chapterId,
            'created_by' => Auth::id(),
            'report_date' => $validated['report_date'],
            'location' => $validated['location'],
            'number_reached' => (int) $validated['number_reached'],
            'testimonies' => $validated['testimonies'] ?? null,
            'images' => $imagePaths,
            'expenses' => $validated['expenses'] ?? null,
            'status' => 'submitted',
        ]);

        $this->toast()->success('Saved', 'Mission report submitted.')->send();
        $this->reset(['location', 'number_reached', 'testimonies', 'expenses', 'images']);
        $this->number_reached = 0;
    }

    public function getRecentReportsProperty()
    {
        return MissionReport::query()
            ->when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->latest('report_date')
            ->limit(10)
            ->get();
    }
};

?>

<div class="space-y-6">
    <x-fancy-header
        title="Mission Report"
        subtitle="Submit outreach reports"
        :breadcrumbs="[
            ['label' => 'Home', 'url' => route('admin.dashboard', request()->query())],
            ['label' => 'Missions', 'url' => route('admin.dashboard.missions.index', request()->query())],
            ['label' => 'Report']
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

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Date</label>
                        <input wire:model="report_date" type="date" class="w-full rounded-lg border px-3 py-2" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Location</label>
                        <input wire:model.lazy="location" type="text" class="w-full rounded-lg border px-3 py-2" />
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Number Reached</label>
                    <input wire:model.lazy="number_reached" type="number" min="0" class="w-full rounded-lg border px-3 py-2" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Testimonies</label>
                    <textarea wire:model.lazy="testimonies" rows="4" class="w-full rounded-lg border px-3 py-2"></textarea>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Expenses</label>
                    <input wire:model.lazy="expenses" type="number" step="0.01" min="0" class="w-full rounded-lg border px-3 py-2" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Images (optional)</label>
                    <input wire:model="images" type="file" multiple accept="image/*" class="w-full rounded-lg border px-3 py-2" />
                    <p class="mt-1 text-xs text-zinc-500">Upload multiple images from the outreach (max 2MB each)</p>
                </div>

                <div class="flex gap-3">
                    <a href="{{ route('admin.dashboard.missions.index', request()->query()) }}" wire:navigate class="inline-flex items-center rounded-lg border px-4 py-2 text-sm">Back</a>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Submit Report</button>
                </div>
            </form>
        </div>

        <div class="rounded-xl bg-white p-6 shadow">
            <h3 class="text-lg font-semibold text-zinc-900">Recent Reports</h3>
            <div class="mt-4 space-y-3">
                @forelse($this->recentReports as $report)
                    <div class="rounded-lg border px-3 py-2">
                        <p class="text-sm font-semibold text-zinc-800">{{ $report->location }}</p>
                        <p class="text-xs text-zinc-500">{{ $report->report_date->format('M d, Y') }} • {{ $report->number_reached }} reached</p>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">No reports yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
