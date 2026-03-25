<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\Team;
use App\Models\User;
use App\Models\Profile;
use App\Models\Unit;
use App\Models\TeamUser;
use App\Models\Media;
use App\Models\ChapterSetting;
use App\Models\GlobalSetting;
use App\Models\Report;
use App\Models\Appointment;
use App\Models\AppointmentReport;
use App\Models\Finance;
use App\Models\FinanceReport;
use App\Models\Attendance;
use App\Models\AttendanceReport;
use App\Models\Minute;
use App\Models\MinuteReport;
use App\Models\Announcement;
use App\Models\AnnouncementReport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Faker\Factory as Faker;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // -------------------------------
        // Roles Seeding
        // -------------------------------
        $roles = [
            'super-admin',
            'admin',
            'team-lead',
            'lead_assist',
            'unit_head',
            'member',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        // -------------------------------
        // Super Admin User
        // -------------------------------
        $superAdmin = User::firstOrCreate(
            ['email' => 'ilem@gmail.com'],
            [
                'name' => 'Ilem',
                'password' => Hash::make('password'),
                'chapter_id' => null,
            ]
        );
        $superAdmin->assignRole('super-admin');

        // Profile for Super Admin
        Profile::create([
            'user_id' => $superAdmin->id,
            'chapter_id' => null,
            'first_name' => 'Ilem',
            'last_name' => 'Admin',
            'gender' => 'male',
            'dob' => $faker->date('Y-m-d', '1990-01-01'),
            'phone' => $faker->phoneNumber(),
            'email' => 'ilem@gmail.com',
            'address' => $faker->streetAddress(),
            'city' => $faker->city(),
            'state' => $faker->state(),
            'country' => $faker->country(),
            'marital_status' => 'single',
            'occupation' => 'Administrator',
            'baptism_status' => 'baptized',
            'membership_date' => $faker->date('Y-m-d', '2018-01-01'),
        ]);

        // -------------------------------
        // Chapters (3 records)
        // -------------------------------
        $chapters = [
            ['id' => 1, 'name' => 'Calabar Branch', 'data' => json_encode(['location' => 'Calabar', 'contact_email' => $faker->safeEmail, 'contact_phone' => $faker->phoneNumber])],
            ['id' => 2, 'name' => 'Lagos', 'data' => json_encode(['location' => 'Lagos', 'contact_email' => $faker->safeEmail, 'contact_phone' => $faker->phoneNumber])],
            ['id' => 3, 'name' => 'DOXA CITY HEADQUARTERS', 'data' => json_encode(['location' => 'HQ', 'contact_email' => $faker->safeEmail, 'contact_phone' => $faker->phoneNumber])],
        ];

        $chapterIds = [];
        foreach ($chapters as $chapterData) {
            $chapter = Chapter::firstOrCreate(
                ['name' => $chapterData['name']],
                ['data' => $chapterData['data']]
            );
            $chapterIds[] = $chapter->id;

            // Chapter Settings (1 per chapter)
            ChapterSetting::create([
                'chapter_id' => $chapter->id,
                'name' => $chapter->name,
                'tagline' => $faker->sentence(4),
                'logo' => $faker->imageUrl(200, 200),
                'banner_image' => $faker->imageUrl(800, 400),
                'address' => $faker->streetAddress(),
                'city' => $faker->city(),
                'state' => $faker->state(),
                'country' => $faker->country(),
                'phone' => $faker->phoneNumber(),
                'email' => $faker->safeEmail(),
                'map_location' => $faker->url(),
                'service_times' => json_encode([['day' => 'Sunday', 'time' => '9AM'], ['day' => 'Wednesday', 'time' => '6PM']]),
                'social_links' => json_encode(['facebook' => $faker->url(), 'youtube' => $faker->url()]),
            ]);

            // Chapter Admin
            $chapterAdmin = User::firstOrCreate(
                ['email' => $faker->unique()->safeEmail],
                [
                    'name' => $faker->name(),
                    'password' => Hash::make('password'),
                    'chapter_id' => $chapter->id,
                ]
            );
            $chapterAdmin->assignRole('admin');

            // Profile for Chapter Admin
            Profile::create([
                'user_id' => $chapterAdmin->id,
                'chapter_id' => $chapter->id,
                'first_name' => $faker->firstName(),
                'last_name' => $faker->lastName(),
                'gender' => $faker->randomElement(['male', 'female', 'other']),
                'dob' => $faker->date('Y-m-d', '1995-01-01'),
                'phone' => $faker->phoneNumber(),
                'email' => $chapterAdmin->email,
                'address' => $faker->streetAddress(),
                'city' => $faker->city(),
                'state' => $faker->state(),
                'country' => $faker->country(),
                'marital_status' => $faker->randomElement(['single', 'married', 'widowed']),
                'occupation' => $faker->jobTitle(),
                'baptism_status' => $faker->randomElement(['baptized', 'not baptized']),
                'membership_date' => $faker->date('Y-m-d', '2020-01-01'),
            ]);
        }

        // -------------------------------
        // Global Settings (single record)
        // -------------------------------
        $this->call(FooterSettingsSeeder::class);
        $this->call(PartnershipFormFieldSeeder::class);
        $this->call(PartnershipCategorySeeder::class);

        // -------------------------------
        // Users (at least 30 per chapter)
        // -------------------------------
        foreach ($chapterIds as $chapterId) {
            for ($i = 0; $i < 30; $i++) {
                $user = User::firstOrCreate(
                    ['email' => $faker->unique()->safeEmail],
                    [
                        'name' => $faker->name(),
                        'password' => Hash::make('password'),
                        'chapter_id' => $chapterId,
                    ]
                );
                $user->assignRole('member');

                // Profile for User
                Profile::create([
                    'user_id' => $user->id,
                    'chapter_id' => $chapterId,
                    'first_name' => $faker->firstName(),
                    'last_name' => $faker->lastName(),
                    'gender' => $faker->randomElement(['male', 'female', 'other']),
                    'dob' => $faker->date('Y-m-d', '1995-01-01'),
                    'phone' => $faker->phoneNumber(),
                    'email' => $user->email,
                    'address' => $faker->streetAddress(),
                    'city' => $faker->city(),
                    'state' => $faker->state(),
                    'country' => $faker->country(),
                    'marital_status' => $faker->randomElement(['single', 'married', 'widowed']),
                    'occupation' => $faker->jobTitle(),
                    'baptism_status' => $faker->randomElement(['baptized', 'not baptized']),
                    'membership_date' => $faker->date('Y-m-d', '2020-01-01'),
                ]);
            }
        }

        // -------------------------------
        // Teams (at least 10 per chapter)
        // -------------------------------
        $baseTeams = [
            ['name' => 'COHTECH APOSTOLIC CENTER', 'short' => 'COHTECH', 'banner' => 'teams/cohtech.png', 'has_team_lead' => true, 'type' => 'apostolic'],
            ['name' => 'UNICROSS APOSTOLIC CENTER', 'short' => 'UNICROSS', 'banner' => 'teams/unicross.png', 'has_team_lead' => true, 'type' => 'apostolic'],
            ['name' => 'DOXA MUSIC MINISTRY', 'short' => 'MUSIC', 'banner' => 'teams/music.png', 'has_team_lead' => true, 'type' => 'music'],
            ['name' => 'PROTOCOL', 'short' => 'PROTO', 'banner' => 'teams/protocol.png', 'has_team_lead' => true, 'type' => 'protocol'],
            ['name' => 'SCRIBE', 'short' => 'SCRIBE', 'banner' => 'teams/scribe.png', 'has_team_lead' => true, 'type' => 'scribe'],
            ['name' => 'MISSIONS', 'short' => 'MISSION', 'banner' => 'teams/missions.png', 'has_team_lead' => true, 'type' => 'missions'],
            ['name' => 'USHERING', 'short' => 'USH', 'banner' => 'teams/ushering.png', 'has_team_lead' => true, 'type' => 'ushering'],
            ['name' => 'MEDIA', 'short' => 'MEDIA', 'banner' => 'teams/media.png', 'has_team_lead' => true, 'type' => 'media'],
            ['name' => 'COUNSELING', 'short' => 'COUNSEL', 'banner' => 'teams/counseling.png', 'has_team_lead' => true, 'type' => 'counseling'],
            ['name' => 'TECHNICAL', 'short' => 'TECH', 'banner' => 'teams/technical.png', 'has_team_lead' => true, 'type' => 'technical'],
        ];

        foreach ($chapterIds as $chapterId) {
            $teamCount = 0;
            foreach ($baseTeams as $teamData) {
                $team = Team::updateOrCreate(
                    ['name' => $teamData['name'], 'chapter_id' => $chapterId],
                    [
                        'short' => $teamData['short'],
                        'banner' => $teamData['banner'],
                        'has_team_lead' => $teamData['has_team_lead'],
                        'chapter_id' => $chapterId,
                    ]
                );
                $teamCount++;

                // Team Leader
                if ($teamData['has_team_lead']) {
                    $teamLeader = User::firstOrCreate(
                        ['email' => $faker->unique()->safeEmail],
                        [
                            'name' => $faker->name(),
                            'password' => Hash::make('password'),
                            'chapter_id' => $chapterId,
                        ]
                    );
                    $teamLeader->assignRole('team-lead');
                    TeamUser::create([
                        'team_id' => $team->id,
                        'user_id' => $teamLeader->id,
                        'chapter_id' => $chapterId,
                        'role_in_team' => 'team-lead',
                    ]);

                    // Profile for Team Leader
                    Profile::create([
                        'user_id' => $teamLeader->id,
                        'chapter_id' => $chapterId,
                        'first_name' => $faker->firstName(),
                        'last_name' => $faker->lastName(),
                        'gender' => $faker->randomElement(['male', 'female', 'other']),
                        'dob' => $faker->date('Y-m-d', '1995-01-01'),
                        'phone' => $faker->phoneNumber(),
                        'email' => $teamLeader->email,
                        'address' => $faker->streetAddress(),
                        'city' => $faker->city(),
                        'state' => $faker->state(),
                        'country' => $faker->country(),
                        'marital_status' => $faker->randomElement(['single', 'married', 'widowed']),
                        'occupation' => $faker->jobTitle(),
                        'baptism_status' => $faker->randomElement(['baptized', 'not baptized']),
                        'membership_date' => $faker->date('Y-m-d', '2020-01-01'),
                    ]);
                }

                // Units (at least 10 per team)
                for ($i = 0; $i < 10; $i++) {
                    $unit = Unit::create([
                        'team_id' => $team->id,
                        'chapter_id' => $chapterId,
                        'name' => $faker->word() . ' Unit',
                        'short' => strtoupper($faker->lexify('???')),
                    ]);

                    // Unit Head
                    $unitHead = User::firstOrCreate(
                        ['email' => $faker->unique()->safeEmail],
                        [
                            'name' => $faker->name(),
                            'password' => Hash::make('password'),
                            'chapter_id' => $chapterId,
                        ]
                    );
                    $unitHead->assignRole('unit_head');
                    TeamUser::create([
                        'team_id' => $team->id,
                        'user_id' => $unitHead->id,
                        'chapter_id' => $chapterId,
                        'unit_id' => $unit->id,
                        'role_in_team' => 'unit_head',
                    ]);

                    // Profile for Unit Head
                    Profile::create([
                        'user_id' => $unitHead->id,
                        'chapter_id' => $chapterId,
                        'first_name' => $faker->firstName(),
                        'last_name' => $faker->lastName(),
                        'gender' => $faker->randomElement(['male', 'female', 'other']),
                        'dob' => $faker->date('Y-m-d', '1995-01-01'),
                        'phone' => $faker->phoneNumber(),
                        'email' => $unitHead->email,
                        'address' => $faker->streetAddress(),
                        'city' => $faker->city(),
                        'state' => $faker->state(),
                        'country' => $faker->country(),
                        'marital_status' => $faker->randomElement(['single', 'married', 'widowed']),
                        'occupation' => $faker->jobTitle(),
                        'baptism_status' => $faker->randomElement(['baptized', 'not baptized']),
                        'membership_date' => $faker->date('Y-m-d', '2020-01-01'),
                    ]);
                }
            }

            // Ensure at least 10 teams per chapter
            while ($teamCount < 10) {
                $extraTeam = $baseTeams[$teamCount % count($baseTeams)];
                $team = Team::create([
                    'name' => $extraTeam['name'] . " Extra $teamCount",
                    'short' => $extraTeam['short'] . "_$teamCount",
                    'banner' => $extraTeam['banner'],
                    'has_team_lead' => $extraTeam['has_team_lead'],
                    'chapter_id' => $chapterId,
                ]);
                $teamCount++;

                if ($extraTeam['has_team_lead']) {
                    $teamLeader = User::firstOrCreate(
                        ['email' => $faker->unique()->safeEmail],
                        [
                            'name' => $faker->name(),
                            'password' => Hash::make('password'),
                            'chapter_id' => $chapterId,
                        ]
                    );
                    $teamLeader->assignRole('team-lead');
                    TeamUser::create([
                        'team_id' => $team->id,
                        'user_id' => $teamLeader->id,
                        'chapter_id' => $chapterId,
                        'role_in_team' => 'team-lead',
                    ]);

                    // Profile for Team Leader
                    Profile::create([
                        'user_id' => $teamLeader->id,
                        'chapter_id' => $chapterId,
                        'first_name' => $faker->firstName(),
                        'last_name' => $faker->lastName(),
                        'gender' => $faker->randomElement(['male', 'female', 'other']),
                        'dob' => $faker->date('Y-m-d', '1995-01-01'),
                        'phone' => $faker->phoneNumber(),
                        'email' => $teamLeader->email,
                        'address' => $faker->streetAddress(),
                        'city' => $faker->city(),
                        'state' => $faker->state(),
                        'country' => $faker->country(),
                        'marital_status' => $faker->randomElement(['single', 'married', 'widowed']),
                        'occupation' => $faker->jobTitle(),
                        'baptism_status' => $faker->randomElement(['baptized', 'not baptized']),
                        'membership_date' => $faker->date('Y-m-d', '2020-01-01'),
                    ]);
                }

                // Units for Extra Team
                for ($i = 0; $i < 10; $i++) {
                    $unit = Unit::create([
                        'team_id' => $team->id,
                        'chapter_id' => $chapterId,
                        'name' => $faker->word() . ' Unit',
                        'short' => strtoupper($faker->lexify('???')),
                    ]);

                    // Unit Head
                    $unitHead = User::firstOrCreate(
                        ['email' => $faker->unique()->safeEmail],
                        [
                            'name' => $faker->name(),
                            'password' => Hash::make('password'),
                            'chapter_id' => $chapterId,
                        ]
                    );
                    $unitHead->assignRole('unit_head');
                    TeamUser::create([
                        'team_id' => $team->id,
                        'user_id' => $unitHead->id,
                        'chapter_id' => $chapterId,
                        'unit_id' => $unit->id,
                        'role_in_team' => 'unit_head',
                    ]);

                    // Profile for Unit Head
                    Profile::create([
                        'user_id' => $unitHead->id,
                        'chapter_id' => $chapterId,
                        'first_name' => $faker->firstName(),
                        'last_name' => $faker->lastName(),
                        'gender' => $faker->randomElement(['male', 'female', 'other']),
                        'dob' => $faker->date('Y-m-d', '1995-01-01'),
                        'phone' => $faker->phoneNumber(),
                        'email' => $unitHead->email,
                        'address' => $faker->streetAddress(),
                        'city' => $faker->city(),
                        'state' => $faker->state(),
                        'country' => $faker->country(),
                        'marital_status' => $faker->randomElement(['single', 'married', 'widowed']),
                        'occupation' => $faker->jobTitle(),
                        'baptism_status' => $faker->randomElement(['baptized', 'not baptized']),
                        'membership_date' => $faker->date('Y-m-d', '2020-01-01'),
                    ]);
                }
            }
        }

        // -------------------------------
        // TeamUser (at least 10 per team)
        // -------------------------------
        $teams = Team::all();
        foreach ($teams as $team) {
            $chapter = Chapter::find($team->chapter_id);
            $users = User::where('chapter_id', $chapter->id)->inRandomOrder()->take(10)->get();
            $units = Unit::where('team_id', $team->id)->inRandomOrder()->take(5)->get();
            foreach ($users as $index => $user) {
                TeamUser::create([
                    'team_id' => $team->id,
                    'user_id' => $user->id,
                    'chapter_id' => $chapter->id,
                    'unit_id' => $index < 5 ? $units[$index]->id : null,
                    'role_in_team' => $faker->randomElement(['member', 'lead_assist']),
                ]);
            }
        }

        // -------------------------------
        // Seed Entity Data and Reports
        // -------------------------------
        foreach ($teams as $team) {
            $chapter = Chapter::find($team->chapter_id);
            $teamLeader = TeamUser::where('team_id', $team->id)->where('role_in_team', 'team-lead')->first()?->user ?? User::where('chapter_id', $chapter->id)->whereHas('roles', fn($q) => $q->where('name', 'admin'))->first();

            // Church Reports (at least 10)
            for ($i = 0; $i < 10; $i++) {
                $report = Report::create([
                    'report_date' => $faker->dateTimeThisYear(),
                    'title' => $faker->sentence(4),
                    'description' => $faker->paragraph(),
                    'event_type' => $faker->randomElement(['service', 'meeting', 'outreach']),
                    'level' => $faker->randomElement(['team', 'chapter', 'hq']),
                    'chapter_id' => $chapter->id,
                    'team_id' => $team->id,
                    'created_by' => $teamLeader->id,
                ]);

             
            }

            // Scribe Team: Announcements and Minutes
            if ($team->short === 'SCRIBE') {
                // Announcements (at least 10)
                for ($i = 0; $i < 10; $i++) {
                    $announcement = Announcement::create([
                        'title' => $faker->sentence(4),
                        'content' => $faker->paragraph(),
                        'date' => $faker->dateTimeThisYear(),
                        'team_id' => $team->id,
                        'chapter_id' => $chapter->id,
                        'user_id' => $teamLeader->id,
                        'status' => $faker->randomElement(['draft', 'published']),
                        'audience' => $faker->randomElement(['all', 'members', 'team']),
                        'publish_at' => $faker->dateTimeThisYear(),
                    ]);

                    // Announcement Reports (at least 10)
                    AnnouncementReport::create([
                        'announcement_id' => $announcement->id,
                        'title' => "Report: {$announcement->title}",
                        'summary' => $faker->paragraph(),
                        'date' => $announcement->date,
                        'team_id' => $team->id,
                        'chapter_id' => $chapter->id,
                        'user_id' => $teamLeader->id,
                        'status' => $faker->randomElement(['draft', 'submitted', 'approved']),
                        'notes' => $faker->sentence(),
                    ]);

                }

                // Minutes (at least 10)
                for ($i = 0; $i < 10; $i++) {
                    $minute = Minute::create([
                        'title' => $faker->sentence(4),
                        'content' => $faker->paragraphs(3, true),
                        'meeting_date' => $faker->dateTimeThisYear(),
                        'team_id' => $team->id,
                        'chapter_id' => $chapter->id,
                        'user_id' => $teamLeader->id,
                        'status' => $faker->randomElement(['draft', 'approved']),
                        'attendees' => json_encode([$faker->name(), $faker->name()]),
                        'location' => $faker->city(),
                    ]);

                    // Minute Reports (at least 10)
                    MinuteReport::create([
                        'minute_id' => $minute->id,
                        'title' => "Report: {$minute->title}",
                        'summary' => $faker->paragraph(),
                        'date' => $minute->meeting_date,
                        'team_id' => $team->id,
                        'chapter_id' => $chapter->id,
                        'user_id' => $teamLeader->id,
                        'status' => $faker->randomElement(['draft', 'submitted', 'approved']),
                        'notes' => $faker->sentence(),
                    ]);

                }
            }

            // Other Teams: Appointments, Finance, Attendance
            // Appointments (at least 10)
            for ($i = 0; $i < 10; $i++) {
                $startTime = $faker->dateTimeThisYear();
                $endTime = (clone $startTime)->modify('+1 hour');
                $appointment = Appointment::create([
                    'title' => $faker->sentence(4),
                    'description' => $faker->paragraph(),
                    'date' => $startTime,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'team_id' => $team->id,
                    'chapter_id' => $chapter->id,
                    'user_id' => $teamLeader->id,
                    'status' => $faker->randomElement(['pending', 'confirmed', 'cancelled']),
                    'location' => $faker->city(),
                    'attendees' => json_encode([$faker->name(), $faker->name()]),
                ]);

                // Appointment Reports (at least 10)
                AppointmentReport::create([
                    'appointment_id' => $appointment->id,
                    'title' => "Report: {$appointment->title}",
                    'summary' => $faker->paragraph(),
                    'date' => $appointment->date,
                    'team_id' => $team->id,
                    'chapter_id' => $chapter->id,
                    'user_id' => $teamLeader->id,
                    'status' => $faker->randomElement(['draft', 'submitted', 'approved']),
                    'notes' => $faker->sentence(),
                ]);

          
            }

            // Finance (at least 10)
            for ($i = 0; $i < 10; $i++) {
                $finance = Finance::create([
                    'type' => $faker->randomElement(['income', 'expense']),
                    'amount' => $faker->randomFloat(2, 10, 1000),
                    'description' => $faker->sentence(),
                    'date' => $faker->dateTimeThisYear(),
                    'team_id' => $team->id,
                    'chapter_id' => $chapter->id,
                    'user_id' => $teamLeader->id,
                    'category' => $faker->randomElement(['tithes', 'donations', 'utilities']),
                    'reference' => $faker->uuid(),
                    'receipt' => $faker->url(),
                ]);

                // Finance Reports (at least 10)
                FinanceReport::create([
                    'finance_id' => $finance->id,
                    'title' => "Report: {$finance->description}",
                    'summary' => $faker->paragraph(),
                    'date' => $finance->date,
                    'team_id' => $team->id,
                    'chapter_id' => $chapter->id,
                    'user_id' => $teamLeader->id,
                    'status' => $faker->randomElement(['draft', 'submitted', 'approved']),
                    'notes' => $faker->sentence(),
                ]);

              
            }

            // Attendance (at least 10)
            for ($i = 0; $i < 10; $i++) {
                $attendance = Attendance::create([
                    'event_name' => $faker->sentence(3),
                    'date' => $faker->dateTimeThisYear(),
                    'attendee_count' => $faker->numberBetween(10, 100),
                    'notes' => $faker->sentence(),
                    'team_id' => $team->id,
                    'chapter_id' => $chapter->id,
                    'user_id' => $teamLeader->id,
                    'event_type' => $faker->randomElement(['service', 'meeting', 'outreach']),
                    'location' => $faker->city(),
                ]);

                // Attendance Reports (at least 10)
                AttendanceReport::create([
                    'attendance_id' => $attendance->id,
                    'title' => "Report: {$attendance->event_name}",
                    'summary' => $faker->paragraph(),
                    'date' => $attendance->date,
                    'team_id' => $team->id,
                    'chapter_id' => $chapter->id,
                    'user_id' => $teamLeader->id,
                    'status' => $faker->randomElement(['draft', 'submitted', 'approved']),
                    'notes' => $faker->sentence(),
                ]);

             
            }
        }
    }
}
