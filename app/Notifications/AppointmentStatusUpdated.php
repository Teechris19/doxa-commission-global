<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Appointment $appointment,
        public string $status
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Appointment Update')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your appointment has been updated.')
            ->line('Status: ' . ucfirst($this->status))
            ->line('Date: ' . $this->appointment->date)
            ->line('Time: ' . $this->appointment->start_time)
            ->action('View Appointment', url('/appointments/' . $this->appointment->id))
            ->line('If you have questions, please contact the church office.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'appointment_id' => $this->appointment->id,
            'status' => $this->status,
            'message' => "Appointment {$this->status}: {$this->appointment->title}",
            'type' => 'appointment_status_updated',
        ];
    }
}
