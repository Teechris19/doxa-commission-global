<?php

namespace App\Notifications;

use App\Models\AcademyClases;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClassCompletedByStudent extends Notification
{
    use Queueable;

    public $student;
    public $class;
    public $completionStatus;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $student, AcademyClases $class, string $completionStatus = 'completed')
    {
        $this->student = $student;
        $this->class = $class;
        $this->completionStatus = $completionStatus;
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
        $statusMessage = $this->completionStatus === 'completed' 
            ? 'successfully completed' 
            : 'marked as ' . $this->completionStatus;

        return (new MailMessage)
            ->subject('Class Progress Update - ' . $this->class->name)
            ->greeting('Hello ' . $this->student->name . ',')
            ->line('Congratulations! Your class has been ' . $statusMessage . '.')
            ->line('Class Name: ' . $this->class->name)
            ->line('Date: ' . $this->class->date)
            ->line('Keep up the great work!')
            ->action('View My Classes', url('/academy/student/dashboard'))
            ->line('Thank you for being part of our academy!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'class_name' => $this->class->name,
            'status' => $this->completionStatus,
            'message' => "Class {$this->class->name} has been marked as {$this->completionStatus}",
            'type' => 'class_completion',
        ];
    }
}
