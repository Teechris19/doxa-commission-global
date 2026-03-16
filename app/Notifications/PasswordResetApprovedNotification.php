<?php

namespace App\Notifications;

use App\Models\PasswordResetRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public PasswordResetRequest $resetRequest;
    public string $resetUrl;

    /**
     * Create a new notification instance.
     */
    public function __construct(PasswordResetRequest $resetRequest, string $resetUrl)
    {
        $this->resetRequest = $resetRequest;
        $this->resetUrl = $resetUrl;
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
            ->subject('Password Reset Link')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your password reset request has been approved.')
            ->action('Reset Your Password', $this->resetUrl)
            ->line('This password reset link will expire in 60 minutes.')
            ->line('If you did not request a password reset, no further action is required.')
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
