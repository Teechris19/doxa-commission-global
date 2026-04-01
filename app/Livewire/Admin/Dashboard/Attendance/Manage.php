<?php

namespace App\Livewire\Admin\Dashboard\Attendance;

use App\Models\{AttendanceSession, Chapter, Service, Events};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;
use Illuminate\Support\Facades\Auth;

#[Layout('components.layouts.admin')]
new class extends Component {
    use Interactions, WithPagination;

    public $chapter;
    public $sessionDate;
    public $sessionType = 'service'; // service, event, custom
    public $selectedServiceId;
    public $selectedEventId;
    public $customName;
    public $customLocation;

    public function mount()
    {
        $user = Auth::user();
        if (!$this->chapter && $user) {
            $this->chapter = Chapter::find($user->chapter_id)?->name;
        }
        $this->sessionDate = now()->toDateString();
    }

    public function createSession()
    {
        $user = Auth::user();
        $chapterId = $this->chapter ? Chapter::where('name', $this->chapter)->first()?->id : $user->chapter_id;

        if (!$chapterId) {
            $this->toast()->error('Chapter missing', 'Please select a chapter first.')->send();
            return;
        }

        $validated = [];
        
        if ($this->sessionType === 'service') {
            $validated['selectedServiceId'] = 'required';
        } elseif ($this->sessionType === 'event') {
            $validated['selectedEventId'] = 'required';
        } else {
            $validated['customName'] = 'required|string|min:3';
        }

        $this->validate($validated);

        $sessionName = '';
        $serviceId = null;
        $eventId = null;
        $location = null;

        if ($this->sessionType === 'service') {
            $service = Service::where('chapter_id', $chapterId)->find($this->selectedServiceId);
            if ($service) {
                $sessionName = $service->name;
                $serviceId = $service->id;
                $location = $service->location;
            }
        } elseif ($this->sessionType === 'event') {
            $event = Events::where('chapter_id', $chapterId)->find($this->selectedEventId);
            if ($event) {
                $sessionName = $event->title;
                $eventId = $event->id;
                $location = $event->location;
            }
        } else {
            $sessionName = $this->customName;
            $location = $this->customLocation;
        }

        if (!$sessionName) {
            $this->toast()->error('Invalid selection', 'Please select a valid service or event.')->send();
            return;
        }

        // Check if session already exists for this date and name
        $existing = AttendanceSession::where('date', $this->sessionDate)
            ->where('session_name', $sessionName)
            ->where('chapter_id', $chapterId)
            ->first();

        if ($existing) {
            $this->toast()->warning('Session exists', 'A session with this name already exists for the selected date.')->send();
            return;
        }

        AttendanceSession::create([
            'chapter_id' => $chapterId,
            'created_by' => $user->id,
            'session_type' => $this->sessionType,
            'session_name' => $sessionName,
            'service_id' => $serviceId,
            'event_id' => $eventId,
            'location' => $location,
            'date' => $this->sessionDate,
            'status' => 'open',
        ]);

        $this->toast()->success('Session created', 'Attendance session created successfully. Navigate to Check-in to mark attendance.')->send();
        
        // Reset form
        $this->reset(['customName', 'customLocation', 'selectedServiceId', 'selectedEventId']);
    }

    public function closeSession($id)
    {
        $session = AttendanceSession::find($id);
        if ($session) {
            $session->status = 'closed';
            $session->closed_at = now();
            $session->save();
            $this->toast()->success('Session closed', 'Attendance session has been closed.')->send();
        }
    }

    public function reopenSession($id)
    {
        $session = AttendanceSession::find($id);
        if ($session) {
            $session->status = 'open';
            $session->closed_at = null;
            $session->save();
            $this->toast()->success('Session reopened', 'Attendance session is now open.')->send();
        }
    }

    private function getSessions()
    {
        $user = Auth::user();
        $chapterId = $this->chapter ? Chapter::where('name', $this->chapter)->first()?->id : $user->chapter_id;

        return AttendanceSession::with(['creator', 'service', 'event'])
            ->withCount('records')
            ->when($chapterId, fn($q) => $q->where('chapter_id', $chapterId))
            ->orderByDesc('date')
            ->paginate(15);
    }

    public function with(): array
    {
        $user = Auth::user();
        $chapterId = $this->chapter ? Chapter::where('name', $this->chapter)->first()?->id : $user->chapter_id;

        $chapters = Chapter::orderBy('name')->get();
        $services = Service::where('chapter_id', $chapterId)->orderBy('name')->get();
        $events = Events::where('chapter_id', $chapterId)->latest()->get();
        $sessions = $this->getSessions();

        return [
            'chapters' => $chapters,
            'services' => $services,
            'events' => $events,
            'sessions' => $sessions,
        ];
    }
};
