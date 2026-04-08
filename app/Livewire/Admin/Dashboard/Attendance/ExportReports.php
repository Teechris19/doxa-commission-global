<?php

namespace App\Livewire\Admin\Dashboard\Attendance;

use App\Models\{AttendanceRecord, AttendanceGuest, Chapter, Team, User};
use Livewire\Attributes\Layout;
use Livewire\Component;
use TallStackUi\Traits\Interactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

#[Layout('components.layouts.admin')]
class ExportReports extends Component {
    use Interactions;

    public $chapter;
    public $dateFilter = '7';
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
                return ['start' => now()->subDay()->toDateString(), 'end' => $today];
            case '7':
                return ['start' => now()->subDays(6)->toDateString(), 'end' => $today];
            case '30':
                return ['start' => now()->subDays(29)->toDateString(), 'end' => $today];
            case '180':
                return ['start' => now()->subDays(179)->toDateString(), 'end' => $today];
            case '365':
                return ['start' => now()->subDays(364)->toDateString(), 'end' => $today];
            case 'custom':
                return ['start' => $this->customStartDate ?: $today, 'end' => $this->customEndDate ?: $today];
            default:
                return ['start' => now()->subDays(29)->toDateString(), 'end' => $today];
        }
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
        // For now, redirect to CSV as Excel-compatible format
        return $this->exportCSV();
    }

    public function render()
    {
        $chapterId = $this->getChapterId();
        $chapters = Chapter::orderBy('name')->get();

        return view('livewire.admin.dashboard.attendance.export-reports', [
            'chapters' => $chapters,
        ]);
    }
}
