<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Url};
use App\Models\{AppointmentTeams, User, Team, Chapter};
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component {

    use Interactions;

    public $schedules = [];
    public $options = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    #[Url]
    public $chapter;

    public $chapterId;

    public $leadersTeam;

    public function mount()
    {
        $this->chapterId         = Chapter::where('name', '=', $this->chapter)->first()->id;
        $this->appointment_teams = AppointmentTeams::where('chapter_id', $this->chapterId)->get()->pluck('team_id')->toArray();
        $this->leadersTeam = Auth()->user()->teams->filter(fn($team) => $team->pivot->role_in_team === 'team-lead')->first();
        $this->schedules         = json_decode(AppointmentTeams::where('chapter_id',$this->chapterId)->where('team_id', '=', $this->leadersTeam->id)->first()->free_days, true) ??
            [
                ['day' => '', 'start' => '', 'end' => '', 'index'=>''],
            ];

        if (!$this->leadersTeam || !in_array($this->leadersTeam->id, $this->appointment_teams)) {
            abort(403);
        }
    }

    public function addSchedule()
    {
        $this->schedules[] = ['day' => '', 'start' => '', 'end' => '', 'index'=>''];
    }

    public function removeSchedule($index)
    {
        $schedule = $this->schedules[$index];
        if($schedule->index !== null){
            $appointment_team = AppointmentTeams::where('team_id', $this->leadersTeam->id)->first();
            $free_days = json_decode($appointment_team->free_days, true);
            foreach ($free_days as $key => &$value) {
                if($value['index'] == $schedule->index){
                    unset($free_days[$key]);
                }
            }
            $appointment_team->free_days = $free_days;
            $appointment_team->save();
        }
             unset($this->schedules[$index]);
        $this->schedules = array_values($this->schedules); // reindex
        $this->toast()->success('Done', 'Appointment Time Removed')->send();
    }

    public function reschedule()
    {
        $this->validate([
            'schedules.*.day' => 'required',
            'schedules.*.start' => 'required|date_format:H:i',
            'schedules.*.end' => 'required|date_format:H:i|after:schedules.*.start',
        ]);
        $appointment_team = AppointmentTeams::where('team_id', $this->leadersTeam->id)->first();

        $free_days = [];
        foreach ($this->schedules as $key => $value) {
            $free_days                   = json_decode($appointment_team->free_days, true);
            $value['index'] = $key+1;
            $free_days[] = $value;
        }
            $appointment_team->free_days = json_encode($free_days);
            $appointment_team->save();
        $this->toast()->success('Done', 'Appointment Time Scheduled')->send();

    }
}; ?>

<div>
    <form wire:submit.prevent="reschedule" class="dark:bg-dark-800 p-4 rounded-md">
        <div class="space-y-4">
            @foreach($schedules as $index => $schedule)
            <div class="grid grid-cols-12 gap-4 items-end">
                <!-- Day -->
                <div class="col-span-4">
                    <label class="block text-sm font-medium text-gray-300">Day</label>
                    <select wire:model="schedules.{{ $index }}.day" class="mt-1 block w-full border-gray-600 bg-dark-700 text-gray-300 rounded-md shadow-sm">
                        <option value="">Select day</option>
                        @foreach($options as $day)
                        <option value="{{ $day }}">{{ ucfirst($day) }}</option>
                        @endforeach
                    </select>
                    @error("schedules.$index.day")
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Start Time -->
                <div class="col-span-3">
                    <label class="block text-sm font-medium text-gray-300">Start Time</label>
                    <input type="time" wire:model="schedules.{{ $index }}.start" class="mt-1 block w-full border-gray-600 bg-dark-700 text-gray-300 rounded-md shadow-sm">
                    @error("schedules.$index.start")
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- End Time -->
                <div class="col-span-3">
                    <label class="block text-sm font-medium text-gray-300">End Time</label>
                    <input type="time" wire:model="schedules.{{ $index }}.end" class="mt-1 block w-full border-gray-600 bg-dark-700 text-gray-300 rounded-md shadow-sm">
                    @error("schedules.$index.end")
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
                <input type="hidden" wire:model="schedules.{{ $index }}.id">

                <!-- Remove Button -->
                <div class="col-span-2 flex justify-end">
                    <x-button type="button" color="red" wire:click="removeSchedule({{ $index }})">Remove</x-button>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Add Button -->
        <div class="mt-4">
            <x-button type="button" color="green" wire:click="addSchedule">+ Add Schedule</x-button>
        </div>

        <!-- Submit -->
        <div class="flex justify-end mt-6">
            <x-button type="submit" color="blue">Save</x-button>
        </div>
    </form>

</div>
