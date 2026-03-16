<?php

namespace App\Notifications;

use App\Models\Sermons;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SermonUploaded extends Notification implements ShouldQueue
{
    use Queueable;

    public $sermon;

    /**
     * Create a new notification instance.
     */
    public function __construct(Sermons $sermon)
    {
        $this->sermon = $sermon;
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
            ->subject('New Sermon Upload - ' . $this->sermon->title)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A new sermon has been uploaded and is ready for viewing.')
            ->line('Sermon Title: ' . $this->sermon->title)
            ->line('Preacher: ' . ($this->sermon->preacher ?? 'Unknown'))
            ->action('Watch Sermon', url('/sermons/' . $this->sermon->id))
            ->line('Thank you for staying connected with us!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'sermon_id' => $this->sermon->id,
            'sermon_title' => $this->sermon->title,
            'preacher' => $this->sermon->preacher,
            'message' => "New sermon '{$this->sermon->title}' has been uploaded",
            'type' => 'sermon_uploaded',
        ];
    }
}
