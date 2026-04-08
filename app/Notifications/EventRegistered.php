<?php

namespace App\Notifications;

use App\Models\Events;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventRegistered extends Notification
{
    use Queueable;

    public $event;
    public $registrant;

    /**
     * Create a new notification instance.
     */
    public function __construct(Events $event, User $registrant)
    {
        $this->event = $event;
        $this->registrant = $registrant;
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
        return (new MailMessage)
            ->subject('New Event Registration - ' . $this->event->name)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A new participant has registered for ' . $this->event->name . '.')
            ->line('Participant Name: ' . $this->registrant->name)
            ->line('Participant Email: ' . $this->registrant->email)
            ->line('Event Date: ' . $this->event->start_date)
            ->action('View Registrations', url('/admin/events/registrations'))
            ->line('Thank you for organizing this event!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'event_id' => $this->event->id,
            'event_name' => $this->event->name,
            'registrant_id' => $this->registrant->id,
            'registrant_name' => $this->registrant->name,
            'message' => "{$this->registrant->name} has registered for {$this->event->name}",
            'type' => 'event_registration',
        ];
    }
}
