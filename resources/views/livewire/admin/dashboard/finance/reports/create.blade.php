<?php

use App\Models\{FinanceReport, Finance, Team};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use TallStackUi\Traits\Interactions;
use Illuminate\Support\Facades\{Auth, DB};
use Carbon\Carbon;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions;

    public $title;
    public $summary;
    public $date;
    public $finance_id;
    public $status = 'draft';
    public $notes;

    public $finances = [];
    public $teamId;
    public $chapterId;

    // Analytics data
    public $totalIncome = 0;
    public $totalExpenses = 0;
    public $netBalance = 0;
    public $transactionCount = 0;

    public function mount()
    {
        $user = Auth::user();
        $this->chapterId = $user->chapter_id;

        // Get user's team if team lead
        $leadersTeam = $user->teams->filter(fn($team) =>
            in_array($team->pivot->role_in_team, ['team-lead', 'lead-assist'])
        )->first();

        $this->teamId = $leadersTeam?->id;
        $this->date = now()->format('Y-m-d');

        $this->loadFinances();
        $this->calculateAnalytics();
    }

    public function loadFinances()
    {
        $this->finances = Finance::when($this->teamId, fn($q) => $q->where('team_id', $this->teamId))
            ->when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->latest()
            ->get();
    }

    public function calculateAnalytics()
    {
        $query = Finance::when($this->teamId, fn($q) => $q->where('team_id', $this->teamId))
            ->when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->whereMonth('date', Carbon::parse($this->date)->month)
            ->whereYear('date', Carbon::parse($this->date)->year);

        $this->totalIncome = (clone $query)->where('type', 'income')->sum('amount');
        $this->totalExpenses = (clone $query)->where('type', 'expense')->sum('amount');
        $this->netBalance = $this->totalIncome - $this->totalExpenses;
        $this->transactionCount = $query->count();
    }

    public function save()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'summary' => 'nullable|string',
            'date' => 'required|date',
            'finance_id' => 'nullable|exists:finances,id',
            'status' => 'required|in:draft,submitted,approved',
            'notes' => 'nullable|string',
        ]);

        try {
            FinanceReport::create([
                'finance_id' => $this->finance_id ?: Finance::first()?->id,
                'title' => $this->title,
                'summary' => $this->summary,
                'date' => $this->date,
                'team_id' => $this->teamId,
                'chapter_id' => $this->chapterId,
                'user_id' => Auth::id(),
                'status' => $this->status,
                'notes' => $this->notes,
            ]);

            $this->toast()->success('Success', 'Finance report created successfully')->send();
            return redirect()->route('admin.dashboard.finance.reports.index');

        } catch (\Exception $e) {
            $this->toast()->error('Error', 'Failed to create report: ' . $e->getMessage())->send();
        }
    }
}; ?>

<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">Create Finance Report</h1>
        <p class="text-zinc-600 dark:text-zinc-400 mt-1">Generate a financial report with analytics</p>
    </div>

    <!-- Analytics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white">
            <p class="text-green-100 text-sm">Total Income</p>
            <h3 class="text-2xl font-bold mt-1">₦{{ number_format($totalIncome, 2) }}</h3>
        </div>
        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-6 text-white">
            <p class="text-red-100 text-sm">Total Expenses</p>
            <h3 class="text-2xl font-bold mt-1">₦{{ number_format($totalExpenses, 2) }}</h3>
        </div>
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white">
            <p class="text-blue-100 text-sm">Net Balance</p>
            <h3 class="text-2xl font-bold mt-1">₦{{ number_format($netBalance, 2) }}</h3>
        </div>
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white">
            <p class="text-purple-100 text-sm">Transactions</p>
            <h3 class="text-2xl font-bold mt-1">{{ number_format($transactionCount) }}</h3>
        </div>
    </div>

    <!-- Report Form -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
        <form wire:submit.prevent="save" class="space-y-4">
            <x-input label="Report Title" wire:model="title" required />

            <x-textarea label="Summary" wire:model="summary" rows="4"
                hint="Provide a summary of the financial activities" />

            <x-input type="date" label="Report Date" wire:model.live="date" required />

            <x-select label="Related Finance Transaction (Optional)" wire:model="finance_id">
                <option value="">Select Transaction</option>
                @foreach($finances as $finance)
                    <option value="{{ $finance->id }}">
                        {{ $finance->description }} - ₦{{ number_format($finance->amount, 2) }}
                    </option>
                @endforeach
            </x-select>

            <x-select label="Status" wire:model="status" required>
                <option value="draft">Draft</option>
                <option value="submitted">Submitted</option>
                <option value="approved">Approved</option>
            </x-select>

            <x-textarea label="Notes" wire:model="notes" rows="3" />

            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.dashboard.finance.reports.index') }}" wire:navigate>
                    <x-button color="secondary" label="Cancel" />
                </a>
                <x-button type="submit" color="primary" label="Create Report" />
            </div>
        </form>
    </div>
</div>
