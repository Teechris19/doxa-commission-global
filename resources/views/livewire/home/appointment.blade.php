<?php
/*
    -TODO: Add authentication check to pre-fill user details if logged in.
    -TODO: Implement email confirmation after booking an appointment.
    -TODO: Add notification system for admins on new appointments.
 */
use App\Models\Team;
use App\Models\TeamUser;
use App\Models\{Chapter, AppointmentTeams, Appointment};
use App\Services\NotificationRecipients;
use App\Notifications\AppointmentScheduled;
use Livewire\Attributes\{Layout, Url};
use Livewire\Volt\Component;

new #[Layout('components.layouts.tailwind-layout')] class extends Component {
    public ?string $name = null;
    public ?string $email = null;

    #[Url]
    public $chapter;
    public $date,
        $title,
        $description,
        $team_id,
        $user = null,
        $appointmentTeams,
        $selectedTeam,
        $freeDays = [],
        $daySelected,
        $startTime,
        $endTime,
        $chapters,
        $selectedChapter,
        $currentChapter = null;

    public function mount()
    {
        $user = auth()->user() ?? null;
        if ($user != null) {
            $this->name = $user->name;
            $this->email = $user->email;
            $this->user = $user;
        }

        $this->appointmentTeams = AppointmentTeams::with('team')
            ->when($this->chapter, function ($q) {
                $q->where('chapter_id', Chapter::where('name', e($this->chapter))->first()->id);
            })
            ->get();

        if ($this->chapter != null) {
            $this->currentChapter = Chapter::where('name', $this->chapter)->first();
            if ($this->currentChapter == null) {
                abort(403, 'Invalid Chapter');
            }
            $this->selectedChapter = $this->currentChapter->id;
        }

        $this->chapters = Chapter::all()->toArray();
    }

    public function updatedselectedChapter()
    {
        $this->appointmentTeams = AppointmentTeams::with('team')->where('chapter_id', $this->selectedChapter)->get();

        if ($this->appointmentTeams->isEmpty()) {
            $this->appointmentTeams = 'empty';
        }
    }

    public function updatedselectedTeam()
    {
        $team_appointment_setting = AppointmentTeams::where('team_id', '=', $this->selectedTeam)->first();
        $free_days = json_decode($team_appointment_setting->free_days);
        $freeDays = [];
        if ($free_days != null) {
            foreach ($free_days as $key => $value) {
                $freeDays[] = $value->day;
            }
        } else {
            $freeDays = null;
        }
        $this->freeDays = $freeDays ?? ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    }

    public function updateddaySelected()
    {
        $settings = json_decode(AppointmentTeams::where('team_id', '=', $this->selectedTeam)->first()->free_days, true);
        $setting = [];
        if (!$settings == null) {
            foreach ($settings as $key => $value) {
                if ($value['day'] != $this->daySelected) {
                    continue;
                }
                $setting = $value;
            }
            $this->startTime = $setting['start'];
            $this->endTime = $setting['end'];
        }
    }

    public function save()
    {
        $this->validateAppointment();

        $chapter = Chapter::where('name', $this->chapter)->first();

        $appointment = Appointment::create([
            'title' => $this->title,
            'description' => $this->description,
            'day' => $this->date,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'team_id' => $this->selectedTeam,
            'chapter_id' => $this->selectedChapter,
            'user_id' => $this->user?->id,
            'username' => $this->name,
            'email' => $this->email,
            'status' => 'pending',
        ]);

        $recipients = (new NotificationRecipients())
            ->forTeamAndChapter($this->selectedTeam, $this->selectedChapter);

        foreach ($recipients as $recipient) {
            $recipient->notify(new AppointmentScheduled($appointment));
        }

        session()->flash('success', 'Appointment booked successfully!');
        $this->resetExcept(['user', 'chapter', 'name', 'email']);
        $this->redirect(route('home'));
    }

    public function validateAppointment()
    {
        $this->validate(
            [
                'title' => 'required|string|max:255',
                'description' => 'required|string|min:20',
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'selectedTeam' => 'required|integer',
                'date' => 'required|date',
                'startTime' => 'required',
                'endTime' => 'required',
            ],
            [
                'title.required' => 'Please provide a title for your appointment.',
                'title.max' => 'The title must not be longer than 255 characters.',
                'description.required' => 'Please enter a reason for the appointment.',
                'description.min' => 'The reason must be at least 20 characters long.',
                'name.required' => 'Your name is required.',
                'name.max' => 'Your name must not exceed 255 characters.',
                'email.required' => 'We need your email address to confirm the appointment.',
                'email.email' => 'Please provide a valid email address.',
                'email.max' => 'The email must not exceed 255 characters.',
                'selectedTeam.required' => 'Please select a team for this appointment.',
                'selectedTeam.integer' => 'Invalid team selection.',
                'date.required' => 'Please pick a date for your appointment.',
                'startTime.required' => 'Start time is required.',
                'endTime.required' => 'End time is required.',
            ],
        );
    }
}; ?>

