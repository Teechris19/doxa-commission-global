<?php

namespace App\Traits;

use App\Models\Team;
use App\Models\BelieversAcademy;
use App\Notifications\UserRegisteredToAcademy;

trait NotifyAcademyTeamLead
{
    /**
     * Notify the team lead when a student registers for an academy.
     * 
     * @param \App\Models\User $student
     * @param \App\Models\BelieversAcademy $academy
     */
    public function notifyTeamLeadOfRegistration($student, BelieversAcademy $academy)
    {
        // Get the team lead from the academy's team
        $academyTeam = $academy->believersAcademyTeam()
            ->with('team')
            ->first();

        if ($academyTeam && $academyTeam->team) {
            $teamLead = $academyTeam->team->leader()
                ->with('user')
                ->first();

            if ($teamLead && $teamLead->user) {
                $teamLead->user->notify(new UserRegisteredToAcademy($student, $academy));
            }
        }
    }

    /**
     * Notify the team lead about class completion status.
     * 
     * @param \App\Models\User $student
     * @param \App\Models\AcademyClases $class
     * @param string $completionStatus
     */
    public function notifyTeamLeadOfClassCompletion($student, $class, $completionStatus = 'completed')
    {
        // Get the team lead from the class's academy
        $academy = $class->academy;

        $academyTeam = $academy->believersAcademyTeam()
            ->with('team')
            ->first();

        if ($academyTeam && $academyTeam->team) {
            $teamLead = $academyTeam->team->leader()
                ->with('user')
                ->first();

            if ($teamLead && $teamLead->user) {
                $teamLead->user->notify(new \App\Notifications\ClassCompletedByStudent($student, $class, $completionStatus));
            }
        }
    }
}
