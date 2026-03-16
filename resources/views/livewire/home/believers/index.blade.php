<?php
//TODO: create the notification to the team lead for the registration of  new user
// TODO:create the track my progress page

//TODO: allow student to lay complain and take permission if they will be absent and the reason
//TODO: allow the team lead to accept the permission when needed or reject
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Url};
use App\Models\{BeliversAcademy, Chapter, BelieversAcademyTeams, StudentClasses, AcademyClases};

new #[Layout('components.layouts.tailwind-layout')] class extends Component {
    #[Url(keep: true)]
    public $chapter;

    public $user;
    public bool $isRegistered = false;
    public $classes;
    public $chapters;
    public $selectedChapter;

    public function mount()
    {
        $this->user = auth()->user();
        $this->chapters = Chapter::all();

        if ($this->user) {
            if ($this->user->hasRole('super-admin')) {
                $this->selectedChapter = request('chapter') ? Chapter::where('name', request('chapter'))->first()->id ?? $this->chapters->first()->id : $this->chapters->first()->id;
            } else {
                $this->selectedChapter = $this->user->chapter_id;
            }
            $this->chapter = Chapter::find($this->selectedChapter)->name ?? 'No Chapter';

            $student = StudentClasses::where('user_id', $this->user->id)->first();
            if ($student) {
                $this->isRegistered = true;
            }
            $this->student = $student;
        } else {
            $this->selectedChapter = request('chapter') ? Chapter::where('name', request('chapter'))->first()->id ?? $this->chapters->first()->id : $this->chapters->first()->id;
            $this->chapter = Chapter::find($this->selectedChapter)->name ?? 'No Chapter';
        }

        $this->loadClasses();
    }

    public function updatedSelectedChapter()
    {
        $this->chapter = Chapter::find($this->selectedChapter)->name ?? 'No Chapter';
        $this->loadClasses();
    }

    private function loadClasses()
    {
        $academy = BeliversAcademy::where('chapter_id', $this->selectedChapter)->first();
        if ($academy) {
            $this->classes = AcademyClases::where('academy_id', $academy->id)->get();
        } else {
            $this->classes = collect();
        }
    }
}; ?>

<div class="font-poppins" style="font-family: 'Poppins', sans-serif;">

    <!-- Hero Section -->
    <section class="flex items-center justify-center px-5 py-16 bg-gradient-to-br from-blue-900 to-indigo-900 mobile-section">
        <div class="w-full max-w-4xl text-center text-white">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-blue-200">Believers Academy</p>
            <h1 class="mt-4 text-3xl md:text-5xl font-bold">Grow in the Word. Build foundations.</h1>
            <p class="mt-4 text-base md:text-lg text-white/85">A structured discipleship path to help you mature in Christ and live your faith with confidence.</p>
        </div>
    </section>

    <!-- Academy Overview Section -->
    <section id="overview" class="py-20 px-5 bg-white mobile-section">
        <div class="max-w-6xl mx-auto">
            <!-- Chapter Selector -->
            <div class="mb-8 text-center">
                <label class="block text-gray-700 font-medium mb-2">Select Branch</label>
                <select wire:model.live="selectedChapter" class="form-input max-w-xs mx-auto" {{ $user && !$user->hasRole('super-admin') ? 'disabled' : '' }}>
                    @foreach($chapters as $chap)
                        <option value="{{ $chap->id }}" {{ $selectedChapter == $chap->id ? 'selected' : '' }}>{{ $chap->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
                <div>
                    <h2 class="text-2xl md:text-3xl font-bold mb-4 text-gray-800">Deepen Your Faith Journey</h2>
                    <p class="text-gray-600 mb-6 text-base leading-relaxed">
                        Believers Academy is a structured discipleship journey to build biblical foundations and practical Christian living.
                    </p>
                    <ul class="space-y-3 text-sm text-gray-700">
                        <li class="flex items-start gap-3"><span class="mt-1 h-2 w-2 rounded-full bg-blue-500"></span>Understand core doctrines and Scripture</li>
                        <li class="flex items-start gap-3"><span class="mt-1 h-2 w-2 rounded-full bg-blue-500"></span>Develop spiritual disciplines</li>
                        <li class="flex items-start gap-3"><span class="mt-1 h-2 w-2 rounded-full bg-blue-500"></span>Grow in community and service</li>
                    </ul>
                    <div class="mt-8">
                        @if(!$isRegistered)
                            <a href="{{ route('believers_academy.register', request()->query()) }}" class="bg-blue-600 text-white px-8 py-3 rounded-full font-bold hover:bg-blue-700 transition-colors duration-300 shadow-md text-sm inline-block" wire:navigate>Register Now</a>
                        @else
                            <a href="{{ route('believers_academy.dashboard', request()->query()) }}" class="bg-green-500 text-white px-8 py-3 rounded-full font-bold hover:bg-green-600 transition-colors duration-300 shadow-md text-sm inline-block" wire:navigate>View Dashboard</a>
                        @endif
                    </div>
                </div>
                <div class="rounded-2xl border border-blue-100 bg-blue-50 p-6">
                    <p class="text-blue-700 italic text-base">"Then you will know the truth, and the truth will set you free." - John 8:32</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Learning Path Section -->
    <section class="py-20 px-5 bg-gradient-to-br from-gray-50 to-blue-50 mobile-section">
        <div class="max-w-6xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-6 text-gray-800 mobile-heading">Learning Path</h2>
            <p class="text-xl text-center text-gray-600 mb-12 max-w-2xl mx-auto leading-relaxed">Structured courses designed for spiritual growth at every stage</p>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mobile-grid-gap">
                @foreach($classes as $index => $class)
                    <div class="bg-white rounded-2xl shadow-soft p-8 border border-gray-100 transition-all duration-300 hover:shadow-lg hover:-translate-y-2 mobile-card">
                        <div class="flex items-center mb-6">
                            <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center mr-4">
                                <i class="fas fa-graduation-cap text-blue-500 text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-gray-800">{{ $class->name }}</h3>
                                <span class="text-blue-600 font-medium">Level {{ $index + 1 }} • Duration TBD</span>
                            </div>
                        </div>
                        <p class="text-gray-600 mb-6 leading-relaxed">{{ $class->description ?? 'Class description coming soon.' }}</p>
                        @if($class->study_material)
                            <div class="space-y-3 mb-6">
                                <div class="flex items-center text-gray-700">
                                    <i class="fas fa-book text-blue-500 mr-3"></i>
                                    <span>Study Materials Available</span>
                                </div>
                            </div>
                        @endif
                        @if(!$isRegistered)
                            <a href="{{ route('believers_academy.register', request()->query()) }}" class="w-full bg-blue-500 text-white py-3 rounded-xl font-medium hover:bg-blue-600 transition-colors inline-block text-center" wire:navigate>Enroll Now</a>
                        @else
                            <span class="w-full bg-gray-400 text-white py-3 rounded-xl font-medium inline-block text-center">Already Enrolled</span>
                        @endif
                    </div>
                @endforeach
                @if($classes->isEmpty())
                    <div class="col-span-full text-center py-12">
                        <p class="text-gray-500 text-lg">Classes will be available soon.</p>
                    </div>
                @endif
            </div>
        </div>
    </section>

</div>
