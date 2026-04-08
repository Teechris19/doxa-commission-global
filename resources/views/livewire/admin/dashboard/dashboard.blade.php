<?php

use App\Models\Chapter;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component {

    public $total_members = 0;
    public $total_teams = 0;
    public $total_conclaves = 0;
    public $total_appointments = 0;
    public $total_transport_requests = 0;
    public $recent_members = [];

    public function mount()
    {
        $user = Auth::user();
        $chapterName = request()->query('chapter');

        // Check if user is an attendance team lead
        $isAttendanceTeamLead = false;
        if ($user->hasRole('team-lead')) {
            $leadersTeam = $user->teams->firstWhere(fn($team) => in_array($team->pivot->role_in_team, ['team-lead', 'lead-assist', 'lead_assist']));
            if ($leadersTeam) {
                $attendanceTeams = \App\Models\AttendanceTeams::where('chapter_id', $leadersTeam->chapter_id)->pluck('team_id')->all();
                $isAttendanceTeamLead = in_array($leadersTeam->id, $attendanceTeams);
            }
        }

        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            $this->total_conclaves = Chapter::count();
            if ($chapterName) {
                $chapter = Chapter::where('name', '=', $chapterName)->first();
                $this->total_members = $chapter ? User::where('chapter_id', $chapter->id)->count() : 0;
                $this->total_teams = $chapter ? \App\Models\Team::where('chapter_id', $chapter->id)->count() : 0;
                $this->total_appointments = $chapter ? \App\Models\Appointment::where('chapter_id', $chapter->id)->count() : 0;
                $this->total_transport_requests = $chapter ? \App\Models\Transport::where('chapter_id', $chapter->id)->count() : 0;
                $this->recent_members = $chapter
                    ? User::where('chapter_id', $chapter->id)->latest()->take(5)->get(['id', 'name', 'email', 'created_at'])->toArray()
                    : [];
            } else {
                // Default to global stats if no chapter is selected
                $this->total_members = User::count();
                $this->total_teams = \App\Models\Team::count();
                $this->total_appointments = \App\Models\Appointment::count();
                $this->total_transport_requests = \App\Models\Transport::count();
                $this->recent_members = User::latest()
                    ->take(5)
                    ->get(['id', 'name', 'email', 'created_at'])
                    ->toArray();
            }
        } elseif ($isAttendanceTeamLead) {
            // Attendance team leads see chapter-wide stats
            $chapter = Chapter::find($user->chapter_id);
            $this->total_members = User::where('chapter_id', $user->chapter_id)->count();
            $this->total_teams = \App\Models\Team::where('chapter_id', $user->chapter_id)->count();
            $this->total_appointments = \App\Models\Appointment::where('chapter_id', $user->chapter_id)->count();
            $this->total_transport_requests = 0;
            $this->recent_members = User::where('chapter_id', $user->chapter_id)
                ->latest()
                ->take(5)
                ->get(['id', 'name', 'email', 'created_at'])
                ->toArray();
        } elseif ($user->hasRole('team-lead')) {
            // Regular team leads see only their team stats
            // Get the IDs of the teams where the user is the team-lead
            $teamIds = $user->teams
                ->filter(fn($team) => $team->pivot->role_in_team === 'team-lead')
                ->pluck('id');

            // Count all users who belong to these teams
            $this->total_members = User::whereHas('teams', fn($q) => $q->whereIn('teams.id', $teamIds))->count();
            $this->total_teams = $teamIds->count();
            $this->total_appointments = \App\Models\Appointment::whereIn('team_id', $teamIds)->count();
            $this->total_transport_requests = 0;
            $this->recent_members = User::whereHas('teams', fn($q) => $q->whereIn('teams.id', $teamIds))
                ->latest()
                ->take(5)
                ->get(['id', 'name', 'email', 'created_at'])
                ->toArray();
        } else {
            // Regular admin or others, use their chapter
            $this->total_members = User::where('chapter_id', $user->chapter_id)->count();
            $this->total_teams = \App\Models\Team::where('chapter_id', $user->chapter_id)->count();
            $this->total_appointments = \App\Models\Appointment::where('chapter_id', $user->chapter_id)->count();
            $this->total_transport_requests = \App\Models\Transport::where('chapter_id', $user->chapter_id)->count();
            $this->recent_members = User::where('chapter_id', $user->chapter_id)
                ->latest()
                ->take(5)
                ->get(['id', 'name', 'email', 'created_at'])
                ->toArray();
        }
    }
}; ?>

