<?php

namespace App\Notifications;

use App\Models\Transport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransportRequestUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Transport $transport,
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
            ->subject('Transport Request Update')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A transport request has been updated.')
            ->line('Requester: ' . $this->transport->name)
            ->line('Phone: ' . $this->transport->phone)
            ->line('Pickup: ' . $this->transport->pickup_location)
            ->line('Status: ' . ucfirst($this->status))
            ->action('View Transport Requests', url('/admin/dashboard/transport'))
            ->line('Please follow up as needed.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'transport_id' => $this->transport->id,
            'status' => $this->status,
            'message' => "Transport request {$this->status}: {$this->transport->name}",
            'type' => 'transport_request_updated',
        ];
    }
}
