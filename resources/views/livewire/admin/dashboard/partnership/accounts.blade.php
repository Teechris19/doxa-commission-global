<?php

use App\Models\Accounts;
use App\Models\Chapter;
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
    public bool $isSuperAdmin = false;

    public int $quantity = 10;
    public ?string $search = null;

    #[Url(keep: true)]
    public ?string $filterRegion = null;

    public ?int $editingId = null;
    public string $account_name = '';
    public string $account_number = '';
    public string $bank_name = '';
    public ?string $bank_code = null;
    public ?string $swift_code = null;
    public ?string $iban = null;
    public string $account_type = 'ministry';
    public string $currency = 'NGN';
    public string $region = '';
    public ?string $country = 'Nigeria';
    public ?string $description = null;
    public bool $is_active = true;
    public bool $accepts_online_payments = false;
    public bool $accepts_international = false;
    public ?float $minimum_amount = null;
    public ?float $maximum_amount = null;
    public ?string $special_instructions = null;
    public ?string $contact_person = null;
    public ?string $contact_email = null;
    public ?string $contact_phone = null;
    public bool $globalChapter = false;
    public bool $globalRegion = false;

    public function mount(): void
    {
        $this->resolveChapterContext();
        $this->isSuperAdmin = (bool) auth()->user()?->hasRole('super-admin');

        if ($this->region === '') {
            $this->region = $this->chapterName ?? '';
        }
    }

    public function with(): array
    {
        $accountsContextQuery = Accounts::query()
            ->where(function ($query): void {
                $query->where('chapter_id', $this->chapterId)
                    ->orWhereNull('chapter_id');
            });

        $accountsQuery = (clone $accountsContextQuery)
            ->when($this->search, function ($query): void {
                $term = '%' . $this->search . '%';
                $query->where(function ($inner) use ($term): void {
                    $inner->where('account_name', 'like', $term)
                        ->orWhere('account_number', 'like', $term)
                        ->orWhere('bank_name', 'like', $term);
                });
            })
            ->when($this->filterRegion, fn($query) => $query->where('region', $this->filterRegion));

        return [
            'accounts' => $accountsQuery
                ->withCount(['events', 'partnershipIntents'])
                ->latest()
                ->paginate($this->quantity, ['*'], 'accountsPage')
                ->withQueryString(),
            'regions' => (clone $accountsContextQuery)
                ->whereNotNull('region')
                ->where('region', '!=', '')
                ->distinct()
                ->orderBy('region')
                ->pluck('region'),
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage('accountsPage');
    }

    public function updatingFilterRegion(): void
    {
        $this->resetPage('accountsPage');
    }

    public function updatedQuantity(): void
    {
        $this->resetPage('accountsPage');
    }

    public function createAccount(): void
    {
        $this->editingId = null;
        $this->resetAccountForm();
    }

    public function editAccount(int $id): void
    {
        $account = $this->findAccessibleAccountOrFail($id);

        if (!$this->isSuperAdmin && $account->chapter_id === null) {
            $this->toast()->error('Not Allowed', 'Only super admins can edit global accounts.')->send();
            return;
        }

        $this->editingId = $account->id;
        $this->account_name = $account->account_name;
        $this->account_number = $account->account_number;
        $this->bank_name = $account->bank_name;
        $this->bank_code = $account->bank_code;
        $this->swift_code = $account->swift_code;
        $this->iban = $account->iban;
        $this->account_type = $account->account_type;
        $this->currency = $account->currency;
        $this->region = $account->region;
        $this->country = $account->country;
        $this->description = $account->description;
        $this->is_active = (bool) $account->is_active;
        $this->accepts_online_payments = (bool) $account->accepts_online_payments;
        $this->accepts_international = (bool) $account->accepts_international;
        $this->minimum_amount = $account->minimum_amount !== null ? (float) $account->minimum_amount : null;
        $this->maximum_amount = $account->maximum_amount !== null ? (float) $account->maximum_amount : null;
        $this->special_instructions = $account->special_instructions;
        $this->contact_person = $account->contact_person;
        $this->contact_email = $account->contact_email;
        $this->contact_phone = $account->contact_phone;
        $this->globalChapter = $this->isSuperAdmin && $account->chapter_id === null;
        $this->globalRegion = $this->isSuperAdmin && strtolower((string) $account->region) === 'global';
    }

    public function saveAccount(): void
    {
        $validated = $this->validate([
            'account_name' => 'required|string|max:255',
            'account_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('accounts', 'account_number')
                    ->where(fn($query) => $query->where('bank_code', $this->bank_code))
                    ->ignore($this->editingId),
            ],
            'bank_name' => 'required|string|max:255',
            'bank_code' => 'nullable|string|max:255',
            'swift_code' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:255',
            'account_type' => 'required|in:checking,savings,business,ministry,donation',
            'currency' => 'required|string|size:3',
            'region' => 'required|string|max:255',
            'country' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'accepts_online_payments' => 'boolean',
            'accepts_international' => 'boolean',
            'minimum_amount' => 'nullable|numeric|min:0',
            'maximum_amount' => 'nullable|numeric|min:0',
            'special_instructions' => 'nullable|string|max:1000',
            'contact_person' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:255',
        ]);

        if (
            $validated['minimum_amount'] !== null
            && $validated['maximum_amount'] !== null
            && $validated['minimum_amount'] > $validated['maximum_amount']
        ) {
            $this->addError('minimum_amount', 'Minimum amount cannot be greater than maximum amount.');
            return;
        }

        $payload = [
            ...$validated,
            'currency' => strtoupper($validated['currency']),
            'chapter_id' => $this->isSuperAdmin && $this->globalChapter ? null : $this->chapterId,
            'region' => $this->isSuperAdmin && $this->globalRegion ? 'Global' : $validated['region'],
            'supported_payment_methods' => [],
        ];

        if ($this->editingId) {
            $account = $this->findAccessibleAccountOrFail($this->editingId);

            if (!$this->isSuperAdmin && $account->chapter_id === null) {
                $this->toast()->error('Not Allowed', 'Only super admins can edit global accounts.')->send();
                return;
            }

            $account->update($payload);
            $this->toast()->success('Updated', 'Account updated successfully.')->send();
        } else {
            Accounts::create($payload);
            $this->toast()->success('Created', 'Account created successfully.')->send();
        }

        $this->resetAccountForm();
        $this->dispatch('$closeModal', 'partnership-account-modal');
    }

    public function deleteAccount(int $id): void
    {
        $this->dialog()
            ->error('Are you sure you want to delete this account?')
            ->hook([
                'ok' => [
                    'method' => 'confirmDeleteAccount',
                    'params' => [$id],
                ],
            ])
            ->send();
    }

    public function confirmDeleteAccount(int $id): void
    {
        $account = $this->findAccessibleAccountOrFail($id);

        if (!$this->isSuperAdmin && $account->chapter_id === null) {
            $this->toast()->error('Not Allowed', 'Only super admins can delete global accounts.')->send();
            return;
        }

        $account->delete();
        $this->toast()->success('Deleted', 'Account removed successfully.')->send();
    }

    private function resetAccountForm(): void
    {
        $this->resetValidation();

        $this->account_name = '';
        $this->account_number = '';
        $this->bank_name = '';
        $this->bank_code = null;
        $this->swift_code = null;
        $this->iban = null;
        $this->account_type = 'ministry';
        $this->currency = 'NGN';
        $this->region = $this->chapterName ?? '';
        $this->country = 'Nigeria';
        $this->description = null;
        $this->is_active = true;
        $this->accepts_online_payments = false;
        $this->accepts_international = false;
        $this->minimum_amount = null;
        $this->maximum_amount = null;
        $this->special_instructions = null;
        $this->contact_person = null;
        $this->contact_email = null;
        $this->contact_phone = null;
        $this->globalChapter = false;
        $this->globalRegion = false;
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

    private function findAccessibleAccountOrFail(int $id): Accounts
    {
        return Accounts::query()
            ->where('id', $id)
            ->where(function ($query): void {
                $query->where('chapter_id', $this->chapterId)
                    ->orWhereNull('chapter_id');
            })
            ->firstOrFail();
    }

    public function updatedGlobalRegion(bool $value): void
    {
        if (!$this->isSuperAdmin) {
            $this->globalRegion = false;
            return;
        }

        if ($value) {
            $this->region = 'Global';
        } elseif (strtolower((string) $this->region) === 'global') {
            $this->region = $this->chapterName ?? '';
        }
    }
}; ?>

