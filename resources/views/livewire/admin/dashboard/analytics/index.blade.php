<?php

use App\Models\{Events, EventForm, BeliversAcademy, StudentClasses, Partnership, PrayerRequest, Sermons, Transport, Accounts, Chapter};
use Livewire\Attributes\{Layout, Url};
use Livewire\Volt\Component;
use Illuminate\Support\Facades\{DB, Auth};
use Carbon\Carbon;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions;

    public $dateRange = '30'; // days
    public $selectedModule = 'overview';

    #[Url(keep: true)]
    public $chapter;

    public $chapterId;
    public $chapters = [];
    public $isLoading = false;

    // Overview Stats
    public $totalEvents;
    public $totalRegistrations;
    public $totalStudents;
    public $totalPartnerships;
    public $totalPrayerRequests;
    public $totalSermons;

    // Event Analytics
    public $eventRegistrationTrend = [];
    public $eventsByStatus = [];
    public $topEvents = [];
    public $capacityUtilization = [];

    // Academy Analytics
    public $studentEnrollmentTrend = [];
    public $classCompletionRate;
    public $topPerformingClasses = [];

    // Financial Analytics
    public $partnershipsByStatus = [];
    public $monthlyPartnershipTrend = [];

    // Prayer & Transport
    public $prayerRequestsByStatus = [];
    public $transportRequestsByStatus = [];

    public function mount()
    {
        $user = Auth::user();

        // Load chapters for super admin
        if ($user->hasRole('super-admin')) {
            $this->chapters = Chapter::orderBy('name')->get();
        }

        // Set chapter filter
        if ($this->chapter) {
            $chapterModel = Chapter::where('name', $this->chapter)->first();
            $this->chapterId = $chapterModel?->id;
        } elseif (!$user->hasRole('super-admin')) {
            // Regular admin: use their chapter
            $this->chapterId = $user->chapter_id;
        }

        $this->loadAnalytics();
    }

    public function updatedChapter()
    {
        if ($this->chapter) {
            $chapterModel = Chapter::where('name', $this->chapter)->first();
            $this->chapterId = $chapterModel?->id;
        } else {
            $this->chapterId = null;
        }
        $this->loadAnalytics();
    }

    public function updatedDateRange()
    {
        $this->loadAnalytics();
    }

    public function loadAnalytics()
    {
        $this->isLoading = true;

        $startDate = null;
        if ($this->dateRange !== 'all') {
            $days = (int) $this->dateRange;
            $startDate = Carbon::now()->subDays($days);
        }

        // Overview Stats with chapter filtering
        $this->totalEvents = Events::when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->when($startDate, fn($q) => $q->where('created_at', '>=', $startDate))
            ->count();

        $this->totalRegistrations = EventForm::when($this->chapterId, function($q) {
                $q->whereHas('event', fn($eq) => $eq->where('chapter_id', $this->chapterId));
            })
            ->when($startDate, fn($q) => $q->where('created_at', '>=', $startDate))
            ->count();

        $this->totalStudents = StudentClasses::when($this->chapterId, function ($q) {
                $q->whereHas('user', fn($uq) => $uq->where('chapter_id', $this->chapterId));
            })
            ->when($startDate, fn($q) => $q->where('created_at', '>=', $startDate))
            ->distinct('user_id')
            ->count('user_id');

        $this->totalPartnerships = Partnership::when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->when($startDate, fn($q) => $q->where('created_at', '>=', $startDate))
            ->count();

        $this->totalPrayerRequests = PrayerRequest::when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->when($startDate, fn($q) => $q->where('created_at', '>=', $startDate))
            ->count();

        $this->totalSermons = Sermons::when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->when($startDate, fn($q) => $q->where('created_at', '>=', $startDate))
            ->count();

        // Event Analytics
        $this->loadEventAnalytics($startDate);

        // Academy Analytics
        $this->loadAcademyAnalytics($startDate);

        // Financial Analytics
        $this->loadFinancialAnalytics($startDate);

        // Prayer & Transport
        $this->loadOtherAnalytics($startDate);

        $this->isLoading = false;

        // Dispatch event to update charts
        $this->dispatch('analytics-updated');
    }

    private function loadEventAnalytics($startDate)
    {
        // Registration trend (daily)
        $registrationTrend = EventForm::when($this->chapterId, function($q) {
                $q->whereHas('event', fn($eq) => $eq->where('chapter_id', $this->chapterId));
            })
            ->when($startDate, fn($q) => $q->where('created_at', '>=', $startDate))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $this->eventRegistrationTrend = [
            'labels' => $registrationTrend->pluck('date')->toArray(),
            'data' => $registrationTrend->pluck('count')->toArray()
        ];

        // Events by status
        $eventsByStatus = Events::when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        $this->eventsByStatus = [
            'labels' => $eventsByStatus->pluck('status')->map(fn($s) => ucfirst($s))->toArray(),
            'data' => $eventsByStatus->pluck('count')->toArray()
        ];

        // Top events by registrations
        $this->topEvents = Events::when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->withCount('forms')
            ->orderBy('forms_count', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($e) => [
                'title' => $e->title,
                'registrations' => $e->forms_count,
                'capacity' => $e->capacity,
                'utilization' => $e->capacity ? round(($e->forms_count / $e->capacity) * 100, 1) : 0
            ])
            ->toArray();
    }

    private function loadAcademyAnalytics($startDate)
    {
        // Student enrollment trend
        $enrollmentTrend = StudentClasses::when($this->chapterId, function ($q) {
                $q->whereHas('user', fn($uq) => $uq->where('chapter_id', $this->chapterId));
            })
            ->when($startDate, fn($q) => $q->where('created_at', '>=', $startDate))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(DISTINCT user_id) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $this->studentEnrollmentTrend = [
            'labels' => $enrollmentTrend->pluck('date')->toArray(),
            'data' => $enrollmentTrend->pluck('count')->toArray()
        ];

        // Class completion rate
        $totalClasses = StudentClasses::when($this->chapterId, function ($q) {
                $q->whereHas('user', fn($uq) => $uq->where('chapter_id', $this->chapterId));
            })
            ->count();
        $completedClasses = StudentClasses::when($this->chapterId, function ($q) {
                $q->whereHas('user', fn($uq) => $uq->where('chapter_id', $this->chapterId));
            })
            ->where('status', 'completed')
            ->count();
        $this->classCompletionRate = $totalClasses > 0 ? round(($completedClasses / $totalClasses) * 100, 1) : 0;

        // Top performing classes
        $this->topPerformingClasses = StudentClasses::when($this->chapterId, function ($q) {
                $q->whereHas('user', fn($uq) => $uq->where('chapter_id', $this->chapterId));
            })
            ->select('academy_id', DB::raw('COUNT(*) as enrollments'))
            ->with('academy')
            ->groupBy('academy_id')
            ->orderBy('enrollments', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($sc) => [
                'class_name' => $sc->academy->title ?? 'Unknown',
                'enrollments' => $sc->enrollments
            ])
            ->toArray();
    }

    private function loadFinancialAnalytics($startDate)
    {
        // Partnerships by status
        $partnershipsByStatus = Partnership::when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        $this->partnershipsByStatus = [
            'labels' => $partnershipsByStatus->pluck('status')->map(fn($s) => ucfirst($s))->toArray(),
            'data' => $partnershipsByStatus->pluck('count')->toArray()
        ];

        // Monthly partnership trend
        $monthlyTrend = Partnership::when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->where('created_at', '>=', Carbon::now()->subMonths(6))
            ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"), DB::raw('COUNT(*) as count'))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $this->monthlyPartnershipTrend = [
            'labels' => $monthlyTrend->pluck('month')->toArray(),
            'data' => $monthlyTrend->pluck('count')->toArray()
        ];
    }

    private function loadOtherAnalytics($startDate)
    {
        // Prayer requests by addressed status
        $prayerByStatus = PrayerRequest::when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->select('is_addressed', DB::raw('COUNT(*) as count'))
            ->groupBy('is_addressed')
            ->get();

        $this->prayerRequestsByStatus = [
            'labels' => $prayerByStatus->pluck('is_addressed')->map(fn($s) => $s ? 'Addressed' : 'Pending')->toArray(),
            'data' => $prayerByStatus->pluck('count')->toArray()
        ];

        // Transport requests by status
        $transportByStatus = Transport::when($this->chapterId, fn($q) => $q->where('chapter_id', $this->chapterId))
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        $this->transportRequestsByStatus = [
            'labels' => $transportByStatus->pluck('status')->map(fn($s) => ucfirst($s))->toArray(),
            'data' => $transportByStatus->pluck('count')->toArray()
        ];
    }

    public function exportAnalytics($format)
    {
        $format = strtolower((string) $format);

        $data = [
            ['metric' => 'Total Events', 'value' => $this->totalEvents],
            ['metric' => 'Total Registrations', 'value' => $this->totalRegistrations],
            ['metric' => 'Total Students', 'value' => $this->totalStudents],
            ['metric' => 'Total Partnerships', 'value' => $this->totalPartnerships],
            ['metric' => 'Total Prayer Requests', 'value' => $this->totalPrayerRequests],
            ['metric' => 'Total Sermons', 'value' => $this->totalSermons],
        ];

        if ($format === 'csv') {
            $filename = 'analytics_export_' . now()->format('Ymd_His') . '.csv';
            return response()->streamDownload(function () use ($data) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Metric', 'Value']);
                foreach ($data as $row) {
                    fputcsv($handle, [$row['metric'], $row['value']]);
                }
                fclose($handle);
            }, $filename);
        }

        if ($format === 'pdf') {
            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.analytics', ['data' => $data]);
                return response()->streamDownload(function () use ($pdf) {
                    echo $pdf->output();
                }, 'analytics_export_' . now()->format('Ymd_His') . '.pdf');
            }

            $this->toast()->error('Export Unavailable', 'PDF export requires dompdf.')->send();
            return;
        }

        $this->toast()->error('Export Unavailable', 'Unsupported export format.')->send();
    }
}; ?>

