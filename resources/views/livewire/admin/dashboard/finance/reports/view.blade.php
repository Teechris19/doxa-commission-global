<?php

use App\Models\FinanceReport;
use Livewire\Attributes\{Layout, Url};
use Livewire\Volt\Component;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions;

    #[Url]
    public $id;

    public $report;

    public function mount()
    {
        $this->report = FinanceReport::with(['finance', 'team', 'chapter', 'user'])->findOrFail($this->id);
    }
}; ?>

<div class="space-y-6">
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h1 class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">{{ $report->title }}</h1>
                <p class="text-zinc-600 dark:text-zinc-400 mt-1">{{ $report->date->format('F d, Y') }}</p>
            </div>
            <span class="px-4 py-2 rounded-full text-sm font-semibold
                {{ $report->status === 'approved' ? 'bg-green-500 text-white' : ($report->status === 'submitted' ? 'bg-blue-500 text-white' : 'bg-gray-500 text-white') }}">
                {{ ucfirst($report->status) }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Team</p>
                <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $report->team->name ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Chapter</p>
                <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $report->chapter->name ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Created By</p>
                <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $report->user->name ?? 'N/A' }}</p>
            </div>
        </div>

        @if($report->summary)
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-2">Summary</h3>
                <p class="text-zinc-700 dark:text-zinc-300">{{ $report->summary }}</p>
            </div>
        @endif

        @if($report->notes)
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-2">Notes</h3>
                <p class="text-zinc-700 dark:text-zinc-300">{{ $report->notes }}</p>
            </div>
        @endif

        <div class="flex justify-between items-center pt-4 border-t border-zinc-200 dark:border-zinc-700">
            <a href="{{ route('admin.dashboard.finance.reports.index') }}" wire:navigate>
                <x-button color="secondary" label="Back to Reports" />
            </a>
        </div>
    </div>
</div>
