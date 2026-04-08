<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AttendanceException extends Notification
{
    use Queueable;

    public function __construct(
        public string $message,
        public ?int $sessionId = null,
        public ?int $chapterId = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => $this->message,
            'session_id' => $this->sessionId,
            'chapter_id' => $this->chapterId,
            'type' => 'attendance_exception',
        ];
    }
}
