<?php
//TODO: set notification,
// TODO allow for upload of student certificate
//TODO allow for upload of class materials
//TODO implement bulk action
//TODO:: allow filter by date to allow print of curent month students

use App\Models\{BeliversAcademy, StudentClasses, AcademyClases, Chapter, AcademyBatch};
use App\Notifications\ClassCompletedByStudent;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions, WithPagination, WithFileUploads;

    public ?int $quantity = 10;
    public ?string $search = null;
    public array $selected = [];
    public ?string $bulkAction = null;
    public $academy;
    public $allClasses;
    public $classesNotDone;
    public ?array $studentProgress = [];
    public $student;
    public $templateFile;
    public $filterBatch = null;
    public $batches = [];

    #[Url(keep: true)]
    public ?string $chapter;

    public function mount()
    {
        $this->academy = BeliversAcademy::where('chapter_id', Chapter::where('name', e($this->chapter))->first()->id)->first();
        if (!$this->academy) {
            $chapter = Chapter::where('name', e($this->chapter))->first();
            if ($chapter) {
                $this->academy = BeliversAcademy::create([
                    'status' => 'open',
                    'start_at' => now()->addDays(7),
                    'chapter_id' => $chapter->id,
                ]);
            }
        }
        $this->allClasses = $this->academy ? AcademyClases::where('academy_id', $this->academy->id)->get() : collect();
        $this->batches = $this->academy ? AcademyBatch::where('academy_id', $this->academy->id)->get(['id', 'name'])->toArray() : [];
    }

    public function selectedUser($id)
    {
        // Search by user_id across all academies, then filter
        $student = StudentClasses::with('batch', 'academy')
            ->where('user_id', $id)
            ->first();
        
        if (!$student) {
            $this->toast()->error('Error', 'Student not found')->send();
            return;
        }
        
        $this->student = $student;
        $this->studentProgress = json_decode($student->class_completed ?? '[]', true) ?? [];
        
        // Load classes for this student's batch
        $this->loadClasses();
    }

    public function loadClasses()
    {
        if ($this->student && $this->student->batch) {
            $this->allClasses = $this->student->batch->classes;
        } else {
            $this->allClasses = AcademyClases::where('academy_id', $this->academy->id)->get();
        }
    }

    public function loadStudentProgress()
    {
        if ($this->student) {
            $this->studentProgress = json_decode($this->student->class_completed ?? '[]', true) ?? [];
        }
    }
    /**
     * Table headers
     */
    public function with(): array
    {
        return [
            'headers' => [['index' => 'user.name', 'label' => 'Team Name'], ['index' => 'phone', 'label' => 'Phone Number'], ['index' => 'status', 'label' => 'Status'], ['index' => 'action', 'label' => 'Action']],
            'rows' => $this->rows(),
        ];
    }

    /**
     * Query rows with filtering + pagination
     */
    public function rows()
    {
        return StudentClasses::with('user', 'batch')
            ->where('academy_id', $this->academy->id)
            ->when($this->filterBatch, fn($q) => $q->where('batch_id', $this->filterBatch))
            ->when($this->search, fn($q) => $q->whereHas('user', fn($userQ) => $userQ->where('name', 'like', "%{$this->search}%")))
            ->paginate($this->quantity);
    }

    /**
     * Get all row IDs for Select All
     */
    public function ids(): array
    {
        return $this->rows()->pluck('id')->toArray();
    }

    /**
     * Select all rows
     */
    public function selectAll()
    {
        $this->selected = $this->ids();
    }

    public function addToStudentCompleteClasses($id)
    {
        $student = $this->student;
        $student->class_completed = json_encode(array_merge($this->studentProgress, [$id]));
        $student->save();
        $this->student = $student;

        // Get the class that was marked complete
        $class = AcademyClases::find($id);

        // Notify student about class completion
        if ($class && $student->user) {
            $student->user->notify(new ClassCompletedByStudent($student->user, $class, 'completed'));
        }

        $this->loadClasses();
        $this->loadStudentProgress();
    }

    public function uploadTemplate()
    {
        $this->validate([
            'templateFile' => 'required|file|mimes:pdf|max:2048',
        ]);

        if ($this->academy) {
            $path = $this->templateFile->store('certificate_templates', 'public');
            $this->academy->certificate_template = $path;
            $this->academy->save();
            $this->toast()->success('Template uploaded successfully');
            $this->templateFile = null;
        }
    }


};
?>

