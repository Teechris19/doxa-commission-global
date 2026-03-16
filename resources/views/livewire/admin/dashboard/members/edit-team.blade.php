<?php

use App\Models\Chapter;
use App\Models\Team;
use App\Models\User;
use App\Notifications\TeamMemberAdded;
use App\Services\NotificationRecipients;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component {
    public User $user;
    public $userId;
    #[Url]
    public $chapter;

    public $userTeams = [];        // IDs of Teams the user belongs to
    public $availableTeams = [];   // IDs of Teams the user does not belong to

    public $userTeamModels = [];
    public $availableTeamModels = [];

    public function mount($member)
    {
        $this->user = User::where('id', '=', $member)->first();

        if (!$this->user) {
            abort(404);
        }

        // Current teams (many-to-many)

        $this->userTeams      = $this->user->teams()->pluck('teams.id')->toArray();
        $this->availableTeams = Team::where('chapter_id', '=', Chapter::where('name', '=', e($this->chapter))->firstOrFail()->id)->whereNotIn('id', $this->userTeams)->pluck('id')->toArray();
        $this->loadTeams();
    }

    public function toggleTeam($teamId, $user_id)
    {

        $user = User::findOrFail($user_id);

        if (in_array($teamId, $this->userTeams)) {
            // Remove from user
            $user->teams()->detach($teamId);
            $this->userTeams        = array_diff($this->userTeams, [$teamId]);
            $this->availableTeams[] = $teamId;
        } else {
            // Add to user
            $user->teams()->attach($teamId);
            $team = Team::find($teamId);
            if ($team) {
                $recipients = (new NotificationRecipients())
                    ->forTeamAndChapter($team->id, $team->chapter_id)
                    ->merge([$user])
                    ->unique('id');

                foreach ($recipients as $recipient) {
                    $recipient->notify(new TeamMemberAdded($team, $user));
                }
            }
            $this->availableTeams = array_diff($this->availableTeams, [$teamId]);
            $this->userTeams[]    = $teamId;
        }

        $this->loadTeams();
    }


    private function loadTeams()
    {
        $this->userTeamModels      = Team::whereIn('id', $this->userTeams)->get();
        $this->availableTeamModels = Team::whereIn('id', $this->availableTeams)->get();
    }
};
?>

<div class="p-6" x-data>

    <x-button href="{{ route('admin.dashboard.members', request()->query()) }}" class="mb-4 mt-4" wire::navigate>Done</x-button>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- User Teams -->
        <div class="border rounded-xl shadow-md bg-white dark:bg-gray-900 p-4 flex flex-col">
            <h3 class="font-semibold text-lg mb-3 text-gray-700 dark:text-gray-200">
                User’s Teams <span class="float-right">{{ count($userTeamModels) }}</span>
            </h3>
            <div
                class="flex-1 overflow-y-auto max-h-64 space-y-2 scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-200">
                @forelse($userTeamModels as $team)
                    <div
                        class="flex justify-between items-center p-2 border rounded-lg bg-gray-50 hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700">
                        <span class="font-medium text-gray-800 dark:text-gray-100">{{ $team->name }}</span>
                        <button wire:click="toggleTeam({{ $team->id }}, {{ $user->id}})"
                            class="px-3 py-1 text-sm rounded-lg bg-red-500 hover:bg-red-600 text-white transition">
                            Remove
                        </button>
                    </div>
                @empty
                    <p class="text-gray-500 italic">No teams selected</p>
                @endforelse
            </div>
        </div>

        <!-- Available Teams -->
        <div class="border rounded-xl shadow-md bg-white dark:bg-gray-900 p-4 flex flex-col">
            <h3 class="font-semibold text-lg mb-3 text-gray-700 dark:text-gray-200">
                Available Teams  <span class="float-right">{{ count($availableTeamModels) }}</span>
            </h3>
            <div
                class="flex-1 overflow-y-auto max-h-64 space-y-2 scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-200">
                @foreach($availableTeamModels as $team)
                    <div
                        class="flex justify-between items-center p-2 border rounded-lg bg-gray-50 hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700">
                        <span class="font-medium text-gray-800 dark:text-gray-100">{{ $team->name }}</span>
                        <button wire:click="toggleTeam({{ $team->id }}, {{ $user->id}})"
                            class="px-3 py-1 text-sm rounded-lg bg-green-500 hover:bg-green-600 text-white transition">
                            Select
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
