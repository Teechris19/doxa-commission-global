@php
    $user = auth()->user();
    $leadersTeam = $user->teams->firstWhere(fn($team) => in_array($team->pivot->role_in_team, ['team-lead', 'lead-assist', 'lead_assist']));
    $isSuperAdmin = $user->hasRole('super-admin');
    $isAdmin = $user->hasRole('admin');
    $isTeamLeader = $user->hasRole(['team-lead', 'lead-assist', 'lead_assist']);

    $chapterId = $isSuperAdmin
        ? \App\Models\Chapter::where('name', request('chapter'))->value('id')
        : $user->chapter_id;

    if (!$isSuperAdmin) {
        $relations = [
            'appointment_teams' => \App\Models\AppointmentTeams::class,
            'prayerRequestTeams' => \App\Models\PrayerRequestTeam::class,
            'believersAcademyTeam' => \App\Models\BelieversAcademyTeams::class,
            'eventTeams' => \App\Models\EventTeam::class,
            'attendanceTeams' => \App\Models\AttendanceTeams::class,
        ];

        foreach ($relations as $var => $model) {
            $$var = $model::where('chapter_id', $chapterId)->pluck('team_id')->all();
        }

        // Check if user's teams have the partnerships function enabled
        $userTeamIds = $user->teams()
            ->where('teams.chapter_id', $chapterId)
            ->pluck('teams.id');

        $isPartnershipTeamMember = \App\Models\TeamFunction::whereIn('team_id', $userTeamIds)
            ->get()
            ->contains(fn($tf) => !empty($tf->function['partnerships']));
    } else {
        $appointment_teams = $prayerRequestTeams = $believersAcademyTeam = $eventTeams = [];
        $isPartnershipTeamMember = false;
    }

    $teamFunctions = null;
    if ($leadersTeam && !$isAdmin && !$isSuperAdmin) {
        $teamFunctions = \App\Models\TeamFunction::where('team_id', $leadersTeam->id)->first();
    }

    $hasFunctionControl = $teamFunctions !== null;
    $functionMap = $teamFunctions?->function ?? [];
    $relationKeys = ['appointments', 'prayer_requests', 'believers_academy', 'events'];
    $forceReports = $isTeamLeader;

    $can = function (string $key) use ($isAdmin, $isSuperAdmin, $isTeamLeader, $hasFunctionControl, $functionMap, $relationKeys) {
        if ($isAdmin || $isSuperAdmin) {
            return true;
        }
        if (!$isTeamLeader) {
            return true;
        }
        if (in_array($key, $relationKeys, true)) {
            return true;
        }
        if (!$hasFunctionControl) {
            return false;
        }
        return (bool) ($functionMap[$key] ?? false);
    };
@endphp

{{-- Cells Management --}}
@if($can('cells') || ($isTeamLeader && $leadersTeam && str_contains(strtolower($leadersTeam->name ?? ''), 'cell')))
    <flux:navlist.group expandable heading="Cells">
        <flux:navlist.item icon="user-group" :href="route('admin.dashboard.cells.index', request()->query())" wire:navigate
            :active="request()->routeIs('admin.dashboard.cells.index') ? 'true' : 'false'">
            All Cells
        </flux:navlist.item>
        <flux:navlist.item icon="plus-circle" :href="route('admin.dashboard.cells.create', request()->query())" wire:navigate
            :active="request()->routeIs('admin.dashboard.cells.create') ? 'true' : 'false'">
            Create Cell
        </flux:navlist.item>
        <flux:navlist.item icon="user-circle" :href="route('admin.dashboard.cells.leaders', request()->query())" wire:navigate
            :active="request()->routeIs('admin.dashboard.cells.leaders') ? 'true' : 'false'">
            Cell Leaders
        </flux:navlist.item>
        <flux:navlist.item icon="cog-6-tooth" :href="route('admin.dashboard.cells.settings', request()->query())" wire:navigate
            :active="request()->routeIs('admin.dashboard.cells.settings') ? 'true' : 'false'">
            Cell Settings
        </flux:navlist.item>
    </flux:navlist.group>
@endif

@if($can('transport') && ($isSuperAdmin || $isAdmin || ($leadersTeam && in_array($leadersTeam->id, $appointment_teams))))
    <flux:navlist.group expandable heading="Transportation">
        <flux:navlist.item icon="truck" :href="route('admin.dashboard.transport.index', request()->query())" wire:navigate
            :active="request()->routeIs('admin.dashboard.transport.index') ? 'true' : 'false'">
            All Requests
        </flux:navlist.item>
    </flux:navlist.group>
@endif

