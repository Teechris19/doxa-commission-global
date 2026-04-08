<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Url, Validate, Computed, Layout};
use App\Models\{Appointment, Chapter};
use TallStackUi\Traits\Interactions;
use Livewire\WithPagination;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions, WithPagination;

    public $team;

    #[Url]
    public $chapter;

    public $selected = [];

    public $quantity;
    #[Url(keep: true)]
    public $search;

    public $selectedAppointment;
    public ?string $appointmentDate;
    public ?string $appointmentTime;

    public function mount()
    {
        $this->teams = Auth()->user()->teams->filter(fn($team) => $team->pivot->role_in_team === 'team-lead')->first();
    }

    #[Computed]
    public function getAppointments()
    {
        return Appointment::onlyTrashed()
            ->with('user')
            ->when($this->team, function ($q) {
                $q->where('team_id', '=', $this->team->id);
            })
            ->when($this->chapter, function ($q) {
                $q->where('chapter_id', '=', Chapter::where('name', e($this->chapter))->first()->id);
            })
            ->when($this->search, function ($q) {
                $q->where('title', 'like', "%$this->search%")->orWhereHas('user', function ($q) {
                    $q->where('users.name', 'like', "%$this->search%");
                });
            })
            ->orderBy('date', 'desc');
    }

    public function with()
    {
        return [
            'headers' => [['index' => 'title', 'label' => 'Subject'], ['index' => 'user.name', 'label' => 'By'], ['index' => 'date', 'label' => 'scheduled'], ['index' => 'action', 'label' => 'action']],
            'rows' => $this->getAppointments()->paginate($this->quantity),
        ];
    }

    public function delete($id)
    {
        $appointment = Appointment::onlyTrashed()->where('id', '=', $id)->first();

        if (!$appointment) {
            abort(403, 'WRONG appointment');
        }
        $appointment->forceDelete();

        $this->toast()->success('Done!', 'appointment deleted successfully!')->send();
        $this->dispatch('$refresh');
    }

    public function deleteAppointment($id)
    {
        $this->dialog()
            ->error('Are You Sure you want to delete the This Appointment')
            ->hook([
                'ok' => [
                    'method' => 'delete',
                    'params' => [$id],
                ],
            ])
            ->send();
    }

    public function BulkDelete()
    {
        $this->dispatch('delete');
        $this->dialog()
            ->error('Are You Sure you want to delete the The selected appointments')
            ->hook([
                'ok' => [
                    'method' => 'deleteBulk',
                ],
            ])
            ->send();
    }

    public function deleteBulk()
    {
        Appointment::onlyTrashed()->whereIn('id', $this->selected)->forceDelete();

        $this->toast()->success('Done!', 'Success')->flash()->send();
        $this->selected = [];
        $this->bulkAction = null;
    }

    public function resheduledAppointment($id)
    {
        $appointment = Appointment::onlyTrashed()->with('user')->find($id);
        if ($appointment) {
            $this->selectedAppointment = $appointment->toArray();
            $this->appointmentDate = $appointment->date;
            $this->appointmentTime = $appointment->start_time;
        } else {
            $this->selectedAppointment = 'err';
        }
    }

    public function reschedule()
    {
        $this->validate(['appointmentDate' => 'required|string'], ['appointmentTime' => 'required|string']);
        $appointment = Appointment::onlyTrashed()->findOrFail($this->selectedAppointment['id']);

        $appointment->date = $this->appointmentDate;
        $appointment->start_time = $this->appointmentTime;

        $appointment->save();
        $appointment->restore();
        $this->reset('selectedAppointment', 'appointmentDate', 'appointmentTime');
        $this->dispatch('$refresh');
        $this->dispatch('rescheduled');

        $this->toast()->success('Done!', 'appointment Rescheduled successfully!')->send();
    }
}; ?>

