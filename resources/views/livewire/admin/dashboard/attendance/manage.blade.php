<?php

use App\Models\{AttendanceEvent, AttendanceSession, AttendanceCheckin, Chapter, Events, Service, User};
use Livewire\Attributes\{Layout, Url};
use Livewire\Volt\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions, WithPagination;

    #[Url(keep: true)]
    public $chapter;

    public $selectedSource = 'service';
    public $selectedEventId;
    public $selectedServiceId;
    public $customName;
    public $customLocation;
    public $sessionDate;

    public $activeSessionId;

    public $searchUser = '';
    public $selectedUserId;
    public $guestName;
    public $guestEmail;
    public $guestPhone;

    public function mount()
    {
        $user = Auth::user();
        if (!$this->chapter && $user) {
            $this->chapter = Chapter::find($user->chapter_id)?->name;
        }

        $this->sessionDate = now()->toDateString();
    }

    public function updatedSelectedSource()
    {
        $this->reset(['selectedEventId', 'selectedServiceId', 'customName', 'customLocation']);
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

    public function createSession()
    {
        $user = Auth::user();
        $chapterId = $this->chapter ? Chapter::where('name', $this->chapter)->first()?->id : $user->chapter_id;

        if (!$chapterId) {
            $this->toast()->error('Chapter missing', 'Please select a chapter first.')->send();
            return;
        }

        $rules = [
            'sessionDate' => ['required', 'date'],
            'selectedSource' => ['required'],
        ];

        if ($this->selectedSource === 'event') {
            $rules['selectedEventId'] = ['required'];
        } elseif ($this->selectedSource === 'service') {
            $rules['selectedServiceId'] = ['required'];
        } else {
            $rules['customName'] = ['required', 'string', 'min:3'];
        }

        $this->validate($rules);

        $attendanceEvent = null;
        if ($this->selectedSource === 'event') {
            $event = Events::where('chapter_id', $chapterId)->find($this->selectedEventId);
            if ($event) {
                $attendanceEvent = AttendanceEvent::firstOrCreate([
                    'chapter_id' => $chapterId,
                    'source_type' => 'event',
                    'event_id' => $event->id,
                ], [
                    'name' => $event->title,
                    'location' => $event->location,
                    'created_by' => $user->id,
                ]);
            }
        } elseif ($this->selectedSource === 'service') {
            $service = Service::where('chapter_id', $chapterId)->find($this->selectedServiceId);
            if ($service) {
                $attendanceEvent = AttendanceEvent::firstOrCreate([
                    'chapter_id' => $chapterId,
                    'source_type' => 'service',
                    'service_id' => $service->id,
                ], [
                    'name' => $service->name,
                    'location' => $service->location,
                    'created_by' => $user->id,
                ]);
            }
        } else {
            $attendanceEvent = AttendanceEvent::create([
                'chapter_id' => $chapterId,
                'source_type' => 'custom',
                'name' => $this->customName,
                'location' => $this->customLocation,
                'created_by' => $user->id,
            ]);
        }

        if (!$attendanceEvent) {
            $this->toast()->error('Unable to create event', 'Please check your selection.')->send();
            return;
        }

        $session = AttendanceSession::firstOrCreate([
            'attendance_event_id' => $attendanceEvent->id,
            'date' => $this->sessionDate,
        ], [
            'status' => 'open',
            'location' => $attendanceEvent->location,
            'opened_by' => $user->id,
        ]);

        if ($session->status !== 'open') {
            $session->status = 'open';
            $session->opened_by = $user->id;
            $session->save();
        }

        $this->activeSessionId = $session->id;
        $this->toast()->success('Attendance opened', 'Session is now open for check-in.')->send();
    }

    public function openSession($id)
    {
        $session = AttendanceSession::find($id);
        if ($session) {
            $session->status = 'open';
            $session->opened_by = Auth::id();
            $session->closed_at = null;
            $session->save();
            $this->activeSessionId = $session->id;
        }
    }

    public function exportSession($id): StreamedResponse
    {
        $session = AttendanceSession::with(['attendanceEvent', 'checkins.user'])->findOrFail($id);
        $filename = sprintf('attendance_%s_%s.csv', str_replace(' ', '_', strtolower($session->attendanceEvent->name)), $session->date->format('Y_m_d'));

        return response()->streamDownload(function () use ($session) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'Type', 'Email', 'Phone', 'Checked In At']);

            foreach ($session->checkins as $checkin) {
                $name = $checkin->user?->name ?? $checkin->guest_name;
                $type = $checkin->user_id ? 'Registered' : 'Guest';
                $email = $checkin->user?->email ?? $checkin->guest_email;
                $phone = $checkin->user?->phone ?? $checkin->guest_phone;
                $time = $checkin->checked_in_at?->format('Y-m-d H:i');
                fputcsv($handle, [$name, $type, $email, $phone, $time]);
            }

            fclose($handle);
        }, $filename);
    }

    public function closeSession($id)
    {
        $session = AttendanceSession::find($id);
        if ($session) {
            $session->status = 'closed';
            $session->closed_at = now();
            $session->save();
            if ($this->activeSessionId == $session->id) {
                $this->activeSessionId = null;
            }
        }
    }

    public function addCheckin()
    {
        if (!$this->activeSessionId) {
            $this->toast()->error('No active session', 'Open a session before adding attendance.')->send();
            return;
        }

        if ($this->selectedUserId) {
            $existing = AttendanceCheckin::where('attendance_session_id', $this->activeSessionId)
                ->where('user_id', $this->selectedUserId)
                ->first();
            if ($existing) {
                $this->toast()->warning('Already checked in', 'This user already checked in for the session.')->send();
                return;
            }
        } else {
            $this->validate([
                'guestName' => ['required', 'string', 'min:2'],
            ]);
        }

        AttendanceCheckin::create([
            'attendance_session_id' => $this->activeSessionId,
            'user_id' => $this->selectedUserId,
            'guest_name' => $this->selectedUserId ? null : $this->guestName,
            'guest_email' => $this->selectedUserId ? null : $this->guestEmail,
            'guest_phone' => $this->selectedUserId ? null : $this->guestPhone,
            'source' => 'admin',
            'checked_in_at' => now(),
        ]);

        $this->reset(['searchUser', 'selectedUserId', 'guestName', 'guestEmail', 'guestPhone']);
    }

    public function removeCheckin($id)
    {
        AttendanceCheckin::find($id)?->delete();
    }

    public function getSessions()
    {
        $user = Auth::user();
        $chapterId = $this->chapter ? Chapter::where('name', $this->chapter)->first()?->id : $user->chapter_id;

        return AttendanceSession::with('attendanceEvent')
            ->withCount('checkins')
            ->when($chapterId, fn($q) => $q->whereHas('attendanceEvent', fn($e) => $e->where('chapter_id', $chapterId)))
            ->orderByDesc('date')
            ->paginate(10);
    }

    public function getCheckins()
    {
        if (!$this->activeSessionId) {
            return collect();
        }

        return AttendanceCheckin::with('user')
            ->where('attendance_session_id', $this->activeSessionId)
            ->latest('checked_in_at')
            ->get();
    }

    public function with(): array
    {
        $user = Auth::user();
        $chapterId = $this->chapter ? Chapter::where('name', $this->chapter)->first()?->id : $user->chapter_id;

        $chapters = Chapter::orderBy('name')->get();
        $events = Events::where('chapter_id', $chapterId)->latest()->take(50)->get();
        $services = Service::where('chapter_id', $chapterId)->orderBy('name')->get();

        $sessions = $this->getSessions();
        $checkins = $this->getCheckins();

        $sessionCounts = AttendanceSession::withCount('checkins')
            ->when($chapterId, fn($q) => $q->whereHas('attendanceEvent', fn($e) => $e->where('chapter_id', $chapterId)))
            ->orderBy('date')
            ->get()
            ->map(fn($s) => [
                'date' => $s->date->format('M d'),
                'count' => $s->checkins_count,
            ]);

        $userSuggestions = $this->searchUser
            ? User::where('name', 'like', "%{$this->searchUser}%")
                ->when($chapterId, fn($q) => $q->where('chapter_id', $chapterId))
                ->limit(6)
                ->get()
            : collect();

        return [
            'chapters' => $chapters,
            'events' => $events,
            'services' => $services,
            'sessions' => $sessions,
            'checkins' => $checkins,
            'sessionCounts' => $sessionCounts,
            'userSuggestions' => $userSuggestions,
        ];
    }
};
?>