<div>


    <x-card class="relative dark:bg-dark-800">
        <div class="mb-4">
            <h3 class="text-lg font-semibold mb-2">Certificate Template</h3>
            <form wire:submit.prevent="uploadTemplate" class="flex items-center space-x-4">
                <input type="file" wire:model="templateFile" accept=".pdf" class="border rounded p-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Upload Template</button>
            </form>
            @if($academy && $academy->certificate_template)
                <p class="mt-2 text-green-600">Template uploaded: {{ $academy->certificate_template }}</p>
            @else
                <p class="mt-2 text-red-600">No template uploaded</p>
            @endif
        </div>
    </x-card>

    <x-card class="relative dark:bg-dark-800">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold">Filter by Batch</h3>
            <button wire:click="$set('filterBatch', null)" class="text-sm text-blue-600 hover:underline">Clear Filter</button>
        </div>
        <div class="flex flex-wrap gap-2">
            <button 
                wire:click="$set('filterBatch', null)" 
                class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $filterBatch === null ? 'bg-blue-600 text-white' : 'bg-gray-100 hover:bg-gray-200' }}">
                All Batches
            </button>
            @foreach($batches as $batch)
                <button 
                    wire:click="$set('filterBatch', {{ $batch['id'] }})" 
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $filterBatch == $batch['id'] ? 'bg-blue-600 text-white' : 'bg-gray-100 hover:bg-gray-200' }}">
                    {{ $batch['name'] }}
                </button>
            @endforeach
        </div>
    </x-card>

    <x-card class="relative dark:bg-dark-800">
        <x-table :$headers :$rows :filter="['quantity' => 'quantity', 'search' => 'search']" :quantity="[5, 15, 50, 100, 250]" paginate persistent selectable
            wire:model.live="selected">

            @interact('column_user.name', $row)
                <div class="flex items-center gap-2">
                    <span>{{ $row->user->name ?? 'N/A' }}</span>
                    @if($row->batch)
                        <span class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded-full">{{ $row->batch->name }}</span>
                    @endif
                </div>
            @endinteract

            @interact('column_action', $row)
                <button class="px-3 rounded py-1 bg-blue-800 text-white"
                    wire:click="selectedUser({{ $row->user_id }})">
                    Check Progress
                </button>
            @endinteract
        </x-table>
    </x-card>

    @if($student)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" x-show="$wire.student" x-transition>
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-2xl w-full max-w-2xl mx-4 p-6" @click.outside="$wire.student = null">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Academy Classes Progress</h3>
                    <button wire:click="$set('student', null)" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="mb-4 p-3 bg-blue-50 dark:bg-zinc-700 rounded-lg">
                    <p class="text-sm font-medium text-blue-900 dark:text-white">Student: {{ $student->user->name }}</p>
                    <p class="text-sm text-blue-700 dark:text-gray-300">Batch: {{ $student->batch?->name ?? 'Not assigned' }}</p>
                    <p class="text-sm text-blue-700 dark:text-gray-300">Phone: {{ $student->phone ?? 'N/A' }}</p>
                </div>

                @if ($allClasses && $allClasses->count() > 0)
                    <div class="space-y-4 max-h-96 overflow-y-auto">
                        @foreach ($allClasses as $class)
                            <div class="flex items-center justify-between bg-gray-50 dark:bg-zinc-900 p-3 rounded-lg shadow-sm">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ in_array($class->id, $studentProgress) ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ $class->name }}
                                </span>
                                <button
                                    class="px-3 py-1 rounded text-xs font-semibold {{ in_array($class->id, $studentProgress) ? 'bg-gray-600 text-white' : 'bg-green-600 text-white' }}"
                                    wire:click="addToStudentCompleteClasses({{ $class->id }})">
                                    {{ in_array($class->id, $studentProgress) ? 'Completed' : 'Mark Done' }}
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-center text-gray-500">No classes available</p>
                @endif
            </div>
        </div>
    @endif

</div>
