<?php

namespace App\Notifications;

use App\Models\PasswordResetRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public PasswordResetRequest $resetRequest;

    /**
     * Create a new notification instance.
     */
    public function __construct(PasswordResetRequest $resetRequest)
    {
        $this->resetRequest = $resetRequest;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Password Reset Request')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('We have received your password reset request.')
            ->line('Our team will review your request and send you a password reset link if it is approved.')
            ->line('This usually takes a few hours.')
            ->line('If you did not request this, you can safely ignore this email.')
            ->salutation('Blessings,')
            ->salutation('Doxa Commission Global');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'reset_request_id' => $this->resetRequest->id,
            'email' => $this->resetRequest->email,
        ];
    }
}
