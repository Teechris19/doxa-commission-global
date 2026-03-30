<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::prefix('admin/dashboard')
    ->middleware(['auth', 'admin'])
    ->group(function () {
        // -----------------------------------------------------------------
        //              SETTINGS SECTION
        //-------------------------------------------------------------------
        // System Settings (Super Admin Only)
        Route::middleware(['super-admin'])->group(function () {
            Volt::route('/settings', 'admin.dashboard.settings.index')->name('admin.dashboard.settings.index');
            Volt::route('/settings/landing', 'admin.dashboard.settings.landing')->name('admin.dashboard.settings.landing');
            Volt::route('/settings/global', 'admin.dashboard.settings.global')->name('admin.dashboard.settings.global');
            Volt::route('/settings/about-page', 'admin.dashboard.settings.about-page')->name('admin.dashboard.settings.about-page');
        });
        
        // Other Settings (Accessible by admins with appropriate team functions)
        volt::route('settings/appointment', 'admin.dashboard.settings.appointment')->name('admin.dashboard.settings.appointment');
        Volt::route('settings/team-functions', 'admin.dashboard.settings.team-functions')->name('admin.dashboard.settings.team-functions');
        Volt::route('prayer-request-teams', 'admin.dashboard.settings.request_teams')->name('admin.dashboard.prayer_requests.request_teams');
        Volt::route('believers-academy', 'admin.dashboard.settings.believersclass')->name('admin.settings.believersclass');
        Volt::route('settings/pastor', 'admin.dashboard.settings.pastor')->name('admin.dashboard.settings.pastor');
        // Settings - Event Teams
        Volt::route('/settings/event-teams', 'admin.dashboard.settings.event-teams')->name('admin.dashboard.settings.event-teams');
        // -----------------------------------------------------------------
        //              MEMBERS SECTION
        //-------------------------------------------------------------------
        Volt::route('/', 'admin.dashboard.dashboard')->name('admin.dashboard');
        Volt::route('members', 'admin.dashboard.members.index')
            ->middleware('team-function:members')
            ->name('admin.dashboard.members');
        Volt::route('members_create', 'admin.dashboard.members.create')
            ->middleware('team-function:members')
            ->name('admin.dashboard.members.create');
        Volt::route('members/add-to-team', 'admin.dashboard.members.add-to-team')
            ->middleware('team-function:members')
            ->name('admin.members.add-to-team');
        Volt::route('members/{member}/edit', 'admin.dashboard.members.edit')
            ->middleware('team-function:members')
            ->name('admin.dashboard.members.edit');
        Volt::route('members/{member}/edit_teams', 'admin.dashboard.members.edit-team')
            ->middleware('team-function:members')
            ->name('admin.dashboard.edit-team');
        // -----------------------------------------------------------------
        //              TEAMS SECTION
        //-------------------------------------------------------------------
        Volt::route('teams', 'admin.dashboard.teams.index')->name('admin.dashboard.teams');
        Volt::route('team_create', 'admin.dashboard.teams.create')->name('admin.dashboard.teams.create');
        Volt::route('teams/{team}/edit', 'admin.dashboard.teams.edit')->name('admin.dashboard.teams.edit');
        Volt::route('teams/edit_lead', 'admin.dashboard.teams.leader')->name('admin.dashboard.teams.edit-leader');
        // -----------------------------------------------------------------
        //              PRAYER REQUEST SECTION
        //-------------------------------------------------------------------
        Volt::route('prayer-requests', 'admin.dashboard.prayer_request.index')
            ->middleware('team-function:prayer_requests')
            ->name('admin.dashboard.prayer_requests.index');
        // -----------------------------------------------------------------
        //              TESTIMONIES SECTION
        //-------------------------------------------------------------------
        Volt::route('testimonies', 'admin.dashboard.testimonies.index')->name('admin.dashboard.testimonies.index');
        // -----------------------------------------------------------------
        //              BELIEVER'S ACADEMY
        //-------------------------------------------------------------------
        Volt::route('believers_academy', 'admin.dashboard.believers_class.academy')
            ->middleware('team-function:believers_academy')
            ->name('admin.dashboard.believers_class.academy');
        Volt::route('believers_academy/classes', 'admin.dashboard.believers_class.index')
            ->middleware('team-function:believers_academy')
            ->name('admin.dashboard.believers_class.index');
        Volt::route('believers_academy/students', 'admin.dashboard.believers_class.student-monitor')
            ->middleware('team-function:believers_academy')
            ->name('admin.dashboard.believers_class.student-monitor');
        // -----------------------------------------------------------------
        //             REPORT SECTION
        //-------------------------------------------------------------------
        Volt::route('reports', 'admin.dashboard.reports.index')
            ->middleware('team-function:reports')
            ->name('admin.dashboard.reports.index');
        Volt::route('create-report', 'admin.dashboard.reports.create-report')
            ->middleware('team-function:reports')
            ->name('admin.dashboard.reports.create-report');
        Volt::route('reports/view-report', 'admin.dashboard.reports.view-report')
            ->middleware('team-function:reports')
            ->name('admin.dashboard.reports.view-report');
        Volt::route('reports/compile-report', 'admin.dashboard.reports.compile-report')
            ->middleware('team-function:reports')
            ->name('admin.dashboard.reports.compile-report');
        Volt::route('reports/report-sent-to-hq', 'admin.dashboard.reports.report-sent-to-hq')
            ->middleware('team-function:reports')
            ->name('admin.dashboard.reports.report-sent-to-hq');

        // -----------------------------------------------------------------
        //              ANALYTICS SECTION
        //-------------------------------------------------------------------
        Volt::route('analytics', 'admin.dashboard.analytics.index')
            ->middleware('team-function:analytics')
            ->name('admin.dashboard.analytics.index');

        // -----------------------------------------------------------------
        //              ANNOUNCEMENTS
        //-------------------------------------------------------------------
        Volt::route('announcements', 'admin.dashboard.announcements.index')
            ->middleware(['auth'])
            ->name('admin.dashboard.announcements.index');

        // -----------------------------------------------------------------
        //              SPECIALIZED REPORTS
        //-------------------------------------------------------------------
        // Finance Reports
        Volt::route('finance/reports', 'admin.dashboard.finance.reports.index')
            ->middleware('team-function:reports')
            ->name('admin.dashboard.finance.reports.index');
        Volt::route('finance/reports/create', 'admin.dashboard.finance.reports.create')
            ->middleware('team-function:reports')
            ->name('admin.dashboard.finance.reports.create');
        Volt::route('finance/reports/view', 'admin.dashboard.finance.reports.view')
            ->middleware('team-function:reports')
            ->name('admin.dashboard.finance.reports.view');

        // Appointment Reports
        Volt::route('appointments/reports', 'admin.dashboard.appointments.reports.index')
            ->middleware('team-function:appointments')
            ->name('admin.dashboard.appointments.reports.index');

        // Attendance Reports
        Volt::route('attendance/reports', 'admin.dashboard.attendance.reports.index')
            ->middleware('team-function:attendance')
            ->name('admin.dashboard.attendance.reports.index');
        Volt::route('attendance/manage', 'admin.dashboard.attendance.manage')
            ->middleware('team-function:attendance')
            ->name('admin.dashboard.attendance.manage');
        Volt::route('attendance/checkin', 'admin.dashboard.attendance.checkin')
            ->middleware('team-function:attendance')
            ->name('admin.dashboard.attendance.checkin');

        // -----------------------------------------------------------------
        //              CELL GROUPS
        //-------------------------------------------------------------------
        Volt::route('cells', 'admin.dashboard.cells.index')->name('admin.dashboard.cells.index');
        Volt::route('cells/create', 'admin.dashboard.cells.create')->name('admin.dashboard.cells.create');
        Volt::route('cells/view', 'admin.dashboard.cells.view')->name('admin.dashboard.cells.view');

        // -----------------------------------------------------------------
        //              PASSWORD RESET REQUESTS
        //-------------------------------------------------------------------
        Volt::route('password-reset-requests', 'admin.dashboard.password-reset.index')->name('admin.dashboard.password-reset.index');

        // -----------------------------------------------------------------
        //              PARTNERSHIP SECTION
        //-------------------------------------------------------------------
        Volt::route('partnerships', 'admin.dashboard.partnership.index')
            ->middleware('team-function:partnerships')
            ->name('admin.dashboard.partnerships.index');
        Volt::route('partnership/intents', 'admin.dashboard.partnership.intents')
            ->middleware('team-function:partnerships')
            ->name('admin.dashboard.partnership.intents');
        Volt::route('partnership/accounts', 'admin.dashboard.partnership.accounts')
            ->middleware('team-function:partnerships')
            ->name('admin.dashboard.partnership.accounts');
        Volt::route('partnership/form-builder', 'admin.dashboard.partnership.form-builder')
            ->middleware('team-function:partnerships')
            ->name('admin.dashboard.partnership.form-builder');
        //--------------------------------------------------------------------
        //             EVENTS SECTION
        //--------------------------------------------------------------------
        Volt::route('/events', 'admin.dashboard.event.index')
            ->middleware('team-function:events')
            ->name('admin.dashboard.events.index');

        // Event Create Form
        Volt::route('/events/create', 'admin.dashboard.event.create-form')
            ->middleware('team-function:events')
            ->name('admin.dashboard.events.create');

        // Event Form Builder
        Volt::route('/events/form-builder', 'admin.dashboard.event.form-builder')
            ->middleware('team-function:events')
            ->name('admin.dashboard.event.form-builder');

        // Event Registrations
        Volt::route('/events/registrations', 'admin.dashboard.event.registrations')
            ->middleware('team-function:events')
            ->name('admin.dashboard.event.registrations');

            
        // Event Gallery
        Volt::route('/events/gallery', 'admin.dashboard.event.gallery-management')
            ->middleware('team-function:events')
            ->name('admin.dashboard.events.gallery');


        // -----------------------------------------------------------------
        //              SERMON SECTION
        //-------------------------------------------------------------------
        Volt::route('sermons', 'admin.dashboard.sermons.index')
            ->middleware('team-function:media')
            ->name('admin.dashboard.sermons.index');

        // TRANSPORT ROUTES
        Volt::route('transport', 'admin.dashboard.transport.index')
            ->middleware('team-function:transport')
            ->name('admin.dashboard.transport.index');
        Volt::route('transport/{id}', 'admin.dashboard.transport.show')
            ->middleware('team-function:transport')
            ->name('admin.dashboard.transport.show');
        Route::put('transport/{transport}/status', [\App\Http\Controllers\TransportController::class, 'updateStatus'])->name('admin.dashboard.transport.update-status');
        Route::delete('transport/{transport}', [\App\Http\Controllers\TransportController::class, 'destroy'])->name('admin.dashboard.transport.destroy');


        // Finance Routes
        Volt::route('finance', 'admin.dashboard.finance.index')
            ->middleware('team-function:reports')
            ->name('admin.dashboard.finance.index');
        Volt::route('finance/payment-details', 'admin.dashboard.finance.payment-details')
            ->middleware('team-function:reports')
            ->name('admin.dashboard.finance.payment-details');
        Volt::route('finance/givings', 'admin.dashboard.finance.givings')
            ->middleware('team-function:reports')
            ->name('admin.dashboard.finance.givings');
        Volt::route('finance/add-givings-details', 'admin.dashboard.finance.add-givings-details')
            ->middleware('team-function:reports')
            ->name('admin.dashboard.finance.add-givings-details');

       
        // Appointments Routes
        Volt::route('appointments', 'admin.dashboard.appointments.index')
            ->middleware('team-function:appointments')
            ->name('admin.dashboard.appointments.index');
        Volt::route('deleted_appointement', 'admin.dashboard.appointments.deleted_appointment')
            ->middleware('team-function:appointments')
            ->name('admin.dashboard.appointments.deleted_appointment');
        Volt::route('appointment_settings', 'admin.dashboard.appointments.settings')
            ->middleware('team-function:appointments')
            ->name('admin.dashboard.appointments.settings');

        // Resource Inventory Routes
        Volt::route('resource/inventory', 'admin.dashboard.resource.inventory.index')->name('admin.dashboard.resource.inventory.index');
        Volt::route('resource/inventory/add', 'admin.dashboard.resource.inventory.add')->name('admin.dashboard.resource.inventory.add');
        Volt::route('resource/inventory/edit/{id}', 'admin.dashboard.resource.inventory.edit')->name('admin.dashboard.resource.inventory.edit');

        // Medicals Routes
        Volt::route('medicals', 'admin.dashboard.medicals.index')
            ->middleware('team-function:reports')
            ->name('admin.dashboard.medicals.index');
        Volt::route('medicals/card', 'admin.dashboard.medicals.card')
            ->middleware('team-function:reports')
            ->name('admin.dashboard.medicals.card');
        Volt::route('medicals/card-payment', 'admin.dashboard.medicals.card-payment')
            ->middleware('team-function:reports')
            ->name('admin.dashboard.medicals.card-payment');
        Volt::route('medicals/card-record', 'admin.dashboard.medicals.card-record')
            ->middleware('team-function:reports')
            ->name('admin.dashboard.medicals.card-record');

        // Scribes Routes
        Volt::route('scribes', 'admin.dashboard.scribes.index')
            ->middleware('team-function:reports')
            ->name('admin.dashboard.scribes.index');
        Volt::route('scribes/general-report', 'admin.dashboard.scribes.general-report')
            ->middleware('team-function:reports')
            ->name('admin.dashboard.scribes.general-report');
        Volt::route('scribes/reports', 'admin.dashboard.scribes.reports')
            ->middleware('team-function:reports')
            ->name('admin.dashboard.scribes.reports');
        Volt::route('scribes/doxa-update', 'admin.dashboard.scribes.doxa-update')
            ->middleware('team-function:reports')
            ->name('admin.dashboard.scribes.doxa-update');

        // Properties Routes
        Volt::route('properties', 'admin.dashboard.properties.index')
            ->middleware('team-function:reports')
            ->name('admin.dashboard.properties.index');
        Volt::route('properties/inventory', 'admin.dashboard.properties.inventory')
            ->middleware('team-function:reports')
            ->name('admin.dashboard.properties.inventory');
        Volt::route('properties/add-inventory', 'admin.dashboard.properties.add-inventory')
            ->middleware('team-function:reports')
            ->name('admin.dashboard.properties.add-inventory');
        Volt::route('properties/edit-inventory/{id}', 'admin.dashboard.properties.edit-inventory')
            ->middleware('team-function:reports')
            ->name('admin.dashboard.properties.edit-inventory');

        // Missions Routes
        Volt::route('missions', 'admin.dashboard.missions.index')
            ->middleware('team-function:reports')
            ->name('admin.dashboard.missions.index');
        Volt::route('missions/report', 'admin.dashboard.missions.report')
            ->middleware('team-function:reports')
            ->name('admin.dashboard.missions.report');
        Volt::route('missions/new-members', 'admin.dashboard.missions.new-members')
            ->middleware('team-function:reports')
            ->name('admin.dashboard.missions.new-members');
        Volt::route('missions/out-reach-details', 'admin.dashboard.missions.out-reach-details')
            ->middleware('team-function:reports')
            ->name('admin.dashboard.missions.out-reach-details');
        Volt::route('missions/outreach-report', 'admin.dashboard.missions.outreach-report')
            ->middleware('team-function:reports')
            ->name('admin.dashboard.missions.outreach-report');

        });
