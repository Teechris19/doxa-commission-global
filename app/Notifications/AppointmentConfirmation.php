<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    public $appointment;

    /**
     * Create a new notification instance.
     */
    public function __construct(Appointment $appointment)
    {
        $this->appointment = $appointment;
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
            ->subject('Appointment Confirmation')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your appointment has been successfully scheduled.')
            ->line('Appointment Details:')
            ->line('Date: ' . $this->appointment->date)
            ->line('Time: ' . $this->appointment->time)
            ->line('Chapter: ' . ($this->appointment->chapter->display_name ?? 'N/A'))
            ->action('View Appointment', url('/appointments/' . $this->appointment->id))
            ->line('If you need to reschedule or cancel, please contact us as soon as possible.')
            ->line('Thank you!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'appointment_id' => $this->appointment->id,
            'date' => $this->appointment->date,
            'time' => $this->appointment->time,
            'chapter' => $this->appointment->chapter->display_name ?? 'N/A',
            'message' => "Your appointment on {$this->appointment->date} at {$this->appointment->time} has been confirmed",
            'type' => 'appointment_confirmation',
        ];
    }
}
