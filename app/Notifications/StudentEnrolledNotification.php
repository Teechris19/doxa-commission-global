<?php

namespace App\Notifications;

use App\Models\BelieversAcademy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentEnrolledNotification extends Notification
{
    use Queueable;

    public $academy;

    /**
     * Create a new notification instance.
     */
    public function __construct(BelieversAcademy $academy)
    {
        $this->academy = $academy;
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
            ->subject('Welcome to ' . $this->academy->name)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Welcome to the ' . $this->academy->name . ' academy!')
            ->line('Your enrollment has been confirmed.')
            ->line('You can now access your student dashboard to track your progress and view assigned classes.')
            ->action('Access Your Dashboard', url('/academy/student/dashboard'))
            ->line('If you have any questions, please contact your academy administrator.')
            ->line('Thank you for joining us!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'academy_id' => $this->academy->id,
            'academy_name' => $this->academy->name,
            'message' => "You have been enrolled in {$this->academy->name}",
            'type' => 'enrollment_confirmation',
        ];
    }
}
