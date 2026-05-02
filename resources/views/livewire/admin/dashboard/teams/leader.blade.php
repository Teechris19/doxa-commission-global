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
    public ?int $selectedTeamId = null;
    public ?Team $selectedTeam = null;

    public array $chapterUsers = [];

    // Assignment state
    public ?int $selectedUserId = null;
    public string $newAssistantTitle = '';

    public function mount(): void
    {
        $chapter = Chapter::where('name', $this->chapter)->firstOrFail();
        $this->chapterId = $chapter->id;
        $this->teams = Team::where('chapter_id', $this->chapterId)->orderBy('name')->get()->toArray();
        $this->chapterUsers = User::where('chapter_id', $this->chapterId)->get(['id', 'name'])->toArray();
    }

    public function updatedSelectedTeamId($teamId): void
    {
        $this->selectedTeam = Team::with(['users' => fn($q) => $q->withPivot('role_title', 'role_in_team')])->find($teamId);
        $this->reset(['selectedUserId', 'newAssistantTitle']);
    }

    public function promoteToLead(int $userId): void
    {
        if (!$this->selectedTeam || !$userId) return;
        $oldLeader = $this->selectedTeam->users()->wherePivot('role_in_team', 'team-lead')->first();
        if ($oldLeader) {
            $this->selectedTeam->users()->updateExistingPivot($oldLeader->id, ['role_in_team' => 'member']);
        }
        $this->selectedTeam->users()->syncWithoutDetaching([$userId => ['role_in_team' => 'team-lead', 'chapter_id' => $this->chapterId]]);
        $this->updatedSelectedTeamId($this->selectedTeamId);
        $this->toast()->success('Done', 'New leader promoted.')->send();
    }

    public function addAssistant(): void
    {
        if (!$this->selectedTeam || !$this->selectedUserId || empty($this->newAssistantTitle)) return;
        $this->selectedTeam->users()->syncWithoutDetaching([$this->selectedUserId => ['role_in_team' => 'assistant-team-lead', 'role_title' => $this->newAssistantTitle, 'chapter_id' => $this->chapterId]]);
        $this->updatedSelectedTeamId($this->selectedTeamId);
        $this->toast()->success('Done', 'Assistant added.')->send();
    }

    public function remove(int $userId): void
    {
        $this->selectedTeam->users()->updateExistingPivot($userId, ['role_in_team' => 'member', 'role_title' => null]);
        $this->updatedSelectedTeamId($this->selectedTeamId);
        $this->toast()->success('Done', 'Member removed from role.')->send();
    }
};
?>
<div class="max-w-6xl mx-auto py-8">
    <x-fancy-header title="Leadership Console" subtitle="Switch teams and update roles in real-time" />

    <div class="mt-8 grid grid-cols-1 md:grid-cols-12 gap-6 h-[600px]">
        {{-- Team Sidebar --}}
        <div class="md:col-span-3 bg-zinc-900 border border-zinc-800 rounded-2xl p-4 overflow-y-auto">
            <h3 class="text-xs font-bold uppercase text-zinc-500 mb-4 px-2">Teams</h3>
            <div class="space-y-1">
                @foreach($teams as $team)
                    <button wire:click="$set('selectedTeamId', {{ $team['id'] }})" class="w-full text-left px-4 py-3 rounded-xl transition {{ $selectedTeamId == $team['id'] ? 'bg-blue-600 text-white' : 'hover:bg-zinc-800 text-zinc-300' }}">
                        {{ $team['name'] }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Main Console --}}
        <div class="md:col-span-9 bg-zinc-900 border border-zinc-800 rounded-2xl p-8">
            @if(!$selectedTeam)
                <div class="h-full flex items-center justify-center text-zinc-500">Select a team to begin managing leadership.</div>
            @else
                <div class="flex justify-between items-start mb-8">
                    <div>
                        <h2 class="text-3xl font-bold text-white">{{ $selectedTeam->name }}</h2>
                        <p class="text-zinc-400">Manage structure for this unit</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-8">
                    {{-- Role Management Form --}}
                    <div class="space-y-6">
                        <select wire:model="selectedUserId" class="w-full p-3 rounded-xl bg-zinc-800 border-zinc-700 text-white">
                            <option value="">Select Member...</option>
                            @foreach($chapterUsers as $u) <option value="{{ $u['value'] }}">{{ $u['label'] }}</option> @endforeach
                        </select>
                        <input wire:model="newAssistantTitle" placeholder="Role (e.g. Secretary, Lead)" class="w-full p-3 rounded-xl bg-zinc-800 border-zinc-700 text-white">
                        <div class="flex gap-2">
                            <x-button wire:click="promoteToLead({{ $selectedUserId ?? 0 }})" color="blue" class="flex-1">Set Leader</x-button>
                            <x-button wire:click="addAssistant" color="green" class="flex-1">Set Assistant</x-button>
                        </div>
                    </div>

                    {{-- Live Preview --}}
                    <div class="bg-zinc-800/50 rounded-2xl p-6 border border-zinc-800">
                        <h4 class="text-zinc-400 font-bold uppercase text-[10px] tracking-widest mb-4">Current Leadership</h4>
                        <div class="space-y-4">
                            <div class="flex justify-between border-b border-zinc-700 pb-2">
                                <span class="text-zinc-500">Lead</span>
                                <span class="text-white font-bold">{{ $selectedTeam->users->firstWhere('pivot.role_in_team', 'team-lead')?->name ?? 'Unassigned' }}</span>
                            </div>
                            @foreach($selectedTeam->users->where('pivot.role_in_team', 'assistant-team-lead') as $asst)
                                <div class="flex justify-between">
                                    <span class="text-zinc-500">{{ $asst->pivot->role_title }}</span>
                                    <span class="text-white">{{ $asst->name }} <button wire:click="remove({{ $asst->id }})" class="ml-2 text-red-500"><i class="fas fa-times"></i></button></span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
