<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Report $report)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Report Submitted')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A new report has been submitted.')
            ->line('Title: ' . $this->report->title)
            ->line('Team: ' . ($this->report->team?->name ?? 'N/A'))
            ->line('Level: ' . ucfirst($this->report->level))
            ->action('View Reports', url('/admin/dashboard/reports'))
            ->line('Please review the report.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'report_id' => $this->report->id,
            'message' => "Report submitted: {$this->report->title}",
            'type' => 'report_submitted',
        ];
    }
}
