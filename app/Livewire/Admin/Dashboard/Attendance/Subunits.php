<?php

namespace App\Livewire\Admin\Dashboard\Attendance;

use App\Models\{Chapter, Subunit, SubunitMember, Team, User};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use TallStackUi\Traits\Interactions;
use Illuminate\Support\Facades\Auth;

#[Layout('components.layouts.admin')]
new class extends Component {
    use Interactions;

    public $chapter;
    public $selectedTeamId = '';
    
    // Create subunit
    public $showCreateSubunit = false;
    public $subunitName = '';
    public $subunitDescription = '';
    public $subunitLeaderId = '';

    // Add members to subunit
    public $selectedSubunitId = '';
    public $memberIds = [];

    public function mount()
    {
        $user = Auth::user();
        if (!$this->chapter && $user) {
            $this->chapter = Chapter::find($user->chapter_id)?->name;
        }
    }

    public function getChapterId()
    {
        $user = Auth::user();
        return $this->chapter ? Chapter::where('name', $this->chapter)->first()?->id : $user->chapter_id;
    }

    public function getTeamLeadTeam()
    {
        $user = Auth::user();
        
        // For team lead, get their team
        if ($user->hasRole('team-lead')) {
            return $user->teams()->first();
        }
        
        return null;
    }

    public function createSubunit()
    {
        $this->validate([
            'subunitName' => 'required|string|min:3|max:255',
            'subunitLeaderId' => 'required|exists:users,id',
        ]);

        $chapterId = $this->getChapterId();
        $team = $this->getTeamLeadTeam();

        if (!$team) {
            $this->toast()->error('No team found', 'You are not assigned to a team.')->send();
            return;
        }

        $subunit = Subunit::create([
            'team_id' => $team->id,
            'chapter_id' => $chapterId,
            'name' => $this->subunitName,
            'description' => $this->subunitDescription ?: null,
            'leader_id' => $this->subunitLeaderId,
            'is_active' => true,
        ]);

        // Add leader as member of subunit
        SubunitMember::firstOrCreate([
            'subunit_id' => $subunit->id,
            'user_id' => $this->subunitLeaderId,
        ]);

        $this->toast()->success('Subunit created', "Subunit '{$this->subunitName}' created successfully.")->send();
        
        $this->reset(['showCreateSubunit', 'subunitName', 'subunitDescription', 'subunitLeaderId']);
    }

    public function addMembersToSubunit()
    {
        if (!$this->selectedSubunitId || empty($this->memberIds)) {
            $this->toast()->error('No members selected', 'Please select members to add.')->send();
            return;
        }

        $subunit = Subunit::find($this->selectedSubunitId);
        if (!$subunit) {
            $this->toast()->error('Subunit not found', 'The selected subunit does not exist.')->send();
            return;
        }

        $added = 0;
        foreach ($this->memberIds as $userId) {
            SubunitMember::firstOrCreate([
                'subunit_id' => $subunit->id,
                'user_id' => $userId,
            ]);
            $added++;
        }

        $this->toast()->success('Members added', "{$added} member(s) added to subunit.")->send();
        
        $this->reset(['selectedSubunitId', 'memberIds']);
    }

    public function assignLeader($subunitId, $userId)
    {
        $subunit = Subunit::find($subunitId);
        if (!$subunit) {
            $this->toast()->error('Subunit not found', 'The selected subunit does not exist.')->send();
            return;
        }

        $subunit->leader_id = $userId;
        $subunit->save();

        // Add leader as member if not already
        SubunitMember::firstOrCreate([
            'subunit_id' => $subunit->id,
            'user_id' => $userId,
        ]);

        $this->toast()->success('Leader assigned', 'Subunit leader has been assigned.')->send();
    }

    public function removeMember($subunitId, $userId)
    {
        SubunitMember::where('subunit_id', $subunitId)
            ->where('user_id', $userId)
            ->delete();

        $this->toast()->success('Member removed', 'Member removed from subunit.')->send();
    }

    public function deleteSubunit($id)
    {
        $subunit = Subunit::find($id);
        if ($subunit) {
            $subunit->delete();
            $this->toast()->success('Subunit deleted', 'Subunit has been deleted.')->send();
        }
    }

    public function with(): array
    {
        $chapterId = $this->getChapterId();
        $team = $this->getTeamLeadTeam();

        $chapters = Chapter::orderBy('name')->get();
        
        $subunits = [];
        $teamMembers = collect();
        
        if ($team) {
            $subunits = Subunit::where('team_id', $team->id)
                ->with(['leader', 'members'])
                ->orderBy('name')
                ->get();

            // Get all members of the team (for selecting as subunit members/leaders)
            $teamMembers = User::where('chapter_id', $chapterId)
                ->whereHas('teams', fn($q) => $q->where('teams.id', $team->id))
                ->orderBy('name')
                ->get();
        }

        return [
            'chapters' => $chapters,
            'subunits' => collect($subunits),
            'teamMembers' => $teamMembers,
            'team' => $team,
        ];
    }
};
