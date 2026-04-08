<?php

use App\Models\{ScribeReport, Chapter};
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

    public string $type = 'weekly_service';
    public string $title = '';
    public ?string $service_date = null;
    public ?string $content = null;

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
            $this->toast()->error('No Branch', 'Select a branch before creating a report.')->send();
            return;
        }

        $validated = $this->validate([
            'type' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'service_date' => 'nullable|date',
            'content' => 'nullable|string',
        ]);

        ScribeReport::create([
            'chapter_id' => $this->chapterId,
            'created_by' => Auth::id(),
            'type' => $validated['type'],
            'title' => $validated['title'],
            'service_date' => $validated['service_date'] ?? null,
            'content' => $validated['content'] ?? null,
            'status' => 'pending',
        ]);

        $this->toast()->success('Saved', 'General report submitted.')->send();
        $this->reset(['type', 'title', 'service_date', 'content']);
        $this->type = 'weekly_service';
    }
};

?>

<div class="space-y-6">
    <x-fancy-header
        title="General Report"
        subtitle="Weekly service, Sunday summary, attendance, and program documentation"
        :breadcrumbs="[
            ['label' => 'Home', 'url' => route('admin.dashboard', request()->query())],
            ['label' => 'Scribes', 'url' => route('admin.dashboard.scribes.index', request()->query())],
            ['label' => 'General Report']
        ]"
    />

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
                    <label class="mb-1 block text-sm font-medium">Report Type</label>
                    <select wire:model="type" class="w-full rounded-lg border px-3 py-2">
                        <option value="weekly_service">Weekly Service Report</option>
                        <option value="sunday_summary">Sunday Summary</option>
                        <option value="attendance_summary">Attendance Summary</option>
                        <option value="program_documentation">Program Documentation</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Service Date</label>
                    <input wire:model="service_date" type="date" class="w-full rounded-lg border px-3 py-2" />
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Title</label>
                <input wire:model.lazy="title" type="text" class="w-full rounded-lg border px-3 py-2" />
                @error('title') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Report Content</label>
                <textarea wire:model.lazy="content" rows="6" class="w-full rounded-lg border px-3 py-2"></textarea>
            </div>

            <div class="flex gap-3">
                <a href="{{ route('admin.dashboard.scribes.index', request()->query()) }}" wire:navigate class="inline-flex items-center rounded-lg border px-4 py-2 text-sm">Back</a>
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Submit Report</button>
            </div>
        </form>
    </div>
</div>
