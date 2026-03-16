<?php

namespace App\Notifications;

use App\Models\PrayerRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PrayerRequestSubmitted extends Notification
{
    use Queueable;

    public $prayerRequest;

    /**
     * Create a new notification instance.
     */
    public function __construct(PrayerRequest $prayerRequest)
    {
        $this->prayerRequest = $prayerRequest;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $submittedBy = $this->prayerRequest->user?->name ?? ($this->prayerRequest->name ?? 'Anonymous');

        return (new MailMessage)
            ->subject('New Prayer Request Submitted')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A new prayer request has been submitted to your team.')
            ->line('Request Title: ' . $this->prayerRequest->title)
            ->line('Submitted by: ' . $submittedBy)
            ->action('View Prayer Request', url('/admin/dashboard/prayer-requests'))
            ->line('Please intercede for this request and share it with your prayer team.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'prayer_request_id' => $this->prayerRequest->id,
            'title' => $this->prayerRequest->title,
            'submitted_by' => $this->prayerRequest->user->name ?? 'Anonymous',
            'message' => "New prayer request: {$this->prayerRequest->title}",
            'type' => 'prayer_request',
        ];
    }
}
