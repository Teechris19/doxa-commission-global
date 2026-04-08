<?php

use App\Models\{ScribeUpdate, Chapter};
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

    public string $category = 'announcement';
    public string $title = '';
    public ?string $body = null;
    public string $status = 'draft';

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
            $this->toast()->error('No Branch', 'Select a branch before creating an update.')->send();
            return;
        }

        $validated = $this->validate([
            'category' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
            'status' => 'required|string|max:20',
        ]);

        ScribeUpdate::create([
            'chapter_id' => $this->chapterId,
            'created_by' => Auth::id(),
            'category' => $validated['category'],
            'title' => $validated['title'],
            'body' => $validated['body'] ?? null,
            'status' => $validated['status'],
        ]);

        $this->toast()->success('Saved', 'Update saved successfully.')->send();
        $this->reset(['category', 'title', 'body', 'status']);
        $this->category = 'announcement';
        $this->status = 'draft';
    }

    public function getUpdatesProperty()
    {
        return ScribeUpdate::query()
            ->when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->latest()
            ->limit(20)
            ->get();
    }
};

?>

<div class="space-y-6">
    <x-fancy-header
        title="Doxa Update"
        subtitle="Manage announcements, website content, newsletters, and event summaries"
        :breadcrumbs="[
            ['label' => 'Home', 'url' => route('admin.dashboard', request()->query())],
            ['label' => 'Scribes', 'url' => route('admin.dashboard.scribes.index', request()->query())],
            ['label' => 'Doxa Update']
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
                        <label class="mb-1 block text-sm font-medium">Category</label>
                        <select wire:model="category" class="w-full rounded-lg border px-3 py-2">
                            <option value="announcement">Announcement</option>
                            <option value="website_content">Website Content</option>
                            <option value="event_summary">Event Summary</option>
                            <option value="newsletter">Newsletter</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Status</label>
                        <select wire:model="status" class="w-full rounded-lg border px-3 py-2">
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Title</label>
                    <input wire:model.lazy="title" type="text" class="w-full rounded-lg border px-3 py-2" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Content</label>
                    <textarea wire:model.lazy="body" rows="6" class="w-full rounded-lg border px-3 py-2"></textarea>
                </div>

                <div class="flex gap-3">
                    <a href="{{ route('admin.dashboard.scribes.index', request()->query()) }}" wire:navigate class="inline-flex items-center rounded-lg border px-4 py-2 text-sm">Back</a>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Save Update</button>
                </div>
            </form>
        </div>

        <div class="rounded-xl bg-white p-6 shadow">
            <h3 class="text-lg font-semibold text-zinc-900">Recent Updates</h3>
            <div class="mt-4 space-y-3">
                @forelse($this->updates as $update)
                    <div class="rounded-lg border px-3 py-2">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-zinc-800">{{ $update->title }}</p>
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $update->status === 'published' ? 'bg-emerald-100 text-emerald-700' : 'bg-zinc-200 text-zinc-600' }}">
                                {{ ucfirst($update->status) }}
                            </span>
                        </div>
                        <p class="text-xs text-zinc-500">{{ ucfirst(str_replace('_', ' ', $update->category)) }}</p>
                        @if($update->body)
                            <p class="mt-2 text-sm text-zinc-600 line-clamp-2">{{ $update->body }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">No updates yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
