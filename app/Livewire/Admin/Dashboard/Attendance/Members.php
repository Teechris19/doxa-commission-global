<?php

namespace App\Livewire\Admin\Dashboard\Attendance;

use App\Models\{Chapter, User, Team};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

#[Layout('components.layouts.admin')]
new class extends Component {
    use WithPagination;

    public $chapter;
    public $searchName = '';
    public $filterTeamId = '';
    public $perPage = 15;

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
