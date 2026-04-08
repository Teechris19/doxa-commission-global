<?php

namespace App\Listeners;

use App\Events\StudentRegisteredToAcademy;
use App\Notifications\UserRegisteredToAcademy;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyTeamLeadOfRegistration implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(StudentRegisteredToAcademy $event): void
    {
        // Get the team lead from the academy's team
        $academyTeam = $event->academy->believersAcademyTeam()
            ->with('team')
            ->first();

        if ($academyTeam && $academyTeam->team) {
            $teamLead = $academyTeam->team->leader()
                ->with('user')
                ->first();

            if ($teamLead && $teamLead->user) {
                $teamLead->user->notify(new UserRegisteredToAcademy($event->student, $event->academy));
            }
        }
    }
}
