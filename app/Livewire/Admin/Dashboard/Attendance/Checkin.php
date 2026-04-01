<?php

namespace App\Livewire\Admin\Dashboard\Attendance;

use App\Models\{AttendanceSession, AttendanceRecord, Chapter, User, Team};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use TallStackUi\Traits\Interactions;
use Illuminate\Support\Facades\Auth;

#[Layout('components.layouts.admin')]
new class extends Component {
    use Interactions;

    public $chapter;
    public $selectedSessionId;
    public $searchName = '';
    public $filterTeamId = '';
    public $showAlreadyMarked = false;

    // For marking attendance
    public $selectedUserId;
    public $manualTime = '';

    protected $userCache = [];

    public function mount()
    {
        $user = Auth::user();
        if (!$this->chapter && $user) {
            $this->chapter = Chapter::find($user->chapter_id)?->name;
        }
    }

    public function updatedSelectedSessionId()
    {
        $this->reset(['searchName', 'filterTeamId', 'selectedUserId', 'manualTime']);
    }

    public function selectUser($userId)
    {
        $this->selectedUserId = $userId;
    }

    public function clearSelection()
    {
        $this->reset(['selectedUserId', 'manualTime']);
    }

    public function markAttendance($status)
    {
        if (!$this->selectedSessionId) {
            $this->toast()->error('No session selected', 'Please select an attendance session first.')->send();
            return;
        }

        if (!$this->selectedUserId) {
            $this->toast()->error('No member selected', 'Please select a member to mark attendance.')->send();
            return;
        }

        $session = AttendanceSession::find($this->selectedSessionId);
        if (!$session || $session->status !== 'open') {
            $this->toast()->error('Invalid session', 'This session is not open for attendance.')->send();
            return;
        }

        $user = User::find($this->selectedUserId);
        if (!$user) {
            $this->toast()->error('User not found', 'The selected member does not exist.')->send();
            return;
        }

        // Check if already marked
        $existing = AttendanceRecord::where('attendance_session_id', $this->selectedSessionId)
            ->where('user_id', $this->selectedUserId)
            ->first();

        if ($existing) {
            $this->toast()->warning('Already marked', 'This member already has attendance recorded for this session.')->send();
            return;
        }

        // Get user's team and role
        $teamId = $user->teams()->first()?->id ?? null;
        if (!$teamId) {
            $this->toast()->error('No team assigned', 'This member is not assigned to any team.')->send();
            return;
        }

        $team = Team::find($teamId);
        $role = $user->role ?? 'Member';

        AttendanceRecord::create([
            'attendance_session_id' => $this->selectedSessionId,
            'user_id' => $this->selectedUserId,
            'team_id' => $teamId,
            'chapter_id' => $session->chapter_id,
            'role' => $role,
            'status' => $status,
            'time' => $this->manualTime ?: null,
            'marked_by' => Auth::id(),
        ]);

        $this->toast()->success('Attendance marked', "{$user->name} marked as {$status}.")->send();
        
        // Reset selection but keep session
        $this->reset(['selectedUserId', 'manualTime', 'searchName']);
    }

    private function getSessions()
    {
        $user = Auth::user();
        $chapterId = $this->chapter ? Chapter::where('name', $this->chapter)->first()?->id : $user->chapter_id;

        return AttendanceSession::with(['service', 'event'])
            ->where('status', 'open')
            ->when($chapterId, fn($q) => $q->where('chapter_id', $chapterId))
            ->orderByDesc('date')
            ->get();
    }

    private function getTeams()
    {
        $user = Auth::user();
        $chapterId = $this->chapter ? Chapter::where('name', $this->chapter)->first()?->id : $user->chapter_id;

        return Team::where('chapter_id', $chapterId)
            ->orderBy('name')
            ->get();
    }

    private function getMembers()
    {
        $user = Auth::user();
        $chapterId = $this->chapter ? Chapter::where('name', $this->chapter)->first()?->id : $user->chapter_id;

        $query = User::where('chapter_id', $chapterId)
            ->whereHas('teams');

        if ($this->filterTeamId) {
            $query->whereHas('teams', fn($q) => $q->where('teams.id', $this->filterTeamId));
        }

        if ($this->searchName) {
            $query->where('name', 'like', "%{$this->searchName}%");
        }

        return $query->with('teams')->limit(50)->get();
    }

    public function getSelectedUserProperty()
    {
        if (!$this->selectedUserId) {
            return null;
        }

        if (isset($this->userCache[$this->selectedUserId])) {
            return $this->userCache[$this->selectedUserId];
        }

        $user = User::with('teams')->find($this->selectedUserId);
        $this->userCache[$this->selectedUserId] = $user;
        return $user;
    }

    public function with(): array
    {
        $sessions = $this->getSessions();
        $teams = $this->getTeams();
        $members = $this->getMembers();

        // Get already marked users for current session
        $markedUserIds = [];
        if ($this->selectedSessionId) {
            $markedUserIds = AttendanceRecord::where('attendance_session_id', $this->selectedSessionId)
                ->pluck('user_id')
                ->toArray();
        }

        return [
            'sessions' => $sessions,
            'teams' => $teams,
            'members' => $members,
            'markedUserIds' => $markedUserIds,
        ];
    }
};
