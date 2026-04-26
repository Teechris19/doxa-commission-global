<?php

use App\Models\Accounts;
use App\Models\Chapter;
use App\Models\Events;
use App\Models\PartnershipCategory;
use App\Models\PartnershipIntent;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions;
    use WithPagination;

    #[Url(keep: true)]
    public ?string $chapter = null;

    public ?int $chapterId = null;
    public ?string $chapterName = null;

    public int $intentQuantity = 10;

    #[Url(keep: true)]
    public ?string $intentSearch = null;

    #[Url(keep: true)]
    public ?string $intentStatusFilter = null;

    #[Url(keep: true)]
    public ?string $intentTypeFilter = null;

    public ?int $editingCategoryId = null;
    public string $categoryName = '';
    public ?string $categoryDescription = null;
    public ?int $categoryAccountId = null;
    public bool $categoryIsActive = true;

    public ?int $editingIntentId = null;
    public ?int $intentUserId = null;
    public ?int $intentCategoryId = null;
    public ?int $intentAccountId = null;
    public ?int $intentEventId = null;
    public string $intentType = 'general';
    public string $intentTitle = '';
    public ?float $intentPledgeAmount = null;
    public string $intentPledgeCurrency = 'NGN';
    public string $intentPledgeFrequency = 'one_time';
    public string $intentStatus = 'pending';
    public ?string $intentPledgedAt = null;
    public ?string $intentNotes = null;
    public ?string $intentAdminNotes = null;
    public $eventAccounts;

    public function mount(): void
    {
        $this->resolveChapterContext();
        $this->eventAccounts = collect();

        Gate::authorize('viewAny', PartnershipIntent::class);
        Gate::authorize('viewAny', PartnershipCategory::class);
    }

    public function with(): array
    {
        $intentsQuery = PartnershipIntent::query()
            ->with([
                'user:id,name',
                'category:id,name',
                'account:id,account_name,bank_name',
                'event:id,title,start_at',
            ])
            ->where('chapter_id', $this->chapterId)
            ->when($this->intentSearch, function ($query): void {
                $term = '%' . $this->intentSearch . '%';
                $query->where(function ($inner) use ($term): void {
                    $inner->where('title', 'like', $term)
                        ->orWhere('notes', 'like', $term)
                        ->orWhere('admin_notes', 'like', $term)
                        ->orWhereHas('user', fn($userQuery) => $userQuery->where('name', 'like', $term))
                        ->orWhereHas('category', fn($categoryQuery) => $categoryQuery->where('name', 'like', $term));
                });
            })
            ->when($this->intentStatusFilter, fn($query) => $query->where('status', $this->intentStatusFilter))
            ->when($this->intentTypeFilter, fn($query) => $query->where('intent_type', $this->intentTypeFilter));

        $intentStatsQuery = PartnershipIntent::query()->where('chapter_id', $this->chapterId);

        return [
            'categories' => PartnershipCategory::query()
                ->where('chapter_id', $this->chapterId)
                ->with('account:id,account_name,account_number,bank_name,chapter_id')
                ->withCount('intents')
                ->orderBy('name')
                ->get(),
            'categoryOptions' => PartnershipCategory::query()
                ->where('chapter_id', $this->chapterId)
                ->whereNotNull('account_id')
                ->with('account:id,account_name,account_number,bank_name,chapter_id')
                ->orderBy('name')
                ->get(['id', 'name', 'account_id', 'is_active']),
            'intents' => $intentsQuery
                ->latest()
                ->paginate($this->intentQuantity, ['*'], 'intentsPage')
                ->withQueryString(),
            'chapterAccounts' => Accounts::query()
                ->where(function ($query): void {
                    $query->where('chapter_id', $this->chapterId)
                        ->orWhereNull('chapter_id');
                })
                ->orderBy('account_name')
                ->get(['id', 'account_name', 'account_number', 'bank_name', 'chapter_id']),
            'chapterEvents' => Events::query()
                ->where('chapter_id', $this->chapterId)
                ->orderByDesc('start_at')
                ->get(['id', 'title', 'start_at']),
            'chapterUsers' => User::query()
                ->where('chapter_id', $this->chapterId)
                ->orderBy('name')
                ->get(['id', 'name']),
            'intentStats' => [
                'pending' => (clone $intentStatsQuery)->where('status', 'pending')->count(),
                'reviewing' => (clone $intentStatsQuery)->where('status', 'reviewing')->count(),
                'approved' => (clone $intentStatsQuery)->where('status', 'approved')->count(),
                'withdrawn' => (clone $intentStatsQuery)->where('status', 'withdrawn')->count(),
            ],
        ];
    }

    public function updatingIntentSearch(): void
    {
        $this->resetPage('intentsPage');
    }

    public function updatingIntentStatusFilter(): void
    {
        $this->resetPage('intentsPage');
    }

    public function updatingIntentTypeFilter(): void
    {
        $this->resetPage('intentsPage');
    }

    public function updatedIntentType(string $value): void
    {
        if ($value !== 'event') {
            $this->intentEventId = null;
            $this->eventAccounts = collect();
        }

        if ($value !== 'project') {
            $this->intentCategoryId = null;
        }

        $this->intentAccountId = null;
    }

    public function updatedIntentEventId($value): void
    {
        if ($this->intentType !== 'event') {
            $this->intentEventId = null;
            $this->eventAccounts = collect();
            return;
        }

        $this->intentEventId = $value ? (int) $value : null;
        $this->intentAccountId = null;
        $this->loadEventAccounts();
    }

    public function updatedIntentQuantity(): void
    {
        $this->resetPage('intentsPage');
    }

    public function createCategory(): void
    {
        Gate::authorize('create', [PartnershipCategory::class, $this->chapterId]);

        $this->editingCategoryId = null;
        $this->resetCategoryForm();
    }

    public function editCategory(int $id): void
    {
        $category = PartnershipCategory::query()
            ->where('chapter_id', $this->chapterId)
            ->findOrFail($id);

        Gate::authorize('update', $category);

        $this->editingCategoryId = $category->id;
        $this->categoryName = $category->name;
        $this->categoryDescription = $category->description;
        $this->categoryAccountId = $category->account_id;
        $this->categoryIsActive = (bool) $category->is_active;
    }

    public function saveCategory(): void
    {
        if ($this->editingCategoryId) {
            $category = PartnershipCategory::query()
                ->where('chapter_id', $this->chapterId)
                ->findOrFail($this->editingCategoryId);
            Gate::authorize('update', $category);
        } else {
            Gate::authorize('create', [PartnershipCategory::class, $this->chapterId]);
            $category = new PartnershipCategory();
        }

        $validated = $this->validate([
            'categoryName' => 'required|string|max:120',
            'categoryDescription' => 'nullable|string|max:1000',
            'categoryAccountId' => [
                'required',
                'integer',
                Rule::exists('accounts', 'id')->where(fn($query) => $query
                    ->where('is_active', true)
                    ->whereNull('deleted_at')
                    ->where(function ($inner): void {
                        $inner->where('chapter_id', $this->chapterId)
                            ->orWhereNull('chapter_id');
                    })),
            ],
            'categoryIsActive' => 'boolean',
        ]);

        $category->fill([
            'chapter_id' => $this->chapterId,
            'account_id' => $validated['categoryAccountId'],
            'name' => $validated['categoryName'],
            'slug' => $this->makeCategorySlug($validated['categoryName'], $this->editingCategoryId),
            'description' => $validated['categoryDescription'] ?? null,
            'is_active' => (bool) ($validated['categoryIsActive'] ?? true),
        ]);
        $category->save();

        $this->dispatch('$closeModal', 'partnership-category-modal');
        $this->toast()->success('Saved', 'Partnership category saved successfully.')->send();
        $this->resetCategoryForm();
    }

    public function deleteCategory($id): void
    {
        // Ensure $id is a scalar value, not an array
        if (is_array($id)) {
            $id = $id['id'] ?? $id[0] ?? null;
        }
        
        if (!$id) {
            $this->toast()->error('Invalid ID', 'No valid ID provided for deletion.')->send();
            return;
        }

        $this->dialog()
            ->error('Delete this partnership category?')
            ->hook([
                'ok' => [
                    'method' => 'confirmDeleteCategory',
                    'params' => [(int) $id],
                ],
            ])
            ->send();
    }

    public function confirmDeleteCategory($id): void
    {
        // Ensure $id is a scalar value, not an array
        if (is_array($id)) {
            $id = $id['id'] ?? $id[0] ?? null;
        }
        
        if (!$id) {
            $this->toast()->error('Invalid ID', 'No valid ID provided for deletion.')->send();
            return;
        }

        $category = PartnershipCategory::query()
            ->where('chapter_id', $this->chapterId)
            ->findOrFail((int) $id);

        Gate::authorize('delete', $category);

        if ($category->intents()->exists()) {
            $category->update(['is_active' => false]);
            $this->toast()->success('Updated', 'Category is linked to intents and has been marked inactive.')->send();
            return;
        }

        $category->delete();
        $this->toast()->success('Deleted', 'Partnership category deleted.')->send();
    }

    public function createIntent(): void
    {
        Gate::authorize('create', [PartnershipIntent::class, $this->chapterId]);

        $this->editingIntentId = null;
        $this->resetIntentForm();
    }

    public function editIntent(int $id): void
    {
        $intent = PartnershipIntent::query()
            ->where('chapter_id', $this->chapterId)
            ->findOrFail($id);

        Gate::authorize('update', $intent);

        $this->editingIntentId = $intent->id;
        $this->intentUserId = $intent->user_id;
        $this->intentCategoryId = $intent->partnership_category_id;
        $this->intentAccountId = $intent->account_id;
        $this->intentEventId = $intent->event_id;
        $this->intentType = $intent->intent_type;
        $this->intentTitle = $intent->title;
        $this->intentPledgeAmount = $intent->pledge_amount !== null ? (float) $intent->pledge_amount : null;
        $this->intentPledgeCurrency = $intent->pledge_currency;
        $this->intentPledgeFrequency = $intent->pledge_frequency;
        $this->intentStatus = $intent->status;
        $this->intentPledgedAt = $intent->pledged_at?->format('Y-m-d\\TH:i');
        $this->intentNotes = $intent->notes;
        $this->intentAdminNotes = $intent->admin_notes;
        $this->loadEventAccounts();
    }

    public function saveIntent(): void
    {
        if ($this->editingIntentId) {
            $intent = PartnershipIntent::query()
                ->where('chapter_id', $this->chapterId)
                ->findOrFail($this->editingIntentId);
            Gate::authorize('update', $intent);
        } else {
            Gate::authorize('create', [PartnershipIntent::class, $this->chapterId]);
            $intent = new PartnershipIntent();
        }

        $validated = $this->validate([
            'intentUserId' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn($query) => $query->where('chapter_id', $this->chapterId)),
            ],
            'intentCategoryId' => $this->intentType === 'project'
                ? [
                    'required',
                    'integer',
                    Rule::exists('partnership_categories', 'id')->where(fn($query) => $query->where('chapter_id', $this->chapterId)),
                ]
                : ['nullable', 'integer'],
            'intentEventId' => $this->intentType === 'event'
                ? [
                    'required',
                    'integer',
                    Rule::exists('events', 'id')->where(fn($query) => $query->where('chapter_id', $this->chapterId)),
                ]
                : ['nullable', 'integer'],
            'intentType' => 'required|in:general,event,project',
            'intentTitle' => 'required|string|max:255',
            'intentPledgeAmount' => 'nullable|numeric|min:0',
            'intentPledgeCurrency' => 'required|string|size:3',
            'intentPledgeFrequency' => 'required|in:one_time,weekly,monthly,quarterly,yearly,custom',
            'intentStatus' => 'required|in:draft,pending,reviewing,approved,declined,withdrawn',
            'intentPledgedAt' => 'nullable|date',
            'intentNotes' => 'nullable|string|max:5000',
            'intentAdminNotes' => 'nullable|string|max:5000',
            'intentAccountId' => $this->intentType === 'general'
                ? [
                    'required',
                    'integer',
                    Rule::exists('accounts', 'id')->where(fn($query) => $query
                        ->where(function ($inner): void {
                            $inner->where('chapter_id', $this->chapterId)
                                ->orWhereNull('chapter_id');
                        })),
                ]
                : ['nullable', 'integer'],
        ]);

        if ($validated['intentType'] === 'project') {
            $selectedCategory = PartnershipCategory::query()
                ->with(['account' => function ($query): void {
                    $query->where('is_active', true)
                        ->whereNull('deleted_at')
                        ->where(function ($inner): void {
                            $inner->where('chapter_id', $this->chapterId)
                                ->orWhereNull('chapter_id');
                        });
                }])
                ->where('chapter_id', $this->chapterId)
                ->find($validated['intentCategoryId']);

            if (!$selectedCategory || !$selectedCategory->account) {
                $this->addError('intentCategoryId', 'Selected category has no active linked account.');
                return;
            }

            $this->intentAccountId = (int) $selectedCategory->account->id;
        }

        if ($validated['intentType'] === 'event') {
            $eventAccounts = Accounts::query()
                ->whereHas('events', fn($query) => $query->where('events.id', $validated['intentEventId']))
                ->where('is_active', true)
                ->orderBy('account_name')
                ->get(['id']);

            if ($eventAccounts->isEmpty()) {
                $this->addError('intentEventId', 'Selected event has no linked contribution account.');
                return;
            }

            if (!$validated['intentAccountId'] && $eventAccounts->count() === 1) {
                $this->intentAccountId = (int) $eventAccounts->first()->id;
            } else {
                $this->intentAccountId = $validated['intentAccountId'] ? (int) $validated['intentAccountId'] : null;
            }

            if (!$this->intentAccountId || !$eventAccounts->pluck('id')->contains($this->intentAccountId)) {
                $this->addError('intentAccountId', 'Select a contribution account linked to the event.');
                return;
            }
        }

        $payload = [
            'chapter_id' => $this->chapterId,
            'user_id' => $validated['intentUserId'] ?? null,
            'partnership_category_id' => $validated['intentCategoryId'] ?? null,
            'account_id' => $this->intentAccountId ?? null,
            'event_id' => $validated['intentEventId'] ?? null,
            'intent_type' => $validated['intentType'],
            'title' => $validated['intentTitle'],
            'pledge_amount' => $validated['intentPledgeAmount'] ?? null,
            'pledge_currency' => strtoupper($validated['intentPledgeCurrency']),
            'pledge_frequency' => $validated['intentPledgeFrequency'],
            'status' => $validated['intentStatus'],
            'pledged_at' => $validated['intentPledgedAt'] ?? null,
            'notes' => $validated['intentNotes'] ?? null,
            'admin_notes' => $validated['intentAdminNotes'] ?? null,
            'withdrawn_at' => $validated['intentStatus'] === 'withdrawn' ? now() : null,
        ];

        $intent->fill($payload);
        $intent->save();

        $this->dispatch('$closeModal', 'partnership-intent-modal');
        $this->toast()->success('Saved', 'Partnership intent saved successfully.')->send();
        $this->resetIntentForm();
    }

    public function setIntentStatus(int $id, string $status): void
    {
        if (!in_array($status, ['draft', 'pending', 'reviewing', 'approved', 'declined', 'withdrawn'], true)) {
            return;
        }

        $intent = PartnershipIntent::query()
            ->where('chapter_id', $this->chapterId)
            ->findOrFail($id);

        Gate::authorize('update', $intent);

        $intent->status = $status;
        $intent->withdrawn_at = $status === 'withdrawn' ? now() : null;
        $intent->save();

        $this->toast()->success('Updated', 'Partnership intent status updated.')->send();
    }

    public function deleteIntent($id): void
    {
        // Ensure $id is a scalar value, not an array
        if (is_array($id)) {
            $id = $id['id'] ?? $id[0] ?? null;
        }
        
        if (!$id) {
            $this->toast()->error('Invalid ID', 'No valid ID provided for deletion.')->send();
            return;
        }

        $this->dialog()
            ->error('Are you sure you want to delete this partnership intent?')
            ->hook([
                'ok' => [
                    'method' => 'confirmDeleteIntent',
                    'params' => [(int) $id],
                ],
            ])
            ->send();
    }

    public function confirmDeleteIntent($id): void
    {
        // Ensure $id is a scalar value, not an array
        if (is_array($id)) {
            $id = $id['id'] ?? $id[0] ?? null;
        }
        
        if (!$id) {
            $this->toast()->error('Invalid ID', 'No valid ID provided for deletion.')->send();
            return;
        }

        $intent = PartnershipIntent::query()
            ->where('chapter_id', $this->chapterId)
            ->findOrFail((int) $id);

        Gate::authorize('delete', $intent);

        $intent->delete();
        $this->toast()->success('Deleted', 'Partnership intent deleted successfully.')->send();
    }

    private function resetCategoryForm(): void
    {
        $this->resetValidation();

        $this->categoryName = '';
        $this->categoryDescription = null;
        $this->categoryAccountId = null;
        $this->categoryIsActive = true;
    }

    private function resetIntentForm(): void
    {
        $this->resetValidation();

        $this->intentUserId = null;
        $this->intentCategoryId = null;
        $this->intentAccountId = null;
        $this->intentEventId = null;
        $this->intentType = 'general';
        $this->intentTitle = '';
        $this->intentPledgeAmount = null;
        $this->intentPledgeCurrency = 'NGN';
        $this->intentPledgeFrequency = 'one_time';
        $this->intentStatus = 'pending';
        $this->intentPledgedAt = null;
        $this->intentNotes = null;
        $this->intentAdminNotes = null;
        $this->eventAccounts = collect();
    }

    private function loadEventAccounts(): void
    {
        if (!$this->intentEventId || $this->intentType !== 'event') {
            $this->eventAccounts = collect();
            return;
        }

        $this->eventAccounts = Accounts::query()
            ->whereHas('events', fn($query) => $query->where('events.id', $this->intentEventId))
            ->where('is_active', true)
            ->orderBy('account_name')
            ->get(['id', 'account_name', 'account_number', 'bank_name', 'chapter_id']);

        if ($this->eventAccounts->count() === 1 && !$this->intentAccountId) {
            $this->intentAccountId = (int) $this->eventAccounts->first()->id;
        }
    }

    private function makeCategorySlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'partnership-category';
        }

        $slug = $base;
        $counter = 2;

        while (
            PartnershipCategory::query()
                ->where('chapter_id', $this->chapterId)
                ->where('slug', $slug)
                ->when($ignoreId, fn($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function resolveChapterContext(): void
    {
        $chapter = null;

        if ($this->chapter) {
            $chapter = Chapter::where('name', $this->chapter)->first();
        }

        if (!$chapter) {
            $user = auth()->user();
            if ($user?->chapter_id) {
                $chapter = Chapter::find($user->chapter_id);
            }
        }

        if (!$chapter) {
            abort(403, 'Select a valid chapter to manage partnership records.');
        }

        $this->chapterId = (int) $chapter->id;
        $this->chapterName = $chapter->name;
        $this->chapter = $chapter->name;
    }

    public function formatIntentStatus(string $status): string
    {
        return str_replace('_', ' ', ucfirst($status));
    }

    public function updatedIntentCategoryId($value): void
    {
        $this->intentCategoryId = $value ? (int) $value : null;

        if (!$this->intentCategoryId) {
            $this->intentAccountId = null;
            return;
        }

        $linkedAccountId = PartnershipCategory::query()
            ->where('chapter_id', $this->chapterId)
            ->where('id', $this->intentCategoryId)
            ->whereHas('account', function ($query): void {
                $query->where('is_active', true)
                    ->whereNull('deleted_at')
                    ->where(function ($inner): void {
                        $inner->where('chapter_id', $this->chapterId)
                            ->orWhereNull('chapter_id');
                    });
            })
            ->value('account_id');

        $this->intentAccountId = $linkedAccountId ? (int) $linkedAccountId : null;
    }
}; ?>

<div>
    <x-fancy-header
        title="Partnership Intent Management"
        subtitle="Capture partnership intent and category status without creating transactions"
        :breadcrumbs="[
            ['label' => 'Home', 'url' => route('admin.dashboard', request()->query())],
            ['label' => 'Partnership Intent Management']
        ]"
    >
        <div class="flex flex-wrap gap-2">
            <a
                href="{{ route('admin.dashboard.partnership.accounts', array_merge(request()->query(), ['chapter' => $chapterName])) }}"
                wire:navigate
                class="rounded-lg border border-blue-200 bg-white px-3 py-2 text-xs font-semibold text-blue-700 dark:border-blue-800 dark:bg-zinc-800 dark:text-blue-400"
            >
                Accounts
            </a>
            <button
                type="button"
                x-on:click="$wire.call('createIntent').then(() => $modalOpen('partnership-intent-modal'))"
                class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600"
            >
                New Intent
            </button>
        </div>
    </x-fancy-header>

    {{-- Stats Bar --}}
    <div class="mb-8 flex flex-wrap items-center gap-3 text-sm">
        <span class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-blue-700 dark:border-blue-800 dark:bg-blue-900/30 dark:text-blue-400">Chapter: {{ $chapterName }}</span>
        <span class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-amber-700 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-400">Pending Intents: {{ $intentStats['pending'] ?? 0 }}</span>
        <span class="rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-indigo-700 dark:border-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400">Reviewing: {{ $intentStats['reviewing'] ?? 0 }}</span>
        <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">Approved: {{ $intentStats['approved'] ?? 0 }}</span>
        <span class="rounded-full border border-zinc-300 bg-zinc-100 px-3 py-1 text-zinc-700 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">Withdrawn: {{ $intentStats['withdrawn'] ?? 0 }}</span>
    </div>

    {{-- Categories Section --}}
    <x-card class="mb-8 dark:bg-zinc-900">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-gray-200">Partnership Categories</h2>
                <p class="text-sm text-slate-500 dark:text-gray-400">Categorize intents as general, event-based, or project-based tracks.</p>
            </div>
            <button
                type="button"
                x-on:click="$wire.call('createCategory').then(() => $modalOpen('partnership-category-modal'))"
                class="rounded-lg border border-blue-200 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-50 dark:border-blue-800 dark:text-blue-400 dark:hover:bg-zinc-800"
            >
                Add Category
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700 dark:text-gray-300">Category</th>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700 dark:text-gray-300">Linked Account</th>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700 dark:text-gray-300">Description</th>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700 dark:text-gray-300">Intents</th>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700 dark:text-gray-300">Status</th>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700 dark:text-gray-300">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                    @forelse($categories as $category)
                        <tr class="dark:border-zinc-700">
                            <td class="px-3 py-3 font-medium text-zinc-900 dark:text-gray-200">{{ $category->name }}</td>
                            <td class="px-3 py-3 text-zinc-600 dark:text-gray-400">
                                @if($category->account)
                                    <p>{{ $category->account->account_name }} ({{ $category->account->bank_name }}){{ $category->account->account_number ? ' • ' . $category->account->account_number : '' }}</p>
                                    @if($category->account->chapter_id === null)
                                        <p class="text-xs text-indigo-600 dark:text-indigo-400">Global account</p>
                                    @endif
                                @else
                                    <p class="text-rose-600 dark:text-rose-400">No linked account</p>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-zinc-600 dark:text-gray-400">{{ $category->description ?: 'No description' }}</td>
                            <td class="px-3 py-3 text-zinc-600 dark:text-gray-400">{{ $category->intents_count }}</td>
                            <td class="px-3 py-3">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $category->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-400' }}">
                                    {{ $category->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        class="rounded-md border border-blue-200 px-2 py-1 text-xs font-medium text-blue-700 hover:bg-blue-50 dark:border-blue-800 dark:text-blue-400 dark:hover:bg-zinc-800"
                                        x-on:click="$wire.call('editCategory', {{ $category->id }}).then(() => $modalOpen('partnership-category-modal'))"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="deleteCategory({{ $category->id }})"
                                        class="rounded-md border border-rose-200 px-2 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50 dark:border-rose-800 dark:text-rose-400 dark:hover:bg-zinc-800"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-5 text-center text-zinc-500 dark:text-gray-400">No categories created for this chapter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    {{-- Intents Section --}}
    <x-card class="mb-8 dark:bg-zinc-900">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-gray-200">Partnership Intents</h2>
                <p class="text-sm text-slate-500 dark:text-gray-400">Intent records are pledges only, not transactions. Intents can be updated or withdrawn.</p>
            </div>
            <button
                type="button"
                x-on:click="$wire.call('createIntent').then(() => $modalOpen('partnership-intent-modal'))"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600"
            >
                Create Intent
            </button>
        </div>

        <div class="mb-4 grid grid-cols-1 gap-3 lg:grid-cols-5">
            <input wire:model.live.debounce.300ms="intentSearch" type="text" class="rounded-lg border px-3 py-2 text-sm dark:bg-zinc-800 dark:border-zinc-700 dark:text-gray-200 dark:placeholder-gray-400" placeholder="Search title, category, user" />
            <select wire:model.live="intentStatusFilter" class="rounded-lg border px-3 py-2 text-sm dark:bg-zinc-800 dark:border-zinc-700 dark:text-gray-200">
                <option value="">All statuses</option>
                <option value="draft">Draft</option>
                <option value="pending">Pending</option>
                <option value="reviewing">Reviewing</option>
                <option value="approved">Approved</option>
                <option value="declined">Declined</option>
                <option value="withdrawn">Withdrawn</option>
            </select>
            <select wire:model.live="intentTypeFilter" class="rounded-lg border px-3 py-2 text-sm dark:bg-zinc-800 dark:border-zinc-700 dark:text-gray-200">
                <option value="">All types</option>
                <option value="general">General</option>
                <option value="event">Event</option>
                <option value="project">Project</option>
            </select>
            <select wire:model.live="intentQuantity" class="rounded-lg border px-3 py-2 text-sm dark:bg-zinc-800 dark:border-zinc-700 dark:text-gray-200">
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700 dark:text-gray-300">Intent</th>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700 dark:text-gray-300">Type / Category</th>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700 dark:text-gray-300">Partner</th>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700 dark:text-gray-300">Pledge</th>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700 dark:text-gray-300">Linked Modules</th>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700 dark:text-gray-300">Status</th>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700 dark:text-gray-300">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                    @forelse($intents as $intent)
                        <tr class="dark:border-zinc-700">
                            <td class="px-3 py-3">
                                <p class="font-medium text-zinc-900 dark:text-gray-200">{{ $intent->title }}</p>
                                <p class="text-xs text-zinc-500 dark:text-gray-400">Created {{ $intent->created_at?->format('M d, Y H:i') }}</p>
                            </td>
                            <td class="px-3 py-3 text-zinc-700 dark:text-gray-300">
                                <p>{{ ucfirst($intent->intent_type) }}</p>
                                <p class="text-xs text-zinc-500 dark:text-gray-400">{{ $intent->category?->name ?: 'No category' }}</p>
                            </td>
                            <td class="px-3 py-3 text-zinc-700 dark:text-gray-300">
                                <p>{{ $intent->user?->name ?: 'Anonymous/External' }}</p>
                            </td>
                            <td class="px-3 py-3 text-zinc-700 dark:text-gray-300">
                                @if($intent->pledge_amount !== null)
                                    <p>{{ $intent->pledge_currency }} {{ number_format((float) $intent->pledge_amount, 2) }}</p>
                                    <p class="text-xs text-zinc-500 dark:text-gray-400">{{ str_replace('_', ' ', ucfirst($intent->pledge_frequency)) }}</p>
                                @else
                                    <p class="text-xs text-zinc-500 dark:text-gray-400">No pledge amount</p>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-zinc-700 dark:text-gray-300">
                                <p class="text-xs dark:text-gray-400">Event: {{ $intent->event?->title ?: 'Not linked' }}</p>
                                <p class="text-xs dark:text-gray-400">Account: {{ $intent->account?->account_name ?: 'Not linked' }}</p>
                            </td>
                            <td class="px-3 py-3">
                                @php
                                    $statusClass = match($intent->status) {
                                        'approved' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                                        'reviewing' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
                                        'declined' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
                                        'withdrawn' => 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-400',
                                        'draft' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400',
                                        default => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                    };
                                @endphp
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $statusClass }}">
                                    {{ $this->formatIntentStatus($intent->status) }}
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        class="rounded-md border border-blue-200 px-2 py-1 text-xs font-medium text-blue-700 hover:bg-blue-50 dark:border-blue-800 dark:text-blue-400 dark:hover:bg-zinc-800"
                                        x-on:click="$wire.call('editIntent', {{ $intent->id }}).then(() => $modalOpen('partnership-intent-modal'))"
                                    >
                                        Edit
                                    </button>
                                    @if($intent->status !== 'withdrawn')
                                        <button
                                            type="button"
                                            wire:click="setIntentStatus({{ $intent->id }}, 'withdrawn')"
                                            class="rounded-md border border-zinc-300 px-2 py-1 text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-400 dark:hover:bg-zinc-800"
                                        >
                                            Withdraw
                                        </button>
                                    @endif
                                    <button
                                        type="button"
                                        wire:click="deleteIntent({{ $intent->id }})"
                                        class="rounded-md border border-rose-200 px-2 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50 dark:border-rose-800 dark:text-rose-400 dark:hover:bg-zinc-800"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-5 text-center text-zinc-500 dark:text-gray-400">No partnership intents found for this chapter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $intents->links() }}
        </div>
    </x-card>

    <x-modal id="partnership-category-modal" :title="$editingCategoryId ? 'Edit Partnership Category' : 'Create Partnership Category'" size="lg">
        <form wire:submit.prevent="saveCategory" class="space-y-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-900 dark:text-gray-200">Category Name</label>
                <input wire:model.lazy="categoryName" type="text" class="w-full rounded-lg border px-3 py-2 text-sm dark:bg-zinc-800 dark:border-zinc-700 dark:text-gray-200 dark:placeholder-gray-400" placeholder="e.g. Outreach Project" />
                @error('categoryName') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-slate-900 dark:text-gray-200">Description</label>
                <textarea wire:model.lazy="categoryDescription" rows="3" class="w-full rounded-lg border px-3 py-2 text-sm dark:bg-zinc-800 dark:border-zinc-700 dark:text-gray-200 dark:placeholder-gray-400" placeholder="Optional category description"></textarea>
                @error('categoryDescription') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-slate-900 dark:text-gray-200">Linked Contribution Account</label>
                <select wire:model.lazy="categoryAccountId" class="w-full rounded-lg border px-3 py-2 text-sm dark:bg-zinc-800 dark:border-zinc-700 dark:text-gray-200">
                    <option value="">Select account</option>
                    @foreach($chapterAccounts as $chapterAccount)
                        <option value="{{ $chapterAccount->id }}">
                            {{ $chapterAccount->account_name }} ({{ $chapterAccount->bank_name }}){{ $chapterAccount->account_number ? ' • ' . $chapterAccount->account_number : '' }}{{ $chapterAccount->chapter_id === null ? ' - Global' : '' }}
                        </option>
                    @endforeach
                </select>
                @error('categoryAccountId') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="categoryIsActive" class="rounded border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800" />
                <span class="text-slate-900 dark:text-gray-200">Active category</span>
            </label>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" x-on:click="$modalClose('partnership-category-modal')" class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-gray-300 dark:hover:bg-zinc-700">Cancel</button>
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600">Save Category</button>
            </div>
        </form>
    </x-modal>

    <x-modal id="partnership-intent-modal" :title="$editingIntentId ? 'Edit Partnership Intent' : 'Create Partnership Intent'" size="3xl">
        <form wire:submit.prevent="saveIntent" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-900 dark:text-gray-200">Intent Title</label>
                    <input wire:model.lazy="intentTitle" type="text" class="w-full rounded-lg border px-3 py-2 text-sm dark:bg-zinc-800 dark:border-zinc-700 dark:text-gray-200 dark:placeholder-gray-400" placeholder="e.g. Youth Conference Partnership" />
                    @error('intentTitle') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-900 dark:text-gray-200">Intent Type</label>
                    <select wire:model.lazy="intentType" class="w-full rounded-lg border px-3 py-2 text-sm dark:bg-zinc-800 dark:border-zinc-700 dark:text-gray-200">
                        <option value="general">General</option>
                        <option value="event">Event</option>
                        <option value="project">Project</option>
                    </select>
                    @error('intentType') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-900 dark:text-gray-200">Partner (User)</label>
                    <select wire:model.lazy="intentUserId" class="w-full rounded-lg border px-3 py-2 text-sm dark:bg-zinc-800 dark:border-zinc-700 dark:text-gray-200">
                        <option value="">Anonymous/External</option>
                        @foreach($chapterUsers as $chapterUser)
                            <option value="{{ $chapterUser->id }}">{{ $chapterUser->name }}</option>
                        @endforeach
                    </select>
                    @error('intentUserId') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                @if($intentType === 'project')
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-900 dark:text-gray-200">Category</label>
                        <select wire:model.lazy="intentCategoryId" class="w-full rounded-lg border px-3 py-2 text-sm dark:bg-zinc-800 dark:border-zinc-700 dark:text-gray-200">
                            <option value="">Select category</option>
                            @foreach($categoryOptions as $categoryOption)
                                <option value="{{ $categoryOption->id }}">
                                    {{ $categoryOption->name }}{{ $categoryOption->is_active ? '' : ' (Inactive)' }}{{ $categoryOption->account ? ' - ' . $categoryOption->account->account_name . ($categoryOption->account->account_number ? ' (' . $categoryOption->account->account_number . ')' : '') : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('intentCategoryId') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                @else
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-900 dark:text-gray-200">Contribution Account</label>
                        <select wire:model.lazy="intentAccountId" class="w-full rounded-lg border px-3 py-2 text-sm dark:bg-zinc-800 dark:border-zinc-700 dark:text-gray-200">
                            <option value="">Select account</option>
                            @if($intentType === 'event')
                                @foreach($eventAccounts as $account)
                                    <option value="{{ $account->id }}">
                                        {{ $account->account_name }} ({{ $account->bank_name }}){{ $account->account_number ? ' • ' . $account->account_number : '' }}{{ $account->chapter_id === null ? ' - Global' : '' }}
                                    </option>
                                @endforeach
                            @else
                                @foreach($chapterAccounts as $account)
                                    <option value="{{ $account->id }}">
                                        {{ $account->account_name }} ({{ $account->bank_name }}){{ $account->account_number ? ' • ' . $account->account_number : '' }}{{ $account->chapter_id === null ? ' - Global' : '' }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        @error('intentAccountId') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                @endif
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-900 dark:text-gray-200">Status</label>
                    <select wire:model.lazy="intentStatus" class="w-full rounded-lg border px-3 py-2 text-sm dark:bg-zinc-800 dark:border-zinc-700 dark:text-gray-200">
                        <option value="draft">Draft</option>
                        <option value="pending">Pending</option>
                        <option value="reviewing">Reviewing</option>
                        <option value="approved">Approved</option>
                        <option value="declined">Declined</option>
                        <option value="withdrawn">Withdrawn</option>
                    </select>
                    @error('intentStatus') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                @if($intentType === 'event')
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-900 dark:text-gray-200">Linked Event</label>
                        <select wire:model.lazy="intentEventId" class="w-full rounded-lg border px-3 py-2 text-sm dark:bg-zinc-800 dark:border-zinc-700 dark:text-gray-200">
                            <option value="">Select event</option>
                            @foreach($chapterEvents as $chapterEvent)
                                <option value="{{ $chapterEvent->id }}">
                                    {{ $chapterEvent->title }}
                                    @if($chapterEvent->start_at)
                                        ({{ $chapterEvent->start_at->format('M d, Y') }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('intentEventId') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                @else
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-800">
                        <label class="mb-1 block text-sm font-medium text-slate-900 dark:text-gray-200">Intent Summary</label>
                        <p class="text-sm text-zinc-700 dark:text-gray-300">
                            {{ $intentType === 'project' ? 'Project partnership' : 'General partnership' }}
                        </p>
                    </div>
                @endif
                @php
                    $selectedIntentCategory = $categoryOptions->firstWhere('id', (int) $intentCategoryId);
                    $linkedIntentAccount = null;

                    if ($intentType === 'project') {
                        $linkedIntentAccount = $selectedIntentCategory?->account;
                    }

                    if ($intentType === 'general') {
                        $linkedIntentAccount = $chapterAccounts->firstWhere('id', (int) $intentAccountId);
                    }

                    if ($intentType === 'event') {
                        $linkedIntentAccount = $eventAccounts->firstWhere('id', (int) $intentAccountId);
                    }
                @endphp
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-800">
                    <label class="mb-1 block text-sm font-medium text-slate-900 dark:text-gray-200">Linked Contribution Account</label>
                    @if($linkedIntentAccount)
                        <p class="text-sm text-zinc-700 dark:text-gray-300">
                            {{ $linkedIntentAccount->account_name }} ({{ $linkedIntentAccount->bank_name }}){{ $linkedIntentAccount->account_number ? ' • ' . $linkedIntentAccount->account_number : '' }}{{ $linkedIntentAccount->chapter_id === null ? ' - Global' : '' }}
                        </p>
                    @else
                        <p class="text-sm text-rose-600 dark:text-rose-400">
                            @if($intentType === 'project')
                                Select a category with a linked account.
                            @elseif($intentType === 'event')
                                Select an event and its linked account.
                            @else
                                Select a contribution account.
                            @endif
                        </p>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-900 dark:text-gray-200">Pledge Amount</label>
                    <input wire:model.lazy="intentPledgeAmount" type="number" step="0.01" min="0" class="w-full rounded-lg border px-3 py-2 text-sm dark:bg-zinc-800 dark:border-zinc-700 dark:text-gray-200 dark:placeholder-gray-400" placeholder="0.00" />
                    @error('intentPledgeAmount') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-900 dark:text-gray-200">Currency</label>
                    <input wire:model.lazy="intentPledgeCurrency" type="text" maxlength="3" class="w-full rounded-lg border px-3 py-2 text-sm dark:bg-zinc-800 dark:border-zinc-700 dark:text-gray-200 dark:placeholder-gray-400" placeholder="NGN" />
                    @error('intentPledgeCurrency') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-900 dark:text-gray-200">Frequency</label>
                    <select wire:model.lazy="intentPledgeFrequency" class="w-full rounded-lg border px-3 py-2 text-sm dark:bg-zinc-800 dark:border-zinc-700 dark:text-gray-200">
                        <option value="one_time">One Time</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly</option>
                        <option value="yearly">Yearly</option>
                        <option value="custom">Custom</option>
                    </select>
                    @error('intentPledgeFrequency') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-900 dark:text-gray-200">Pledged At</label>
                    <input wire:model.lazy="intentPledgedAt" type="datetime-local" class="w-full rounded-lg border px-3 py-2 text-sm dark:bg-zinc-800 dark:border-zinc-700 dark:text-gray-200" />
                    @error('intentPledgedAt') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-slate-900 dark:text-gray-200">Intent Notes</label>
                <textarea wire:model.lazy="intentNotes" rows="3" class="w-full rounded-lg border px-3 py-2 text-sm dark:bg-zinc-800 dark:border-zinc-700 dark:text-gray-200 dark:placeholder-gray-400" placeholder="Describe the pledge intent (not a transaction)."></textarea>
                @error('intentNotes') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-slate-900 dark:text-gray-200">Internal Admin Notes</label>
                <textarea wire:model.lazy="intentAdminNotes" rows="3" class="w-full rounded-lg border px-3 py-2 text-sm dark:bg-zinc-800 dark:border-zinc-700 dark:text-gray-200 dark:placeholder-gray-400" placeholder="Internal follow-up, reconciliation notes, next steps."></textarea>
                @error('intentAdminNotes') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" x-on:click="$modalClose('partnership-intent-modal')" class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-gray-300 dark:hover:bg-zinc-700">Cancel</button>
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600">Save Intent</button>
            </div>
        </form>
    </x-modal>
</div>
