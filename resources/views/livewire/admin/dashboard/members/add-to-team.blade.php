<?php

use App\Models\Team;
use App\Models\User;
use App\Notifications\TeamMemberAdded;
use App\Services\NotificationRecipients;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component {
    public ?Team $leaderTeam = null;
    public $allTeams = [];  // All teams in the chapter for admin to select

    public $teamUsers = [];
    public $chapterUsers = [];

    // Search filters
    public string $teamSearch = '';
    public string $chapterSearch = '';

    #[Url]
    public ?string $team_id = null;

    #[Url]
    public ?string $chapter = null;

    public function mount(): void
    {
        $authUser = Auth::user();

        // Admin and super-admin can access any team
        if ($authUser->hasRole(['admin', 'super-admin'])) {
            // Get chapter ID from parameter or user
            $chapterId = null;
            if ($this->chapter) {
                $chapterId = \App\Models\Chapter::where('name', $this->chapter)->value('id');
            } elseif ($authUser->chapter_id) {
                $chapterId = $authUser->chapter_id;
            }

            if (!$chapterId) {
                abort(403, 'No chapter found. Please ensure you have a chapter assigned.');
            }

            // Get all teams in the chapter
            $this->allTeams = Team::where('chapter_id', $chapterId)->get();

            if ($this->allTeams->isEmpty()) {
                abort(403, 'No teams found for "' . $this->chapter . '". Please create teams first in the Teams section.');
            }

            // If a specific team_id is provided via URL, use it
            if ($this->team_id) {
                $this->leaderTeam = Team::findOrFail($this->team_id);
            } else {
                // Default to first team in chapter
                $this->leaderTeam = $this->allTeams->first();
            }
        } else {
            // For team leads, find the team where this user is a team-lead
            $leaderTeam = $authUser->teams()
                ->wherePivotIn('role_in_team', ['team-lead', 'lead-assist', 'lead_assist'])
                ->first();

            if (!$leaderTeam) {
                abort(403, 'You are not a team lead.');
            }

            $this->leaderTeam = $leaderTeam;
            $this->allTeams = [$leaderTeam];
        }

        $this->loadUsers();
    }

    public function toggleUser(int $userId): void
    {
        $user = User::findOrFail($userId);

        if ($this->leaderTeam->users->contains($userId)) {
            $this->leaderTeam->users()->detach($userId);
        } else {
            $this->leaderTeam->users()->attach($userId);

            $recipients = (new NotificationRecipients())
                ->forTeamAndChapter($this->leaderTeam->id, $this->leaderTeam->chapter_id)
                ->merge([$user])
                ->unique('id');

            foreach ($recipients as $recipient) {
                $recipient->notify(new TeamMemberAdded($this->leaderTeam, $user));
            }
        }

        $this->loadUsers();
    }

    private function loadUsers(): void
    {
        // Members of the team
        $this->teamUsers = $this->leaderTeam
            ->users()
            ->when(
                $this->teamSearch,
                fn($q) =>
                $q->where('name', 'like', '%' . $this->teamSearch . '%')
            )
            ->get();

        // Users in the same chapter but not in the team
        $chapterId = $this->leaderTeam->chapter_id;

        $this->chapterUsers = User::where('chapter_id', $chapterId)
            ->whereNotIn('id', $this->leaderTeam->users()->pluck('users.id'))
            ->when(
                $this->chapterSearch,
                fn($q) =>
                $q->where('name', 'like', '%' . $this->chapterSearch . '%')
            )
            ->get();
    }

    public function updatedTeamSearch(): void
    {
        $this->loadUsers();
    }

    public function updatedChapterSearch(): void
    {
        $this->loadUsers();
    }

    public function selectTeam(int $teamId): void
    {
        $this->leaderTeam = Team::findOrFail($teamId);
        $this->team_id = $teamId;
        $this->loadUsers();
    }
};
?>
<div class="p-6 space-y-6" x-data>
    <x-fancy-header title="{{ $leaderTeam->name }} Team" subtitle="Manage team members and add users from the chapter"
        :breadcrumbs="[
        ['label' => 'Home', 'url' => route('admin.dashboard')],
        ['label' => 'Teams', 'url' => route('admin.dashboard.teams')],
        ['label' => $leaderTeam->name . ' Members']
    ]">
    <x-button href="{{ route('admin.dashboard.members', request()->query()) }}" class="mb-4 mt-4" wire:navigate>
            Done
        </x-button>

    </x-fancy-header>

    {{-- Team Selector for Admin/Super Admin --}}
    @if(auth()->user()->hasRole(['admin', 'super-admin']) && count($allTeams) > 1)
    <div class="bg-zinc-800 rounded-lg p-4">
        <label class="block text-sm font-medium text-zinc-300 mb-2">Select Team</label>
        <select 
            wire:change="selectTeam($event.target.value)" 
            class="w-full px-4 py-2 rounded-lg bg-zinc-700 text-zinc-200 border border-zinc-600 focus:ring focus:ring-zinc-500"
        >
            @foreach($allTeams as $team)
                <option value="{{ $team->id }}" {{ $team->id == $leaderTeam->id ? 'selected' : '' }}>
                    {{ $team->name }}
                </option>
            @endforeach
        </select>
    </div>
    @endif


    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Team Members -->
        <div class="border rounded-xl shadow-md bg-zinc-900 p-4 flex flex-col">
            <h3 class="font-semibold text-lg mb-3 text-zinc-200">
                {{ $leaderTeam->name }} Members
                <span class="float-right">{{ count($teamUsers) }}</span>
            </h3>

            <input type="text" wire:model.live="teamSearch" placeholder="Search team members..." class="mb-3 w-full px-3 py-2 rounded-lg bg-zinc-800 text-zinc-200 
                          border border-zinc-700 focus:ring focus:ring-zinc-600" />

            <div class="flex-1 overflow-y-auto max-h-64 space-y-2 
                        scrollbar-thin scrollbar-thumb-zinc-600 scrollbar-track-zinc-800">
                @forelse($teamUsers as $member)
                    <div class="flex justify-between items-center p-2 border rounded-lg 
                                    bg-zinc-800 hover:bg-zinc-700">
                        <span class="font-medium text-zinc-100">{{ $member->name }}</span>
                        @if($member->id == Auth::id())
                            <span class="text-sm italic text-zinc-400 mr-2">(You)</span>
                        @else
                            <button wire:click="toggleUser({{ $member->id }})" class="px-3 py-1 text-sm rounded-lg bg-red-500 
                                               hover:bg-red-600 text-white transition">
                                Remove
                            </button>
                        @endif

                    </div>
                @empty
                    <p class="text-zinc-500 italic">No team members</p>
                @endforelse
            </div>
        </div>

        <!-- Chapter Users -->
        <div class="border rounded-xl shadow-md bg-zinc-900 p-4 flex flex-col">
            <h3 class="font-semibold text-lg mb-3 text-zinc-200">
                Chapter Users Not In Team
                <span class="float-right">{{ count($chapterUsers) }}</span>
            </h3>

            <input type="text" wire:model.live="chapterSearch" placeholder="Search chapter users..." class="mb-3 w-full px-3 py-2 rounded-lg bg-zinc-800 text-zinc-200 
                          border border-zinc-700 focus:ring focus:ring-zinc-600" />

            <div class="flex-1 overflow-y-auto max-h-64 space-y-2 
                        scrollbar-thin scrollbar-thumb-zinc-600 scrollbar-track-zinc-800">
                @forelse($chapterUsers as $member)
                    <div class="flex justify-between items-center p-2 border rounded-lg 
                                    bg-zinc-800 hover:bg-zinc-700">
                        <span class="font-medium text-zinc-100">{{ $member->name }}</span>
                        <button wire:click="toggleUser({{ $member->id }})" class="px-3 py-1 text-sm rounded-lg bg-green-500 
                                           hover:bg-green-600 text-white transition">
                            Add
                        </button>
                    </div>
                @empty
                    <p class="text-zinc-500 italic">No available users</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
