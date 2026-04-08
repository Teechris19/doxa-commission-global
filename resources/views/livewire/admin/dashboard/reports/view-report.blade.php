<?php

use App\Models\Report;
use App\Services\ReportPdfGenerator;
use Livewire\Attributes\{Layout, Url};
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component
{
    use Interactions;

    #[Url]
    public $id;
    #[Url(keep:true)]
    public $chapter;
    public $report;
    public $fileType = null;
    public $fileUrl = null;

    public function mount()
    {
        if (!$this->id) {
            abort(404, 'Report not found');
        }

        $this->report = Report::with(['chapter', 'team', 'createdBy'])->findOrFail($this->id);

        // Check permissions
        $user = Auth::user();
        $leadersTeam = $user->teams->filter(fn($team) => in_array($team->pivot->role_in_team, ['team-lead', 'lead-assist']))->first();

        // Team leads can only view their team's reports
        if (!$user->hasRole('admin') && $leadersTeam && $this->report->team_id !== $leadersTeam->id) {
            abort(403, 'Unauthorized to view this report');
        }

        // Check if report has file attached
        if ($this->report->report_path) {
            $extension = strtolower(pathinfo($this->report->report_path, PATHINFO_EXTENSION));
            $this->fileType = $extension;

            if (Storage::disk('public')->exists($this->report->report_path)) {
                $this->fileUrl = Storage::disk('public')->url($this->report->report_path);
            }
        }
    }

    public function downloadReport()
    {
        if ($this->report->report_path && Storage::disk('public')->exists($this->report->report_path)) {
            return Storage::disk('public')->download($this->report->report_path, $this->report->title . '.' . $this->fileType);
        }

        $this->toast()
            ->error('Error', 'Report file not found')
            ->send();
    }

    public function generatePdf()
    {
        $generator = app(ReportPdfGenerator::class);
        $path = $generator->generate($this->report);

        $this->report->report_path = $path;
        $this->report->save();

        $this->fileType = 'pdf';
        $this->fileUrl = Storage::disk('public')->url($path);

        $this->toast()
            ->success('PDF Generated', 'Report PDF has been generated successfully ✅')
            ->send();

        return Storage::disk('public')->download($path, $this->report->title . '.pdf');
    }

    public function goBack()
    {
        return redirect()->route('admin.dashboard.reports.index', request()->query());
    }
};

?>

