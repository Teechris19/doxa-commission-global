<?php

use App\Models\{AttendanceReport, Attendance, Chapter};
use Livewire\Attributes\{Layout, Url};
use Livewire\Volt\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions, WithPagination;

    #[Url(keep: true)]
    public $chapter;

    public function getReports()
    {
        $user = Auth::user();
        $chapterId = $this->chapter ? Chapter::where('name', $this->chapter)->first()?->id : $user->chapter_id;

        return AttendanceReport::with(['attendance', 'team', 'chapter', 'user'])
            ->when($chapterId, fn($q) => $q->where('chapter_id', $chapterId))
            ->latest('date')
            ->paginate(15);
    }

    public function with()
    {
        return [
            'headers' => [
                ['index' => 'title', 'label' => 'Title'],
                ['index' => 'date', 'label' => 'Date'],
                ['index' => 'team.name', 'label' => 'Team'],
                ['index' => 'status', 'label' => 'Status'],
                ['index' => 'actions', 'label' => 'Actions']
            ],
            'rows' => $this->getReports(),
        ];
    }

    public function deleteReport($id)
    {
        AttendanceReport::findOrFail($id)->delete();
        $this->toast()->success('Deleted', 'Attendance report deleted')->send();
    }
}; ?>

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold">Attendance Reports</h1>
            <p class="text-zinc-600 dark:text-zinc-400">Manage attendance reports and analytics</p>
        </div>
    </div>

    <x-table :$headers :$rows>
        @interact('column_date', $row)
            {{ $row->date->format('M d, Y') }}
        @endinteract

        @interact('column_status', $row)
            <span class="px-3 py-1 rounded-full text-xs font-semibold
                {{ $row->status === 'approved' ? 'bg-green-500' : ($row->status === 'submitted' ? 'bg-blue-500' : 'bg-gray-500') }} text-white">
                {{ ucfirst($row->status) }}
            </span>
        @endinteract

        @interact('column_actions', $row)
            <x-button.circle color="red" icon="trash" wire:click="deleteReport({{ $row->id }})" />
        @endinteract
    </x-table>
</div>
