<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\Team;
use App\Models\Unit;
use App\Models\User;
use App\Models\TeamUser;
use App\Models\Profile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class TeamsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Define all teams
        $teams = [
            ['name' => 'COHTECH APOSTOLIC CENTER', 'short' => 'COHTECH', 'banner' => 'teams/cohtech.png', 'has_team_lead' => true],
            ['name' => 'UNICROSS APOSTOLIC CENTER', 'short' => 'UNICROSS', 'banner' => 'teams/unicross.png', 'has_team_lead' => true],
            ['name' => 'DOXA MUSIC MINISTRY', 'short' => 'MUSIC', 'banner' => 'teams/music.png', 'has_team_lead' => true],
            ['name' => 'PROTOCOL', 'short' => 'PROTO', 'banner' => 'teams/protocol.png', 'has_team_lead' => true],
            ['name' => 'SCRIBE', 'short' => 'SCRIBE', 'banner' => 'teams/scribe.png', 'has_team_lead' => true],
            ['name' => 'MISSIONS', 'short' => 'MISSION', 'banner' => 'teams/missions.png', 'has_team_lead' => true],
            ['name' => 'USHERING', 'short' => 'USH', 'banner' => 'teams/ushering.png', 'has_team_lead' => true],
            ['name' => 'MEDIA', 'short' => 'MEDIA', 'banner' => 'teams/media.png', 'has_team_lead' => true],
            ['name' => 'COUNSELING', 'short' => 'COUNSEL', 'banner' => 'teams/counseling.png', 'has_team_lead' => true],
            ['name' => 'TECHNICAL', 'short' => 'TECH', 'banner' => 'teams/technical.png', 'has_team_lead' => true],
            ['name' => 'DOXA PROPERTIES TEAM', 'short' => 'PROPERTIES', 'banner' => 'teams/properties.png', 'has_team_lead' => true],
            ['name' => 'HOSPITALITY', 'short' => 'HOSP', 'banner' => 'teams/hospitality.png', 'has_team_lead' => true],
            ['name' => 'SANCTUARY', 'short' => 'SANCT', 'banner' => 'teams/sanctuary.png', 'has_team_lead' => true],
            ['name' => 'PHOS THEATRE', 'short' => 'PHOS', 'banner' => 'teams/phos.png', 'has_team_lead' => true],
            ['name' => 'MEDICAL TEAM', 'short' => 'MED', 'banner' => 'teams/medical.png', 'has_team_lead' => true],
            ['name' => 'TRANSPORT', 'short' => 'TRANS', 'banner' => 'teams/transport.png', 'has_team_lead' => true],
            ['name' => 'SPORTS', 'short' => 'SPORTS', 'banner' => 'teams/sports.png', 'has_team_lead' => true],
            ['name' => 'CONTENT CREATION TEAM', 'short' => 'CONTENT', 'banner' => 'teams/content.png', 'has_team_lead' => true],
            ['name' => 'SOCIAL MEDIA', 'short' => 'SOCIAL', 'banner' => 'teams/social.png', 'has_team_lead' => true],
            ['name' => 'LIGHTING', 'short' => 'LIGHT', 'banner' => 'teams/lighting.png', 'has_team_lead' => true],
            ['name' => 'DOXA KIDS CHURCH', 'short' => 'KIDS', 'banner' => 'teams/kids.png', 'has_team_lead' => true],
        ];

        // Get all chapters
        $chapters = Chapter::all();

        if ($chapters->isEmpty()) {
            $this->command->error('No chapters found. Please create chapters first.');
            return;
        }

        $this->command->info("Creating teams for {$chapters->count()} chapter(s)...");

        foreach ($chapters as $chapter) {
            $this->command->info("  Processing: {$chapter->name}");

            foreach ($teams as $teamData) {
                // Create or update team
                $team = Team::updateOrCreate(
                    [
                        'name' => $teamData['name'],
                        'chapter_id' => $chapter->id,
                    ],
                    [
                        'short' => $teamData['short'],
                        'banner' => $teamData['banner'],
                        'has_team_lead' => $teamData['has_team_lead'],
                    ]
                );

                $this->command->info("    ✓ Team created: {$team->name} ({$team->short})");
            }
        }

        $this->command->info('');
        $this->command->info('All teams created successfully!');
        $this->command->info('Total teams created: ' . (count($teams) * $chapters->count()));
    }
}
