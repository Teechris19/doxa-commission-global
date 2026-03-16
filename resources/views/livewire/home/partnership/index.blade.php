<?php

use App\Models\Accounts;
use App\Models\Chapter;
use App\Models\Events;
use App\Models\Partnership;
use App\Models\PartnershipCategory;
use App\Models\PartnershipIntent;
use App\Notifications\PartnershipIntentSubmitted;
use App\Services\NotificationRecipients;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('components.layouts.tailwind-layout')] class extends Component {
    #[Url]
    public ?string $chapter = null;

    public ?int $selectedChapterId = null;
    public $chapters;

    public string $name = '';
    public string $email = '';
    public ?string $phone = null;
    public ?string $organization = null;
    public ?string $website = null;

    public string $intentType = 'general';
    public ?int $categoryId = null;
    public ?int $accountId = null;
    public ?int $eventId = null;

    public string $intentTitle = '';
    public ?float $pledgeAmount = null;
    public string $pledgeCurrency = 'NGN';
    public string $pledgeFrequency = 'one_time';
    public ?string $intentNotes = null;

    public $chapterAccounts;
    public $chapterCategories;
    public $chapterEvents;
    public $eventAccounts;

    public ?string $message = null;
    public string $messageType = 'success';

    public function mount(): void
    {
        $this->chapters = Chapter::query()->orderBy('name')->get(['id', 'name']);

        $this->setInitialChapter();
        $this->loadChapterResources();

        if (auth()->check()) {
            $this->name = (string) auth()->user()->name;
            $this->email = (string) auth()->user()->email;
        }

        if ($this->intentTitle === '') {
            $this->intentTitle = 'Partnership Intent';
        }
    }

    public function updatedSelectedChapterId($value): void
    {
        $this->selectedChapterId = $value ? (int) $value : null;

        $selected = $this->chapters->firstWhere('id', $this->selectedChapterId);
        $this->chapter = $selected?->name;

        $this->accountId = null;
        $this->categoryId = null;
        $this->eventId = null;

        $this->loadChapterResources();
    }

    public function updatedIntentType(string $value): void
    {
        if ($value !== 'event') {
            $this->eventId = null;
            $this->eventAccounts = collect();
        }

        if ($value !== 'project') {
            $this->categoryId = null;
        }

        $this->accountId = null;
    }

    public function updatedCategoryId($value): void
    {
        if ($this->intentType !== 'project') {
            $this->categoryId = null;
            return;
        }

        $this->categoryId = $value ? (int) $value : null;

        if (!$this->categoryId) {
            $this->accountId = null;
            return;
        }

        $linkedAccountId = PartnershipCategory::query()
            ->where('chapter_id', $this->selectedChapterId)
            ->where('is_active', true)
            ->where('id', $this->categoryId)
            ->whereHas('account', function ($query): void {
                $query->where('is_active', true)
                    ->where(function ($inner): void {
                        $inner->where('chapter_id', $this->selectedChapterId)
                            ->orWhereNull('chapter_id');
                    });
            })
            ->value('account_id');

        $this->accountId = $linkedAccountId ? (int) $linkedAccountId : null;
    }

    public function updatedEventId($value): void
    {
        if ($this->intentType !== 'event') {
            $this->eventId = null;
            $this->eventAccounts = collect();
            return;
        }

        $this->eventId = $value ? (int) $value : null;
        $this->accountId = null;
        $this->loadEventAccounts();
    }

    public function submit(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:30',
            'organization' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'selectedChapterId' => 'required|integer|exists:chapters,id',
            'intentType' => 'required|in:general,event,project',
            'categoryId' => $this->intentType === 'project'
                ? [
                    'required',
                    'integer',
                    Rule::exists('partnership_categories', 'id')->where(fn($query) => $query
                        ->where('chapter_id', $this->selectedChapterId)
                        ->where('is_active', true)
                        ->whereNotNull('account_id')),
                ]
                : ['nullable', 'integer'],
            'eventId' => $this->intentType === 'event'
                ? [
                    'required',
                    'integer',
                    Rule::exists('events', 'id')->where(fn($query) => $query->where('chapter_id', $this->selectedChapterId)),
                ]
                : ['nullable', 'integer'],
            'accountId' => $this->intentType === 'general'
                ? [
                    'required',
                    'integer',
                    Rule::exists('accounts', 'id')->where(fn($query) => $query
                        ->where('is_active', true)
                        ->where(function ($inner): void {
                            $inner->where('chapter_id', $this->selectedChapterId)
                                ->orWhereNull('chapter_id');
                        })),
                ]
                : ['nullable', 'integer'],
            'intentTitle' => 'required|string|max:255',
            'pledgeAmount' => 'nullable|numeric|min:0',
            'pledgeCurrency' => 'required|string|size:3',
            'pledgeFrequency' => 'required|in:one_time,weekly,monthly,quarterly,yearly,custom',
            'intentNotes' => 'nullable|string|max:5000',
        ]);

        if ($validated['intentType'] === 'project') {
            $selectedCategory = PartnershipCategory::query()
                ->with(['account' => function ($query): void {
                    $query->where('is_active', true)
                        ->where(function ($inner): void {
                            $inner->where('chapter_id', $this->selectedChapterId)
                                ->orWhereNull('chapter_id');
                        });
                }])
                ->where('chapter_id', $this->selectedChapterId)
                ->where('is_active', true)
                ->find($validated['categoryId']);

            if (!$selectedCategory || !$selectedCategory->account) {
                $this->addError('categoryId', 'Selected category does not have an active linked account.');
                return;
            }

            $this->accountId = (int) $selectedCategory->account->id;
        }

        if ($validated['intentType'] === 'event') {
            $eventAccounts = Accounts::query()
                ->whereHas('events', fn($query) => $query->where('events.id', $validated['eventId']))
                ->where('is_active', true)
                ->orderBy('account_name')
                ->get(['id']);

            if ($eventAccounts->isEmpty()) {
                $this->addError('eventId', 'Selected event does not have a linked contribution account.');
                return;
            }

            if (!$validated['accountId'] && $eventAccounts->count() === 1) {
                $this->accountId = (int) $eventAccounts->first()->id;
            } else {
                $this->accountId = $validated['accountId'] ? (int) $validated['accountId'] : null;
            }

            if (!$this->accountId || !$eventAccounts->pluck('id')->contains($this->accountId)) {
                $this->addError('accountId', 'Select a contribution account linked to the event.');
                return;
            }
        }

        $chapter = Chapter::find($validated['selectedChapterId']);

        $intent = PartnershipIntent::create([
            'chapter_id' => $validated['selectedChapterId'],
            'user_id' => auth()->id(),
            'partnership_category_id' => $validated['categoryId'] ?? null,
            'account_id' => $this->accountId ?? null,
            'event_id' => $validated['eventId'] ?? null,
            'intent_type' => $validated['intentType'],
            'title' => $validated['intentTitle'],
            'pledge_amount' => $validated['pledgeAmount'] ?? null,
            'pledge_currency' => strtoupper($validated['pledgeCurrency']),
            'pledge_frequency' => $validated['pledgeFrequency'],
            'status' => 'pending',
            'notes' => $validated['intentNotes'] ?? null,
            'pledged_at' => now(),
        ]);

        // Keep legacy partnership pipeline populated while transition is ongoing.
        Partnership::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'preferred_location' => $chapter?->name,
            'partnership_interests' => $validated['intentNotes'] ?? null,
            'organization' => $validated['organization'] ?? null,
            'website' => $validated['website'] ?? null,
            'partnership_type' => $this->mapIntentTypeToLegacyPartnershipType($validated['intentType']),
            'proposed_amount' => $validated['pledgeAmount'] ?? null,
            'status' => 'pending',
        ]);

        $recipients = (new NotificationRecipients())
            ->forFunctionAndChapter('partnerships', (int) $validated['selectedChapterId']);

        foreach ($recipients as $recipient) {
            $recipient->notify(new PartnershipIntentSubmitted($intent));
        }

        $this->messageType = 'success';
        $this->message = 'Partnership intent submitted successfully. The partnership team will review it shortly.';

        $this->reset([
            'phone',
            'organization',
            'website',
            'intentType',
            'categoryId',
            'accountId',
            'eventId',
            'intentTitle',
            'pledgeAmount',
            'pledgeCurrency',
            'pledgeFrequency',
            'intentNotes',
        ]);

        $this->intentType = 'general';
        $this->intentTitle = 'Partnership Intent';
        $this->pledgeCurrency = 'NGN';
        $this->pledgeFrequency = 'one_time';

        if (auth()->check()) {
            $this->name = (string) auth()->user()->name;
            $this->email = (string) auth()->user()->email;
        }
    }

    private function setInitialChapter(): void
    {
        $selected = null;

        if ($this->chapter) {
            $selected = $this->chapters->firstWhere('name', $this->chapter);
        }

        if (!$selected && auth()->check() && auth()->user()->chapter_id) {
            $selected = $this->chapters->firstWhere('id', (int) auth()->user()->chapter_id);
        }

        if (!$selected) {
            $selected = $this->chapters->first();
        }

        $this->selectedChapterId = $selected?->id;
        $this->chapter = $selected?->name;
    }

    private function loadChapterResources(): void
    {
        if (!$this->selectedChapterId) {
            $this->chapterAccounts = collect();
            $this->chapterCategories = collect();
            $this->chapterEvents = collect();
            $this->eventAccounts = collect();
            return;
        }

        $this->chapterAccounts = Accounts::query()
            ->where(function ($query): void {
                $query->where('chapter_id', $this->selectedChapterId)
                    ->orWhereNull('chapter_id');
            })
            ->where('is_active', true)
            ->orderBy('account_type')
            ->orderBy('account_name')
            ->get([
                'id',
                'account_name',
                'account_number',
                'bank_name',
                'currency',
                'account_type',
                'contact_email',
                'contact_phone',
                'special_instructions',
                'chapter_id',
            ]);

        $this->chapterCategories = PartnershipCategory::query()
            ->where('chapter_id', $this->selectedChapterId)
            ->where('is_active', true)
            ->whereNotNull('account_id')
            ->whereHas('account', function ($query): void {
                $query->where('is_active', true)
                    ->where(function ($inner): void {
                        $inner->where('chapter_id', $this->selectedChapterId)
                            ->orWhereNull('chapter_id');
                    });
            })
            ->with('account:id,account_name,account_number,bank_name,chapter_id,is_active')
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'account_id']);

        $this->chapterEvents = Events::query()
            ->where('chapter_id', $this->selectedChapterId)
            ->where('status', 'published')
            ->orderBy('start_at')
            ->get(['id', 'title', 'start_at']);

        $this->loadEventAccounts();
    }

    private function loadEventAccounts(): void
    {
        if (!$this->eventId || $this->intentType !== 'event') {
            $this->eventAccounts = collect();
            return;
        }

        $this->eventAccounts = Accounts::query()
            ->whereHas('events', fn($query) => $query->where('events.id', $this->eventId))
            ->where('is_active', true)
            ->orderBy('account_name')
            ->get(['id', 'account_name', 'account_number', 'bank_name', 'chapter_id']);

        if ($this->eventAccounts->count() === 1 && !$this->accountId) {
            $this->accountId = (int) $this->eventAccounts->first()->id;
        }
    }

    private function mapIntentTypeToLegacyPartnershipType(string $intentType): string
    {
        return match ($intentType) {
            'event' => 'ministry',
            'project' => 'strategic',
            default => 'financial',
        };
    }
}; ?>

