<?php

namespace App\Events;

use App\Models\StudentClasses;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudentRegisteredToAcademy
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $student;
    public $academy;
    public $studentClass;

    /**
     * Create a new event instance.
     */
    public function __construct(User $student, $academy, StudentClasses $studentClass)
    {
        $this->student = $student;
        $this->academy = $academy;
        $this->studentClass = $studentClass;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
