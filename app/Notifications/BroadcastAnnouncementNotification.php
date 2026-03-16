<?php

namespace App\Notifications;

use App\Models\BroadcastAnnouncement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BroadcastAnnouncementNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public BroadcastAnnouncement $announcement)
    {
        $this->announcement->load('chapter');
    }

    public function via(object $notifiable): array
    {
        return match ($this->announcement->channel) {
            'mail' => ['mail'],
            'database' => ['database'],
            default => ['mail', 'database'],
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        $chapterName = $this->announcement->chapter?->name;
        $branchPrefix = $chapterName ? "[{$chapterName}] " : '';

        return (new MailMessage)
            ->subject($branchPrefix.$this->announcement->title)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($branchPrefix.$this->announcement->message)
            ->line('Thank you for staying connected.');
    }

    public function toDatabase(object $notifiable): array
    {
        $chapterName = $this->announcement->chapter?->name;
        $branchPrefix = $chapterName ? "[{$chapterName}] " : '';

        return [
            'broadcast_announcement_id' => $this->announcement->id,
            'message' => $branchPrefix.$this->announcement->title,
            'message_full' => $branchPrefix.$this->announcement->message,
            'chapter_name' => $chapterName,
            'type' => 'broadcast_announcement',
        ];
    }
}
