<?php

namespace App\Notifications;

use App\Models\PartnershipIntent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PartnershipIntentSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public PartnershipIntent $intent)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Partnership Intent')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A new partnership intent has been submitted.')
            ->line('Title: ' . $this->intent->title)
            ->line('Type: ' . ucfirst($this->intent->intent_type))
            ->line('Amount: ' . ($this->intent->pledge_amount ?? 'N/A'))
            ->action('View Intent', url('/admin/dashboard/partnership/intents'))
            ->line('Please review and follow up.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'partnership_intent_id' => $this->intent->id,
            'message' => "Partnership intent submitted: {$this->intent->title}",
            'type' => 'partnership_intent_submitted',
        ];
    }
}
