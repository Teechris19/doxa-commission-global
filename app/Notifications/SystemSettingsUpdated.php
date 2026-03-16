<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SystemSettingsUpdated extends Notification
{
    use Queueable;

    public function __construct(
        public int $userId,
        public string $context = 'global_settings'
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
            'context' => $this->context,
            'message' => 'System settings were updated.',
            'type' => 'system_settings_updated',
        ];
    }
}
