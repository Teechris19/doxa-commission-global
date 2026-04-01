<?php

namespace App\Livewire\Admin\Dashboard\Attendance;

use App\Models\{AttendanceRecord, AttendanceSession, Chapter, User, Team};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

#[Layout('components.layouts.admin')]
new class extends Component {
    public $chapter;
    public $dateFilter = '30'; // 7, 30, 180, 365 days

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

    private function getDateRange()
    {
        $days = (int) $this->dateFilter;
        return [
            'start' => now()->subDays($days)->toDateString(),
            'end' => now()->toDateString(),
        ];
    }

    public function getOverallStatsProperty()
    {
        $chapterId = $this->getChapterId();
        $dateRange = $this->getDateRange();

        $totalRecords = AttendanceRecord::where('chapter_id', $chapterId)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        $present = AttendanceRecord::where('chapter_id', $chapterId)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->where('status', 'present')
            ->count();

        $late = AttendanceRecord::where('chapter_id', $chapterId)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->where('status', 'late')
            ->count();

        $absent = AttendanceRecord::where('chapter_id', $chapterId)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->where('status', 'absent')
            ->count();

        $attendanceRate = $totalRecords > 0 ? round((($present + $late) / $totalRecords) * 100, 1) : 0;
        $absenteeRate = $totalRecords > 0 ? round(($absent / $totalRecords) * 100, 1) : 0;

        return [
            'total' => $totalRecords,
            'present' => $present,
            'late' => $late,
            'absent' => $absent,
            'attendance_rate' => $attendanceRate,
            'absentee_rate' => $absenteeRate,
        ];
    }

    public function getTeamStatsProperty()
    {
        $chapterId = $this->getChapterId();
        $dateRange = $this->getDateRange();

        return Team::where('chapter_id', $chapterId)
            ->withCount([
                'attendanceRecords as present_count' => fn($q) => $q->where('status', 'present')
                    ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]),
                'attendanceRecords as late_count' => fn($q) => $q->where('status', 'late')
                    ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]),
                'attendanceRecords as absent_count' => fn($q) => $q->where('status', 'absent')
                    ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]),
                'attendanceRecords as total_count' => fn($q) => $q->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]),
            ])
            ->orderByDesc('total_count')
            ->get()
            ->map(function($team) {
                $total = $team->total_count;
                $presentLate = $team->present_count + $team->late_count;
                $rate = $total > 0 ? round(($presentLate / $total) * 100, 1) : 0;
                
                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'total' => $total,
                    'present' => $team->present_count,
                    'late' => $team->late_count,
                    'absent' => $team->absent_count,
                    'rate' => $rate,
                ];
            });
    }

    public function getMemberStatsProperty()
    {
        $chapterId = $this->getChapterId();
        $dateRange = $this->getDateRange();

        return User::where('chapter_id', $chapterId)
            ->withCount([
                'attendanceRecords as present_count' => fn($q) => $q->where('status', 'present')
                    ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]),
                'attendanceRecords as late_count' => fn($q) => $q->where('status', 'late')
                    ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]),
                'attendanceRecords as absent_count' => fn($q) => $q->where('status', 'absent')
                    ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]),
                'attendanceRecords as total_count' => fn($q) => $q->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]),
            ])
            ->where('total_count', '>', 0)
            ->orderByDesc('total_count')
            ->limit(20)
            ->get()
            ->map(function($user) {
                $total = $user->total_count;
                $presentLate = $user->present_count + $user->late_count;
                $rate = $total > 0 ? round(($presentLate / $total) * 100, 1) : 0;
                
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'total' => $total,
                    'present' => $user->present_count,
                    'late' => $user->late_count,
                    'absent' => $user->absent_count,
                    'rate' => $rate,
                ];
            });
    }

    public function getTrendDataProperty()
    {
        $chapterId = $this->getChapterId();
        $dateRange = $this->getDateRange();
        $days = (int) $this->dateFilter;

        $sessions = AttendanceSession::where('chapter_id', $chapterId)
            ->whereBetween('date', [$dateRange['start'], $dateRange['end']])
            ->orderBy('date')
            ->get();

        return $sessions->map(function($session) {
            $present = $session->records()->where('status', 'present')->count();
            $late = $session->records()->where('status', 'late')->count();
            $absent = $session->records()->where('status', 'absent')->count();
            
            return [
                'date' => $session->date->format('M d'),
                'present' => $present,
                'late' => $late,
                'absent' => $absent,
                'total' => $present + $late + $absent,
            ];
        });
    }

    public function getRoleBreakdownProperty()
    {
        $chapterId = $this->getChapterId();
        $dateRange = $this->getDateRange();

        return AttendanceRecord::where('chapter_id', $chapterId)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->select('role', DB::raw('COUNT(*) as total'), 
                DB::raw('SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present'),
                DB::raw('SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late'),
                DB::raw('SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent')
            )
            ->groupBy('role')
            ->get()
            ->map(function($item) {
                $rate = $item->total > 0 ? round((($item->present + $item->late) / $item->total) * 100, 1) : 0;
                return [
                    'role' => $item->role,
                    'total' => $item->total,
                    'present' => $item->present,
                    'late' => $item->late,
                    'absent' => $item->absent,
                    'rate' => $rate,
                ];
            });
    }

    public function with(): array
    {
        $chapters = Chapter::orderBy('name')->get();
        
        return [
            'chapters' => $chapters,
            'overallStats' => $this->overallStats,
            'teamStats' => $this->teamStats,
            'memberStats' => $this->memberStats,
            'trendData' => $this->trendData,
            'roleBreakdown' => $this->roleBreakdown,
        ];
    }
};
