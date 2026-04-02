<?php

namespace App\Livewire\Admin\Dashboard\Attendance;

use App\Models\{Chapter, User, Team};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.admin')]
new class extends Component {
    use WithPagination, Interactions;

    public $chapter;
    public $searchName = '';
    public $filterTeamId = '';
    public $perPage = 15;

    // Create member modal
    public $showCreateModal = false;
    public $newMemberName = '';
    public $newMemberEmail = '';
    public $newMemberPhone = '';
    public $newMemberPassword = '';
    public $newMemberRole = 'Member';
    public $selectedTeams = [];

    public function mount()
    {
        $user = Auth::user();
        if (!$this->chapter && $user) {
            $this->chapter = Chapter::find($user->chapter_id)?->name;
        }
    }

    private function getChapterId()
    {
        $user = Auth::user();
        return $this->chapter ? Chapter::where('name', $this->chapter)->first()?->id : $user->chapter_id;
    }

    public function createMember()
    {
        $chapterId = $this->getChapterId();

        $validated = $this->validate([
            'newMemberName' => 'required|string|min:3|max:255',
            'newMemberEmail' => 'nullable|email|unique:users,email',
            'newMemberPhone' => 'nullable|string|max:50',
            'newMemberPassword' => 'nullable|min:8',
            'selectedTeams' => 'nullable|array',
        ]);

        $user = User::create([
            'name' => $validated['newMemberName'],
            'email' => $validated['newMemberEmail'] ?: null,
            'phone' => $validated['newMemberPhone'] ?: null,
            'password' => $validated['newMemberPassword'] ? Hash::make($validated['newMemberPassword']) : Hash::make('password'),
            'chapter_id' => $chapterId,
        ]);

        // Assign to selected teams
        if (!empty($this->selectedTeams)) {
            $user->teams()->attach($this->selectedTeams);
        }

        $this->toast()->success('Member created', "{$user->name} has been added successfully.")->send();
        
        $this->reset(['showCreateModal', 'newMemberName', 'newMemberEmail', 'newMemberPhone', 'newMemberPassword', 'newMemberRole', 'selectedTeams']);
    }

    private function getMembers()
    {
        $chapterId = $this->getChapterId();

        $query = User::where('chapter_id', $chapterId)
            ->with(['teams']);

        if ($this->filterTeamId) {
            $query->whereHas('teams', fn($q) => $q->where('teams.id', $this->filterTeamId));
        }

        if ($this->searchName) {
            $query->where('name', 'like', "%{$this->searchName}%");
        }

        return $query->orderBy('name')->paginate($this->perPage);
    }

    public function with(): array
    {
        $chapterId = $this->getChapterId();
        $chapters = Chapter::orderBy('name')->get();
        $teams = Team::where('chapter_id', $chapterId)->orderBy('name')->get();
        $members = $this->getMembers();

        return [
            'chapters' => $chapters,
            'teams' => $teams,
            'members' => $members,
        ];
    }
};
