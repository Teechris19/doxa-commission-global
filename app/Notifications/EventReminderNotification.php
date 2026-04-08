<?php

namespace App\Notifications;

use App\Models\Events;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Events $event,
        public string $windowLabel,
        public ?string $recipientName = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $this->recipientName ?: ($notifiable->name ?? 'there');

        return (new MailMessage)
            ->subject('Event Reminder: ' . $this->event->title)
            ->greeting('Hello ' . $name . ',')
            ->line('This is a reminder for the upcoming event:')
            ->line($this->event->title)
            ->line('Starts: ' . $this->event->start_at?->format('F j, Y g:i A'))
            ->line('Reminder: ' . $this->windowLabel)
            ->action('View Event', url('/events/' . $this->event->id))
            ->line('We look forward to seeing you.');
    }
}
