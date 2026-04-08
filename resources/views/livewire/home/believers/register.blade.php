<?php
//TODO: work on the spinner on the page
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Url};
use App\Models\{BeliversAcademy, Chapter, BelieversAcademyTeams, User, StudentClasses, AcademyBatch};
use App\Events\StudentRegisteredToAcademy;
use App\Notifications\StudentEnrolledNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.tailwind-layout')] class extends Component {
    public $academy;
    public $academyTeam;
    public $chapters;
    public $selectedChapter;
    public $statusMessage;
    public $statusType; // 'error' | 'success' | 'countdown'
    public $countdown;
    public $errorMessages = [];

    #[Url(keep: true)]
    public $chapter;

    public ?string $name = null;
    public ?string $email = null;
    public $number;
    public $howDidYouKnowAboutUs;
    public $interest;
    public $chapterId;
    public $availableBatches = [];
    public $selectedBatch;
    public ?User $user = null;

    public function mount()
    {
        $user = Auth()->user();

        if ($user) {
            $student = StudentClasses::where('user_id', $user->id)->first();
            if($student) {
                session()->flash('info', 'You are already registered for the Believers Academy.');
                $this->redirect(route('believers_academy.dashboard', request()->query()));
            }
            $this->name = $user->name;
            $this->email = $user->email;

            $this->user = $user;

        }

        $this->chapters = Chapter::all();
        if ($this->user) {
            $this->selectedChapter = $this->user->chapter_id;
        } elseif (request('chapter')) {
            $this->selectedChapter = Chapter::where('name', request('chapter'))->first()?->id;
        }

        if ($this->selectedChapter) {
            $this->updatedSelectedChapter();
        }
    }

    public function updatedSelectedChapter()
    {
        // Reset
        $this->statusMessage = null;
        $this->statusType = null;
        $this->academyTeam = null;
        $this->academy = null;

        // 1. Check if a team exists4
        $this->academyTeam = BelieversAcademyTeams::where('chapter_id', $this->selectedChapter)->first();

        if (!$this->academyTeam) {
            $this->statusType = 'error';
            $this->statusMessage = 'No team available for this chapter. Registration cannot proceed.';
            return;
        }

        // 2. Check if Academy is open
        $this->academy = BeliversAcademy::where('chapter_id', $this->selectedChapter)->first();

        if (!$this->academy) {
            $this->statusType = 'error';
            $this->statusMessage = 'Believers Academy is not available for this chapter.';
            return;
        }

        $now = Carbon::now();
        $startDate = Carbon::parse($this->academy->start_at);

        if ($now->lt($startDate)) {
            $this->statusType = 'countdown';
            $this->countdown = $startDate->toDateString();
            $this->statusMessage = 'Registration will open soon. Starts in '.$this->countdown;
            return;
        }

        if ($this->academy->status == 'closed') {
            $this->statusType = 'error';
            $this->statusMessage = 'Registration is closed for this academy.';
            return;
        }

        $this->statusType = 'success';
        $this->statusMessage = 'You can now proceed with registration.';
    }

    public function register()
    {
        // Ensure registration is currently allowed for the selected chapter
        if ($this->statusType !== 'success') {
            session()->flash('error', 'Registration is not open for the selected chapter.');
            return; // prevent invalid registration
        }

        // Validation rules and clean messages
        $phoneRegex = '/^\+?[0-9\s\-\(\)]{7,20}$/';

        $rules = [
            'selectedChapter' => ['required'],
            'howDidYouKnowAboutUs' => ['required'],
            'interest' => ['nullable', 'string', 'max:500'],
            'number' => ['required', "regex:$phoneRegex"],
        ];

        if (!$this->user) {
            $rules = array_merge($rules, [
                'name' => ['required', 'string', 'min:3'],
                'email' => ['required', 'email', 'exists:users,email'],
            ]);
        }

        $messages = [
            'name.required' => 'Full name is required.',
            'name.min' => 'Full name must be at least 3 characters.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Enter a valid email address.',
            'email.exists' => 'No account found for this email. Please sign in to continue.',
            'number.required' => 'Phone number is required.',
            'number.regex' => 'Enter a valid phone number.',
            'selectedChapter.required' => 'Please select a chapter.',
            'howDidYouKnowAboutUs.required' => 'Please tell us how you heard about us.',
            'interest.max' => 'Your interest must not exceed 500 characters.',
        ];

        $this->validate($rules, $messages);

        // Resolve the user (either authenticated or by provided email)
        if ($this->user) {
            $user = $this->user;
        } else {
            $user = User::where('email', $this->email)->first();
            if (!$user) {
                $this->errorMessages[] = 'The provided email was not found. Please <a href="'.route('home.login').'" wire:navigate class="underline">sign in</a> to continue.';
                return;
            }
        }

        // Check if user is a member of the selected chapter
        if ($user->chapter_id !== (int)$this->selectedChapter) {
            $this->errorMessages[] = 'You are not a member of the selected chapter. Please select your chapter.';
            return;
        }

        // Prevent duplicate enrollment
        if (StudentClasses::where('user_id', $user->id)->exists()) {
            $this->errorMessages[] = 'You are already registered for the Believers Academy.';
            return;
        }

        // Assign to a batch (first open batch for the academy)
        $batch = AcademyBatch::where('academy_id', $this->academy->id)->where('status', 'open')->orderBy('start_date')->first();

        $studentClass = StudentClasses::create([
            'user_id' => $user->id,
            'class_completed' => json_encode([]),
            'status' => 'started',
            'cert' => null,
            'interest' => $this->interest ?: '',
            'how_did_you_know_about_us' => $this->howDidYouKnowAboutUs,
            'phone' => $this->number,
            'academy_id' => $this->academy->id,
            'batch_id' => $batch ? $batch->id : null,
        ]);

        // Log in the user if not already authenticated
        if (!Auth::check()) {
            Auth::login($user);
        }

        // Send enrollment confirmation to student
        $user->notify(new StudentEnrolledNotification($this->academy));

        // Dispatch event to notify team lead
        StudentRegisteredToAcademy::dispatch($user, $this->academy, $studentClass);

        $this->reset(['name', 'email', 'number', 'howDidYouKnowAboutUs', 'interest', 'selectedChapter', 'statusMessage', 'statusType', 'academy', 'academyTeam']);
        $this->errorMessages = [];
        session()->flash('success', 'Registration successful!');
        $this->redirect(route('believers_academy.dashboard', request()->query()));
    }
};
?>