<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Attendance Management</h1>
            <p class="text-sm text-zinc-600">Open sessions, record attendance, and track trends per event/service.</p>
        </div>
    </div>

    <x-card>
        <div class="grid gap-4 md:grid-cols-2">
            @if(auth()->user()->hasRole('super-admin'))
                <div class="md:col-span-2">
                    <label class="text-sm font-medium">Chapter</label>
                    <select wire:model="chapter" class="mt-1 w-full rounded-lg border px-3 py-2">
                        @foreach($chapters as $chap)
                            <option value="{{ $chap->name }}">{{ $chap->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div>
                <label class="text-sm font-medium">Session date</label>
                <input type="date" wire:model="sessionDate" class="mt-1 w-full rounded-lg border px-3 py-2" />
            </div>
            <div>
                <label class="text-sm font-medium">Source</label>
                <select wire:model="selectedSource" class="mt-1 w-full rounded-lg border px-3 py-2">
                    <option value="service">Service</option>
                    <option value="event">Event</option>
                    <option value="custom">Custom</option>
                </select>
            </div>

            @if($selectedSource === 'event')
                <div class="md:col-span-2" wire:key="attendance-source-event">
                    <label class="text-sm font-medium">Pick event</label>
                    <select wire:model="selectedEventId" class="mt-1 w-full rounded-lg border px-3 py-2">
                        <option value="">Select event</option>
                        @foreach($events as $event)
                            <option value="{{ $event->id }}">{{ $event->title }}</option>
                        @endforeach
                    </select>
                </div>
            @elseif($selectedSource === 'service')
                <div class="md:col-span-2" wire:key="attendance-source-service">
                    <label class="text-sm font-medium">Pick service</label>
                    <select wire:model="selectedServiceId" class="mt-1 w-full rounded-lg border px-3 py-2">
                        <option value="">Select service</option>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}">{{ $service->name }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <div wire:key="attendance-source-custom-name">
                    <label class="text-sm font-medium">Custom name</label>
                    <input type="text" wire:model="customName" class="mt-1 w-full rounded-lg border px-3 py-2" placeholder="e.g. Sunday 1st Service" />
                </div>
                <div wire:key="attendance-source-custom-location">
                    <label class="text-sm font-medium">Location (optional)</label>
                    <input type="text" wire:model="customLocation" class="mt-1 w-full rounded-lg border px-3 py-2" placeholder="Main auditorium" />
                </div>
            @endif

            <div class="md:col-span-2">
                <button wire:click="createSession" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Open Attendance Session</button>
            </div>
        </div>
    </x-card>

    <x-card>
        <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
            <div>
                <h2 class="text-lg font-semibold mb-3">Session check-ins</h2>

                @if(!$activeSessionId)
                    <p class="text-sm text-zinc-500">Open a session to start recording attendance.</p>
                @else
                    <div class="mb-4 grid gap-3 md:grid-cols-2">
                        <div class="relative">
                            <label class="text-sm font-medium">Search registered user</label>
                            <input type="text" wire:model.live.debounce.300ms="searchUser" class="mt-1 w-full rounded-lg border px-3 py-2" placeholder="Type a name" />
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
                        <div>
                            <label class="text-sm font-medium">Guest name</label>
                            <input type="text" wire:model="guestName" class="mt-1 w-full rounded-lg border px-3 py-2" placeholder="Walk-in attendee" />
                        </div>
                        <div>
                            <label class="text-sm font-medium">Guest email</label>
                            <input type="email" wire:model="guestEmail" class="mt-1 w-full rounded-lg border px-3 py-2" placeholder="guest@email.com" />
                        </div>
                        <div>
                            <label class="text-sm font-medium">Guest phone</label>
                            <input type="text" wire:model="guestPhone" class="mt-1 w-full rounded-lg border px-3 py-2" placeholder="+234 ..." />
                        </div>
                    </div>

                    <button wire:click="addCheckin" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Add Attendance</button>

                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-zinc-500">
                                <tr>
                                    <th class="py-2">Name</th>
                                    <th class="py-2">Type</th>
                                    <th class="py-2">Time</th>
                                    <th class="py-2">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($checkins as $checkin)
                                    <tr class="border-t">
                                        <td class="py-2">{{ $checkin->user?->name ?? $checkin->guest_name }}</td>
                                        <td class="py-2">{{ $checkin->user_id ? 'Registered' : 'Guest' }}</td>
                                        <td class="py-2">{{ $checkin->checked_in_at->format('H:i') }}</td>
                                        <td class="py-2">
                                            <button wire:click="removeCheckin({{ $checkin->id }})" class="text-red-600 text-xs">Remove</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="py-4 text-zinc-500">No check-ins yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div>
                <h2 class="text-lg font-semibold mb-3">Attendance trend</h2>
                <canvas id="attendanceChart" height="180"></canvas>
            </div>
        </div>
    </x-card>

    <x-card>
        <h2 class="text-lg font-semibold mb-3">Sessions</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-zinc-500">
                    <tr>
                        <th class="py-2">Event</th>
                        <th class="py-2">Date</th>
                        <th class="py-2">Status</th>
                        <th class="py-2">Check-ins</th>
                        <th class="py-2">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sessions as $session)
                        <tr class="border-t">
                            <td class="py-2">{{ $session->attendanceEvent->name }}</td>
                            <td class="py-2">{{ $session->date->format('M d, Y') }}</td>
                            <td class="py-2">{{ ucfirst($session->status) }}</td>
                            <td class="py-2">{{ $session->checkins_count }}</td>
                            <td class="py-2 flex gap-2">
                                <button wire:click="openSession({{ $session->id }})" class="text-blue-600 text-xs">Open</button>
                                <button wire:click="closeSession({{ $session->id }})" class="text-amber-600 text-xs">Close</button>
                                <button wire:click="exportSession({{ $session->id }})" class="text-emerald-600 text-xs">Export CSV</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $sessions->links() }}</div>
    </x-card>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('livewire:navigated', initAttendanceChart);
    document.addEventListener('livewire:load', initAttendanceChart);

    let attendanceChart;
    function initAttendanceChart() {
        const ctx = document.getElementById('attendanceChart');
        if (!ctx) return;

        const labels = @json($sessionCounts->pluck('date'));
        const data = @json($sessionCounts->pluck('count'));

        if (attendanceChart) {
            attendanceChart.destroy();
        }

        attendanceChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Attendance',
                    data: data,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.2)',
                    tension: 0.3,
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
</script>
