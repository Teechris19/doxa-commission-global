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
                'chapter_id' => $this->chapterId ?? null,
                'academy_id' => $this->academy->id,
            ]);
        }
        $this->toast()->success('Success', 'Class Saved Successfully')->send();
    }
}; ?>

<div>
    <x-card>
        @if (empty($classes))
            <div class="p-8">
                <div class="text-center">
                    <div
                        class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900/30">
                        <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-300" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M12 14l9-5-9-5-9 5 9 5z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M12 14l6.16-3.422A12.083 12.083 0 0112 21.5a12.083 12.083 0 01-6.16-10.922L12 14z" />
                        </svg>
                    </div>

                    <h2 class="mt-4 text-lg font-semibold text-gray-900 dark:text-gray-100">There are no classes now
                    </h2>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Click the Add Class button to begin adding new classes to the Believers Academy.
                    </p>

                    <div class="mt-6 flex items-center justify-center gap-3">
                        <button type="button"
                            class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            title="Add Class" wire:click="addClass">
                            <svg class="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            <span wire:loading wire:target="addClass">
                                <x-spinner-loader size="sm" color="white"></x-spinner-loader> Loading
                            </span>
                            <span wire:loading.remove>Add Class</span>
                        </button>

                        <a href="{{ route('admin.dashboard') }}"
                            class="inline-flex items-center rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                            Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        @else
            <form wire:submit.prevent="save" class="space-y-4">
                @csrf

                @foreach ($this->classes as $index => $class)
                    <x-card class="dark:bg-zinc-900 mb-3" wire:key="class-{{ $index }}">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Class
                                #{{ $index + 1 }}</h3>
                            <button type="button"
                                class="inline-flex items-center rounded-md bg-red-600 px-2 py-1 text-xs font-medium text-white shadow-sm hover:bg-red-500"
                                wire:click="removeClass({{ $index }})" title="Remove Class">
                                Remove
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="font-bold block text-sm text-gray-700 dark:text-gray-300">Class
                                    Name</label>
                                <input wire:model.defer="classes.{{ $index }}.name" placeholder="Class Name"
                                    class="mt-1 block w-full border-gray-600 dark:bg-dark-700 text-gray-300 rounded-md shadow-sm" />
                                @error("classes.$index.name")
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="font-bold block text-sm text-gray-700 dark:text-gray-300">Date</label>
                                <input type="date" wire:model.defer="classes.{{ $index }}.date"
                                    class="mt-1 block w-full border-gray-600 bg-dark-700 text-gray-300 rounded-md shadow-sm" />
                                @error("classes.$index.date")
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label
                                    class="font-bold block text-sm text-gray-700 dark:text-gray-300">Description</label>
                                <textarea wire:model.defer="classes.{{ $index }}.description" placeholder="Class Description"
                                    class="mt-1 block w-full border-gray-600 bg-dark-700 text-gray-300 rounded-md shadow-sm"></textarea>
                                @error("classes.$index.description")
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="font-bold block text-sm text-gray-700 dark:text-gray-300">Time</label>
                                <input type="time" wire:model.defer="classes.{{ $index }}.time"
                                    class="mt-1 block w-full border-gray-600 bg-dark-700 text-gray-300 rounded-md shadow-sm" />
                                @error("classes.$index.time")
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- If using URL for study material --}}
                            <div>
                                <label class="font-bold block text-sm text-gray-700 dark:text-gray-300">Study Material
                                    (URL)
                                </label>
                                <input type="url" placeholder="https://example.com/material.pdf"
                                    wire:model.defer="classes.{{ $index }}.study_material"
                                    class="mt-1 block w-full border-gray-600 bg-dark-700 text-gray-300 rounded-md shadow-sm" />
                                @error("classes.$index.study_material")
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="font-bold block text-sm text-gray-700 dark:text-gray-300">Tutor</label>
                                <select wire:model.defer="classes.{{ $index }}.tutor"
                                    class="mt-1 block w-full border-gray-600 bg-dark-700 text-gray-300 rounded-md shadow-sm">
                                    <option value="">Select a Tutor</option>
                                    @foreach($users as $key => $value)
                                        <option value="{{ $value['id'] }}">{{ $value['name'] }}</option>
                                    @endforeach
                                </select>
                                @error("classes.$index.tutor")
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <input type="hidden" wire:model='"classes.{{ $index }}.id'>
                            </div>
                        </div>
                    </x-card>
                @endforeach

                <div class="flex items-center gap-3">
                    <button type="submit"
                        class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading wire:target="save">
                            <x-spinner-loader size="sm" color="white"></x-spinner-loader> Saving...
                        </span>
                        <span wire:loading.remove>Save</span>
                    </button>

                    <button type="button"
                        class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        title="Add Class" wire:click="addClass">
                        <svg class="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M12 4v16m8-8H4" />
                        </svg>
                        <span wire:loading wire:target="addClass">
                            <x-spinner-loader size="sm" color="white"></x-spinner-loader> Loading
                        </span>
                        <span wire:loading.remove>Add Class</span>
                    </button>
                </div>
            </form>
        @endif
    </x-card>

    @if (session('status'))
        <div class="mt-4 text-sm text-green-600 dark:text-green-400">
            {{ session('status') }}
        </div>
    @endif
</div>
