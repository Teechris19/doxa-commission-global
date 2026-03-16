<?php

use App\Models\Chapter;
use App\Models\Report;
use App\Models\User;
use App\Notifications\ReportEscalated;
use Livewire\Attributes\{Layout, Url};
use Livewire\Volt\Component;
use TallStackUi\Traits\Interactions;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions;

    #[Url(keep:true)]
    public $chapter;

    public $leadersTeam;

    public function mount()
    {
        // Get logged-in user's lead role team (if any)
        $this->leadersTeam = Auth::user()->teams->filter(fn($team) => in_array($team->pivot->role_in_team, ['team-lead', 'lead-assist']))->first();
    }
    public function getReports()
    {
        $query = Report::with(['createdBy', 'team']);
        $user = Auth::user();

        // Team-lead / lead-assist (non-admin): see ALL their team's reports (any level)
        $query->when($this->leadersTeam && !$user->hasRole('admin'), function ($q) {
            $q->where('team_id', $this->leadersTeam->id);
        });

        // Admin: see everything
        $query->when($user->hasRole('admin'), function ($q) {
            // If you want admins to see all reports:
            // nothing needed, they already do
            // If you want them limited to their team + chapter, uncomment:
            /*
        if ($this->leadersTeam) {
            $q->where(function ($sub) {
                $sub->where('team_id', $this->leadersTeam->id)
                    ->orWhere('level', 'chapter');
            });
        }
        */
        });

        // Optional filter by chapter param
        $query->when($this->chapter, function ($q) {
            $chapter = Chapter::where('name', e($this->chapter))->firstOrFail();
            $q->where('chapter_id', $chapter->id);
        });

        return $query->latest()->paginate(10);
    }

    public function with()
    {
        return [
            'headers' => [['index' => 'title', 'label' => 'Title'], ['index' => 'report_date', 'label' => 'Date'], ['index' => 'team.name', 'label' => 'Team'], ['index' => 'level', 'label' => 'Report Level'], ['index' => 'createdBy.name', 'label' => 'Created By'], ['index' => 'actions', 'label' => 'Actions']],
            'rows' => $this->getReports(),
            'branch'=> $this->chapter
        ];
    }

    public function changeLevel(int $id, string $level)
    {
        $report = Report::findOrFail($id);
        $user = Auth::user();

        $nextLevels = [
            'team' => 'chapter',
            'chapter' => 'hq',
        ];

        $currentLevel = $report->level;
        $allowedNext = $nextLevels[$currentLevel] ?? null;

        // 🚨 Validation: Stop invalid or backward changes
        if ($allowedNext !== $level) {
            $this->toast()->error('Not allowed', 'You cannot change report level this way ❌')->send();
            return;
        }

        // 🚨 Validation: Check if report has required content
        if (empty($report->report) && empty($report->report_path)) {
            $this->toast()->error('Invalid Report', 'Report must have content before escalation ❌')->send();
            return;
        }

        // 🚨 Validation: Check if report is too old (> 90 days)
        if ($report->report_date && $report->report_date->lt(now()->subDays(90))) {
            $this->toast()->error('Report Too Old', 'Cannot escalate reports older than 90 days ❌')->send();
            return;
        }

        // ✅ Permission: team-lead / lead-assist can only move team -> chapter for their own reports
        if (in_array($currentLevel, ['team']) && $level === 'chapter') {
            if (!$user->hasRole('admin') && $report->team_id !== $this->leadersTeam?->id) {
                $this->toast()->error('Unauthorized', 'You can only push reports from your own team ❌')->send();
                return;
            }

            // Additional check: Ensure team belongs to user's chapter
            if ($report->chapter_id !== $user->chapter_id) {
                $this->toast()->error('Unauthorized', 'Report chapter mismatch ❌')->send();
                return;
            }
        }

        // ✅ Permission: only admin can push chapter -> hq
        if ($currentLevel === 'chapter' && $level === 'hq') {
            if (!$user->hasRole('admin')) {
                $this->toast()->error('Unauthorized', 'Only admins can escalate to HQ ❌')->send();
                return;
            }

            // Admin must be from the same chapter
            if ($report->chapter_id !== $user->chapter_id && !$user->hasRole('super-admin')) {
                $this->toast()->error('Unauthorized', 'You can only escalate reports from your chapter ❌')->send();
                return;
            }
        }

        // 📝 Log the escalation (for audit trail)
        \Log::info('Report escalated', [
            'report_id' => $report->id,
            'from_level' => $currentLevel,
            'to_level' => $level,
            'escalated_by' => $user->id,
            'escalated_by_name' => $user->name,
            'escalated_at' => now(),
        ]);

        // Save promotion with timestamp tracking
        $report->level = $level;
        $report->status = 'forwarded';
        $report->save();

        $this->notifyEscalation($report, $currentLevel, $level);

        // Success messages with context
        if ($level === 'chapter') {
            $this->toast()->success('✅ Report Escalated', "Report sent to Chapter Admin for review")->send();
        } elseif ($level === 'hq') {
            $this->toast()->success('🚀 Report Escalated', "Report sent to HQ for final review")->send();
        }
    }

    private function notifyEscalation(Report $report, string $fromLevel, string $toLevel): void
    {
        if ($toLevel === 'chapter') {
            $recipients = User::role('admin')
                ->where('chapter_id', $report->chapter_id)
                ->get();
        } elseif ($toLevel === 'hq') {
            $recipients = User::role('super-admin')->get();
        } else {
            return;
        }

        if ($recipients->isEmpty()) {
            return;
        }

        foreach ($recipients as $recipient) {
            $recipient->notify(new ReportEscalated($report, $fromLevel, $toLevel));
        }
    }

    public function deleteReport(int $id)
    {
        $report = Report::findOrFail($id);

        // Check if report is less than 24 hours old
        if ($report->report_date && \Carbon\Carbon::parse($report->report_date)->greaterThan(now()->subDay())) {
            $report->delete();
            $this->toast()->success('Deleted', 'Report deleted successfully')->send();
        } else {
            $this->toast()->error('Not Allowed', 'Reports older than 24 hours cannot be deleted ❌')->send();
        }
    }
}; ?>

