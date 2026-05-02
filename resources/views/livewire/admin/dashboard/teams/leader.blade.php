<?php

use App\Models\Chapter;
use App\Models\Team;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions;

    #[Url]
    public ?string $chapter = null; 
    public ?int $chapterId = null;

    public array $teams = [];
    public ?int $selectedTeam = null;

    // Leader assignment state
    public ?int $selectedUserForLeader = null;
    // Assistant assignment state
    public ?int $selectedUserForAssistant = null;
    public string $newAssistantTitle = '';

    public ?User $currentLeader = null;
    public array $currentAssistants = [];
    public array $chapterUsers = [];

    public function mount(): void
    {
        $chapter = Chapter::where('name', $this->chapter)->firstOrFail();
        $this->chapterId = $chapter->id;

        $this->teams = Team::where('chapter_id', $this->chapterId)
            ->get()
            ->map(fn($t) => ['value' => $t->id, 'label' => $t->name])
            ->toArray();
    }

    public function updatedSelectedTeam($teamId): void
    {
        $team = Team::with(['users' => fn($q) => $q->withPivot('role_title', 'role_in_team')])->find($teamId);

        if (!$team) {
            $this->reset(['currentLeader', 'currentAssistants', 'chapterUsers']);
            return;
        }

        $this->currentLeader = $team->users()->wherePivot('role_in_team', 'team-lead')->first();
        
        $this->currentAssistants = $team->users()
            ->wherePivot('role_in_team', 'assistant-team-lead')
            ->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'title' => $u->pivot->role_title ?? 'Assistant'
            ])
            ->toArray();

        $this->chapterUsers = User::where('chapter_id', $this->chapterId)
            ->get()
            ->map(fn($u) => ['value' => $u->id, 'label' => $u->name])
            ->toArray();

        $this->reset(['selectedUserForLeader', 'selectedUserForAssistant', 'newAssistantTitle']);
    }

    public function assignLeader(): void
    {
        if (!$this->selectedTeam || !$this->selectedUserForLeader) return;

        $team = Team::find($this->selectedTeam);
        $user = User::find($this->selectedUserForLeader);

        // Demote old leader
        $oldLeader = $team->users()->wherePivot('role_in_team', 'team-lead')->first();
        if ($oldLeader) {
            $team->users()->updateExistingPivot($oldLeader->id, ['role_in_team' => 'member']);
        }

        // Promote new leader
        $team->users()->syncWithoutDetaching([
            $user->id => ['chapter_id' => $this->chapterId, 'role_in_team' => 'team-lead']
        ]);
        
        $this->toast()->success('Done', 'Team leader updated.')->send();
        $this->updatedSelectedTeam($this->selectedTeam);
    }

    public function addAssistant(): void
    {
        if (!$this->selectedTeam || !$this->selectedUserForAssistant || empty($this->newAssistantTitle)) {
            $this->toast()->error('Error', 'Please select a user and provide a role title.')->send();
            return;
        }

        $team = Team::find($this->selectedTeam);
        $team->users()->syncWithoutDetaching([
            $this->selectedUserForAssistant => [
                'chapter_id' => $this->chapterId, 
                'role_in_team' => 'assistant-team-lead',
                'role_title' => $this->newAssistantTitle
            ]
        ]);
        
        $this->reset(['selectedUserForAssistant', 'newAssistantTitle']);
        $this->updatedSelectedTeam($this->selectedTeam);
        $this->toast()->success('Done', 'Assistant added.')->send();
    }

    public function removeAssistant(int $userId): void
    {
        $team = Team::find($this->selectedTeam);
        $team->users()->updateExistingPivot($userId, ['role_in_team' => 'member', 'role_title' => null]);
        $this->updatedSelectedTeam($this->selectedTeam);
        $this->toast()->success('Done', 'Assistant removed.')->send();
    }
};
?>
<div>
    <x-fancy-header title="Team Leadership Management" subtitle="Assign leaders and support roles" :breadcrumbs="[
        ['label' => 'Home', 'url' => route('admin.dashboard', request()->query())],
        ['label'=>'Teams', 'url'=>route('admin.dashboard.teams', request()->query())],
        ['label' => 'Edit Leadership']
    ]" class="mb-4" />

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
        {{-- Team Selection --}}
        <x-card header="Team Selection" class="dark:bg-zinc-900">
            <select wire:model.live="selectedTeam" class="w-full p-2 rounded-lg bg-zinc-800 text-zinc-100 border border-zinc-700">
                <option value="">Choose a team</option>
                @foreach($teams as $team)
                    <option value="{{ $team['value'] }}">{{ $team['label'] }}</option>
                @endforeach
            </select>
        </x-card>

        {{-- Main Leader --}}
        <x-card header="Team Leader" class="dark:bg-zinc-900">
            <p class="text-sm text-zinc-400 mb-2">Current Leader: <span class="font-bold text-white">{{ $currentLeader?->name ?? 'None' }}</span></p>
            <select wire:model="selectedUserForLeader" class="w-full p-2 rounded bg-zinc-800 text-white border border-zinc-700 mb-2">
                <option value="">New Leader</option>
                @foreach($chapterUsers as $u) <option value="{{ $u['value'] }}">{{ $u['label'] }}</option> @endforeach
            </select>
            <x-button wire:click="assignLeader" color="blue" class="w-full">Change Leader</x-button>
        </x-card>

        {{-- Assistant Management --}}
        <x-card header="Assistant Team Leads" class="dark:bg-zinc-900">
            <details class="group mb-4">
                <summary class="cursor-pointer font-bold text-zinc-300 p-2 bg-zinc-800 rounded">Manage Assistants ({{ count($currentAssistants) }})</summary>
                <ul class="mt-2 space-y-1">
                    @foreach($currentAssistants as $asst)
                        <li class="flex justify-between p-2 bg-zinc-800 rounded text-sm text-white">
                            {{ $asst['name'] }} ({{ $asst['title'] }})
                            <button wire:click="removeAssistant({{ $asst['id'] }})" class="text-red-400">×</button>
                        </li>
                    @endforeach
                </ul>
            </details>
            <div class="space-y-2">
                <select wire:model="selectedUserForAssistant" class="w-full p-2 rounded bg-zinc-800 text-white border border-zinc-700">
                    <option value="">Select User</option>
                    @foreach($chapterUsers as $u) <option value="{{ $u['value'] }}">{{ $u['label'] }}</option> @endforeach
                </select>
                <input wire:model="newAssistantTitle" placeholder="Custom Role Title" class="w-full p-2 rounded bg-zinc-800 text-white border border-zinc-700">
                <x-button wire:click="addAssistant" color="green" class="w-full">Add Assistant</x-button>
            </div>
        </x-card>
    </div>
</div>