@if ($can('appointments') && ($isSuperAdmin || $isAdmin || ($leadersTeam && in_array($leadersTeam->id, $appointment_teams))))
    <flux:navlist.group expandable heading="Appointments">
        <flux:navlist.item icon="calendar-days" :href="route('admin.dashboard.appointments.index', request()->query())" wire:navigate
            :active="request()->routeIs('admin.dashboard.appointments.index') ? 'true' : 'false'">
            All Appointments
        </flux:navlist.item>
        <flux:navlist.item icon="cog-6-tooth" :href="route('admin.dashboard.appointments.settings', request()->query())" wire:navigate>
            Appointment Settings
        </flux:navlist.item>
        <flux:navlist.item icon="archive-box" :href="route('admin.dashboard.appointments.deleted_appointment', request()->query())"
            wire:navigate
            :active="request()->routeIs('admin.dashboard.appointments.deleted_appointment') ? 'true' : 'false'">
            Deleted Appointment
        </flux:navlist.item>

    </flux:navlist.group>
@endif
@if ($can('prayer_requests') && ($isSuperAdmin || $isAdmin || ($leadersTeam && in_array($leadersTeam->id, $prayerRequestTeams))))
    <flux:navlist.group expandable heading="Prayer Requests">
        <flux:navlist.item icon="heart" :href="route('admin.dashboard.prayer_requests.index', request()->query())" wire:navigate>
            View Prayer Request
        </flux:navlist.item>
    </flux:navlist.group>
@endif

{{-- Testimonies --}}
@if ($isSuperAdmin || $isAdmin)
    <flux:navlist.group expandable heading="Testimonies">
        <flux:navlist.item icon="chat-bubble-left-ellipsis" :href="route('admin.dashboard.testimonies.index', request()->query())" wire:navigate
            :active="request()->routeIs('admin.dashboard.testimonies.index') ? 'true' : 'false'">
            View Testimonies
        </flux:navlist.item>
    </flux:navlist.group>
@endif
@if($can('team_settings'))
    <flux:navlist.group expandable heading="Team Setting">
        <flux:navlist.item icon="users" :href="route('admin.dashboard.settings.team-functions', request()->query())" wire:navigate
            :active="request()->routeIs('admin.dashboard.settings.team-functions') ? 'true' : 'false'">
            Team Functions
        </flux:navlist.item>
    </flux:navlist.group>
@endif

{{-- System Settings - Super Admin Only --}}
@if($isSuperAdmin)
    <flux:navlist.group expandable heading="System Settings">
        <flux:navlist.item icon="cog-6-tooth" :href="route('admin.dashboard.settings.index', request()->query())" wire:navigate
            :active="request()->routeIs('admin.dashboard.settings.index') ? 'true' : 'false'">
            Global & Landing
        </flux:navlist.item>
    </flux:navlist.group>
@endif

{{-- Appearance Settings - All Users --}}
<flux:navlist.group expandable heading="Appearance">
    <flux:navlist.item icon="paint-brush" :href="route('settings.appearance', request()->query())" wire:navigate
        :active="request()->routeIs('settings.appearance') ? 'true' : 'false'">
        Theme Preference
    </flux:navlist.item>
</flux:navlist.group>
@if($can('partnerships') && ($isSuperAdmin || $isAdmin || $isPartnershipTeamMember))
    <flux:navlist.group expandable heading="Partnerships">
        <flux:navlist.item icon="hand-raised" :href="route('admin.dashboard.partnership.intents', request()->query())" wire:navigate
            :active="request()->routeIs('admin.dashboard.partnership.intents') ? 'true' : 'false'">
            Intent Management
        </flux:navlist.item>
        <flux:navlist.item icon="banknotes" :href="route('admin.dashboard.partnership.accounts', request()->query())" wire:navigate
            :active="request()->routeIs('admin.dashboard.partnership.accounts') ? 'true' : 'false'">
            Accounts
        </flux:navlist.item>
        <flux:navlist.item icon="rectangle-group" :href="route('admin.dashboard.partnership.form-builder', request()->query())" wire:navigate
            :active="request()->routeIs('admin.dashboard.partnership.form-builder') ? 'true' : 'false'">
            Form Builder
        </flux:navlist.item>
    </flux:navlist.group>
@endif
@if ($can('believers_academy') && ($isSuperAdmin || $isAdmin || ($leadersTeam && in_array($leadersTeam->id, $believersAcademyTeam))))
    <flux:navlist.group expandable heading="Believer's Academy">
        <flux:navlist.item icon="academic-cap" :href="route('admin.dashboard.believers_class.academy', request()->query())" wire:navigate
            :active="request()->routeIs('admin.dashboard.believers_class.academy') ? 'true' : 'false'">
            Academy
        </flux:navlist.item>
        <flux:navlist.item icon="book-open" :href="route('admin.dashboard.believers_class.index', request()->query())" wire:navigate
            :active="request()->routeIs('admin.dashboard.believers_class.index') ? 'true' : 'false'">
            Believer's Classes
        </flux:navlist.item>
        <flux:navlist.item icon="users" :href="route('admin.dashboard.believers_class.student-monitor', request()->query())" wire:navigate
            :active="request()->routeIs('admin.dashboard.believers_class.student-monitor') ? 'true' : 'false'">
            Students Monitor
        </flux:navlist.item>
    </flux:navlist.group>
