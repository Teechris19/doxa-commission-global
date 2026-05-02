<?php
// TODO: create Add Classes feature
// TODO: Add the monitoring of classes feature
// TODO: check for implementation of study material if needed

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Url};
use App\Models\{AcademyClases, Chapter, BeliversAcademy, User};
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions;

    public $classes = [];

    #[Url(keep: true)]
    public $chapter;

    public $leadersTeam;
    public $chapterId;
    public $academy;
    public $users;

    public function mount()
    {
        $this->academy = BeliversAcademy::with('chapter')
            ->whereHas('chapter', function ($q) {
                $q->where('name', e($this->chapter));
            })
            ->first();

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

        $this->classes = $this->academy ? AcademyClases::with('academy')->whereHas('academy', fn($q) => $q->where('academy_id', $this->academy->id))->get()->toArray() : [];

        $this->leadersTeam = auth()->user()?->teams?->filter(fn($team) => $team->pivot->role_in_team === 'team-lead');

        $chapter = Chapter::where('name', '=', $this->chapter)->first();
        $this->chapterId = $chapter?->id;
        $this->users = User::where('chapter_id', $this->chapterId)->get(['id', 'name'])->toArray();
        if (!$this->leadersTeam) {
            abort(403);
        }
    }

    // Livewire validation rules for the dynamic classes array
    protected function rules(): array
    {
        return [
            'classes' => 'required|array|min:1',
            'classes.*.name' => 'required|string|max:255',
            'classes.*.description' => 'nullable|string|max:2000',
            'classes.*.date' => 'required|date|after_or_equal:today',
            'classes.*.time' => 'required|date_format:H:i',
            // 'classes.*.study_material' => 'nullable|url|max:2048',
            'classes.*.tutor' => 'nullable|int',
        ];
    }

    protected $validationAttributes = [
        'classes.*.name' => 'class name',
        'classes.*.description' => 'class description',
        'classes.*.date' => 'class date',
        'classes.*.time' => 'class time',
        'classes.*.study_material' => 'study material link',
        'classes.*.tutor' => 'tutor',
    ];

    public function updated($property)
    {
        $this->validateOnly($property);
    }

    public function addClass()
    {
        $this->classes[] = [
            'name' => '',
            'description' => '',
            'date' => '',
            'time' => '',
            'study_material' => '',
            'tutor' => '',
        ];
    }

    public function removeClass(int $index): void
    {
        if (isset($this->classes[$index])) {
            if(isset($this->classes[$index]['id'])){
                AcademyClases::find($this->classes[$index]['id'])->delete();
            }
            unset($this->classes[$index]);
            $this->classes = array_values($this->classes); // reindex
        }
    }

    public function save()
    {
        $validated = $this->validate();

        foreach ($validated['classes'] as $class) {
            AcademyClases::updateOrCreate([
                'name' => $class['name'],
                'description' => $class['description'] ?? null,
                'date' => $class['date'],
                'time' => $class['time'],
                'study_material' => $class['study_material'] ?? null,
                'tutor' => $class['tutor'] ?? null,
                'academy_id' => $this->academy->id,
            ]);
        }
        $this->toast()->success('Success', 'Class Saved Successfully')->send();
    }
}; ?>

