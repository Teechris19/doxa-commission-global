<?php

use App\Models\BroadcastAnnouncement;
use App\Models\Chapter;
use App\Models\Team;
use App\Models\TeamUser;
use App\Models\User;
use App\Notifications\BroadcastAnnouncementNotification;
use App\Services\SchedulerTasks;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super-admin']);
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'team-lead']);

    $this->chapter = Chapter::factory()->create();
    $this->anotherChapter = Chapter::factory()->create();
    
    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole('super-admin');
    
    $this->admin = User::factory()->create(['chapter_id' => $this->chapter->id]);
    $this->admin->assignRole('admin');
    
    $this->anotherAdmin = User::factory()->create(['chapter_id' => $this->anotherChapter->id]);
    $this->anotherAdmin->assignRole('admin');
    
    $this->team = Team::factory()->create(['chapter_id' => $this->chapter->id]);
    $this->teamLead = User::factory()->create(['chapter_id' => $this->chapter->id]);
    TeamUser::create([
        'user_id' => $this->teamLead->id,
        'team_id' => $this->team->id,
        'chapter_id' => $this->chapter->id,
        'role_in_team' => 'team-lead',
    ]);
    
    $this->regularUser = User::factory()->create(['chapter_id' => $this->chapter->id]);
    $this->anotherChapterUser = User::factory()->create(['chapter_id' => $this->anotherChapter->id]);
});

    describe('BroadcastAnnouncement Model', function () {
        test('can create announcement with all fields', function () {
            $announcement = BroadcastAnnouncement::create([
                'title' => 'Test Announcement',
                'message' => 'This is a test message',
                'status' => 'draft',
                'channel' => 'mail_database',
                'target_type' => 'both',
                'target_audience' => 'all_users',
                'chapter_id' => $this->chapter->id,
                'creator_type' => 'admin',
                'created_by' => $this->admin->id,
            ]);

            expect($announcement->id)->toBeInt();
            expect($announcement->title)->toBe('Test Announcement');
            expect($announcement->target_audience)->toBe('all_users');
        });

        test('belongs to chapter', function () {
            $announcement = BroadcastAnnouncement::create([
                'title' => 'Test',
                'message' => 'Test message',
                'status' => 'draft',
                'channel' => 'database',
                'target_audience' => 'all_users',
                'chapter_id' => $this->chapter->id,
                'creator_type' => 'admin',
                'created_by' => $this->admin->id,
            ]);

            expect($announcement->chapter->id)->toBe($this->chapter->id);
        });

        test('scope for all users returns correct announcements', function () {
            BroadcastAnnouncement::create([
                'title' => 'For All Users',
                'message' => 'Test',
                'status' => 'sent',
                'channel' => 'database',
                'target_audience' => 'all_users',
                'chapter_id' => $this->chapter->id,
            ]);

            BroadcastAnnouncement::create([
                'title' => 'For Admins Only',
                'message' => 'Test',
                'status' => 'sent',
                'channel' => 'database',
                'target_audience' => 'admins',
                'chapter_id' => $this->chapter->id,
            ]);

            $allUsersAnnouncements = BroadcastAnnouncement::forAllUsers()->get();
            expect($allUsersAnnouncements->count())->toBe(1);
            expect($allUsersAnnouncements->first()->title)->toBe('For All Users');
        });

        test('scope for admins returns correct announcements', function () {
            BroadcastAnnouncement::create([
                'title' => 'For All Users',
                'message' => 'Test',
                'status' => 'sent',
                'channel' => 'database',
                'target_audience' => 'all_users',
                'chapter_id' => $this->chapter->id,
            ]);

            BroadcastAnnouncement::create([
                'title' => 'For Admins Only',
                'message' => 'Test',
                'status' => 'sent',
                'channel' => 'database',
                'target_audience' => 'admins',
                'chapter_id' => $this->chapter->id,
            ]);

            $adminAnnouncements = BroadcastAnnouncement::forAdmins()->get();
            expect($adminAnnouncements->count())->toBe(1);
            expect($adminAnnouncements->first()->title)->toBe('For Admins Only');
        });

        test('scope for team leads returns correct announcements', function () {
            BroadcastAnnouncement::create([
                'title' => 'For All Users',
                'message' => 'Test',
                'status' => 'sent',
                'channel' => 'database',
                'target_audience' => 'all_users',
                'chapter_id' => $this->chapter->id,
            ]);

            BroadcastAnnouncement::create([
                'title' => 'For Team Leads',
                'message' => 'Test',
                'status' => 'sent',
                'channel' => 'database',
                'target_audience' => 'team_leads',
                'chapter_id' => $this->chapter->id,
            ]);

            $teamLeadAnnouncements = BroadcastAnnouncement::forTeamLeads()->get();
            expect($teamLeadAnnouncements->count())->toBe(1);
            expect($teamLeadAnnouncements->first()->title)->toBe('For Team Leads');
        });
    });

    describe('BroadcastAnnouncementNotification', function () {
        test('sends via mail and database when channel is mail_database', function () {
            $announcement = BroadcastAnnouncement::create([
                'title' => 'Test',
                'message' => 'Test message',
                'status' => 'draft',
                'channel' => 'mail_database',
                'target_audience' => 'all_users',
            ]);

            $notification = new BroadcastAnnouncementNotification($announcement);
            $via = $notification->via($this->regularUser);

            expect($via)->toContain('mail');
            expect($via)->toContain('database');
        });

        test('sends via mail only when channel is mail', function () {
            $announcement = BroadcastAnnouncement::create([
                'title' => 'Test',
                'message' => 'Test message',
                'status' => 'draft',
                'channel' => 'mail',
                'target_audience' => 'all_users',
            ]);

            $notification = new BroadcastAnnouncementNotification($announcement);
            $via = $notification->via($this->regularUser);

            expect($via)->toBe(['mail']);
        });

        test('sends via database only when channel is database', function () {
            $announcement = BroadcastAnnouncement::create([
                'title' => 'Test',
                'message' => 'Test message',
                'status' => 'draft',
                'channel' => 'database',
                'target_audience' => 'all_users',
            ]);

            $notification = new BroadcastAnnouncementNotification($announcement);
            $via = $notification->via($this->regularUser);

            expect($via)->toBe(['database']);
        });

        test('email includes chapter name prefix when chapter is set', function () {
            $announcement = BroadcastAnnouncement::create([
                'title' => 'Test Announcement',
                'message' => 'Test message',
                'status' => 'draft',
                'channel' => 'mail',
                'target_audience' => 'all_users',
                'chapter_id' => $this->chapter->id,
            ]);

            $notification = new BroadcastAnnouncementNotification($announcement);
            $mail = $notification->toMail($this->regularUser);

            expect($mail->subject)->toContain($this->chapter->name);
        });

        test('email does not include chapter prefix when chapter is null', function () {
            $announcement = BroadcastAnnouncement::create([
                'title' => 'Test Announcement',
                'message' => 'Test message',
                'status' => 'draft',
                'channel' => 'mail',
                'target_audience' => 'all_users',
            ]);

            $notification = new BroadcastAnnouncementNotification($announcement);
            $mail = $notification->toMail($this->regularUser);

            expect($mail->subject)->toBe('Test Announcement');
        });

        test('database notification includes chapter name', function () {
            $announcement = BroadcastAnnouncement::create([
                'title' => 'Test Announcement',
                'message' => 'Test message',
                'status' => 'draft',
                'channel' => 'database',
                'target_audience' => 'all_users',
                'chapter_id' => $this->chapter->id,
            ]);

            $notification = new BroadcastAnnouncementNotification($announcement);
            $data = $notification->toDatabase($this->regularUser);

            expect($data['chapter_name'])->toBe($this->chapter->name);
            expect($data['message'])->toContain($this->chapter->name);
        });
    });

    describe('SchedulerTasks - Recipient Selection', function () {
        test('sends to all users when target_audience is all_users', function () {
            Notification::fake();

            $announcement = BroadcastAnnouncement::create([
                'title' => 'Test for All Users',
                'message' => 'Test message',
                'status' => 'scheduled',
                'send_at' => now()->subMinute(),
                'channel' => 'database',
                'target_audience' => 'all_users',
                'chapter_id' => $this->chapter->id,
            ]);

            $scheduler = new SchedulerTasks();
            $scheduler->runBroadcastAnnouncements();

            Notification::assertSentTo(
                $this->regularUser,
                BroadcastAnnouncementNotification::class
            );
            Notification::assertSentTo(
                $this->admin,
                BroadcastAnnouncementNotification::class
            );
        });

        test('sends to admins only when target_audience is admins', function () {
            Notification::fake();

            $announcement = BroadcastAnnouncement::create([
                'title' => 'Test for Admins',
                'message' => 'Test message',
                'status' => 'scheduled',
                'send_at' => now()->subMinute(),
                'channel' => 'database',
                'target_audience' => 'admins',
                'chapter_id' => $this->chapter->id,
            ]);

            $scheduler = new SchedulerTasks();
            $scheduler->runBroadcastAnnouncements();

            Notification::assertSentTo(
                $this->admin,
                BroadcastAnnouncementNotification::class
            );
            Notification::assertSentTo(
                $this->superAdmin,
                BroadcastAnnouncementNotification::class
            );
            Notification::assertNotSentTo(
                $this->regularUser,
                BroadcastAnnouncementNotification::class
            );
        });

        test('sends to team leads only when target_audience is team_leads', function () {
            Notification::fake();

            $announcement = BroadcastAnnouncement::create([
                'title' => 'Test for Team Leads',
                'message' => 'Test message',
                'status' => 'scheduled',
                'send_at' => now()->subMinute(),
                'channel' => 'database',
                'target_audience' => 'team_leads',
                'chapter_id' => $this->chapter->id,
            ]);

            $scheduler = new SchedulerTasks();
            $scheduler->runBroadcastAnnouncements();

            Notification::assertSentTo(
                $this->teamLead,
                BroadcastAnnouncementNotification::class
            );
            Notification::assertNotSentTo(
                $this->regularUser,
                BroadcastAnnouncementNotification::class
            );
            Notification::assertNotSentTo(
                $this->admin,
                BroadcastAnnouncementNotification::class
            );
        });

        test('does not send to users from other chapter when chapter is specified', function () {
            Notification::fake();

            $announcement = BroadcastAnnouncement::create([
                'title' => 'Test for Chapter Users',
                'message' => 'Test message',
                'status' => 'scheduled',
                'send_at' => now()->subMinute(),
                'channel' => 'database',
                'target_audience' => 'all_users',
                'chapter_id' => $this->chapter->id,
            ]);

            $scheduler = new SchedulerTasks();
            $scheduler->runBroadcastAnnouncements();

            Notification::assertSentTo(
                $this->regularUser,
                BroadcastAnnouncementNotification::class
            );
            Notification::assertNotSentTo(
                $this->anotherChapterUser,
                BroadcastAnnouncementNotification::class
            );
        });

        test('sends to super admin regardless of chapter when target is admins', function () {
            Notification::fake();

            $announcement = BroadcastAnnouncement::create([
                'title' => 'Test for Admins',
                'message' => 'Test message',
                'status' => 'scheduled',
                'send_at' => now()->subMinute(),
                'channel' => 'database',
                'target_audience' => 'admins',
                'chapter_id' => $this->chapter->id,
            ]);

            $scheduler = new SchedulerTasks();
            $scheduler->runBroadcastAnnouncements();

            Notification::assertSentTo(
                $this->superAdmin,
                BroadcastAnnouncementNotification::class
            );
        });

        test('updates announcement status to sent after sending', function () {
            Notification::fake();

            $announcement = BroadcastAnnouncement::create([
                'title' => 'Test',
                'message' => 'Test',
                'status' => 'scheduled',
                'send_at' => now()->subMinute(),
                'channel' => 'database',
                'target_audience' => 'all_users',
                'chapter_id' => $this->chapter->id,
            ]);

            $scheduler = new SchedulerTasks();
            $scheduler->runBroadcastAnnouncements();

            $announcement->refresh();
            expect($announcement->status)->toBe('sent');
            expect($announcement->sent_at)->not->toBeNull();
        });

        test('does not process already sent announcements', function () {
            Notification::fake();

            BroadcastAnnouncement::create([
                'title' => 'Already Sent',
                'message' => 'Test',
                'status' => 'sent',
                'sent_at' => now()->subHour(),
                'channel' => 'database',
                'target_audience' => 'all_users',
                'chapter_id' => $this->chapter->id,
            ]);

            $scheduler = new SchedulerTasks();
            $sent = $scheduler->runBroadcastAnnouncements();

            expect($sent)->toBe(0);
            Notification::assertNothingSent();
        });

        test('does not process future scheduled announcements', function () {
            Notification::fake();

            BroadcastAnnouncement::create([
                'title' => 'Future',
                'message' => 'Test',
                'status' => 'scheduled',
                'send_at' => now()->addHour(),
                'channel' => 'database',
                'target_audience' => 'all_users',
                'chapter_id' => $this->chapter->id,
            ]);

            $scheduler = new SchedulerTasks();
            $sent = $scheduler->runBroadcastAnnouncements();

            expect($sent)->toBe(0);
            Notification::assertNothingSent();
        });
    });

    describe('Admin Announcement Form', function () {
        test('admin can only select their own chapter', function () {
            $this->actingAs($this->admin);

            $response = $this->get(route('admin.dashboard.announcements.index'));
            $response->assertStatus(200);

            $response->assertSee('All Users (Branch Members)');
            $response->assertSee('All Admins');
            $response->assertSee('Team Leads (Branch)');
        });

        test('super admin can select any chapter', function () {
            $this->actingAs($this->superAdmin);

            $response = $this->get(route('admin.dashboard.announcements.index'));
            $response->assertStatus(200);
        });
    });
};