<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
        <div class="flex justify-between items-start">
            <div class="flex-1">
                <div class="flex items-center space-x-3 mb-2">
                    <a href="{{ route('admin.dashboard.reports.index', request()->query())}}">
                        Reports
                    </a>
                </div>
                <h1 class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">{{ $report->title }}</h1>
                @if($report->description)
                    <p class="text-zinc-600 dark:text-zinc-400 mt-2">{{ $report->description }}</p>
                @endif
            </div>
            <div class="text-right">
                @php
                    $levelLabels = [
                        'team' => 'Team',
                        'chapter' => 'Chapter',
                        'hq' => 'Super Admin',
                    ];
                    $levelColors = [
                        'team' => 'bg-green-500 text-white',
                        'chapter' => 'bg-blue-500 text-white',
                        'hq' => 'bg-purple-600 text-white',
                    ];
                    $badgeClass = 'px-4 py-2 rounded-full text-sm font-semibold ' . ($levelColors[$report->level] ?? 'bg-zinc-400 text-white');
                @endphp
                <span class="{{ $badgeClass }}">
                    {{ $levelLabels[$report->level] ?? ucfirst($report->level) }} Level
                </span>
            </div>
        </div>
    </div>

    <!-- Report Metadata -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4 text-zinc-900 dark:text-zinc-100">Report Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-1">Report Date</p>
                <p class="font-medium text-zinc-900 dark:text-zinc-100">
                    {{ $report->report_date->format('M d, Y') }}
                </p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ $report->report_date->format('h:i A') }}
                </p>
            </div>

            @if($report->chapter)
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-1">Chapter</p>
                    <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $report->chapter->name }}</p>
                </div>
            @endif

            @if($report->team)
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-1">Team</p>
                    <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $report->team->name }}</p>
                </div>
            @endif

            @if($report->event_type)
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-1">Event Type</p>
                    <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ ucfirst($report->event_type) }}</p>
                </div>
            @endif

            <div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-1">Created By</p>
                <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $report->createdBy->name ?? 'Unknown' }}</p>
            </div>

            <div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-1">Created On</p>
                <p class="font-medium text-zinc-900 dark:text-zinc-100">
                    {{ $report->created_at->format('M d, Y') }}
                </p>
            </div>
        </div>
    </div>

    <!-- Report Content -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow">
        <div class="p-6 border-b border-zinc-200 dark:border-zinc-700">
            <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Report Content</h2>
        </div>

        <div class="p-6">
            @if($report->report && !$report->report_path)
                {{-- Text-based report --}}
                <div class="prose prose-lg dark:prose-invert max-w-none">
                    {!! $report->report !!}
                </div>
            @elseif($report->report_path && $fileUrl)
                {{-- Document-based report --}}
                <div class="space-y-4">
                    @if($fileType === 'pdf')
                        {{-- PDF Viewer --}}
                        <div class="bg-zinc-100 dark:bg-zinc-900 rounded-lg p-4">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                                    <i class="fas fa-file-pdf text-red-500 mr-2"></i>
                                    PDF Report Document
                                </h3>
                                <a href="{{ $fileUrl }}" download class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                                    <i class="fas fa-download"></i>
                                    Download PDF
                                </a>
                            </div>

                            {{-- Embedded PDF Viewer --}}
                            <div class="bg-white dark:bg-zinc-800 rounded border border-zinc-300 dark:border-zinc-600" style="height: 800px;">
                                <iframe
                                    src="{{ $fileUrl }}"
                                    class="w-full h-full rounded"
                                    frameborder="0"
                                ></iframe>
                            </div>

                            <div class="mt-3 text-center">
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                    If the PDF doesn't display properly, please download it using the button above.
                                </p>
                            </div>
                        </div>
                    @elseif(in_array($fileType, ['doc', 'docx']))
                        {{-- Word Document --}}
                        <div class="bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-200 dark:border-blue-800 rounded-lg p-8">
                            <div class="text-center">
                                <i class="fas fa-file-word text-blue-600 dark:text-blue-400 text-6xl mb-4"></i>
                                <h3 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100 mb-2">
                                    Word Document Report
                                </h3>
                                <p class="text-zinc-600 dark:text-zinc-400 mb-4">
                                    This report is in {{ strtoupper($fileType) }} format and needs to be downloaded to view.
                                </p>
                                <x-button wire:click="downloadReport" color="primary" icon="download" size="lg" label="Download Document" />
                            </div>
                        </div>
                    @else
                        {{-- Unknown file type --}}
                        <div class="bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg p-8">
                            <div class="text-center">
                                <i class="fas fa-file text-zinc-400 text-6xl mb-4"></i>
                                <h3 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100 mb-2">
                                    Report Document
                                </h3>
                                <p class="text-zinc-600 dark:text-zinc-400 mb-4">
                                    Download the report document to view its contents.
                                </p>
                                <x-button wire:click="downloadReport" color="primary" icon="download" label="Download Report" />
                            </div>
                        </div>
                    @endif

                    {{-- Show text content if also available --}}
                    @if($report->report)
                        <div class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Additional Notes</h3>
                            <div class="prose dark:prose-invert max-w-none">
                                {!! $report->report !!}
                            </div>
                        </div>
                    @endif
                </div>
            @else
                {{-- No content available --}}
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">No Report Content</h3>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        This report does not have any content or attached documents.
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- Actions -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
        <div class="flex justify-between items-center">
            <x-button wire:click="goBack" color="secondary" label="Back to Reports" />

            @if($report->report_path)
                <x-button wire:click="downloadReport" color="primary" icon="download" label="Download Report" />
            @else
                <x-button wire:click="generatePdf" color="primary" icon="download" label="Generate PDF" />
            @endif
        </div>
    </div>
</div>
