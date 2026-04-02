<?php

namespace App\Livewire\Admin\Dashboard\Attendance;

use App\Models\{AttendanceSession, AttendanceRecord, AttendanceGuest, Chapter, User, Team};
use Livewire\Attributes\Layout;
use Livewire\Component;
use TallStackUi\Traits\Interactions;
use Illuminate\Support\Facades\Auth;

#[Layout('components.layouts.admin')]
class Checkin extends Component {
    use Interactions;

    public $chapter;
    public $selectedSessionId;
    public $searchName = '';
    public $filterTeamId = '';
    public $showAlreadyMarked = false;

    // For marking attendance (registered members)
    public $selectedUserId;
    public $manualTime = '';

    // For guest check-in
    public $showGuestForm = false;
    public $guestName = '';
    public $guestPhone = '';
    public $guestEmail = '';
    public $guestTime = '';
    public $guestStatus = 'present';

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

    public function toggleGuestForm()
    {
        $this->showGuestForm = !$this->showGuestForm;
        $this->reset(['guestName', 'guestPhone', 'guestEmail', 'guestTime', 'guestStatus']);
    }

    public function markGuestAttendance()
    {
        if (!$this->selectedSessionId) {
            $this->toast()->error('No session selected', 'Please select an attendance session first.')->send();
            return;
        }

        $validated = $this->validate([
            'guestName' => 'required|string|min:2|max:255',
            'guestPhone' => 'nullable|string|max:50',
            'guestEmail' => 'nullable|email|max:255',
            'guestTime' => 'nullable|date_format:H:i',
            'guestStatus' => 'required|in:present,late,absent',
        ]);

        AttendanceGuest::create([
            'attendance_session_id' => $this->selectedSessionId,
            'name' => $validated['guestName'],
            'phone' => $validated['guestPhone'] ?: null,
            'email' => $validated['guestEmail'] ?: null,
            'status' => $validated['guestStatus'],
            'time' => $validated['guestTime'] ?: null,
            'marked_by' => Auth::id(),
        ]);

        $this->toast()->success('Guest checked in', "{$validated['guestName']} has been checked in as {$validated['guestStatus']}.")->send();
        
        $this->reset(['showGuestForm', 'guestName', 'guestPhone', 'guestEmail', 'guestTime', 'guestStatus']);
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

        // Refresh the page to show updated status
    }

    public function markMemberAttendance($userId, $status)
    {
        if (!$this->selectedSessionId) {
            $this->toast()->error('No session selected', 'Please select an attendance session first.')->send();
            return;
        }

        $user = User::find($userId);
        if (!$user) {
            $this->toast()->error('User not found', 'The selected member does not exist.')->send();
            return;
        }

        // Check if already marked
        $existing = AttendanceRecord::where('attendance_session_id', $this->selectedSessionId)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            $this->toast()->warning('Already marked', 'This member already has attendance recorded.')->send();
            return;
        }

        // Get user's team
        $teamId = $user->teams()->first()?->id ?? 1;
        $role = $user->role ?? 'Member';
        $chapterId = $user->chapter_id ?? $this->getChapterId();

        AttendanceRecord::create([
            'attendance_session_id' => $this->selectedSessionId,
            'user_id' => $userId,
            'team_id' => $teamId,
            'chapter_id' => $chapterId,
            'role' => $role,
            'status' => $status,
            'marked_by' => Auth::id(),
        ]);

        $this->toast()->success('Success', "{$user->name} marked as {$status}.")->send();
    }

    private function getChapterId()
    {
        $user = Auth::user();
        return $this->chapter ? Chapter::where('name', $this->chapter)->first()?->id : $user->chapter_id;
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

        // Get all users in the chapter (with or without teams)
        $query = User::where('chapter_id', $chapterId);

        if ($this->filterTeamId) {
            $query->whereHas('teams', fn($q) => $q->where('teams.id', $this->filterTeamId));
        } else {
            // If no filter, still show all members but eager load teams
            $query->with('teams');
        }

        if ($this->searchName) {
            $query->where('name', 'like', "%{$this->searchName}%");
        }

        return $query->with('teams')->orderBy('name')->limit(50)->get();
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

    public function render()
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

        // Get guest check-ins count for current session
        $guestCount = 0;
        if ($this->selectedSessionId) {
            $guestCount = AttendanceGuest::where('attendance_session_id', $this->selectedSessionId)->count();
        }

        // Get selected user details
        $selectedUser = null;
        if ($this->selectedUserId) {
            $selectedUser = User::with('teams')->find($this->selectedUserId);
        }

        return view('livewire.admin.dashboard.attendance.checkin', [
            'sessions' => $sessions,
            'teams' => $teams,
            'members' => $members,
            'markedUserIds' => $markedUserIds,
            'guestCount' => $guestCount,
            'selectedUser' => $selectedUser,
        ]);
    }
}
