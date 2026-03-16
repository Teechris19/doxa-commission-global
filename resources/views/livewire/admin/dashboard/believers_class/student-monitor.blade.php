<?php
//TODO: set notification,
// TODO allow for upload of student certificate
//TODO allow for upload of class materials
//TODO implement bulk action
//TODO:: allow filter by date to allow print of curent month students

use App\Models\{BeliversAcademy, StudentClasses, AcademyClases, Chapter};
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
    // public ?int $selectedUser;

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
    }

    public function selectedUser($id)
    {
        $student = StudentClasses::with('batch')->where('user_id', $id)->where('academy_id', $this->academy->id)->first();
        $this->student = $student;
        $this->studentProgress = json_decode($student->class_completed ?? '[]', true) ?? [];
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
        return StudentClasses::with('user')
            ->where('academy_id', $this->academy->id)
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
        <x-table :$headers :$rows :filter="['quantity' => 'quantity', 'search' => 'search']" :quantity="[5, 15, 50, 100, 250]" paginate persistent selectable
            wire:model.live="selected">

            @interact('column_action', $row)
                {{-- Check Progress --}}
                <button class="px-3 rounded py-1 bg-blue-800 text-white"
                    x-on:click="$wire.call('selectedUser', {{ $row->user_id }}).then(() => $modalOpen('modal-id'))">
                    Check Progress
                </button>
            @endinteract
        </x-table>
    </x-card>
    <x-modal title="Academy Classes Progress" z-index="z-10" id="modal-id">

        @if ($allClasses != null)

            <div class="space-y-4">
                @foreach ($allClasses as $class)
                    <div class="flex items-center justify-between bg-gray-50 dark:bg-zinc-900 p-3 rounded-lg shadow-sm">
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                        {{ in_array($class->id, $studentProgress) ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                            {{ $class->name }}
                        </span>

                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            {{ in_array($class->id, $studentProgress) ? now()->format('Y-m-d H:i') : 'Pending' }}
                        </span>

                        <button
                            class="px-3 py-1 rounded text-xs font-semibold
                            {{ in_array($class->id, $studentProgress) ? 'bg-gray-600 text-white' : 'bg-green-600 text-white' }}"
                            @click="$wire.call('addToStudentCompleteClasses', {{ $class->id }})">
                            {{ in_array($class->id, $studentProgress) ? 'Completed' : 'Mark Done' }}
                        </button>
                    </div>
                @endforeach
            </div>
        @else
            <x-spinner-loader size="xl" color="white"></x-spinner-loader>
        @endif

    </x-modal>

</div>