<div>
    <x-fancy-header
        title="Partnership Accounts"
        subtitle="Create and manage chapter accounts linked to events and partnership intents"
        :breadcrumbs="[
            ['label' => 'Home', 'url' => route('admin.dashboard', request()->query())],
            ['label' => 'Partnership Accounts']
        ]"
    >
        <div class="flex flex-wrap gap-2">
            <a
                href="{{ route('admin.dashboard.partnership.intents', array_merge(request()->query(), ['chapter' => $chapterName])) }}"
                wire:navigate
                class="rounded-lg border border-blue-200 bg-white px-3 py-2 text-xs font-semibold text-blue-700"
            >
                Intent Management
            </a>
            <button
                type="button"
                x-on:click="$wire.call('createAccount').then(() => $modalOpen('partnership-account-modal'))"
                class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white"
            >
                Add Account
            </button>
        </div>
    </x-fancy-header>

    <div class="mb-6 flex flex-wrap items-center gap-3 text-sm">
        <span class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-blue-700">Chapter: {{ $chapterName }}</span>
        @if($isSuperAdmin)
            <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-emerald-700">Super Admin: You can create global accounts</span>
        @endif
        <a
            href="{{ route('admin.dashboard.partnership.intents', array_merge(request()->query(), ['chapter' => $chapterName])) }}"
            wire:navigate
            class="rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-indigo-700"
        >
            Go to Intent Management
        </a>
    </div>

    <x-card>
        <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Accounts</h2>
                <p class="text-sm text-slate-500">Create and manage chapter accounts linked to events and partnership intents.</p>
            </div>
            <button
                type="button"
                x-on:click="$wire.call('createAccount').then(() => $modalOpen('partnership-account-modal'))"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white"
            >
                Add Account
            </button>
        </div>

        <div class="mb-4 grid grid-cols-1 gap-3 lg:grid-cols-4">
            <input wire:model.live.debounce.300ms="search" type="text" class="rounded-lg border px-3 py-2 text-sm" placeholder="Search account, number, bank" />
            <select wire:model.live="filterRegion" class="rounded-lg border px-3 py-2 text-sm">
                <option value="">All regions</option>
                @foreach($regions as $regionOption)
                    <option value="{{ $regionOption }}">{{ $regionOption }}</option>
                @endforeach
            </select>
            <select wire:model.live="quantity" class="rounded-lg border px-3 py-2 text-sm">
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                <thead class="bg-zinc-50">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700">Account</th>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700">Bank</th>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700">Type</th>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700">Region</th>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700">Linked</th>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700">Status</th>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white">
                    @forelse($accounts as $account)
                        <tr>
                            <td class="px-3 py-3">
                                <p class="font-medium text-zinc-900">{{ $account->account_name }}</p>
                                <p class="text-xs text-zinc-500">{{ $account->account_number }}</p>
                            </td>
                            <td class="px-3 py-3 text-zinc-700">
                                <p>{{ $account->bank_name }}</p>
                                <p class="text-xs text-zinc-500">{{ $account->currency }}</p>
                            </td>
                            <td class="px-3 py-3 text-zinc-700">{{ ucfirst($account->account_type) }}</td>
                            <td class="px-3 py-3 text-zinc-700">
                                <p>{{ $account->region }}</p>
                                <p class="text-xs text-zinc-500">{{ $account->chapter_id === null ? 'Global chapter scope' : 'Chapter scoped' }}</p>
                            </td>
                            <td class="px-3 py-3 text-zinc-700">
                                <p class="text-xs">Events: {{ $account->events_count }}</p>
                                <p class="text-xs">Partnership Intents: {{ $account->partnership_intents_count }}</p>
                            </td>
                            <td class="px-3 py-3">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $account->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-zinc-200 text-zinc-700' }}">
                                    {{ $account->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex flex-wrap gap-2">
                                    @if($account->chapter_id === null && !$isSuperAdmin)
                                        <span class="rounded-md border border-zinc-300 px-2 py-1 text-xs font-medium text-zinc-600">
                                            Super admin only
                                        </span>
                                    @else
                                        <button
                                            type="button"
                                            class="rounded-md border border-blue-200 px-2 py-1 text-xs font-medium text-blue-700"
                                            x-on:click="$wire.call('editAccount', {{ $account->id }}).then(() => $modalOpen('partnership-account-modal'))"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="deleteAccount({{ $account->id }})"
                                            class="rounded-md border border-rose-200 px-2 py-1 text-xs font-medium text-rose-700"
                                        >
                                            Delete
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-5 text-center text-zinc-500">No accounts found for this chapter context.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $accounts->links() }}
        </div>
    </x-card>

    <x-modal id="partnership-account-modal" :title="$editingId ? 'Edit Account' : 'Create Account'" size="4xl">
        <form wire:submit.prevent="saveAccount" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Account Name</label>
                    <input wire:model.lazy="account_name" type="text" class="w-full rounded-lg border px-3 py-2" placeholder="e.g. Doxa Partnership Account" />
                    @error('account_name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Account Number</label>
                    <input wire:model.lazy="account_number" type="text" class="w-full rounded-lg border px-3 py-2" />
                    @error('account_number') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-medium">Bank Name</label>
                    <input wire:model.lazy="bank_name" type="text" class="w-full rounded-lg border px-3 py-2" />
                    @error('bank_name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Bank Code</label>
                    <input wire:model.lazy="bank_code" type="text" class="w-full rounded-lg border px-3 py-2" />
                    @error('bank_code') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">SWIFT Code</label>
                    <input wire:model.lazy="swift_code" type="text" class="w-full rounded-lg border px-3 py-2" />
                    @error('swift_code') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                    <label class="mb-1 block text-sm font-medium">IBAN</label>
                    <input wire:model.lazy="iban" type="text" class="w-full rounded-lg border px-3 py-2" />
                    @error('iban') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Type</label>
                    <select wire:model.lazy="account_type" class="w-full rounded-lg border px-3 py-2">
                        <option value="checking">Checking</option>
                        <option value="savings">Savings</option>
                        <option value="business">Business</option>
                        <option value="ministry">Ministry</option>
                        <option value="donation">Donation</option>
                    </select>
                    @error('account_type') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Currency</label>
                    <input wire:model.lazy="currency" type="text" maxlength="3" class="w-full rounded-lg border px-3 py-2" placeholder="NGN" />
                    @error('currency') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Region</label>
                    <input wire:model.lazy="region" type="text" class="w-full rounded-lg border px-3 py-2" placeholder="Calabar Branch" />
                    @error('region') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            @if($isSuperAdmin)
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-emerald-700">Global Scope</p>
                    <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                        <label class="inline-flex items-center gap-2 text-sm text-emerald-800">
                            <input type="checkbox" wire:model="globalChapter" class="rounded border-zinc-300" />
                            Make chapter global (show in all chapters)
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-emerald-800">
                            <input type="checkbox" wire:model="globalRegion" class="rounded border-zinc-300" />
                            Set region as Global
                        </label>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Country</label>
                    <input wire:model.lazy="country" type="text" class="w-full rounded-lg border px-3 py-2" placeholder="Nigeria" />
                    @error('country') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Contact Person</label>
                    <input wire:model.lazy="contact_person" type="text" class="w-full rounded-lg border px-3 py-2" />
                    @error('contact_person') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Contact Email</label>
                    <input wire:model.lazy="contact_email" type="email" class="w-full rounded-lg border px-3 py-2" />
                    @error('contact_email') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Contact Phone</label>
                    <input wire:model.lazy="contact_phone" type="text" class="w-full rounded-lg border px-3 py-2" />
                    @error('contact_phone') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Minimum Amount</label>
                    <input wire:model.lazy="minimum_amount" type="number" step="0.01" min="0" class="w-full rounded-lg border px-3 py-2" />
                    @error('minimum_amount') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Maximum Amount</label>
                    <input wire:model.lazy="maximum_amount" type="number" step="0.01" min="0" class="w-full rounded-lg border px-3 py-2" />
                    @error('maximum_amount') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Description</label>
                <textarea wire:model.lazy="description" rows="3" class="w-full rounded-lg border px-3 py-2" placeholder="Purpose of this account."></textarea>
                @error('description') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Special Instructions</label>
                <textarea wire:model.lazy="special_instructions" rows="2" class="w-full rounded-lg border px-3 py-2" placeholder="Manual processing/reconciliation guidance."></textarea>
                @error('special_instructions') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model="is_active" class="rounded border-zinc-300" />
                    Account is active
                </label>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model="accepts_online_payments" class="rounded border-zinc-300" />
                    Accepts online payments
                </label>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model="accepts_international" class="rounded border-zinc-300" />
                    Accepts international transfers
                </label>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" x-on:click="$modalClose('partnership-account-modal')" class="rounded-lg border px-4 py-2 text-sm">Cancel</button>
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Save Account</button>
            </div>
        </form>
    </x-modal>
</div>
