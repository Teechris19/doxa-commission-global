<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReportEscalated extends Notification
{
    use Queueable;

    public function __construct(
        public Report $report,
        public string $fromLevel,
        public string $toLevel,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'report_id' => $this->report->id,
            'title' => $this->report->title,
            'from_level' => $this->fromLevel,
            'to_level' => $this->toLevel,
            'chapter_id' => $this->report->chapter_id,
            'team_id' => $this->report->team_id,
        ];
    }
}