<div class="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    <section class="overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-[0_24px_60px_-40px_rgba(37,99,235,0.45)]">
        <div class="bg-gradient-to-r from-blue-600 to-blue-500 px-6 py-12 text-white sm:px-10">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-blue-100">Partnership</p>
            <h1 class="mt-3 text-3xl font-bold sm:text-4xl">Partnership Intent</h1>
            <p class="mt-3 max-w-3xl text-sm text-blue-100 sm:text-base">
                Submit your partnership intent, choose a category, and link it to a chapter/global account.
                This records intent only and does not process payments online.
            </p>
        </div>

        <div class="space-y-8 px-6 py-8 sm:px-10">
            @if ($message)
                <div class="rounded-2xl border px-4 py-3 text-sm {{ $messageType === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700' }}">
                    {{ $message }}
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-[1.15fr_1fr]">
                <article class="rounded-2xl border border-blue-100 bg-white p-5">
                    <h2 class="text-lg font-semibold text-slate-900">Chapter Accounts</h2>
                    <p class="mt-1 text-sm text-slate-600">Available chapter and global partnership accounts.</p>

                    <div class="mt-4">
                        <label for="selected_chapter_id" class="mb-2 block text-sm font-medium text-slate-700">Chapter</label>
                        <select
                            id="selected_chapter_id"
                            wire:model.live="selectedChapterId"
                            class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900"
                        >
                            <option value="">Select chapter</option>
                            @foreach($chapters as $chapterOption)
                                <option value="{{ $chapterOption->id }}">{{ $chapterOption->name }}</option>
                            @endforeach
                        </select>
                        @error('selectedChapterId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="mt-4 max-h-[350px] space-y-3 overflow-y-auto pr-1">
                        @forelse($chapterAccounts as $account)
                            <div class="rounded-xl border border-blue-100 bg-blue-50/60 p-3">
                                <p class="text-sm font-semibold text-slate-900">{{ $account->account_name }}</p>
                                <p class="mt-1 text-xs text-slate-600">{{ $account->bank_name }} • {{ $account->account_number }}</p>
                                <p class="mt-1 text-xs text-slate-600">{{ strtoupper($account->account_type) }} • {{ $account->currency }}</p>
                                @if($account->chapter_id === null)
                                    <p class="mt-1 text-xs font-medium text-indigo-600">Global account</p>
                                @endif
                                @if($account->contact_email || $account->contact_phone)
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $account->contact_email ?: '' }}{{ $account->contact_email && $account->contact_phone ? ' • ' : '' }}{{ $account->contact_phone ?: '' }}
                                    </p>
                                @endif
                                @if($account->special_instructions)
                                    <p class="mt-2 text-xs text-slate-500">{{ $account->special_instructions }}</p>
                                @endif
                            </div>
                        @empty
                            <div class="rounded-xl border border-dashed border-blue-200 bg-blue-50/60 p-4 text-sm text-slate-500">
                                No active partnership accounts are configured for this chapter yet.
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-5 rounded-xl border border-indigo-100 bg-indigo-50/70 p-4">
                        <h3 class="text-sm font-semibold text-indigo-900">Available Categories</h3>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @forelse($chapterCategories as $category)
                                    <span class="rounded-full border border-indigo-200 bg-white px-3 py-1 text-xs font-medium text-indigo-700">
                                        {{ $category->name }}{{ $category->account ? ' • ' . $category->account->account_name . ($category->account->account_number ? ' (' . $category->account->account_number . ')' : '') : '' }}
                                    </span>
                                @empty
                                    <span class="text-xs text-indigo-700">No categories configured for this chapter yet.</span>
                                @endforelse
                            </div>
                    </div>
                </article>

                <article class="rounded-2xl border border-blue-100 bg-white p-5">
                    <h2 class="text-lg font-semibold text-slate-900">Submit Intent</h2>
                    <p class="mt-1 text-sm text-slate-600">All submissions are reviewed by the chapter partnership team.</p>

                    <form class="mt-4 space-y-4" wire:submit.prevent="submit">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label for="name" class="mb-2 block text-sm font-medium text-slate-700">Name</label>
                                <input id="name" type="text" wire:model="name" class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900" />
                                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="email" class="mb-2 block text-sm font-medium text-slate-700">Email</label>
                                <input id="email" type="email" wire:model="email" class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900" />
                                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label for="phone" class="mb-2 block text-sm font-medium text-slate-700">Phone (Optional)</label>
                                <input id="phone" type="text" wire:model="phone" class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900" />
                                @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="organization" class="mb-2 block text-sm font-medium text-slate-700">Organization (Optional)</label>
                                <input id="organization" type="text" wire:model="organization" class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900" />
                                @error('organization') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="website" class="mb-2 block text-sm font-medium text-slate-700">Website (Optional)</label>
                            <input id="website" type="url" wire:model="website" class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900" placeholder="https://example.com" />
                            @error('website') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label for="intent_type" class="mb-2 block text-sm font-medium text-slate-700">Intent Type</label>
                                <select id="intent_type" wire:model.live="intentType" class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900">
                                    <option value="general">General</option>
                                    <option value="event">Event</option>
                                    <option value="project">Project</option>
                                </select>
                                @error('intentType') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            @if($intentType === 'project')
                                <div>
                                    <label for="category_id" class="mb-2 block text-sm font-medium text-slate-700">Category</label>
                                    <select id="category_id" wire:model.live="categoryId" class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900">
                                        <option value="">Select category</option>
                                        @foreach($chapterCategories as $category)
                                            <option value="{{ $category->id }}">
                                                {{ $category->name }}{{ $category->account ? ' - ' . $category->account->account_name . ($category->account->account_number ? ' (' . $category->account->account_number . ')' : '') : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('categoryId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            @else
                                <div>
                                    <label for="account_id" class="mb-2 block text-sm font-medium text-slate-700">Contribution Account</label>
                                    <select id="account_id" wire:model.live="accountId" class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900">
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
                                    @error('accountId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            @endif
                        </div>

                        <div>
                            <label for="intent_title" class="mb-2 block text-sm font-medium text-slate-700">Intent Title</label>
                            <input id="intent_title" type="text" wire:model="intentTitle" class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900" placeholder="Partnership Intent" />
                            @error('intentTitle') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div>
                                <label for="pledge_amount" class="mb-2 block text-sm font-medium text-slate-700">Pledge Amount (Optional)</label>
                                <input id="pledge_amount" type="number" min="0" step="0.01" wire:model="pledgeAmount" class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900" />
                                @error('pledgeAmount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="pledge_currency" class="mb-2 block text-sm font-medium text-slate-700">Currency</label>
                                <input id="pledge_currency" type="text" maxlength="3" wire:model="pledgeCurrency" class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900" />
                                @error('pledgeCurrency') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="pledge_frequency" class="mb-2 block text-sm font-medium text-slate-700">Frequency</label>
                                <select id="pledge_frequency" wire:model="pledgeFrequency" class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900">
                                    <option value="one_time">One Time</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="quarterly">Quarterly</option>
                                    <option value="yearly">Yearly</option>
                                    <option value="custom">Custom</option>
                                </select>
                                @error('pledgeFrequency') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        @if($intentType === 'event')
                            <div>
                                <label for="event_id" class="mb-2 block text-sm font-medium text-slate-700">Event</label>
                                <select id="event_id" wire:model="eventId" class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900">
                                    <option value="">Select event</option>
                                    @foreach($chapterEvents as $event)
                                        <option value="{{ $event->id }}">
                                            {{ $event->title }}
                                            @if($event->start_at)
                                                ({{ $event->start_at->format('M d, Y') }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('eventId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        @endif

                        @php
                            $selectedCategory = $chapterCategories->firstWhere('id', (int) $categoryId);
                            $selectedAccount = null;

                            if ($intentType === 'project') {
                                $selectedAccount = $selectedCategory?->account;
                            }

                            if ($intentType === 'general') {
                                $selectedAccount = $chapterAccounts->firstWhere('id', (int) $accountId);
                            }

                            if ($intentType === 'event') {
                                $selectedAccount = $eventAccounts->firstWhere('id', (int) $accountId);
                            }
                        @endphp
                        <div class="rounded-xl border border-blue-100 bg-blue-50/50 px-4 py-3">
                            <label class="mb-2 block text-sm font-medium text-slate-700">Linked Contribution Account</label>
                            @if($selectedAccount)
                                <p class="text-sm font-semibold text-slate-900">
                                    {{ $selectedAccount->account_name }} ({{ $selectedAccount->bank_name }}){{ $selectedAccount->account_number ? ' • ' . $selectedAccount->account_number : '' }}{{ $selectedAccount->chapter_id === null ? ' - Global' : '' }}
                                </p>
                            @else
                                <p class="text-sm text-slate-600">
                                    @if($intentType === 'project')
                                        Select a category to see the linked account.
                                    @elseif($intentType === 'event')
                                        Select an event and its linked account.
                                    @else
                                        Select a contribution account to continue.
                                    @endif
                                </p>
                            @endif
                        </div>

                        <div>
                            <label for="intent_notes" class="mb-2 block text-sm font-medium text-slate-700">Intent Notes</label>
                            <textarea id="intent_notes" rows="4" wire:model="intentNotes" class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900" placeholder="Tell us about your partnership intent."></textarea>
                            @error('intentNotes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700" wire:loading.attr="disabled">
                            <span wire:loading.remove>Submit Intent</span>
                            <span wire:loading>Submitting...</span>
                        </button>
                    </form>
                </article>
            </div>
        </div>
    </section>
</div>
