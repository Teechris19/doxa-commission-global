<?php
use Livewire\Volt\Component;
use Livewire\Attributes\{Url, Layout};
use App\Models\{Chapter, Team, Event, EventTeam};
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions;

    #[Url]
    public $chapter;

    public $chapterId;
    public $teams;

    public $teamForEvent = [];
    public $teamNotForEvent = [];

    #[Url]
    public $eventTeamSearch;

    #[Url]
    public $nonEventTeamSearch;

    public function mount()
    {
        $this->chapterId = Chapter::where('name', '=', e($this->chapter))
            ->firstOrFail(['id'])->id;
        $this->loadTeams();
    }

    protected function loadTeams()
    {
        // Teams already assigned to this event
        $this->teamForEvent = Team::with('eventTeams')
            ->whereHas('eventTeams', fn($q) => $q->where('chapter_id', $this->chapterId))
            ->when($this->eventTeamSearch, fn($q) => $q->where('name', 'like', "%{$this->eventTeamSearch}%"))
            ->where('chapter_id', $this->chapterId)
            ->get() ?? [];

        // Teams not assigned to this event
        $this->teamNotForEvent = Team::where('chapter_id', $this->chapterId)
            ->when($this->nonEventTeamSearch, fn($q) => $q->where('name', 'like', "%{$this->nonEventTeamSearch}%"))
            ->whereNotIn('id', $this->teamForEvent->pluck('id'))
            ->get() ?? [];
    }

    public function toggleTeam($id, string $action = 'add')
    {
        if ($action == 'add') {
            EventTeam::create([
                'team_id' => $id,
                'chapter_id' => $this->chapterId,
            ]);

            $this->toast()->success('Done', 'Team added to Event')->send();
        } elseif ($action == 'remove') {
            $event_team = EventTeam::where('team_id', $id)
                ->where('chapter_id', $this->chapterId)
                ->first();

            if ($event_team) {
                $event_team->delete();
            }

            $this->toast()->success('Done', 'Team removed from Event')->send();
        }

        $this->dispatch('$refresh');
        $this->loadTeams();
    }

    public function updatedeventTeamSearch()
    {
        $this->loadTeams();
    }

    public function updatednonEventTeamSearch()
    {
        $this->loadTeams();
    }
};
?>

<div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Teams in Event -->
        <div class="border rounded-xl shadow-md bg-zinc-900 p-4 flex flex-col">
            <h3 class="font-semibold text-lg mb-3 text-zinc-200">
                Teams in Event
                <span class="float-right">{{ count($teamForEvent) }}</span>
            </h3>

            <input type="text" wire:model.live="eventTeamSearch" placeholder="Search event teams..."
                class="mb-3 w-full px-3 py-2 rounded-lg bg-zinc-800 text-zinc-200 
                          border border-zinc-700 focus:ring focus:ring-zinc-600" />

            <div
                class="flex-1 overflow-y-auto max-h-64 space-y-2 
                        scrollbar-thin scrollbar-thumb-zinc-600 scrollbar-track-zinc-800">
                @forelse($teamForEvent as $team)
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
                    <p class="text-zinc-500 italic">No teams in this event</p>
                @endforelse
            </div>
        </div>

        <!-- Teams not in Event -->
        <div class="border rounded-xl shadow-md bg-zinc-900 p-4 flex flex-col">
            <h3 class="font-semibold text-lg mb-3 text-zinc-200">
                Teams Not in Event
                <span class="float-right">{{ count($teamNotForEvent) }}</span>
            </h3>

            <input type="text" wire:model.live="nonEventTeamSearch" placeholder="Search teams..."
                class="mb-3 w-full px-3 py-2 rounded-lg bg-zinc-800 text-zinc-200 
                          border border-zinc-700 focus:ring focus:ring-zinc-600" />

            <div
                class="flex-1 overflow-y-auto max-h-64 space-y-2 
                        scrollbar-thin scrollbar-thumb-zinc-600 scrollbar-track-zinc-800">
                @forelse($teamNotForEvent as $team)
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
                    <p class="text-zinc-500 italic">No available teams</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

