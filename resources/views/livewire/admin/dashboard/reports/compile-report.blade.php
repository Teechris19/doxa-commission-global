<?php

use App\Models\{Report, Chapter, Team};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component
{
    use WithFileUploads, Interactions;

    public $title;
    public $description;
    public $event_type;
    public $content;
    public $report_path;
    public $chapter_id;
    public $selectedReports = [];
    public $teamReports;
    public $chapters;
    public $showReportSelector = false;

    public function mount()
    {
        $user = Auth::user();
        $this->chapters = Chapter::all();

        // Only admins can access this page
        if (!$user->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        // Get user's chapter
        $this->chapter_id = $user->chapter_id;

        // Load team reports that have been pushed to chapter level
        $this->loadTeamReports();
    }

    public function loadTeamReports()
    {
        // Get all team-level reports for the admin's chapter
        $this->teamReports = Report::where('level', 'team')
            ->where('chapter_id', $this->chapter_id)
            ->with(['team', 'createdBy'])
            ->latest()
            ->get();
    }

    public function toggleReportSelector()
    {
        $this->showReportSelector = !$this->showReportSelector;
    }

    public function compileReport()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_type' => 'nullable|string|max:100',
            'content' => 'nullable|string',
            'report_path' => 'nullable|file|mimes:pdf,doc,docx|max:10240', // PDF, DOC, DOCX only, max 10MB
            'chapter_id' => 'required|exists:chapters,id',
        ]);

        $path = null;
        if ($this->report_path) {
            $path = $this->report_path->store('reports/chapter', 'public');
        }

        // Build compiled content from selected reports
        $compiledContent = $this->content ?? '';

        if (!empty($this->selectedReports)) {
            $compiledContent .= "\n\n<hr><h3>Referenced Team Reports:</h3>\n";

            foreach ($this->selectedReports as $reportId) {
                $report = Report::find($reportId);
                if ($report) {
                    $compiledContent .= "\n<div class='team-report'>";
                    $compiledContent .= "<h4>{$report->title}</h4>";
                    $compiledContent .= "<p><strong>Team:</strong> {$report->team->name}</p>";
                    $compiledContent .= "<p><strong>Date:</strong> {$report->report_date->format('M d, Y')}</p>";
                    $compiledContent .= "<div>{$report->report}</div>";
                    $compiledContent .= "</div><hr>";
                }
            }
        }

        try {
            Report::create([
                'report_date' => now(),
                'title' => $this->title,
                'description' => $this->description,
                'event_type' => $this->event_type,
                'report_path' => $path,
                'report' => $compiledContent,
                'level' => 'chapter',
                'chapter_id' => $this->chapter_id,
                'team_id' => null,
                'created_by' => auth()->id(),
            ]);

            $this->toast()
                ->success('Success!', 'Chapter report compiled successfully ✅')
                ->send();

            $this->reset(['title', 'description', 'event_type', 'content', 'report_path', 'selectedReports']);
            $this->loadTeamReports();

        } catch (\Exception $e) {
            $this->toast()
                ->error('Error!', 'Failed to compile report: ' . $e->getMessage())
                ->send();
        }
    }
};

?>

<div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-2xl font-bold mb-4">Compile Chapter Report</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-6">
            Create a comprehensive chapter-level report by compiling team reports. This report can then be sent to HQ.
        </p>

        <form wire:submit.prevent="compileReport" class="space-y-4">
            <x-input label="Report Title" type="text" wire:model="title" required />

            <x-textarea label="Description" wire:model="description" />

            <x-select label="Event Type" wire:model="event_type">
                <option value="">Select Event Type</option>
                <option value="meeting">Meeting</option>
                <option value="workshop">Workshop</option>
                <option value="conference">Conference</option>
                <option value="general">General Report</option>
                <option value="monthly">Monthly Report</option>
                <option value="quarterly">Quarterly Report</option>
            </x-select>

            <div>
                <label class="block text-sm font-medium mb-2">Report Content</label>
                <x-textarea id="chapter-report-editor" label="Chapter Report Content" wire:model="content" rows="10"></x-textarea>
                <p class="text-gray-500 text-xs mt-1">Write your chapter report here. You can reference team reports below.</p>
            </div>

            <div>
                <x-input type="file" label="Attach Report File (Optional)" wire:model="report_path" accept=".pdf,.doc,.docx" />
                @error('report_path') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                <p class="text-gray-500 text-xs mt-1">Upload PDF or Document format (PDF, DOC, DOCX only - max 10MB)</p>
            </div>

            <x-select label="Chapter" wire:model="chapter_id" required>
                @foreach ($chapters as $chapter)
                    <option value="{{ $chapter->id }}">{{ $chapter->name }}</option>
                @endforeach
            </x-select>

            <!-- Team Reports Reference Section -->
            <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-4 bg-gray-50 dark:bg-gray-700">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-lg font-semibold">Reference Team Reports</h3>
                    <x-button
                        type="button"
                        color="secondary"
                        wire:click="toggleReportSelector"
                        label="{{ $showReportSelector ? 'Hide' : 'Show' }} Reports"
                    />
                </div>

                @if($showReportSelector)
                    @if($teamReports->isEmpty())
                        <p class="text-gray-500 text-sm">No team reports available for this chapter.</p>
                    @else
                        <div class="space-y-2 max-h-96 overflow-y-auto">
                            @foreach($teamReports as $report)
                                <div class="flex items-start space-x-3 p-3 bg-white dark:bg-gray-800 rounded border">
                                    <input
                                        type="checkbox"
                                        id="report-{{ $report->id }}"
                                        wire:model="selectedReports"
                                        value="{{ $report->id }}"
                                        class="mt-1"
                                    />
                                    <label for="report-{{ $report->id }}" class="flex-1 cursor-pointer">
                                        <div class="font-medium">{{ $report->title }}</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            <span class="font-semibold">Team:</span> {{ $report->team->name ?? 'N/A' }} |
                                            <span class="font-semibold">Date:</span> {{ $report->report_date->format('M d, Y') }} |
                                            <span class="font-semibold">By:</span> {{ $report->createdBy->name ?? 'N/A' }}
                                        </div>
                                        @if($report->description)
                                            <p class="text-xs text-gray-500 mt-1">{{ Str::limit($report->description, 100) }}</p>
                                        @endif
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        @if(count($selectedReports) > 0)
                            <p class="text-sm text-green-600 dark:text-green-400 mt-3">
                                {{ count($selectedReports) }} report(s) selected for compilation
                            </p>
                        @endif
                    @endif
                @endif
            </div>

            <div class="flex justify-end space-x-3 pt-4">
                <x-button
                    type="submit"
                    color="primary"
                    label="Compile & Save Chapter Report"
                />
            </div>
        </form>
    </div>
</div>
