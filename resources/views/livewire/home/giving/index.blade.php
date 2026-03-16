<?php

use App\Models\{PartnershipIntent, Accounts, PartnershipCategory, Chapter};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.tailwind-layout')] class extends Component {
    use Interactions, WithFileUploads;

    public $givingHistory = [];
    public $activePledges = [];
    public $accounts = [];
    public $categories = [];
    public $chapters = [];

    public $showDonateModal = false;
    public $selectedAccount = null;
    public $selectedCategory = null;
    public $selectedChapter = null;
    public $donationType = 'one-time';
    public $amount = '';
    public $frequency = 'monthly';
    public $notes = '';

    public function mount()
    {
        if (Auth::check()) {
            $this->loadUserData();
        }
        $this->loadPublicData();
    }

    private function loadUserData()
    {
        $userId = Auth::id();
        
        $this->givingHistory = PartnershipIntent::where('user_id', $userId)
            ->where('status', 'completed')
            ->with(['account', 'category', 'chapter'])
            ->latest('pledged_at')
            ->limit(10)
            ->get();

        $this->activePledges = PartnershipIntent::where('user_id', $userId)
            ->whereIn('status', ['pending', 'reviewing', 'approved'])
            ->with(['account', 'category', 'chapter'])
            ->latest('pledged_at')
            ->get();
    }

    private function loadPublicData()
    {
        $this->accounts = Accounts::active()
            ->acceptsOnlinePayments()
            ->with('chapter')
            ->get();

        $this->categories = PartnershipCategory::where('is_active', true)->get();
        
        $this->chapters = Chapter::where('is_active', true)->get();
    }

    public function openDonateModal()
    {
        $this->showDonateModal = true;
    }

    public function closeDonateModal()
    {
        $this->showDonateModal = false;
        $this->reset(['selectedAccount', 'selectedCategory', 'selectedChapter', 'donationType', 'amount', 'frequency', 'notes']);
    }

    public function saveDonation()
    {
        if (!Auth::check()) {
            return redirect()->route('home.login');
        }

        $this->validate([
            'selectedAccount' => 'required|exists:accounts,id',
            'selectedCategory' => 'required|exists:partnership_categories,id',
            'selectedChapter' => 'required|exists:chapters,id',
            'amount' => 'required|numeric|min:1',
            'frequency' => 'required|in:one-time,weekly,monthly,quarterly,yearly',
        ]);

        $category = PartnershipCategory::find($this->selectedCategory);

        PartnershipIntent::create([
            'user_id' => Auth::id(),
            'chapter_id' => $this->selectedChapter,
            'account_id' => $this->selectedAccount,
            'partnership_category_id' => $this->selectedCategory,
            'intent_type' => $this->donationType === 'one-time' ? 'one-time' : 'recurring',
            'title' => $category?->name . ' - ' . ($this->donationType === 'one-time' ? 'One-time' : 'Recurring'),
            'pledge_amount' => $this->amount,
            'pledge_currency' => 'NGN',
            'pledge_frequency' => $this->donationType === 'one-time' ? 'one-time' : $this->frequency,
            'status' => 'pending',
            'notes' => $this->notes,
            'pledged_at' => now(),
        ]);

        $this->toast()->success('Thank you!', 'Your donation has been submitted. We will contact you with payment details.')->send();
        $this->loadUserData();
        $this->closeDonateModal();
    }

    public function getTotalGivenThisYear()
    {
        if (!Auth::check()) return 0;

        return PartnershipIntent::where('user_id', Auth::id())
            ->where('status', 'completed')
            ->whereYear('pledged_at', now()->year)
            ->sum('pledge_amount');
    }

    public function getTotalPledged()
    {
        if (!Auth::check()) return 0;

        return PartnershipIntent::where('user_id', Auth::id())
            ->whereIn('status', ['pending', 'reviewing', 'approved'])
            ->sum('pledge_amount');
    }
};
?>