<div class="mx-auto w-full max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
    <section class="rounded-3xl border border-blue-100 bg-white p-6 shadow-[0_24px_60px_-40px_rgba(37,99,235,0.5)] sm:p-8">
        <header class="mb-8 text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-blue-600">Appointments</p>
            <h1 class="mt-3 text-3xl font-bold text-slate-900">Book an Appointment</h1>
            <p class="mt-2 text-sm text-slate-600">Schedule time with the church team in a few steps.</p>
        </header>

        <form class="space-y-5" wire:submit.prevent="save">
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label for="name" class="mb-2 block text-sm font-medium text-slate-700">Your Name</label>
                    <input
                        type="text"
                        id="name"
                        wire:model.live="name"
                        @if($name) disabled @endif
                        class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-200 disabled:bg-slate-50 disabled:text-slate-500"
                    >
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-slate-700">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        wire:model="email"
                        @if($email) disabled @endif
                        class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-200 disabled:bg-slate-50 disabled:text-slate-500"
                    >
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="chapter" class="mb-2 block text-sm font-medium text-slate-700">Pick a Chapter</label>
                @if ($currentChapter != null)
                    <select id="chapter" class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-700" wire:model.live="selectedChapter" disabled>
                        <option value="{{ $currentChapter->id }}" selected>{{ $currentChapter->name }}</option>
                    </select>
                @else
                    <select id="chapter" class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-700" wire:model.live="selectedChapter">
                        <option value="">Select a chapter</option>
                        @foreach ($chapters as $chapter)
                            <option value="{{ $chapter['id'] }}">{{ $chapter['name'] }}</option>
                        @endforeach
                    </select>
                @endif
                @error('selectedChapter') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            @if ($appointmentTeams == 'empty')
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Sorry, there are no teams available for appointments in this chapter.
                </div>
            @else
                <div>
                    <label for="team" class="mb-2 block text-sm font-medium text-slate-700">Team</label>
                    <select id="team" class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-700" wire:model.live="selectedTeam">
                        <option value="">Select a team</option>
                        @foreach ($appointmentTeams as $appointment_team)
                            <option value="{{ $appointment_team->team->id }}">{{ $appointment_team->team->name }}</option>
                        @endforeach
                    </select>
                    @error('selectedTeam') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 lg:grid-cols-3">
                    <div
                        x-data="{
                            selectedDate: null,
                            enabledDays: {{ Js::from($freeDays) }},
                            init() {
                                const dayMap = {
                                    sunday: 0,
                                    monday: 1,
                                    tuesday: 2,
                                    wednesday: 3,
                                    thursday: 4,
                                    friday: 5,
                                    saturday: 6
                                };

                                const numericDays = this.enabledDays.map((d) => dayMap[d.toLowerCase()]);

                                flatpickr(this.$refs.datePicker, {
                                    dateFormat: 'Y-m-d',
                                    disable: [(date) => !numericDays.includes(date.getDay())],
                                    onChange: (selectedDates, dateStr) => {
                                        this.selectedDate = dateStr;
                                        $wire.set('date', dateStr);
                                        $wire.set('daySelected', selectedDates[0].toLocaleDateString('en-US', { weekday: 'long' }).toLowerCase());
                                    }
                                });
                            }
                        }"
                        class="rounded-2xl border border-blue-100 bg-blue-50 p-4"
                    >
                        <label class="mb-2 block text-sm font-medium text-slate-700">Pick an Available Date</label>
                        <input
                            x-ref="datePicker"
                            x-model="selectedDate"
                            type="text"
                            placeholder="Select a date"
                            class="w-full rounded-xl border border-blue-200 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                        >
                        @error('date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Start Time</label>
                        <input type="time" class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900" wire:model="startTime">
                        @error('startTime') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">End Time</label>
                        <input type="time" class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900" wire:model="endTime">
                        @error('endTime') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="title" class="mb-2 block text-sm font-medium text-slate-700">Title</label>
                    <input id="title" type="text" wire:model="title" class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900">
                    @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="description" class="mb-2 block text-sm font-medium text-slate-700">Reason for Appointment</label>
                    <textarea id="description" rows="4" wire:model="description" class="w-full rounded-xl border border-blue-100 px-4 py-3 text-sm text-slate-900"></textarea>
                    @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
                    Book Appointment
                </button>
            @endif
        </form>
    </section>
</div>
