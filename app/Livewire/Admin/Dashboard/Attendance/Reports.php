<?php

namespace App\Livewire\Admin\Dashboard\Attendance;

use App\Models\{AttendanceRecord, AttendanceSession, Chapter, User, Team};
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

#[Layout('components.layouts.admin')]
class Reports extends Component {
    public $chapter;
    public $dateFilter = '7'; // today, yesterday, 7, 30, 180, 365, custom
    public $customStartDate;
    public $customEndDate;

    public function mount()
    {
        $user = Auth::user();
        if (!$this->chapter && $user) {
            $this->chapter = Chapter::find($user->chapter_id)?->name;
        }
        $this->customStartDate = now()->toDateString();
        $this->customEndDate = now()->toDateString();
    }

    private function getChapterId()
    {
        $user = Auth::user();
        return $this->chapter ? Chapter::where('name', $this->chapter)->first()?->id : $user->chapter_id;
    }

    private function getDateRange()
    {
        $today = now()->toDateString();
        
        switch ($this->dateFilter) {
            case 'today':
                return ['start' => $today, 'end' => $today];
            
            case 'yesterday':
                return [
                    'start' => now()->subDay()->toDateString(),
                    'end' => now()->toDateString()
                ];
            
            case '7':
                return [
                    'start' => now()->subDays(6)->toDateString(),
                    'end' => $today
                ];
            
            case '30':
                return [
                    'start' => now()->subDays(29)->toDateString(),
                    'end' => $today
                ];
            
            case '180':
                return [
                    'start' => now()->subDays(179)->toDateString(),
                    'end' => $today
                ];
            
            case '365':
                return [
                    'start' => now()->subDays(364)->toDateString(),
                    'end' => $today
                ];
            
            case 'custom':
                return [
                    'start' => $this->customStartDate ?: $today,
                    'end' => $this->customEndDate ?: $today
                ];
            
            default:
                return [
                    'start' => now()->subDays(29)->toDateString(),
                    'end' => $today
                ];
        }
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

        $absent = AttendanceRecord::where('chapter_id', $chapterId)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->where('status', 'absent')
            ->count();

        $attendanceRate = $totalRecords > 0 ? round(($present / $totalRecords) * 100, 1) : 0;
        $absenteeRate = $totalRecords > 0 ? round(($absent / $totalRecords) * 100, 1) : 0;

        return [
            'total' => $totalRecords,
            'present' => $present,
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
                'attendanceRecords as absent_count' => fn($q) => $q->where('status', 'absent')
                    ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]),
                'attendanceRecords as total_count' => fn($q) => $q->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]),
            ])
            ->orderByDesc('total_count')
            ->take(5)
            ->get()
            ->map(function($team) {
                $total = $team->total_count;
                $present = $team->present_count;
                $rate = $total > 0 ? round(($present / $total) * 100, 1) : 0;

                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'total' => $total,
                    'present' => $present,
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
                'attendanceRecords as absent_count' => fn($q) => $q->where('status', 'absent')
                    ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]),
                'attendanceRecords as total_count' => fn($q) => $q->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]),
            ])
            ->orderByDesc('total_count')
            ->limit(20)
            ->get()
            ->filter(fn($user) => $user->total_count > 0)
            ->map(function($user) {
                $total = $user->total_count;
                $present = $user->present_count;
                $rate = $total > 0 ? round(($present / $total) * 100, 1) : 0;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'total' => $total,
                    'present' => $present,
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
            $absent = $session->records()->where('status', 'absent')->count();

            return [
                'date' => $session->date->format('M d'),
                'present' => $present,
                'absent' => $absent,
                'total' => $present + $absent,
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
                DB::raw('SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent')
            )
            ->groupBy('role')
            ->get()
            ->map(function($item) {
                $rate = $item->total > 0 ? round(($item->present / $item->total) * 100, 1) : 0;
                return [
                    'role' => $item->role,
                    'total' => $item->total,
                    'present' => $item->present,
                    'absent' => $item->absent,
                    'rate' => $rate,
                ];
            });
    }

    public function render()
    {
        $chapters = Chapter::orderBy('name')->get();

        return view('livewire.admin.dashboard.attendance.reports', [
            'chapters' => $chapters,
            'overallStats' => $this->overallStats,
            'teamStats' => $this->teamStats,
            'memberStats' => $this->memberStats,
            'trendData' => $this->trendData,
            'roleBreakdown' => $this->roleBreakdown,
        ]);
    }

    public function exportCSV()
    {
        $chapterId = $this->getChapterId();
        $dateRange = $this->getDateRange();

        $records = AttendanceRecord::with(['user', 'team'])
            ->where('chapter_id', $chapterId)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->orderBy('created_at', 'desc')
            ->get();

        $csvData = "Name,Team,Role,Status,Time,Date\n";
        
        foreach ($records as $record) {
            $csvData .= sprintf(
                '"%s","%s","%s","%s","%s","%s"' . "\n",
                $record->user->name ?? 'N/A',
                $record->team->name ?? 'N/A',
                $record->role,
                ucfirst($record->status),
                $record->time ?? '-',
                $record->created_at->format('Y-m-d')
            );
        }

        $filename = 'attendance_report_' . date('Y-m-d') . '.csv';
        
        return response()->streamDownload(function () use ($csvData) {
            echo $csvData;
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function exportExcel()
    {
        // CSV is Excel-compatible
        return $this->exportCSV();
    }
}
