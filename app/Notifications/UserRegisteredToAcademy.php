<?php

namespace App\Notifications;

use App\Models\StudentClasses;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserRegisteredToAcademy extends Notification
{
    use Queueable;

    public $student;
    public $academy;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $student, $academy)
    {
        $this->student = $student;
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
            ->subject('New Student Registration - ' . $this->academy->name)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A new student has registered for the ' . $this->academy->name . ' academy.')
            ->line('Student Name: ' . $this->student->name)
            ->line('Student Email: ' . $this->student->email)
            ->line('You can now assign classes and track their progress.')
            ->action('View Student', url('/admin/academy/students'))
            ->line('Thank you for managing the academy!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'student_id' => $this->student->id,
            'student_name' => $this->student->name,
            'academy_id' => $this->academy->id,
            'academy_name' => $this->academy->name,
            'message' => "New student {$this->student->name} has registered for {$this->academy->name}",
            'type' => 'academy_registration',
        ];
    }
}