<div class="space-y-6" wire:loading.class="opacity-50">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">Analytics Dashboard</h1>
            <p class="text-zinc-600 dark:text-zinc-400 mt-1">Comprehensive insights and reporting</p>
            @if($chapterId && $chapter)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 mt-2">
                    <i class="bi bi-filter-circle mr-1"></i> Filtered by: {{ $chapter }}
                </span>
            @endif
        </div>

        <div class="flex flex-wrap gap-3">
            {{-- Chapter Filter (Super Admin Only) --}}
            @if(Auth::user()->hasRole('super-admin') && count($chapters) > 0)
                <select wire:model.live="chapter" class="px-4 py-2 rounded-lg bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600">
                    <option value="">All Chapters</option>
                    @foreach($chapters as $chap)
                        <option value="{{ $chap->name }}">{{ $chap->name }}</option>
                    @endforeach
                </select>
            @endif

            <select wire:model.live="dateRange" class="px-4 py-2 rounded-lg bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600">
                <option value="7">Last 7 Days</option>
                <option value="30">Last 30 Days</option>
                <option value="90">Last 90 Days</option>
                <option value="180">Last 6 Months</option>
                <option value="365">Last Year</option>
                <option value="all">All Time</option>
            </select>

            <div class="flex gap-2">
                <button wire:click="exportAnalytics('pdf')" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition disabled:opacity-50" wire:loading.attr="disabled">
                    <i class="bi bi-file-pdf"></i> PDF
                </button>
                <button wire:click="exportAnalytics('excel')" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition disabled:opacity-50" wire:loading.attr="disabled">
                    <i class="bi bi-file-excel"></i> Excel
                </button>
            </div>
        </div>
    </div>

    {{-- Loading Indicator --}}
    <div wire:loading class="fixed top-20 right-5 z-50 bg-blue-500 text-white px-4 py-2 rounded-lg shadow-lg flex items-center gap-2">
        <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>Loading analytics...</span>
    </div>

    <!-- Overview Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white shadow-lg">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-blue-100 text-sm font-medium">Total Events</p>
                    <h3 class="text-3xl font-bold mt-2">{{ number_format($totalEvents) }}</h3>
                </div>
                <div class="bg-blue-400 bg-opacity-30 rounded-lg p-3">
                    <i class="bi bi-calendar-event text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white shadow-lg">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-purple-100 text-sm font-medium">Registrations</p>
                    <h3 class="text-3xl font-bold mt-2">{{ number_format($totalRegistrations) }}</h3>
                </div>
                <div class="bg-purple-400 bg-opacity-30 rounded-lg p-3">
                    <i class="bi bi-people text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white shadow-lg">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-green-100 text-sm font-medium">Students</p>
                    <h3 class="text-3xl font-bold mt-2">{{ number_format($totalStudents) }}</h3>
                </div>
                <div class="bg-green-400 bg-opacity-30 rounded-lg p-3">
                    <i class="bi bi-mortarboard text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl p-6 text-white shadow-lg">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-yellow-100 text-sm font-medium">Partnerships</p>
                    <h3 class="text-3xl font-bold mt-2">{{ number_format($totalPartnerships) }}</h3>
                </div>
                <div class="bg-yellow-400 bg-opacity-30 rounded-lg p-3">
                    <i class="bi bi-heart text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-pink-500 to-pink-600 rounded-xl p-6 text-white shadow-lg">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-pink-100 text-sm font-medium">Prayer Requests</p>
                    <h3 class="text-3xl font-bold mt-2">{{ number_format($totalPrayerRequests) }}</h3>
                </div>
                <div class="bg-pink-400 bg-opacity-30 rounded-lg p-3">
                    <i class="bi bi-chat-heart text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl p-6 text-white shadow-lg">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-indigo-100 text-sm font-medium">Sermons</p>
                    <h3 class="text-3xl font-bold mt-2">{{ number_format($totalSermons) }}</h3>
                </div>
                <div class="bg-indigo-400 bg-opacity-30 rounded-lg p-3">
                    <i class="bi bi-play-circle text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" x-data="analyticsCharts" @analytics-updated.window="updateCharts()">
        <!-- Event Registration Trend -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100 mb-4">
                <i class="bi bi-graph-up text-blue-500"></i> Event Registrations Trend
            </h3>
            @if(empty($eventRegistrationTrend['data']) || array_sum($eventRegistrationTrend['data']) == 0)
                <div class="flex flex-col items-center justify-center h-[300px] text-zinc-400">
                    <i class="bi bi-graph-up text-6xl mb-3"></i>
                    <p class="text-sm">No registration data available</p>
                </div>
            @else
                <canvas id="registrationTrendChart" height="300"></canvas>
            @endif
        </div>

        <!-- Events by Status -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100 mb-4">
                <i class="bi bi-pie-chart text-green-500"></i> Events by Status
            </h3>
            @if(empty($eventsByStatus['data']) || array_sum($eventsByStatus['data']) == 0)
                <div class="flex flex-col items-center justify-center h-[300px] text-zinc-400">
                    <i class="bi bi-pie-chart text-6xl mb-3"></i>
                    <p class="text-sm">No events data available</p>
                </div>
            @else
                <canvas id="eventStatusChart" height="300"></canvas>
            @endif
        </div>

        <!-- Student Enrollment Trend -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100 mb-4">
                <i class="bi bi-graph-up-arrow text-purple-500"></i> Student Enrollment Trend
            </h3>
            @if(empty($studentEnrollmentTrend['data']) || array_sum($studentEnrollmentTrend['data']) == 0)
                <div class="flex flex-col items-center justify-center h-[300px] text-zinc-400">
                    <i class="bi bi-mortarboard text-6xl mb-3"></i>
                    <p class="text-sm">No enrollment data available</p>
                </div>
            @else
                <canvas id="enrollmentTrendChart" height="300"></canvas>
            @endif
        </div>

        <!-- Partnership Status -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100 mb-4">
                <i class="bi bi-pie-chart-fill text-yellow-500"></i> Partnership Status
            </h3>
            @if(empty($partnershipsByStatus['data']) || array_sum($partnershipsByStatus['data']) == 0)
                <div class="flex flex-col items-center justify-center h-[300px] text-zinc-400">
                    <i class="bi bi-heart text-6xl mb-3"></i>
                    <p class="text-sm">No partnership data available</p>
                </div>
            @else
                <canvas id="partnershipStatusChart" height="300"></canvas>
            @endif
        </div>
    </div>

    <!-- Top Events Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100 mb-4">
            <i class="bi bi-trophy text-orange-500"></i> Top Events by Registration
        </h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase">Event</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase">Registrations</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase">Capacity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase">Utilization</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($topEvents as $event)
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $event['title'] }}</td>
                            <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-400">{{ $event['registrations'] }}</td>
                            <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-400">{{ $event['capacity'] ?: 'Unlimited' }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-zinc-200 dark:bg-zinc-700 rounded-full h-2 max-w-[100px]">
                                        <div class="bg-gradient-to-r from-blue-500 to-purple-500 h-2 rounded-full" style="width: {{ min($event['utilization'], 100) }}%"></div>
                                    </div>
                                    <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $event['utilization'] }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-sm text-zinc-500 dark:text-zinc-400">No events found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Additional Metrics -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Class Completion Rate -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100 mb-4">Class Completion Rate</h3>
            <div class="flex items-center justify-center">
                <div class="relative inline-flex items-center justify-center">
                    <svg class="w-32 h-32">
                        <circle class="text-zinc-200 dark:text-zinc-700" stroke-width="8" stroke="currentColor" fill="transparent" r="56" cx="64" cy="64"/>
                        <circle class="text-green-500" stroke-width="8" stroke-dasharray="{{ $classCompletionRate * 3.51 }} 351" stroke-linecap="round" stroke="currentColor" fill="transparent" r="56" cx="64" cy="64" transform="rotate(-90 64 64)"/>
                    </svg>
                    <span class="absolute text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $classCompletionRate }}%</span>
                </div>
            </div>
        </div>

        <!-- Prayer Requests Status -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100 mb-4">Prayer Requests</h3>
            @if(empty($prayerRequestsByStatus['data']) || array_sum($prayerRequestsByStatus['data']) == 0)
                <div class="flex flex-col items-center justify-center h-[200px] text-zinc-400">
                    <i class="bi bi-chat-heart text-5xl mb-3"></i>
                    <p class="text-sm">No prayer request data</p>
                </div>
            @else
                <canvas id="prayerStatusChart" height="200"></canvas>
            @endif
        </div>

        <!-- Transport Requests Status -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100 mb-4">Transport Requests</h3>
            @if(empty($transportRequestsByStatus['data']) || array_sum($transportRequestsByStatus['data']) == 0)
                <div class="flex flex-col items-center justify-center h-[200px] text-zinc-400">
                    <i class="bi bi-bus-front text-5xl mb-3"></i>
                    <p class="text-sm">No transport request data</p>
                </div>
            @else
                <canvas id="transportStatusChart" height="200"></canvas>
            @endif
        </div>
    </div>
