<?php

use App\Models\{AttendanceSession, AttendanceCheckin, Chapter, User};
use App\Notifications\AttendanceException;
use App\Services\NotificationRecipients;
use Livewire\Attributes\{Layout, Url};
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component {
    #[Url(keep: true)]
    public $chapter;

    public $sessionId;
    public $searchUser = '';
    public $selectedUserId;
    public $guestName;
    public $guestEmail;
    public $guestPhone;

    public function mount()
    {
        if (!$this->chapter && auth()->check()) {
            $this->chapter = Chapter::find(auth()->user()->chapter_id)?->name;
        }
    }

    public function updatedSearchUser()
    {
        $this->selectedUserId = null;
    }

    public function selectUser($id)
    {
        $this->selectedUserId = $id;
        $this->searchUser = User::find($id)?->name ?? '';
    }

    public function checkIn()
    {
        if (!$this->sessionId) {
            $this->addError('sessionId', 'Please select an attendance session.');
            return;
        }

        if ($this->selectedUserId) {
            $existing = AttendanceCheckin::where('attendance_session_id', $this->sessionId)
                ->where('user_id', $this->selectedUserId)
                ->first();
            if ($existing) {
                $chapterId = $this->chapter ? Chapter::where('name', $this->chapter)->value('id') : null;
                $recipients = (new NotificationRecipients())
                    ->forFunctionAndChapter('attendance', $chapterId);

                foreach ($recipients as $recipient) {
                    $recipient->notify(new AttendanceException(
                        'Duplicate attendance check-in attempted.',
                        (int) $this->sessionId,
                        $chapterId
                    ));
                }

                $this->addError('searchUser', 'This user is already checked in for the session.');
                return;
            }
        } else {
            $this->validate([
                'guestName' => ['required', 'string', 'min:2'],
            ]);
        }

        AttendanceCheckin::create([
            'attendance_session_id' => $this->sessionId,
            'user_id' => $this->selectedUserId,
            'guest_name' => $this->selectedUserId ? null : $this->guestName,
            'guest_email' => $this->selectedUserId ? null : $this->guestEmail,
            'guest_phone' => $this->selectedUserId ? null : $this->guestPhone,
            'source' => 'admin',
            'checked_in_at' => now(),
        ]);

        $this->reset(['searchUser', 'selectedUserId', 'guestName', 'guestEmail', 'guestPhone']);
        session()->flash('success', 'Attendance recorded successfully.');
    }

    public function with(): array
    {
        $chapterId = $this->chapter ? Chapter::where('name', $this->chapter)->first()?->id : null;

        $chapters = Chapter::orderBy('name')->get();
        $sessions = AttendanceSession::with('attendanceEvent')
            ->where('status', 'open')
            ->when($chapterId, fn($q) => $q->whereHas('attendanceEvent', fn($e) => $e->where('chapter_id', $chapterId)))
            ->orderByDesc('date')
            ->get();

        $checkins = $this->sessionId
            ? AttendanceCheckin::with('user')
                ->where('attendance_session_id', $this->sessionId)
                ->latest('checked_in_at')
                ->limit(50)
                ->get()
            : collect();

        $userSuggestions = $this->searchUser
            ? User::where('name', 'like', "%{$this->searchUser}%")
                ->when($chapterId, fn($q) => $q->where('chapter_id', $chapterId))
                ->limit(6)
                ->get()
            : collect();

        return [
            'chapters' => $chapters,
            'sessions' => $sessions,
            'checkins' => $checkins,
            'userSuggestions' => $userSuggestions,
        ];
    }
};
?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold">Attendance Check-in</h1>
        <p class="text-sm text-zinc-600">Record attendance for open sessions.</p>
    </div>

    @if (session('success'))
        <x-card>
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        </x-card>
    @endif

    <x-card>
        <div class="space-y-5">
            @if(auth()->user()->hasRole('super-admin'))
                <div>
                    <label class="block text-sm font-medium">Chapter</label>
                    <select wire:model="chapter" class="mt-1 w-full rounded-lg border px-3 py-2">
                        @foreach($chapters as $chap)
                            <option value="{{ $chap->name }}">{{ $chap->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div>
                <label class="block text-sm font-medium">Attendance session</label>
                <select wire:model="sessionId" class="mt-1 w-full rounded-lg border px-3 py-2">
                    <option value="">Select session</option>
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}">{{ $session->attendanceEvent->name }} • {{ $session->date->format('M d, Y') }}</option>
                    @endforeach
                </select>
                @error('sessionId')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="relative">
                <label class="block text-sm font-medium">Search registered user (optional)</label>
                <input type="text" wire:model.live.debounce.300ms="searchUser" class="mt-1 w-full rounded-lg border px-3 py-2" placeholder="Type a name" />
                @error('searchUser')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
                @if($userSuggestions->isNotEmpty())
                    <div class="absolute z-10 mt-1 w-full rounded-lg border bg-white shadow">
                        @foreach($userSuggestions as $suggestion)
                            <button type="button" class="block w-full px-3 py-2 text-left text-sm hover:bg-blue-50" wire:click="selectUser({{ $suggestion->id }})">
                                {{ $suggestion->name }}
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium">Guest name</label>
                    <input type="text" wire:model="guestName" class="mt-1 w-full rounded-lg border px-3 py-2" placeholder="Guest name" />
                    @error('guestName')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium">Guest email</label>
                    <input type="email" wire:model="guestEmail" class="mt-1 w-full rounded-lg border px-3 py-2" placeholder="guest@email.com" />
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium">Guest phone</label>
                    <input type="text" wire:model="guestPhone" class="mt-1 w-full rounded-lg border px-3 py-2" placeholder="+234 ..." />
                </div>
            </div>

            <button wire:click="checkIn" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Add attendance</button>
        </div>
    </x-card>

    <x-card>
        <h2 class="text-lg font-semibold mb-3">Recent check-ins</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-zinc-500">
                    <tr>
                        <th class="py-2">Name</th>
                        <th class="py-2">Type</th>
                        <th class="py-2">Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($checkins as $checkin)
                        <tr class="border-t">
                            <td class="py-2">{{ $checkin->user?->name ?? $checkin->guest_name }}</td>
                            <td class="py-2">{{ $checkin->user_id ? 'Registered' : 'Guest' }}</td>
                            <td class="py-2">{{ $checkin->checked_in_at->format('H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-4 text-zinc-500">No check-ins yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