<div class="bg-white pb-12">
    <section class="border-b border-blue-100 bg-gradient-to-b from-blue-50 to-white">
        <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8 lg:py-14">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-blue-600">Believers Academy</p>
            <h1 class="mt-3 text-3xl font-semibold text-slate-900 sm:text-4xl">Register for discipleship classes</h1>
            <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-600">Choose your chapter, complete your details, and join the next academy cycle.</p>
        </div>
    </section>

    <section class="mx-auto max-w-4xl px-4 pt-8 sm:px-6 lg:px-8">
        <div class="rounded-3xl border border-blue-100 bg-white p-6 shadow-sm sm:p-8">
            @if (!empty($errorMessages) && is_array($errorMessages))
                <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3">
                    <h4 class="text-sm font-semibold text-rose-800">Please fix the following:</h4>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-rose-700">
                        @foreach ($errorMessages as $error)
                            <li>{!! $error !!}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($chapters->isEmpty())
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-8 text-center">
                    <p class="text-sm text-rose-700">No chapters are available for Believers Academy registration at this time.</p>
                </div>
            @else
                <form wire:submit.prevent="register" class="space-y-5">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="chapterSelect" class="block text-sm font-medium text-slate-700">Chapter</label>
                            <select
                                id="chapterSelect"
                                wire:model.live="selectedChapter"
                                class="mt-2 w-full rounded-2xl border border-blue-100 bg-white px-4 py-3 text-sm text-slate-700 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100"
                            >
                                <option value="">Select chapter</option>
                                @foreach ($chapters as $value)
                                    <option value="{{ $value->id }}">{{ $value->name }}</option>
                                @endforeach
                            </select>
                            @error('selectedChapter')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="fullName" class="block text-sm font-medium text-slate-700">Full Name</label>
                            <input
                                type="text"
                                id="fullName"
                                wire:model="name"
                                placeholder="Your full name"
                                class="mt-2 w-full rounded-2xl border border-blue-100 bg-white px-4 py-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                @if($user) readonly @endif
                            >
                            @error('name')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-slate-700">Email Address</label>
                            <input
                                type="email"
                                id="email"
                                wire:model="email"
                                placeholder="you@example.com"
                                class="mt-2 w-full rounded-2xl border border-blue-100 bg-white px-4 py-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                @if($user) readonly @endif
                            >
                            @error('email')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="phone" class="block text-sm font-medium text-slate-700">Phone Number</label>
                            <input
                                type="tel"
                                id="phone"
                                wire:model="number"
                                placeholder="+234 801 234 5678"
                                class="mt-2 w-full rounded-2xl border border-blue-100 bg-white px-4 py-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100"
                            >
                            @error('number')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    @if ($statusType == 'error')
                        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                            {{ $statusMessage }}
                        </div>
                    @elseif ($statusType == 'countdown')
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                            {{ $statusMessage }}
                        </div>
                    @elseif ($statusType == 'success')
                        <div class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                            {{ $statusMessage }}
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="howHeard" class="block text-sm font-medium text-slate-700">How did you hear about Doxa Church?</label>
                                <select
                                    id="howHeard"
                                    wire:model="howDidYouKnowAboutUs"
                                    class="mt-2 w-full rounded-2xl border border-blue-100 bg-white px-4 py-3 text-sm text-slate-700 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                >
                                    <option value="">Select an option</option>
                                    <option value="friendsAndFamily">Friend or Family</option>
                                    <option value="Social_media">Social Media</option>
                                    <option value="website">Website</option>
                                    <option value="others">Other</option>
                                </select>
                                @error('howDidYouKnowAboutUs')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="questions" class="block text-sm font-medium text-slate-700">Interests or questions</label>
                                <textarea
                                    id="questions"
                                    rows="4"
                                    wire:model="interest"
                                    placeholder="Tell us what you want to learn"
                                    class="mt-2 w-full rounded-2xl border border-blue-100 bg-white px-4 py-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                ></textarea>
                                @error('interest')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    @endif

                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300"
                        @if ($statusType !== 'success') disabled @endif
                    >
                        <span wire:loading.remove wire:target="register">Register for Believers Academy</span>
                        <span wire:loading wire:target="register" class="flex items-center gap-2">
                            <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Processing...
                        </span>
                    </button>
                </form>
            @endif
        </div>
    </section>
</div>
