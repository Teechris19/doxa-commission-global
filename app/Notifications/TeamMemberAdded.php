<?php

namespace App\Notifications;

use App\Models\Team;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamMemberAdded extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Team $team,
        public User $member
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Team Assignment Update')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line($this->member->name . ' has been added to the ' . $this->team->name . ' team.')
            ->action('View Team', route('admin.dashboard.teams', ['chapter' => $this->team->chapter?->name]))
            ->line('Thank you for leading and supporting the team.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'team_id' => $this->team->id,
            'member_id' => $this->member->id,
            'message' => "{$this->member->name} added to {$this->team->name} team",
            'type' => 'team_member_added',
        ];
    }
}
