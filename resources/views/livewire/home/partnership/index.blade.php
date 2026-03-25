<?php

use App\Models\Accounts;
use App\Models\Chapter;
use App\Models\Events;
use App\Models\Partnership;
use App\Models\PartnershipCategory;
use App\Models\PartnershipFormField;
use App\Models\PartnershipIntent;
use App\Notifications\PartnershipIntentSubmitted;
use App\Services\NotificationRecipients;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('components.layouts.tailwind-layout')] class extends Component {
    #[Url]
    public ?string $chapter = null;

    public ?int $selectedChapterId = null;
    public $chapters;

    // User Information (auto-filled if logged in)
    public string $name = '';
    public string $email = '';
    public ?string $phone = null;

    // Partnership Type and Selection
    public string $partnershipType = 'commission'; // commission, event, project
    public ?int $accountId = null;
    public ?int $categoryId = null;
    public ?int $eventId = null;

    // Dynamic form fields values
    public array $dynamicFieldValues = [];

    public $chapterAccounts;
    public $chapterCategories;
    public $partnershipEvents;
    public $eventAccounts;
    public $formFields;

    public function mount(): void
    {
        $this->chapters = Chapter::query()->orderBy('name')->get(['id', 'name']);

        $this->setInitialChapter();
        $this->loadChapterResources();

        // Auto-fill user information if logged in
        if (auth()->check()) {
            $user = auth()->user();
            $this->name = (string) $user->name;
            $this->email = (string) $user->email;
            $this->phone = $user->phone ?? null;
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
        $this->dynamicFieldValues = [];
        $this->eventAccounts = collect();

        $this->loadChapterResources();
    }

    public function updatedPartnershipType($value): void
    {
        // Reset dependent fields when partnership type changes
        if ($value !== 'event') {
            $this->eventId = null;
            $this->eventAccounts = collect();
        }
        
        if ($value !== 'project') {
            $this->categoryId = null;
        }
        
        if ($value !== 'commission') {
            $this->accountId = null;
        }
    }

    public function updatedEventId($value): void
    {
        $this->eventId = $value ? (int) $value : null;
        $this->accountId = null;
        $this->loadEventAccounts();
    }

    public function updatedCategoryId($value): void
    {
        $this->categoryId = $value ? (int) $value : null;
        
        // If a category is selected, auto-set the linked account
        if ($this->categoryId && $this->partnershipType === 'project') {
            $linkedAccountId = PartnershipCategory::query()
                ->where('chapter_id', $this->selectedChapterId)
                ->where('id', $this->categoryId)
                ->whereHas('account', function ($query) {
                    $query->where('is_active', true)
                        ->whereNull('deleted_at')
                        ->where(function ($inner) {
                            $inner->where('chapter_id', $this->selectedChapterId)
                                ->orWhereNull('chapter_id');
                        });
                })
                ->value('account_id');
            
            $this->accountId = $linkedAccountId ? (int) $linkedAccountId : null;
        }
    }

    public function submit(): void
    {
        // Build validation rules dynamically based on form fields and partnership type
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:30',
            'selectedChapterId' => 'required|integer|exists:chapters,id',
            'partnershipType' => 'required|in:commission,event,project',
        ];

        // Add validation based on partnership type
        if ($this->partnershipType === 'commission') {
            $rules['accountId'] = 'required|integer|exists:accounts,id';
        } elseif ($this->partnershipType === 'event') {
            $rules['eventId'] = 'required|integer|exists:events,id';
            $rules['accountId'] = 'required|integer|exists:accounts,id';
        } elseif ($this->partnershipType === 'project') {
            $rules['categoryId'] = 'required|integer|exists:partnership_categories,id';
        }

        // Add validation for required dynamic fields
        foreach ($this->formFields as $field) {
            if ($field->is_required) {
                $rules['dynamicFieldValues.' . $field->name] = 'required';
            }
        }

        $validated = $this->validate($rules, [
            'name.required' => 'Please provide your name.',
            'email.required' => 'Please provide your email address.',
            'email.email' => 'Please provide a valid email address.',
            'accountId.required' => 'Please select an account.',
            'eventId.required' => 'Please select an event.',
            'categoryId.required' => 'Please select a project category.',
        ]);

        $chapter = Chapter::find($validated['selectedChapterId']);

        // Determine title based on partnership type
        $title = match($validated['partnershipType']) {
            'event' => 'Event Partnership - ' . (Events::find($validated['eventId'])?->title ?? 'Event'),
            'project' => 'Project Partnership - ' . (PartnershipCategory::find($validated['categoryId'])?->name ?? 'Project'),
            default => 'Commission Partnership',
        };

        // Create Partnership Intent
        PartnershipIntent::create([
            'chapter_id' => $validated['selectedChapterId'],
            'user_id' => auth()->id(),
            'account_id' => $validated['accountId'] ?? null,
            'partnership_category_id' => $validated['categoryId'] ?? null,
            'event_id' => $validated['eventId'] ?? null,
            'intent_type' => $validated['partnershipType'],
            'title' => $title,
            'status' => 'pending',
            'pledged_at' => now(),
            'notes' => json_encode($this->dynamicFieldValues),
        ]);

        // Keep legacy partnership pipeline populated
        Partnership::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'preferred_location' => $chapter?->name,
            'partnership_type' => ucfirst($validated['partnershipType']),
            'status' => 'pending',
        ]);

        $recipients = (new NotificationRecipients())
            ->forFunctionAndChapter('partnerships', (int) $validated['selectedChapterId']);

        foreach ($recipients as $recipient) {
            $recipient->notify(new PartnershipIntentSubmitted(null));
        }

        session()->flash('message', 'Partnership intent submitted successfully. The partnership team will review it shortly.');
        session()->flash('messageType', 'success');

        // Reset form but keep user info
        $this->reset([
            'accountId',
            'categoryId',
            'eventId',
            'partnershipType',
            'dynamicFieldValues',
        ]);

        $this->partnershipType = 'commission';
        $this->eventAccounts = collect();

        $this->redirectRoute('home.partnership.index', navigate: true);
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
            $this->partnershipEvents = collect();
            $this->eventAccounts = collect();
            $this->formFields = collect();
            return;
        }

        // Load accounts (for commission partnership)
        $this->chapterAccounts = Accounts::query()
            ->where(function ($query): void {
                $query->where('chapter_id', $this->selectedChapterId)
                    ->orWhereNull('chapter_id');
            })
            ->where('is_active', true)
            ->orderBy('account_name')
            ->get([
                'id',
                'account_name',
                'account_number',
                'bank_name',
                'currency',
                'chapter_id',
            ]);

        // Load categories (for project partnership)
        $this->chapterCategories = PartnershipCategory::query()
            ->where('chapter_id', $this->selectedChapterId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'account_id']);

        // Load events that require partners (for event partnership)
        $this->partnershipEvents = Events::query()
            ->where('chapter_id', $this->selectedChapterId)
            ->where('requires_partners', true)
            ->where('status', 'published')
            ->where(function ($query): void {
                $query->whereNull('partnership_deadline')
                    ->orWhere('partnership_deadline', '>', now());
            })
            ->where('start_at', '>', now())
            ->orderBy('start_at')
            ->get(['id', 'title', 'description', 'start_at', 'end_at']);

        // Load dynamic form fields (only active ones)
        $this->formFields = PartnershipFormField::query()
            ->where(function ($query): void {
                $query->where('chapter_id', $this->selectedChapterId)
                    ->orWhereNull('chapter_id');
            })
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    private function loadEventAccounts(): void
    {
        if (!$this->eventId) {
            $this->eventAccounts = collect();
            return;
        }

        $event = Events::with('accounts')->find($this->eventId);
        
        if ($event) {
            $this->eventAccounts = $event->accounts->map(function ($account) {
                return [
                    'id' => $account->id,
                    'account_name' => $account->account_name,
                    'account_number' => $account->account_number,
                    'bank_name' => $account->bank_name,
                    'currency' => $account->currency,
                ];
            });
            
            // Auto-select if only one account
            if ($this->eventAccounts->count() === 1 && !$this->accountId) {
                $this->accountId = $this->eventAccounts->first()['id'];
            }
        }
    }
}; ?>

