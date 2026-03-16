<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PasswordResetRequested extends Notification
{
    use Queueable;

    public function __construct(
        public int $userId,
        public string $email
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'user_id' => $this->userId,
            'email' => $this->email,
            'message' => 'Password reset request submitted.',
            'type' => 'password_reset_requested',
        ];
    }
}