<div class="dark:bg-zinc-900">
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @role('super-admin')
        <x-card header="Chapters" minimize class="dark:bg-zinc-800 dark:text-gray-200">
            <div class="text-5xl m-3 font-[montserat] text-slate-900 dark:text-gray-100">{{ $total_conclaves }}</div>
            <span><small class="text-slate-600 dark:text-gray-400">Total active chapters</small></span>
            <x-link :href="route('super-admin.conclaves')" text="Manage All"
                icon="arrow-up-right" position="right" wire:navigate />
        </x-card>
        @endrole

        <x-card header="Members" minimize class="dark:bg-zinc-800 dark:text-gray-200">
            <div class="text-5xl m-3 font-[montserat] text-slate-900 dark:text-gray-100">{{ $total_members }}</div>
            <span><small class="text-slate-600 dark:text-gray-400">Total members in this chapter</small></span>
            <x-link :href="route('admin.dashboard.members', ['chapter' => e(request()->query('chapter'))])" text="View All"
                icon="arrow-up-right" position="right" wire:navigate />
        </x-card>

        @php
            $user = auth()->user();
            $isSuperAdmin = $user->hasRole('super-admin');
            $isAdmin = $user->hasRole('admin');
            $isTeamLeader = $user->hasRole(['team-lead', 'lead-assist', 'lead_assist']);
            
            // Check if user is an attendance team lead
            $isAttendanceTeamLead = false;
            $leadersTeam = null;
            if ($isTeamLeader) {
                $leadersTeam = $user->teams->firstWhere(fn($team) => in_array($team->pivot->role_in_team, ['team-lead', 'lead-assist', 'lead_assist']));
                if ($leadersTeam) {
                    $attendanceTeams = \App\Models\AttendanceTeams::where('chapter_id', $leadersTeam->chapter_id)->pluck('team_id')->all();
                    $isAttendanceTeamLead = in_array($leadersTeam->id, $attendanceTeams);
                }
            }

            $chapterId = $isSuperAdmin
                ? \App\Models\Chapter::where('name', request('chapter'))->value('id')
                : $user->chapter_id;

            $appointment_teams = [];
            if (!$isSuperAdmin && !$isAdmin) {
                $appointment_teams = \App\Models\AppointmentTeams::where('chapter_id', $chapterId)->pluck('team_id')->all();
            }

            $teamFunctions = null;
            if ($leadersTeam && !$isAdmin && !$isSuperAdmin) {
                $teamFunctions = \App\Models\TeamFunction::where('team_id', $leadersTeam->id)->first();
            }

            $hasFunctionControl = $teamFunctions !== null;
            $functionMap = $teamFunctions?->function ?? [];

            $canSeeAppointments = $isSuperAdmin || $isAdmin || ($isTeamLeader && $leadersTeam && in_array($leadersTeam->id, $appointment_teams));
            if ($isTeamLeader && !$isAdmin && !$isSuperAdmin && $hasFunctionControl) {
                $canSeeAppointments = isset($functionMap['appointments']) && $functionMap['appointments'];
            }
        @endphp

        @if(!auth()->user()->hasRole('team-lead') || $isAttendanceTeamLead)
        <x-card header="Teams" minimize class="dark:bg-zinc-800 dark:text-gray-200">
            <div class="text-5xl m-3 font-[montserat] text-slate-900 dark:text-gray-100">{{ $total_teams }}</div>
            <span><small class="text-slate-600 dark:text-gray-400">Active teams in this chapter</small></span>
            <x-link :href="route('admin.dashboard.teams', request()->query())" text="View Teams"
                icon="arrow-up-right" position="right" wire:navigate />
        </x-card>
        @endif

        @if($canSeeAppointments)
        <x-card header="Appointments" minimize class="dark:bg-zinc-800 dark:text-gray-200">
            <div class="text-5xl m-3 font-[montserat] text-slate-900 dark:text-gray-100">{{ $total_appointments }}</div>
            <span><small class="text-slate-600 dark:text-gray-400">Total appointments logged</small></span>
            <x-link :href="route('admin.dashboard.appointments.index', request()->query())" text="View Appointments"
                icon="arrow-up-right" position="right" wire:navigate />
        </x-card>
        @endif

        @if(!auth()->user()->hasRole('team-lead'))
        <x-card header="Transport Requests" minimize class="dark:bg-zinc-800 dark:text-gray-200">
            <div class="text-5xl m-3 font-[montserat] text-slate-900 dark:text-gray-100">{{ $total_transport_requests }}</div>
            <span><small class="text-slate-600 dark:text-gray-400">Requests awaiting processing</small></span>
            <x-link :href="route('admin.dashboard.transport.index', request()->query())" text="View Requests"
                icon="arrow-up-right" position="right" wire:navigate />
        </x-card>
        @endif
    </div>

    <div class="grid lg:grid-cols-2 gap-6 mt-6">
        <x-card header="Quick Actions" class="dark:bg-zinc-800 dark:text-gray-200">
            <div class="text-sm text-slate-600 dark:text-gray-400 mb-4">
                Jump to common admin tasks.
            </div>
            <div class="flex flex-wrap gap-3">
                @if($isAttendanceTeamLead)
                    <x-link :href="route('admin.dashboard.members.create', request()->query())" text="Create New Member"
                        icon="arrow-up-right" position="right" wire:navigate />
                    <x-link :href="route('admin.members.add-to-team', request()->query())" text="Add Member to Team"
                        icon="arrow-up-right" position="right" wire:navigate />
                @elseif(!auth()->user()->hasRole('team-lead'))
                    <x-link :href="route('admin.dashboard.members.create', request()->query())" text="Add Member"
                        icon="arrow-up-right" position="right" wire:navigate />
                    <x-link :href="route('admin.dashboard.teams.create', request()->query())" text="Create Team"
                        icon="arrow-up-right" position="right" wire:navigate />
                    <x-link :href="route('admin.dashboard.transport.index', request()->query())" text="Transport"
                        icon="arrow-up-right" position="right" wire:navigate />
                @else
                    <x-link :href="route('admin.members.add-to-team', request()->query())" text="Add Member to Team"
                        icon="arrow-up-right" position="right" wire:navigate />
                @endif
                @if($canSeeAppointments)
                <x-link :href="route('admin.dashboard.appointments.index', request()->query())" text="Appointments"
                    icon="arrow-up-right" position="right" wire:navigate />
                @endif
                @role('super-admin')
                <x-link :href="route('super-admin.conclaves.create')" text="New Chapter"
                    icon="plus" position="right" wire:navigate />
                <x-link :href="route('super-admin.conclaves.add-admin')" text="Assign Admin"
                    icon="user-plus" position="right" wire:navigate />
                @endrole
            </div>
        </x-card>

        <x-card header="Recent Members" class="dark:bg-zinc-800 dark:text-gray-200">
            <div class="text-sm text-slate-600 dark:text-gray-400 mb-4">
                @if(auth()->user()->hasRole('team-lead'))
                    Latest members to join your teams.
                @else
                    Latest registrations in this chapter.
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-slate-500 dark:text-gray-400">
                        <tr>
                            <th class="py-2 pr-4">Name</th>
                            <th class="py-2 pr-4">Email</th>
                            <th class="py-2">Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recent_members as $member)
                            <tr class="border-t border-slate-200 dark:border-zinc-700">
                                <td class="py-2 pr-4 text-slate-900 dark:text-gray-200">{{ $member['name'] }}</td>
                                <td class="py-2 pr-4 text-slate-600 dark:text-gray-400">{{ $member['email'] }}</td>
                                <td class="py-2 text-slate-600 dark:text-gray-400">
                                    {{ \Carbon\Carbon::parse($member['created_at'])->toDayDateTimeString() }}
                                </td>
                            </tr>
                        @empty
                            <tr class="border-t border-slate-200 dark:border-zinc-700">
                                <td class="py-2 text-slate-500 dark:text-gray-400" colspan="3">
                                    No recent members found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</div>