@endif

@if($forceReports || $can('reports'))
<flux:navlist.group expandable heading="Report">

    <flux:navlist.item icon="document-text" :href="route('admin.dashboard.reports.index', request()->query())" wire:navigate
        :active="request()->routeIs('admin.dashboard.reports.index') ? 'true' : 'false'">
        Report
    </flux:navlist.item>
    <flux:navlist.item icon="pencil-square" :href="route('admin.dashboard.reports.create-report', request()->query())" wire:navigate
        :active="request()->routeIs('admin.dashboard.reports.create-report') ? 'true' : 'false'">
        Create Report
    </flux:navlist.item>

</flux:navlist.group>
@endif

@if($can('analytics'))
<flux:navlist.group expandable heading="Analytics">

    <flux:navlist.item icon="chart-bar" :href="route('admin.dashboard.analytics.index', request()->query())" wire:navigate
        :active="request()->routeIs('admin.dashboard.analytics.index') ? 'true' : 'false'">
        Analytics Dashboard
    </flux:navlist.item>

</flux:navlist.group>
@endif

{{-- Attendance Management System - Only for assigned teams --}}
@if($isSuperAdmin || $isAdmin || ($leadersTeam && in_array($leadersTeam->id, $attendanceTeams ?? [])))
<flux:navlist.group expandable heading="Attendance">
    <flux:navlist.item icon="calendar" :href="route('admin.dashboard.attendance.manage', request()->query())" wire:navigate
        :active="request()->routeIs('admin.dashboard.attendance.manage') ? 'true' : 'false'">
        Manage Attendance
    </flux:navlist.item>
    <flux:navlist.item icon="check-circle" :href="route('admin.dashboard.attendance.checkin', request()->query())" wire:navigate
        :active="request()->routeIs('admin.dashboard.attendance.checkin') ? 'true' : 'false'">
        Check-in
    </flux:navlist.item>
    <flux:navlist.item icon="document-chart-bar" :href="route('admin.dashboard.attendance.reports', request()->query())" wire:navigate
        :active="request()->routeIs('admin.dashboard.attendance.reports') ? 'true' : 'false'">
        Reports
    </flux:navlist.item>
    <flux:navlist.item icon="users" :href="route('admin.dashboard.attendance.members', request()->query())" wire:navigate
        :active="request()->routeIs('admin.dashboard.attendance.members') ? 'true' : 'false'">
        Team Members
    </flux:navlist.item>
</flux:navlist.group>
@endif

{{-- Subunits Management - Team Lead Only (Separate from Attendance) --}}
@role(['team-lead'])
<flux:navlist.group expandable heading="Subunits">
    <flux:navlist.item icon="rectangle-group" :href="route('admin.dashboard.subunits.index', request()->query())" wire:navigate
        :active="request()->routeIs('admin.dashboard.subunits.index') ? 'true' : 'false'">
        Manage Subunits
    </flux:navlist.item>
</flux:navlist.group>
@endrole

@if ($can('events') && ($isSuperAdmin || $isAdmin || ($leadersTeam && in_array($leadersTeam->id, $eventTeams))))
    <flux:navlist.group expandable heading="Events">
        <flux:navlist.item icon="calendar-days" :href="route('admin.dashboard.events.index', request()->query())" wire:navigate
            :active="request()->routeIs('admin.dashboard.events.index') ? 'true' : 'false'">
            All Events
        </flux:navlist.item>
        <flux:navlist.item icon="plus-circle" :href="route('admin.dashboard.events.create', request()->query())" wire:navigate
            :active="request()->routeIs('admin.dashboard.events.create') ? 'true' : 'false'">
            Create Event
        </flux:navlist.item>

    </flux:navlist.group>
@endif

@if($can('media'))
    <flux:navlist.group expandable heading="Media">
        <flux:navlist.item icon="play-circle" :href="route('admin.dashboard.sermons.index', request()->query())" wire:navigate
            :active="request()->routeIs('admin.dashboard.sermons.index') ? 'true' : 'false'">
            Sermons
        </flux:navlist.item>
    </flux:navlist.group>
@endif

@role(['admin', 'super-admin'])
<flux:navlist.group expandable heading="Announcements">
    <flux:navlist.item icon="megaphone" :href="route('admin.dashboard.announcements.index', request()->query())" wire:navigate
        :active="request()->routeIs('admin.dashboard.announcements.index') ? 'true' : 'false'">
        Broadcasts
    </flux:navlist.item>
</flux:navlist.group>
@endrole