<div class="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <section class="overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-[0_24px_60px_-40px_rgba(37,99,235,0.45)]">
        <div class="bg-gradient-to-r from-blue-600 to-blue-500 px-6 py-12 text-white sm:px-10">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-blue-100">Partnership</p>
            <h1 class="mt-3 text-3xl font-bold sm:text-4xl">Partnership Form</h1>
            <p class="mt-3 max-w-3xl text-sm text-blue-100 sm:text-base">
                Partner with us through commission giving, event sponsorship, or project support.
            </p>
        </div>

        <div class="px-6 py-8 sm:px-10">
            @if (session()->has('message'))
                <div class="mb-6 rounded-2xl border px-4 py-3 text-sm {{ session('messageType', 'success') === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700' }}">
                    {{ session('message') }}
                </div>
            @endif

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                <!-- Left Sidebar - Accounts & Events -->
                @if($selectedChapterId)
                    <div class="lg:col-span-3 space-y-4">
                        <!-- Available Accounts (Commission) -->
                        <div class="rounded-2xl border border-blue-100 bg-blue-50/50 p-4">
                            <h3 class="mb-3 text-sm font-semibold text-slate-900">
                                <i class="fas fa-university mr-2 text-blue-600"></i>
                                Available Accounts
                            </h3>
                            <p class="mb-3 text-xs text-slate-500">For commission/general partnership</p>
                            <div class="space-y-2">
                                @forelse($chapterAccounts as $account)
                                    <button
                                        type="button"
                                        wire:click="$set('accountId', {{ $account->id }}); $set('partnershipType', 'commission')"
                                        class="w-full rounded-xl border px-3 py-2 text-left text-xs transition {{ $accountId == $account->id && $partnershipType === 'commission' ? 'border-blue-500 bg-blue-100 text-blue-700' : 'border-transparent bg-white text-slate-700 hover:bg-blue-50' }}"
                                    >
                                        <div class="font-medium">{{ $account->account_name }}</div>
                                        <div class="text-xs text-slate-500">{{ $account->bank_name }} • {{ $account->currency }}</div>
                                    </button>
                                @empty
                                    <p class="text-xs text-slate-500">No accounts available</p>
                                @endforelse
                            </div>
                        </div>

                        <!-- Events Requiring Partners -->
                        @if($partnershipEvents->count() > 0)
                            <div class="rounded-2xl border border-blue-100 bg-blue-50/50 p-4">
                                <h3 class="mb-3 text-sm font-semibold text-slate-900">
                                    <i class="fas fa-calendar-alt mr-2 text-blue-600"></i>
                                    Events Partnership
                                </h3>
                                <p class="mb-3 text-xs text-slate-500">Sponsor upcoming events</p>
                                <div class="space-y-2">
                                    @foreach($partnershipEvents as $event)
                                        <button
                                            type="button"
                                            wire:click="$set('eventId', {{ $event->id }}); $set('partnershipType', 'event')"
                                            class="w-full rounded-xl border px-3 py-2 text-left text-xs transition {{ $eventId == $event->id && $partnershipType === 'event' ? 'border-blue-500 bg-blue-100 text-blue-700' : 'border-transparent bg-white text-slate-700 hover:bg-blue-50' }}"
                                        >
                                            <div class="font-medium">{{ Str::limit($event->title, 40) }}</div>
                                            <div class="text-xs text-slate-500">{{ $event->start_at->format('M d, Y') }}</div>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Main Form Area -->
                <div class="{{ $selectedChapterId ? 'lg:col-span-6' : 'lg:col-span-12' }}">
                    <form class="space-y-6" wire:submit.prevent="submit">
                        <!-- Chapter Selection (Always Required) -->
                        <div>
                            <label for="selected_chapter_id" class="mb-2 block text-sm font-medium text-slate-700">Select Chapter</label>
                            <select
                                id="selected_chapter_id"
                                wire:model.live="selectedChapterId"
                                class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                            >
                                <option value="">Select chapter</option>
                                @foreach($chapters as $chapterOption)
                                    <option value="{{ $chapterOption->id }}">{{ $chapterOption->name }}</option>
                                @endforeach
                            </select>
                            @error('selectedChapterId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        @if($selectedChapterId)
                            <!-- Partnership Type Selection -->
                            <div class="rounded-2xl border border-blue-100 bg-blue-50/50 p-5">
                                <h3 class="mb-4 text-sm font-semibold text-slate-900">
                                    <i class="fas fa-hand-holding-heart mr-2 text-blue-600"></i>
                                    Partnership Type *
                                </h3>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                    <button
                                        type="button"
                                        wire:click="$set('partnershipType', 'commission')"
                                        class="rounded-xl border-2 px-4 py-3 text-center text-sm font-medium transition {{ $partnershipType === 'commission' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-200 bg-white text-slate-600 hover:border-blue-300' }}"
                                    >
                                        <i class="fas fa-church block mb-1 text-lg"></i>
                                        Commission
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="$set('partnershipType', 'event')"
                                        class="rounded-xl border-2 px-4 py-3 text-center text-sm font-medium transition {{ $partnershipType === 'event' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-200 bg-white text-slate-600 hover:border-blue-300' }}"
                                        @if($partnershipEvents->count() === 0) disabled @endif
                                    >
                                        <i class="fas fa-calendar-alt block mb-1 text-lg"></i>
                                        Event
                                        @if($partnershipEvents->count() === 0)
                                            <span class="text-xs text-slate-400">(None available)</span>
                                        @endif
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="$set('partnershipType', 'project')"
                                        class="rounded-xl border-2 px-4 py-3 text-center text-sm font-medium transition {{ $partnershipType === 'project' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-200 bg-white text-slate-600 hover:border-blue-300' }}"
                                    >
                                        <i class="fas fa-project-diagram block mb-1 text-lg"></i>
                                        Project
                                    </button>
                                </div>
                            </div>

                            <!-- Event Selection (if Event partnership type) -->
                            @if($partnershipType === 'event' && $partnershipEvents->count() > 0)
                                <div class="rounded-2xl border border-blue-100 bg-blue-50/50 p-5">
                                    <h3 class="mb-4 text-sm font-semibold text-slate-900">
                                        <i class="fas fa-calendar-check mr-2 text-blue-600"></i>
                                        Select Event *
                                    </h3>
                                    <select
                                        wire:model.live="eventId"
                                        class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                                    >
                                        <option value="">Choose an event...</option>
                                        @foreach($partnershipEvents as $event)
                                            <option value="{{ $event->id }}">{{ $event->title }} ({{ $event->start_at->format('M d, Y') }})</option>
                                        @endforeach
                                    </select>
                                    @error('eventId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    
                                    @if($eventId)
                                        @php
                                            $selectedEvent = $partnershipEvents->firstWhere('id', $eventId);
                                        @endphp
                                        @if($selectedEvent)
                                            <div class="mt-4 rounded-xl border border-blue-200 bg-white p-4">
                                                <h4 class="font-semibold text-blue-700">{{ $selectedEvent->title }}</h4>
                                                <p class="mt-2 text-sm text-slate-600">{{ Str::limit($selectedEvent->description, 150) ?? 'No description available.' }}</p>
                                                <div class="mt-3 flex items-center gap-4 text-xs text-slate-500">
                                                    <span><i class="fas fa-clock mr-1"></i>{{ $selectedEvent->start_at->format('F d, Y') }}</span>
                                                    @if($selectedEvent->end_at)
                                                        <span><i class="fas fa-flag-checkered mr-1"></i>{{ $selectedEvent->end_at->format('F d, Y') }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            @endif

                            <!-- Account Selection (for Event partnership) -->
                            @if($partnershipType === 'event' && $eventId && $eventAccounts->count() > 0)
                                <div class="rounded-2xl border border-blue-100 bg-blue-50/50 p-5">
                                    <h3 class="mb-4 text-sm font-semibold text-slate-900">
                                        <i class="fas fa-university mr-2 text-blue-600"></i>
                                        Select Contribution Account *
                                    </h3>
                                    <div class="space-y-2">
                                        @foreach($eventAccounts as $account)
                                            <button
                                                type="button"
                                                wire:click="$set('accountId', {{ $account['id'] }})"
                                                class="w-full rounded-xl border px-3 py-3 text-left text-sm transition {{ $accountId == $account['id'] ? 'border-blue-500 bg-blue-100 text-blue-700' : 'border-transparent bg-white text-slate-700 hover:bg-blue-50' }}"
                                            >
                                                <div class="font-medium">{{ $account['account_name'] }}</div>
                                                <div class="text-xs text-slate-500">{{ $account['bank_name'] }} • {{ $account['account_number'] }} • {{ $account['currency'] }}</div>
                                            </button>
                                        @endforeach
                                    </div>
                                    @error('accountId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            @endif

                            <!-- Project Category Selection (if Project partnership type) -->
                            @if($partnershipType === 'project' && $chapterCategories->count() > 0)
                                <div class="rounded-2xl border border-blue-100 bg-blue-50/50 p-5">
                                    <h3 class="mb-4 text-sm font-semibold text-slate-900">
                                        <i class="fas fa-th-list mr-2 text-blue-600"></i>
                                        Select Project Category *
                                    </h3>
                                    <div class="space-y-2">
                                        @foreach($chapterCategories as $category)
                                            <button
                                                type="button"
                                                wire:click="$set('categoryId', {{ $category->id }})"
                                                class="w-full rounded-xl border px-3 py-3 text-left text-sm transition {{ $categoryId == $category->id && $partnershipType === 'project' ? 'border-blue-500 bg-blue-100 text-blue-700' : 'border-transparent bg-white text-slate-700 hover:bg-blue-50' }}"
                                            >
                                                <div class="font-medium">{{ $category->name }}</div>
                                                @if($category->description)
                                                    <div class="mt-1 text-xs text-slate-500">{{ Str::limit($category->description, 80) }}</div>
                                                @endif
                                            </button>
                                        @endforeach
                                    </div>
                                    @error('categoryId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            @endif

                            <!-- Account Selection (for Commission partnership) -->
                            @if($partnershipType === 'commission' && $chapterAccounts->count() > 0 && !$accountId)
                                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                                    <h3 class="mb-4 text-sm font-semibold text-amber-800">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>
                                        Select Account
                                    </h3>
                                    <p class="mb-3 text-sm text-amber-700">Please select an account from the left sidebar or click one below:</p>
                                    <div class="space-y-2">
                                        @foreach($chapterAccounts as $account)
                                            <button
                                                type="button"
                                                wire:click="$set('accountId', {{ $account->id }})"
                                                class="w-full rounded-xl border px-3 py-3 text-left text-sm transition {{ $accountId == $account->id ? 'border-blue-500 bg-blue-100 text-blue-700' : 'border-transparent bg-white text-slate-700 hover:bg-blue-50' }}"
                                            >
                                                <div class="font-medium">{{ $account->account_name }}</div>
                                                <div class="text-xs text-slate-500">{{ $account->bank_name }} • {{ $account->currency }}</div>
                                            </button>
                                        @endforeach
                                    </div>
                                    @error('accountId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            @endif
                            <!-- User Information (auto-filled for logged in users) -->
                            <div class="rounded-2xl border border-blue-100 bg-blue-50/50 p-5">
                                <h3 class="mb-4 text-sm font-semibold text-slate-900">
                                    <i class="fas fa-user mr-2 text-blue-600"></i>
                                    Your Information
                                </h3>
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <label for="name" class="mb-2 block text-sm font-medium text-slate-700">Name</label>
                                        <input id="name" type="text" wire:model="name" class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" />
                                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="email" class="mb-2 block text-sm font-medium text-slate-700">Email</label>
                                        <input id="email" type="email" wire:model="email" class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" />
                                        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Dynamic Form Fields (Built by Admin) - ONLY for Commission -->
                            @if($partnershipType === 'commission' && $formFields->count() > 0)
                                <div class="rounded-2xl border border-blue-100 bg-blue-50/50 p-5">
                                    <h3 class="mb-4 text-sm font-semibold text-slate-900">
                                        <i class="fas fa-file-alt mr-2 text-blue-600"></i>
                                        Partnership Details
                                    </h3>

                                    @foreach($formFields as $field)
                                        <div class="mb-4">
                                            <label for="{{ $field->name }}" class="mb-2 block text-sm font-medium text-slate-700">
                                                {{ $field->label }}
                                                @if($field->is_required)
                                                    <span class="text-red-500">*</span>
                                                @endif
                                            </label>

                                            @if($field->description)
                                                <p class="mb-2 text-xs text-slate-500">{{ $field->description }}</p>
                                            @endif

                                            @switch($field->type)
                                                @case('textarea')
                                                    <textarea
                                                        id="{{ $field->name }}"
                                                        wire:model="dynamicFieldValues.{{ $field->name }}"
                                                        placeholder="{{ $field->placeholder ?? '' }}"
                                                        @if($field->is_required) required @endif
                                                        class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                                                    ></textarea>
                                                    @break
                                                @case('select')
                                                    <select
                                                        id="{{ $field->name }}"
                                                        wire:model="dynamicFieldValues.{{ $field->name }}"
                                                        @if($field->is_required) required @endif
                                                        class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                                                    >
                                                        <option value="">Select...</option>
                                                        @foreach($field->options_array ?? [] as $option)
                                                            <option value="{{ $option }}">{{ $option }}</option>
                                                        @endforeach
                                                    </select>
                                                    @break
                                                @case('checkbox')
                                                    <label class="inline-flex items-center gap-2">
                                                        <input
                                                            type="checkbox"
                                                            id="{{ $field->name }}"
                                                            wire:model="dynamicFieldValues.{{ $field->name }}"
                                                            @if($field->is_required) required @endif
                                                            class="h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-500"
                                                        />
                                                        <span class="text-sm text-slate-700">{{ $field->placeholder ?? 'Check this box' }}</span>
                                                    </label>
                                                    @break
                                                @default
                                                    <input
                                                        type="{{ $field->type }}"
                                                        id="{{ $field->name }}"
                                                        wire:model="dynamicFieldValues.{{ $field->name }}"
                                                        placeholder="{{ $field->placeholder ?? '' }}"
                                                        @if($field->is_required) required @endif
                                                        class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                                                    />
                                            @endswitch
                                            @error('dynamicFieldValues.' . $field->name) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                    @endforeach
                                </div>
                            @elseif($partnershipType === 'commission')
                                <div class="rounded-2xl border border-dashed border-amber-200 bg-amber-50 p-6 text-center">
                                    <p class="text-sm text-amber-800">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        No form fields have been configured yet. Please contact the administrator to set up the partnership form.
                                    </p>
                                </div>
                            @else
                                <!-- Message for Event/Project types -->
                                <div class="rounded-2xl border border-dashed border-blue-200 bg-blue-50 p-6 text-center">
                                    <p class="text-sm text-blue-800">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        Custom form fields are only required for Commission partnerships. For Event and Project partnerships, your selection above is sufficient.
                                    </p>
                                </div>
                            @endif

                            <!-- Submit Button -->
                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 focus:ring-2 focus:ring-blue-200">
                                <i class="fas fa-paper-plane mr-2"></i>
                                Submit Partnership Intent
                            </button>
                        @endif
                    </form>
                </div>

                <!-- Right Sidebar - Selected Summary -->
                @if($selectedChapterId)
                    <div class="lg:col-span-3 space-y-4">
                        <!-- Selected Partnership Summary -->
                        <div class="rounded-2xl border border-blue-100 bg-gradient-to-br from-blue-50 to-blue-100/50 p-4">
                            <h3 class="mb-3 text-sm font-semibold text-slate-900">
                                <i class="fas fa-clipboard-check mr-2 text-blue-600"></i>
                                Your Selection
                            </h3>
                            
                            @if($partnershipType === 'commission' && $accountId)
                                <div class="space-y-2 text-xs">
                                    <div class="flex items-center gap-2">
                                        <span class="rounded bg-blue-100 px-2 py-1 font-medium text-blue-700">Commission</span>
                                    </div>
                                    @php
                                        $selectedAccount = $chapterAccounts->firstWhere('id', $accountId);
                                    @endphp
                                    @if($selectedAccount)
                                        <div class="rounded-lg bg-white p-2">
                                            <div class="font-medium text-slate-700">{{ $selectedAccount->account_name }}</div>
                                            <div class="text-slate-500">{{ $selectedAccount->bank_name }}</div>
                                            <div class="text-slate-500">{{ $selectedAccount->currency }}</div>
                                        </div>
                                    @endif
                                </div>
                            @elseif($partnershipType === 'event' && $eventId && $accountId)
                                <div class="space-y-2 text-xs">
                                    <div class="flex items-center gap-2">
                                        <span class="rounded bg-blue-100 px-2 py-1 font-medium text-blue-700">Event</span>
                                    </div>
                                    @php
                                        $selectedEvent = $partnershipEvents->firstWhere('id', $eventId);
                                        $selectedAccount = $eventAccounts->firstWhere('id', $accountId);
                                    @endphp
                                    @if($selectedEvent)
                                        <div class="rounded-lg bg-white p-2">
                                            <div class="font-medium text-slate-700">{{ Str::limit($selectedEvent->title, 35) }}</div>
                                            <div class="text-slate-500">{{ $selectedEvent->start_at->format('M d, Y') }}</div>
                                        </div>
                                    @endif
                                    @if($selectedAccount)
                                        <div class="rounded-lg bg-white p-2">
                                            <div class="font-medium text-slate-700">{{ $selectedAccount['account_name'] }}</div>
                                            <div class="text-slate-500">{{ $selectedAccount['bank_name'] }}</div>
                                        </div>
                                    @endif
                                </div>
                            @elseif($partnershipType === 'project' && $categoryId)
                                <div class="space-y-2 text-xs">
                                    <div class="flex items-center gap-2">
                                        <span class="rounded bg-blue-100 px-2 py-1 font-medium text-blue-700">Project</span>
                                    </div>
                                    @php
                                        $selectedCategory = $chapterCategories->firstWhere('id', $categoryId);
                                    @endphp
                                    @if($selectedCategory)
                                        <div class="rounded-lg bg-white p-2">
                                            <div class="font-medium text-slate-700">{{ $selectedCategory->name }}</div>
                                            @if($selectedCategory->description)
                                                <div class="text-slate-500">{{ Str::limit($selectedCategory->description, 60) }}</div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @else
                                <p class="text-xs text-slate-500">Select a partnership type and complete your selection to see summary here.</p>
                            @endif
                        </div>

                        <!-- Project Categories (Quick Access) -->
                        @if($chapterCategories->count() > 0 && $partnershipType !== 'project')
                            <div class="rounded-2xl border border-blue-100 bg-blue-50/50 p-4">
                                <h3 class="mb-3 text-sm font-semibold text-slate-900">
                                    <i class="fas fa-th-list mr-2 text-blue-600"></i>
                                    Project Categories
                                </h3>
                                <p class="mb-3 text-xs text-slate-500">Click "Project" above to partner with these</p>
                                <div class="max-h-64 overflow-y-auto space-y-2">
                                    @foreach($chapterCategories as $category)
                                        <div class="rounded-lg border border-slate-200 bg-white p-2 text-xs">
                                            <div class="font-medium text-slate-700">{{ $category->name }}</div>
                                            @if($category->description)
                                                <div class="text-slate-500">{{ Str::limit($category->description, 50) }}</div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </section>
</div>
