<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Url, Layout};
use App\Models\{Chapter, Team, PrayerRequestTeam};
use TallStackUi\Traits\Interactions;
use Livewire\WithPagination;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions;
    #[Url]
    public $chapter;

    public $chapterId;

    public $teams;

    public $teamForPrayerRequest = [];

    public $teamNotForPrayerRequest = [];

    #[Url]
    public $prayerRequestTeamSearch;

    #[Url]
    public $nonPrayerRequestTeamSearch;

    public function mount()
    {
        $this->chapterId = Chapter::where('name', '=', e($this->chapter))->firstOrFail(['id'])->id;
        $this->loadTeams();
    }

    protected function loadTeams()
    {
        $this->teamForPrayerRequest = Team::with('prayerRequests')->whereHas('prayerRequests')->when($this->prayerRequestTeamSearch, fn($q) => $q->where('name', 'like', "%{$this->prayerRequestTeamSearch}%"))->where('chapter_id', $this->chapterId)->get() ?? [];

        $this->teamNotForPrayerRequest =
            Team::where('chapter_id', $this->chapterId)
                ->when($this->nonPrayerRequestTeamSearch, fn($q) => $q->where('name', 'like', "%{$this->nonPrayerRequestTeamSearch}%"))
                ->whereNotIn('id', $this->teamForPrayerRequest->pluck('id'))
                ->get() ?? [];
    }

    public function toggleTeam($id, string $action = 'add')
    {
        if ($action == 'add') {
            $chapter_id = $this->chapterId;
            $team_id = $id;

            $prayerRequestTeam = new PrayerRequestTeam();
            $prayerRequestTeam->team_id = $team_id;
            $prayerRequestTeam->chapter_id = $chapter_id;
            $prayerRequestTeam->save();

            $this->toast()->success('Done', 'Team Has Permission for Prayer Request')->send();
        } elseif ($action == 'remove') {
            $prayerRequestTeam = PrayerRequestTeam::where('team_id', '=', $id)->first();
            $prayerRequestTeam->delete();

            $this->toast()->success('Done', 'Team  Permission For Prayer Request Removed')->send();
        }
        $this->dispatch('$refresh');
        $this->loadTeams();
    }

    public function updatedprayerRequestTeamSearch()
    {
        $this->loadTeams();
    }

    public function updatednonPrayerRequestTeamSearch()
    {
        $this->loadTeams();
    }
}; ?>


<div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Team Members -->
        <div class="border rounded-xl shadow-md bg-zinc-900 p-4 flex flex-col">
            <h3 class="font-semibold text-lg mb-3 text-zinc-200">
                <span>
                    <span wire:loading wire:target='updatedprayerRequestTeamSearch'>Loading</span>
                </span>
                <span class="float-right">{{ count($teamForPrayerRequest) }}</span>
            </h3>

            <input type="text" wire:model.live="prayerRequestTeamSearch" placeholder="Search team members..."
                class="mb-3 w-full px-3 py-2 rounded-lg bg-zinc-800 text-zinc-200 
                          border border-zinc-700 focus:ring focus:ring-zinc-600" />

            <div
                class="flex-1 overflow-y-auto max-h-64 space-y-2 
                        scrollbar-thin scrollbar-thumb-zinc-600 scrollbar-track-zinc-800">
                @forelse($teamForPrayerRequest as $team)
                    <div
                        class="flex justify-between items-center p-2 border rounded-lg 
                                    bg-zinc-800 hover:bg-zinc-700">
                        <span class="font-medium text-zinc-100">{{ $team->name }}</span>

                        <button wire:click="toggleTeam({{ $team->id }}, 'remove')"
                            class="px-3 py-1 text-sm rounded-lg bg-red-500 
                                               hover:bg-red-600 text-white transition">
                            Remove
                        </button>

                    </div>
                @empty
                    <p class="text-zinc-500 italic">No team members</p>
                @endforelse
            </div>
        </div>

        <!-- Chapter Users -->
        <div class="border rounded-xl shadow-md bg-zinc-900 p-4 flex flex-col">
            <h3 class="font-semibold text-lg mb-3 text-zinc-200">
                Team Not Having Appointment
                <span class="float-right">{{ count($teamNotForPrayerRequest) }}</span>
            </h3>

            <input type="text" wire:model.live="nonPrayerRequestTeamSearch" placeholder="Search chapter users..."
                class="mb-3 w-full px-3 py-2 rounded-lg bg-zinc-800 text-zinc-200 
                          border border-zinc-700 focus:ring focus:ring-zinc-600" />

            <div
                class="flex-1 overflow-y-auto max-h-64 space-y-2 
                        scrollbar-thin scrollbar-thumb-zinc-600 scrollbar-track-zinc-800">
                @forelse($teamNotForPrayerRequest as $team)
                    <div
                        class="flex justify-between items-center p-2 border rounded-lg 
                                    bg-zinc-800 hover:bg-zinc-700">
                        <span class="font-medium text-zinc-100">{{ $team->name }}</span>
                        <button wire:click="toggleTeam({{ $team->id }})"
                            class="px-3 py-1 text-sm rounded-lg bg-green-500 
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