<div>
    <x-modal id="reschedule">
        @if ($selectedAppointment == 'err')
            <div class="p-4 dark:bg-dark-800 rounded-md">
                <p class="text-red-500">No appointment selected</p>
            </div>
        @elseif ($selectedAppointment == null)
            <div class="p-4 dark:bg-dark-800 rounded-md">
                <p class="text-gray-300">Loading...</p>
            </div>
        @else
            <p>The appoitment was scheduled for the
                {{ \Carbon\Carbon::parse($this->selectedAppointment['date'])->format('D, d') }} of
                {{ \Carbon\Carbon::parse($this->selectedAppointment['date'])->format('M') }}
                {{ \Carbon\Carbon::parse($this->selectedAppointment['date'])->format('Y') }}</p>
            <form wire:submit.prevent="reschedule" class="dark:bg-dark-800 p-4 rounded-md">
                <div class="mb-4">
                    <label for="date" class="block text-sm font-medium text-gray-300">Date</label>
                    <input type="date" id="date" wire:model.live="appointmentDate"
                        class="mt-1 block w-full border-gray-600 bg-dark-700 text-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('appointmentDate')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
                <div class="mb-4">
                    <label for="time" class="block text-sm font-medium text-gray-300">Time</label>
                    <input type="time" id="time" wire:model.live="appointmentTime"
                        class="mt-1 block w-full border-gray-600 bg-dark-700 text-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @error('appointmentTime')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
                <div class="flex justify-end">
                    <x-button type="submit" color="blue">Save</x-button>
                </div>
            </form>
        @endif
    </x-modal>
    <x-modal id="view">
        @if ($selectedAppointment == 'err')
            <div class="p-4 dark:bg-dark-800 rounded-md">
                <p class="text-red-500">No appointment selected</p>
            </div>
        @elseif ($selectedAppointment == null)
            <div class="p-4 dark:bg-dark-800 rounded-md">
                <p class="text-gray-300">Loading...</p>
            </div>
        @else
            <p>The appoitment was scheduled for the
                {{ \Carbon\Carbon::parse($this->selectedAppointment['date'])->format('D, d') }} of
                {{ \Carbon\Carbon::parse($this->selectedAppointment['date'])->format('M') }}
                {{ \Carbon\Carbon::parse($this->selectedAppointment['date'])->format('Y') }}</p>
            <div class="p-4 dark:bg-dark-800 rounded-md">
                <h3 class="text-lg font-medium text-gray-300">Appointment Details</h3>
                <p><strong>Subject:</strong> {{ $selectedAppointment['title'] }}</p>
                <p><strong>Scheduled By:</strong> {{ $selectedAppointment['user']['name'] }}</p>
                <p><strong>Date:</strong> {{ \Carbon\Carbon::parse($selectedAppointment['date'])->format('D, d M Y') }}
                </p>
                <p><strong>Time:</strong>
                    {{ \Carbon\Carbon::parse($selectedAppointment['start_time'])->format('g:i A') }}</p>
                <p><strong>User Name:</strong> {{ $selectedAppointment['user']['name'] }}</p>
                <p><strong>Body:</strong> {{ $selectedAppointment['description'] }}</p>

                {{-- <x-button.circle color="red" icon="trash"
                    wire:click="deleteAppointment('{{ $selectedAppointment['id'] }}')" loading /> --}}
            </div>
        @endif
    </x-modal>
    <x-card class="relative dark:bg-dark-800">
        <p
            class="dark:bg-dark-900 p-2 rounded-b-md mb-4
        @if (count($selected) < 2) hidden @endif 
        ">
            you have selected <b>{{ count($selected) }}</b> out of
            <b>{{ count($this->getAppointments()->get()) }}</b>
            <button
                class="border border-red-500 text-red-500 hover:text-red-200 hover:cursor-pointer hover:bg-red-500 m-3 py-1 px-4 rounded-md transition-all duration-100 @if (count($selected) < 2) hidden @endif "
                wire:click='BulkDelete'>Delete All</button><button
                class="border border-green-500 text-green-500 hover:text-green-200 hover:cursor-pointer hover:bg-green-500 m-3 py-1 px-4 rounded-md transition-all duration-100 @if (count($selected) < 2) hidden @endif ">Export
                All</button>
        </p>
        <x-table :$headers :$rows :filter="['quantity' => 'quantity', 'search' => 'search']" :quantity="[5, 15, 50, 100, 250]" paginate persistent selectable
            wire:model.live="selected">
            @interact('column_date', $row)
                <p>{{ \Carbon\Carbon::parse($row->date)->format('F j, Y, g:i a') }}</p>
            @endinteract
            @interact('column_action', $row)
                {{-- Delete --}}
                <x-button.circle color="red" icon="trash" wire:click="deleteAppointment('{{ $row->id }}')"
                    loading />

                {{-- View --}}
                <x-button.circle color="green" icon="eye"
                    x-on:click="$wire.resheduledAppointment('{{ $row->id }}'); $modalOpen('view'); " loading />
                {{-- Edit appointment Link --}}
                <x-button x-on:click="$wire.resheduledAppointment('{{ $row->id }}'); $modalOpen('reschedule'); "
                    color="blue" icon="pencil">
                    Reschedule
                </x-button>
            @endinteract

        </x-table>

    </x-card>
    @script
        <script>
            $wire.on('rescheduled', (event) => {
                $modalClose('reschedule')
            });
            $wire.on('delete', (event) => {
                $modalClose('view')
            });
        </script>
    @endscript

</div>