</div>

@script
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // Chart.js configuration
    const chartColors = {
        blue: '#3b82f6',
        purple: '#a855f7',
        green: '#22c55e',
        yellow: '#eab308',
        red: '#ef4444',
        pink: '#ec4899',
    };

    // Alpine.js component for analytics charts
    document.addEventListener('alpine:init', () => {
        Alpine.data('analyticsCharts', () => ({
            charts: {},

            init() {
                this.initCharts();
            },

            initCharts() {
                // Destroy existing charts before creating new ones
                Object.values(this.charts).forEach(chart => chart?.destroy());
                this.charts = {};

                // Registration Trend Chart
                const regCanvas = document.getElementById('registrationTrendChart');
                if (regCanvas) {
                    this.charts.registration = new Chart(regCanvas, {
                        type: 'line',
                        data: {
                            labels: @json($eventRegistrationTrend['labels'] ?? []),
                            datasets: [{
                                label: 'Registrations',
                                data: @json($eventRegistrationTrend['data'] ?? []),
                                borderColor: chartColors.blue,
                                backgroundColor: chartColors.blue + '20',
                                tension: 0.4,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false }
                            }
                        }
                    });
                }

                // Event Status Chart
                const eventCanvas = document.getElementById('eventStatusChart');
                if (eventCanvas) {
                    this.charts.eventStatus = new Chart(eventCanvas, {
                        type: 'doughnut',
                        data: {
                            labels: @json($eventsByStatus['labels'] ?? []),
                            datasets: [{
                                data: @json($eventsByStatus['data'] ?? []),
                                backgroundColor: [chartColors.green, chartColors.blue, chartColors.red, chartColors.yellow]
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false
                        }
                    });
                }

                // Enrollment Trend Chart
                const enrollmentCanvas = document.getElementById('enrollmentTrendChart');
                if (enrollmentCanvas) {
                    this.charts.enrollment = new Chart(enrollmentCanvas, {
                        type: 'bar',
                        data: {
                            labels: @json($studentEnrollmentTrend['labels'] ?? []),
                            datasets: [{
                                label: 'New Students',
                                data: @json($studentEnrollmentTrend['data'] ?? []),
                                backgroundColor: chartColors.purple,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false }
                            }
                        }
                    });
                }

                // Partnership Status Chart
                const partnershipCanvas = document.getElementById('partnershipStatusChart');
                if (partnershipCanvas) {
                    this.charts.partnership = new Chart(partnershipCanvas, {
                        type: 'pie',
                        data: {
                            labels: @json($partnershipsByStatus['labels'] ?? []),
                            datasets: [{
                                data: @json($partnershipsByStatus['data'] ?? []),
                                backgroundColor: [chartColors.green, chartColors.yellow, chartColors.red]
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false
                        }
                    });
                }

                // Prayer Status Chart
                const prayerCanvas = document.getElementById('prayerStatusChart');
                if (prayerCanvas) {
                    this.charts.prayer = new Chart(prayerCanvas, {
                        type: 'doughnut',
                        data: {
                            labels: @json($prayerRequestsByStatus['labels'] ?? []),
                            datasets: [{
                                data: @json($prayerRequestsByStatus['data'] ?? []),
                                backgroundColor: [chartColors.pink, chartColors.blue, chartColors.green]
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false
                        }
                    });
                }

                // Transport Status Chart
                const transportCanvas = document.getElementById('transportStatusChart');
                if (transportCanvas) {
                    this.charts.transport = new Chart(transportCanvas, {
                        type: 'doughnut',
                        data: {
                            labels: @json($transportRequestsByStatus['labels'] ?? []),
                            datasets: [{
                                data: @json($transportRequestsByStatus['data'] ?? []),
                                backgroundColor: [chartColors.yellow, chartColors.green, chartColors.red, chartColors.blue]
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false
                        }
                    });
                }
            },

            updateCharts() {
                // Livewire will reload the page with new data, which will reinitialize charts
                setTimeout(() => {
                    this.initCharts();
                }, 100);
            }
        }));
    });
</script>
@endscript