<div class="bg-white pb-12">
    <section class="border-b border-amber-100 bg-gradient-to-b from-amber-50 to-white">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8 lg:py-16">
            <div class="max-w-3xl">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-amber-600">Giving</p>
                <h1 class="mt-3 text-3xl font-semibold text-slate-900 sm:text-4xl">Partner with Us</h1>
                <p class="mt-4 text-sm leading-7 text-slate-600">Support the vision of Doxa Commission Global through your generous giving. Every contribution makes a difference in advancing God's kingdom.</p>
            </div>
        </div>
    </section>

    @auth
    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="grid gap-6 md:grid-cols-3">
            <div class="rounded-xl bg-gradient-to-br from-amber-500 to-amber-600 p-6 text-white">
                <p class="text-xs font-semibold uppercase tracking-wider opacity-90">Total Given This Year</p>
                <p class="mt-2 text-3xl font-bold">₦{{ number_format($this->getTotalGivenThisYear(), 2) }}</p>
            </div>
            <div class="rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 p-6 text-white">
                <p class="text-xs font-semibold uppercase tracking-wider opacity-90">Active Pledges</p>
                <p class="mt-2 text-3xl font-bold">₦{{ number_format($this->getTotalPledged(), 2) }}</p>
            </div>
            <div class="rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 p-6 text-white">
                <p class="text-xs font-semibold uppercase tracking-wider opacity-90">Total Transactions</p>
                <p class="mt-2 text-3xl font-bold">{{ count($givingHistory) + count($activePledges) }}</p>
            </div>
        </div>
    </section>
    @endauth

    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8 flex items-center justify-between">
            <h2 class="text-2xl font-semibold text-gray-900">Make a Donation</h2>
            <button wire:click="openDonateModal" class="rounded-lg bg-amber-600 px-6 py-3 font-semibold text-white hover:bg-amber-700">
                Donate Now
            </button>
        </div>

        @if($accounts->isEmpty())
            <div class="rounded-xl border border-gray-200 p-8 text-center">
                <p class="text-gray-500">No donation accounts available at the moment. Please check back later.</p>
            </div>
        @else
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach($accounts as $account)
                    <div class="rounded-xl border border-gray-200 p-6 hover:border-amber-300 hover:shadow-md transition-all">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="font-semibold text-gray-900">{{ $account->account_name }}</h3>
                                <p class="text-sm text-gray-500">{{ $account->bank_name }}</p>
                            </div>
                            <span class="text-2xl">{{ $account->region_flag }}</span>
                        </div>
                        <div class="mt-4 space-y-2 text-sm">
                            <p class="text-gray-600"><span class="font-medium">Account:</span> {{ $account->account_number }}</p>
                            @if($account->swift_code)
                                <p class="text-gray-600"><span class="font-medium">SWIFT:</span> {{ $account->swift_code }}</p>
                            @endif
                        </div>
                        @if($account->special_instructions)
                            <p class="mt-3 text-xs text-gray-500">{{ $account->special_instructions }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    @auth
    @if($activePledges->isNotEmpty())
    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h2 class="mb-6 text-2xl font-semibold text-gray-900">Active Pledges</h2>
        <div class="space-y-4">
            @foreach($activePledges as $pledge)
                <div class="rounded-xl border border-gray-200 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-gray-900">{{ $pledge->title }}</h3>
                            <p class="text-sm text-gray-500">{{ $pledge->category?->name }} • {{ $pledge->chapter?->name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-gray-900">₦{{ number_format($pledge->pledge_amount, 2) }}</p>
                            <p class="text-xs text-gray-500">{{ ucfirst($pledge->pledge_frequency) }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
    @endif

    @if($givingHistory->isNotEmpty())
    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h2 class="mb-6 text-2xl font-semibold text-gray-900">Giving History</h2>
        <div class="rounded-xl border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Account</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @foreach($givingHistory as $gift)
                        <tr>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ $gift->pledged_at->format('M d, Y') }}</td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-medium text-gray-900">{{ $gift->title }}</p>
                                <p class="text-xs text-gray-500">{{ $gift->category?->name }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $gift->account?->account_name }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-gray-900">₦{{ number_format($gift->pledge_amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
    @endif
    @endauth

    @if($showDonateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" wire:click="closeDonateModal">
            <div class="bg-white rounded-xl max-w-lg w-full max-h-[90vh] overflow-y-auto p-6" wire:click.stop>
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-semibold text-gray-900">Make a Donation</h3>
                    <button wire:click="closeDonateModal" class="text-gray-400 hover:text-gray-600">
                        <i class="bi bi-x-lg text-xl"></i>
                    </button>
                </div>

                <form wire:submit.prevent="saveDonation" class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Select Chapter</label>
                        <select wire:model="selectedChapter" class="w-full rounded-lg border px-3 py-2">
                            <option value="">Select chapter</option>
                            @foreach($chapters as $chapter)
                                <option value="{{ $chapter->id }}">{{ $chapter->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Donation Type</label>
                        <div class="flex gap-4">
                            <label class="flex items-center">
                                <input type="radio" wire:model="donationType" value="one-time" class="mr-2">
                                One-time
                            </label>
                            <label class="flex items-center">
                                <input type="radio" wire:model="donationType" value="recurring" class="mr-2">
                                Recurring
                            </label>
                        </div>
                    </div>

                    @if($donationType === 'recurring')
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Frequency</label>
                        <select wire:model="frequency" class="w-full rounded-lg border px-3 py-2">
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                    </div>
                    @endif

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Category</label>
                        <select wire:model="selectedCategory" class="w-full rounded-lg border px-3 py-2">
                            <option value="">Select category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Amount (NGN)</label>
                        <input wire:model="amount" type="number" min="1" step="0.01" class="w-full rounded-lg border px-3 py-2" placeholder="Enter amount">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Account</label>
                        <select wire:model="selectedAccount" class="w-full rounded-lg border px-3 py-2">
                            <option value="">Select account</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->account_name }} - {{ $account->bank_name }} ({{ $account->account_number }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Notes (optional)</label>
                        <textarea wire:model="notes" rows="2" class="w-full rounded-lg border px-3 py-2" placeholder="Add a note"></textarea>
                    </div>

                    <button type="submit" class="w-full rounded-lg bg-amber-600 px-4 py-3 font-semibold text-white hover:bg-amber-700">
                        Submit Donation
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>
