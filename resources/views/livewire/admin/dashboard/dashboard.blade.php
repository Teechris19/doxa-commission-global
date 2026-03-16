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
        } elseif ($user->hasRole('team-lead')) {
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

<div class="dark:bg-zinc-800">
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @role('super-admin')
        <x-card header="Conclaves" minimize class="dark:bg-zinc-900 dark:text-gray-200 text-zinc-900">
            <div class="text-5xl m-3 font-[montserat]">{{ $total_conclaves }}</div>
            <span><small>Total active conclaves</small></span>
            <x-link :href="route('super-admin.conclaves')" text="Manage All"
                icon="arrow-up-right" position="right" wire:navigate />
        </x-card>
        @endrole

        <x-card header="Members" minimize class="dark:bg-zinc-900 dark:text-gray-200 text-zinc-900">
            <div class="text-5xl m-3 font-[montserat]">{{ $total_members }}</div>
            <span><small>Total members in this chapter</small></span>
            <x-link :href="route('admin.dashboard.members', ['chapter' => e(request()->query('chapter'))])" text="View All"
                icon="arrow-up-right" position="right" wire:navigate />
        </x-card>

        <x-card header="Teams" minimize class="dark:bg-zinc-900 dark:text-gray-200 text-zinc-900">
            <div class="text-5xl m-3 font-[montserat]">{{ $total_teams }}</div>
            <span><small>Active teams in this chapter</small></span>
            <x-link :href="route('admin.dashboard.teams', request()->query())" text="View Teams"
                icon="arrow-up-right" position="right" wire:navigate />
        </x-card>

        <x-card header="Appointments" minimize class="dark:bg-zinc-900 dark:text-gray-200 text-zinc-900">
            <div class="text-5xl m-3 font-[montserat]">{{ $total_appointments }}</div>
            <span><small>Total appointments logged</small></span>
            <x-link :href="route('admin.dashboard.appointments.index', request()->query())" text="View Appointments"
                icon="arrow-up-right" position="right" wire:navigate />
        </x-card>

        <x-card header="Transport Requests" minimize class="dark:bg-zinc-900 dark:text-gray-200 text-zinc-900">
            <div class="text-5xl m-3 font-[montserat]">{{ $total_transport_requests }}</div>
            <span><small>Requests awaiting processing</small></span>
            <x-link :href="route('admin.dashboard.transport.index', request()->query())" text="View Requests"
                icon="arrow-up-right" position="right" wire:navigate />
        </x-card>
    </div>

    <div class="grid lg:grid-cols-2 gap-6 mt-6">
        <x-card header="Quick Actions" class="dark:bg-zinc-900 dark:text-gray-200 text-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-4">
                Jump to common admin tasks.
            </div>
            <div class="flex flex-wrap gap-3">
                <x-link :href="route('admin.dashboard.members.create', request()->query())" text="Add Member"
                    icon="arrow-up-right" position="right" wire:navigate />
                <x-link :href="route('admin.dashboard.teams.create', request()->query())" text="Create Team"
                    icon="arrow-up-right" position="right" wire:navigate />
                <x-link :href="route('admin.dashboard.appointments.index', request()->query())" text="Appointments"
                    icon="arrow-up-right" position="right" wire:navigate />
                <x-link :href="route('admin.dashboard.transport.index', request()->query())" text="Transport"
                    icon="arrow-up-right" position="right" wire:navigate />
                @role('super-admin')
                <x-link :href="route('super-admin.conclaves.create')" text="New Conclave"
                    icon="plus" position="right" wire:navigate />
                <x-link :href="route('super-admin.conclaves.add-admin')" text="Assign Admin"
                    icon="user-plus" position="right" wire:navigate />
                @endrole
            </div>
        </x-card>

        <x-card header="Recent Members" class="dark:bg-zinc-900 dark:text-gray-200 text-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-4">
                Latest registrations in this chapter.
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-zinc-500 dark:text-zinc-400">
                        <tr>
                            <th class="py-2 pr-4">Name</th>
                            <th class="py-2 pr-4">Email</th>
                            <th class="py-2">Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recent_members as $member)
                            <tr class="border-t border-zinc-200 dark:border-zinc-700">
                                <td class="py-2 pr-4">{{ $member['name'] }}</td>
                                <td class="py-2 pr-4 text-zinc-600 dark:text-zinc-400">{{ $member['email'] }}</td>
                                <td class="py-2 text-zinc-600 dark:text-zinc-400">
                                    {{ \Carbon\Carbon::parse($member['created_at'])->toDayDateTimeString() }}
                                </td>
                            </tr>
                        @empty
                            <tr class="border-t border-zinc-200 dark:border-zinc-700">
                                <td class="py-2 text-zinc-500 dark:text-zinc-400" colspan="3">
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
