<?php

namespace App\Notifications;

use App\Models\ProfileChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProfileChangeRequestSubmitted extends Notification
{
    use Queueable;

    public function __construct(public ProfileChangeRequest $request)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'profile_change_request_id' => $this->request->id,
            'user_id' => $this->request->user_id,
            'message' => 'New profile change request submitted.',
            'type' => 'profile_change_request_submitted',
        ];
    }
}