<div>
    <x-table :$headers :$rows>
        {{-- Format Report Date --}}
        @interact('column_report_date', $row)
            <p>{{ \Carbon\Carbon::parse($row->report_date)->format('M d, Y @ h:i A') }}</p>
        @endinteract

        {{-- Report Level (Admins see as text, leaders can escalate) --}}
        @interact('column_level', $row)
            @php
                $colors = [
                    'team' => 'bg-green-500 text-white',
                    'chapter' => 'bg-blue-500 text-white',
                    'hq' => 'bg-purple-600 text-white',
                ];
                $badgeClass =
                    'px-3 py-1 rounded-full text-xs font-semibold ' .
                    ($colors[$row->level] ?? 'bg-gray-400 text-white');

                $nextLevels = [
                    'team' => 'chapter',
                    'chapter' => 'hq',
                ];
                $nextLevel = $nextLevels[$row->level] ?? null;

                $tooltip = $nextLevel
                    ? 'Click to upgrade to ' . ucfirst($nextLevel)
                    : ucfirst($row->level) . ' (final level)';
            @endphp

            @if (Auth::user()->hasRole('admin'))
                {{-- Admin can push chapter -> HQ --}}
                @if ($row->level === 'chapter')
                    <button class="{{ $badgeClass }} hover:opacity-80 transition" title="{{ $tooltip }}"
                        wire:click="changeLevel({{ $row->id }}, 'hq')">
                        Push to Hq
                    </button>
                @else
                    <span class="{{ $badgeClass }}" title="{{ ucfirst($row->level) }} level">
                        {{ ucfirst($row->level) }}
                    </span>
                @endif
            @elseif($row->level === 'team' && $row->team_id === $leadersTeam?->id)
                {{-- Team lead/assist can only push their own reports to chapter --}}
                <button class="{{ $badgeClass }} hover:opacity-80 transition" title="{{ $tooltip }}"
                    wire:click="changeLevel({{ $row->id }}, 'chapter')">
                    Push To Chapter
                </button>
            @else
                {{-- Otherwise just static --}}
                <span class="{{ $badgeClass }}" title="{{ ucfirst($row->level) }} level">
                    {{ ucfirst($row->level) }}
                </span>
            @endif

        @endinteract

        {{-- Actions --}}
        @interact('column_actions', $row)
            <a href="{{ route('admin.dashboard.reports.view-report', request()->query()) }}">
                <x-button.circle color="primary" icon="eye" label="View" class="mr-2" />
            </a>
            <x-button.circle color="red" icon="trash" wire:click='deleteReport({{ $row->id }})' />
        @endinteract
    </x-table>
</div>
