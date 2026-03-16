<?php

use App\Models\{Report, Chapter};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

new #[Layout('components.layouts.admin')] class extends Component
{
    use WithPagination, Interactions;

    public $filterChapter = '';
    public $filterEventType = '';
    public $searchTerm = '';
    public $selectedReport = null;
    public $showReportModal = false;

    public function mount()
    {
        $user = Auth::user();

        // Only admins and super-admins can view HQ reports
        if (!$user->hasAnyRole(['admin', 'super-admin'])) {
            abort(403, 'Unauthorized access to HQ reports');
        }
    }

    public function getHqReports()
    {
        $query = Report::where('level', 'hq')
            ->with(['chapter', 'createdBy']);

        // Filter by chapter
        if ($this->filterChapter) {
            $query->where('chapter_id', $this->filterChapter);
        }

        // Filter by event type
        if ($this->filterEventType) {
            $query->where('event_type', $this->filterEventType);
        }

        // Search functionality
        if ($this->searchTerm) {
            $query->where(function($q) {
                $q->where('title', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $this->searchTerm . '%');
            });
        }

        return $query->latest('report_date')->paginate(15);
    }

    public function viewReport($reportId)
    {
        $this->selectedReport = Report::with(['chapter', 'team', 'createdBy'])->findOrFail($reportId);
        $this->showReportModal = true;
    }

    public function closeModal()
    {
        $this->showReportModal = false;
        $this->selectedReport = null;
    }

    public function downloadReport($reportId)
    {
        $report = Report::findOrFail($reportId);

        if ($report->report_path && Storage::disk('public')->exists($report->report_path)) {
            return Storage::disk('public')->download($report->report_path);
        }

        $this->toast()
            ->error('Error', 'Report file not found')
            ->send();
    }

    public function with()
    {
        return [
            'hqReports' => $this->getHqReports(),
            'chapters' => Chapter::all(),
            'eventTypes' => ['meeting', 'workshop', 'conference', 'general', 'monthly', 'quarterly'],
        ];
    }
};

?>

<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold">Reports Sent to HQ</h2>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                    View all chapter reports that have been escalated to headquarters
                </p>
            </div>
            <div class="text-right">
                <div class="text-3xl font-bold text-blue-600">{{ $hqReports->total() }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Total HQ Reports</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Filters</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-input
                label="Search"
                wire:model.live="searchTerm"
                placeholder="Search by title or description..."
            />

            <x-select label="Filter by Chapter" wire:model.live="filterChapter">
                <option value="">All Chapters</option>
                @foreach($chapters as $chapter)
                    <option value="{{ $chapter->id }}">{{ $chapter->name }}</option>
                @endforeach
            </x-select>

            <x-select label="Filter by Event Type" wire:model.live="filterEventType">
                <option value="">All Event Types</option>
                @foreach($eventTypes as $type)
                    <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                @endforeach
            </x-select>
        </div>
    </div>

    <!-- Reports List -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        @if($hqReports->isEmpty())
            <div class="p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No HQ Reports</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    No reports have been sent to HQ yet.
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Report Details
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Chapter
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Event Type
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Date
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Created By
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($hqReports as $report)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $report->title }}
                                    </div>
                                    @if($report->description)
                                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                            {{ Str::limit($report->description, 80) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                        {{ $report->chapter->name ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ $report->event_type ? ucfirst($report->event_type) : '-' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ $report->report_date->format('M d, Y') }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $report->report_date->format('h:i A') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $report->createdBy->name ?? 'Unknown' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <a href="{{ route('admin.dashboard.reports.view-report', ['id' => $report->id]) }}">
                                        <x-button
                                            color="primary"
                                            label="View Full Report"
                                            sm
                                        />
                                    </a>
                                    @if($report->report_path)
                                        <x-button
                                            wire:click="downloadReport({{ $report->id }})"
                                            color="secondary"
                                            label="Download"
                                            sm
                                        />
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $hqReports->links() }}
            </div>
        @endif
    </div>

    <!-- Report Details Modal -->
    @if($showReportModal && $selectedReport)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                {{ $selectedReport->title }}
                            </h3>
                            <button wire:click="closeModal" class="text-gray-400 hover:text-gray-500">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <div class="space-y-4">
                            <!-- Report Metadata -->
                            <div class="grid grid-cols-2 gap-4 border-b border-gray-200 dark:border-gray-700 pb-4">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Chapter</p>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ $selectedReport->chapter->name ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Event Type</p>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ $selectedReport->event_type ? ucfirst($selectedReport->event_type) : 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Report Date</p>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ $selectedReport->report_date->format('M d, Y @ h:i A') }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Created By</p>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ $selectedReport->createdBy->name ?? 'Unknown' }}</p>
                                </div>
                            </div>

                            <!-- Description -->
                            @if($selectedReport->description)
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Description</p>
                                    <p class="text-gray-900 dark:text-gray-100">{{ $selectedReport->description }}</p>
                                </div>
                            @endif

                            <!-- Report Content -->
                            @if($selectedReport->report)
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Report Content</p>
                                    <div class="prose dark:prose-invert max-w-none bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
                                        {!! $selectedReport->report !!}
                                    </div>
                                </div>
                            @endif

                            <!-- Attached File -->
                            @if($selectedReport->report_path)
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Attached File</p>
                                    <x-button
                                        wire:click="downloadReport({{ $selectedReport->id }})"
                                        color="secondary"
                                        label="Download Report File"
                                    />
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <x-button wire:click="closeModal" color="secondary" label="Close" />
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
