<?php

namespace App\Services;

use App\Models\BroadcastAnnouncement;
use App\Models\EventForm;
use App\Models\User;
use App\Notifications\BroadcastAnnouncementNotification;
use App\Notifications\EventReminderNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

class SchedulerTasks
{
    public function runEventReminders(): int
    {
        $now = Carbon::now();
        $sent = 0;

        $sent += $this->sendEventWindowReminder($now, 24, '24 hours', 'reminder_24h_sent_at');
        $sent += $this->sendEventWindowReminder($now, 2, '2 hours', 'reminder_2h_sent_at');

        return $sent;
    }

    private function sendEventWindowReminder(Carbon $now, int $hours, string $label, string $column): int
    {
        $start = $now->copy()->addHours($hours)->subMinutes(10);
        $end = $now->copy()->addHours($hours)->addMinutes(10);

        $forms = EventForm::query()
            ->where('status', 'confirmed')
            ->whereNull($column)
            ->whereNotNull('email')
            ->whereHas('event', function ($q) use ($start, $end) {
                $q->whereBetween('start_at', [$start, $end]);
            })
            ->with('event')
            ->limit(500)
            ->get();

        $sent = 0;
        foreach ($forms as $form) {
            if (! $form->event) {
                continue;
            }

            Notification::route('mail', $form->email)
                ->notify(new EventReminderNotification($form->event, $label, $form->name));

            $form->update([$column => $now]);
            $sent++;
        }

        return $sent;
    }

    public function runBroadcastAnnouncements(): int
    {
        $now = Carbon::now();

        $announcements = BroadcastAnnouncement::query()
            ->where('status', 'scheduled')
            ->whereNotNull('send_at')
            ->where('send_at', '<=', $now)
            ->whereNull('sent_at')
            ->limit(10)
            ->get();

        $sent = 0;

        foreach ($announcements as $announcement) {
            $recipients = $this->getRecipients($announcement);

            foreach ($recipients as $user) {
                $user->notify(new BroadcastAnnouncementNotification($announcement));
                $sent++;
            }

            $announcement->update([
                'status' => 'sent',
                'sent_at' => $now,
            ]);
        }

        return $sent;
    }

    private function getRecipients(BroadcastAnnouncement $announcement)
    {
        $targetAudience = $announcement->target_audience ?? 'all_users';
        $chapterId = $announcement->chapter_id;

        return match ($targetAudience) {
            'admins' => $this->getAdmins($chapterId),
            'team_leads' => $this->getTeamLeads($chapterId),
            default => $this->getAllUsers($chapterId),
        };
    }

    private function getAllUsers(?int $chapterId): \Illuminate\Support\Collection
    {
        return User::query()
            ->when($chapterId, fn ($q) => $q->where('chapter_id', $chapterId))
            ->get();
    }

    private function getAdmins(?int $chapterId): \Illuminate\Support\Collection
    {
        $admins = User::role(['admin'])
            ->when($chapterId, fn ($q) => $q->where('chapter_id', $chapterId))
            ->get();

        $superAdmins = User::role(['super-admin'])->get();

        return $admins->merge($superAdmins)->unique('id');
    }

    private function getTeamLeads(?int $chapterId): \Illuminate\Support\Collection
    {
        $teamLeads = User::query()
            ->whereHas('teams', function ($q) use ($chapterId) {
                $q->whereIn('role_in_team', ['team-lead', 'lead-assist', 'lead_assist'])
                    ->when($chapterId, fn ($qq) => $qq->whereHas('team', fn ($qqq) => $qqq->where('chapter_id', $chapterId)));
            })
            ->get();

        return $teamLeads;
    }
}
