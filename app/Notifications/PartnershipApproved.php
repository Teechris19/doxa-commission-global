<?php

namespace App\Notifications;

use App\Models\Partnership;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PartnershipApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public $partnership;

    /**
     * Create a new notification instance.
     */
    public function __construct(Partnership $partnership)
    {
        $this->partnership = $partnership;
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
            ->subject('Partnership Approved - ' . $this->partnership->name)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your partnership request has been approved!')
            ->line('Partnership Name: ' . $this->partnership->name)
            ->line('Status: ' . ucfirst($this->partnership->status))
            ->action('View Partnership Details', url('/partnerships/' . $this->partnership->id))
            ->line('Thank you for your partnership with us!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'partnership_id' => $this->partnership->id,
            'partnership_name' => $this->partnership->name,
            'status' => $this->partnership->status,
            'message' => "Your partnership '{$this->partnership->name}' has been approved",
            'type' => 'partnership_approved',
        ];
    }
}
