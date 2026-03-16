<?php

use App\Models\{FinanceReport, Finance, Chapter};
use Livewire\Attributes\{Layout, Url};
use Livewire\Volt\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions, WithPagination;

    #[Url(keep: true)]
    public $chapter;

    public $chapterId;

    public function mount()
    {
        $user = Auth::user();

        // Set chapter filter
        if ($this->chapter) {
            $chapterModel = Chapter::where('name', $this->chapter)->first();
            $this->chapterId = $chapterModel?->id;
        } elseif (!$user->hasRole('super-admin')) {
            $this->chapterId = $user->chapter_id;
        }
    }

    public function getReports()
    {
        $query = FinanceReport::with(['finance', 'team', 'chapter', 'user'])
            ->when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId));

        return $query->latest('date')->paginate(15);
    }

    public function with()
    {
        return [
            'headers' => [
                ['index' => 'title', 'label' => 'Title'],
                ['index' => 'date', 'label' => 'Date'],
                ['index' => 'team.name', 'label' => 'Team'],
                ['index' => 'status', 'label' => 'Status'],
                ['index' => 'user.name', 'label' => 'Created By'],
                ['index' => 'actions', 'label' => 'Actions']
            ],
            'rows' => $this->getReports(),
        ];
    }

    public function deleteReport($id)
    {
        $report = FinanceReport::findOrFail($id);

        // Check permissions
        if (!Auth::user()->hasRole('admin') && $report->user_id !== Auth::id()) {
            $this->toast()->error('Unauthorized', 'You cannot delete this report')->send();
            return;
        }

        $report->delete();
        $this->toast()->success('Deleted', 'Finance report deleted successfully')->send();
    }
}; ?>

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">Finance Reports</h1>
            <p class="text-zinc-600 dark:text-zinc-400 mt-1">Manage financial reports and analytics</p>
        </div>
        <a href="{{ route('admin.dashboard.finance.reports.create') }}" wire:navigate>
            <x-button color="primary" icon="plus" label="Create Report" />
        </a>
    </div>

    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow">
        <x-table :$headers :$rows>
            @interact('column_date', $row)
                {{ $row->date->format('M d, Y') }}
            @endinteract

            @interact('column_status', $row)
                @php
                    $colors = [
                        'draft' => 'bg-gray-500 text-white',
                        'submitted' => 'bg-blue-500 text-white',
                        'approved' => 'bg-green-500 text-white',
                    ];
                    $badgeClass = 'px-3 py-1 rounded-full text-xs font-semibold ' . ($colors[$row->status] ?? 'bg-gray-400 text-white');
                @endphp
                <span class="{{ $badgeClass }}">{{ ucfirst($row->status) }}</span>
            @endinteract

            @interact('column_actions', $row)
                <div class="flex gap-2">
                    <a href="{{ route('admin.dashboard.finance.reports.view', ['id' => $row->id]) }}" wire:navigate>
                        <x-button.circle color="primary" icon="eye" />
                    </a>
                    <x-button.circle color="red" icon="trash" wire:click="deleteReport({{ $row->id }})" />
                </div>
            @endinteract
        </x-table>
    </div>
</div>