<div class="space-y-6">
    <x-fancy-header 
        title="Academy Curriculum" 
        subtitle="Design and structure your academy's learning path"
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route('admin.dashboard', request()->query())],
            ['label' => 'Academy', 'url' => route('admin.dashboard.believers_class.academy', request()->query())],
            ['label' => 'Curriculum']
        ]"
    >
        <div class="flex items-center gap-3">
            <x-button wire:click="addClass" icon="plus" color="blue" class="shadow-sm">
                Add New Class
            </x-button>
            <x-button wire:click="save" icon="check" color="green" class="shadow-sm" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Save Curriculum</span>
                <span wire:loading wire:target="save">Saving...</span>
            </x-button>
        </div>
    </x-fancy-header>

    @if (empty($classes))
        <x-card class="py-16 text-center">
            <div class="max-w-md mx-auto">
                <div class="w-16 h-16 bg-blue-50 dark:bg-blue-900/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-graduation-cap text-2xl text-blue-600 dark:text-blue-400"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Build Your Curriculum</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6">Create classes to guide your students through their spiritual journey in the Believers Academy.</p>
                <x-button wire:click="addClass" icon="plus" color="blue" class="px-8">
                    Start by Adding a Class
                </x-button>
            </div>
        </x-card>
    @else
        <div class="space-y-4">
            <form wire:submit.prevent="save" class="space-y-4">
                @foreach ($this->classes as $index => $class)
                    <div class="bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-800 rounded-2xl overflow-hidden transition-all duration-200 hover:shadow-md group" wire:key="class-{{ $index }}">
                        {{-- Class Header --}}
                        <div class="px-6 py-4 bg-gray-50/50 dark:bg-zinc-800/30 border-b border-gray-100 dark:border-zinc-800 flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-sm font-bold">
                                    {{ $index + 1 }}
                                </span>
                                <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                                    {{ $class['name'] ?: 'Untitled Class' }}
                                </h3>
                            </div>
                            <x-button.circle 
                                wire:click="removeClass({{ $index }})" 
                                icon="trash" 
                                color="red" 
                                variant="soft"
                                class="opacity-0 group-hover:opacity-100 transition-opacity"
                            />
                        </div>

                        {{-- Class Body --}}
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                                {{-- Main Info --}}
                                <div class="md:col-span-8 space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <x-input 
                                            label="Class Title" 
                                            placeholder="e.g., Understanding the Gospel" 
                                            wire:model.defer="classes.{{ $index }}.name"
                                        />
                                        <x-select.styled 
                                            label="Tutor" 
                                            placeholder="Assign a tutor" 
                                            wire:model.defer="classes.{{ $index }}.tutor"
                                            :options="$users"
                                            select="label:name|value:id"
                                        />
                                    </div>
                                    <x-textarea 
                                        label="Description" 
                                        placeholder="Briefly explain what this class covers..." 
                                        wire:model.defer="classes.{{ $index }}.description"
                                        rows="3"
                                    />
                                </div>

                                {{-- Schedule & Resources --}}
                                <div class="md:col-span-4 space-y-4 p-4 bg-gray-50/50 dark:bg-zinc-800/20 rounded-xl border border-gray-100 dark:border-zinc-800/50">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-4">Schedule & Resources</h4>
                                    <div class="grid grid-cols-1 gap-4">
                                        <x-input 
                                            type="date" 
                                            label="Class Date" 
                                            wire:model.defer="classes.{{ $index }}.date"
                                        />
                                        <x-input 
                                            type="time" 
                                            label="Class Time" 
                                            wire:model.defer="classes.{{ $index }}.time"
                                        />
                                        <x-input 
                                            label="Study Material Link" 
                                            placeholder="https://..." 
                                            wire:model.defer="classes.{{ $index }}.study_material"
                                            icon="link"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- Bottom Actions --}}
                <div class="flex items-center justify-between pt-4 bg-white dark:bg-zinc-950/50 p-4 rounded-xl border border-dashed border-gray-200 dark:border-zinc-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400 italic">
                        <i class="fas fa-info-circle mr-2"></i>You have {{ count($classes) }} classes in your curriculum.
                    </p>
                    <div class="flex items-center gap-3">
                        <x-button wire:click="addClass" variant="outline" icon="plus">
                            Add Another Class
                        </x-button>
                        <x-button wire:click="save" color="blue" class="px-8 shadow-md" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="save">Finalize curriculum</span>
                            <span wire:loading wire:target="save">Saving...</span>
                        </x-button>
                    </div>
                </div>
            </form>
        </div>
    @endif

    @if (session('status'))
        <x-alert title="Curriculum Updated" color="green" light>
            {{ session('status') }}
        </x-alert>
    @endif
</div>
