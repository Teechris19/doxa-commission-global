<?php

namespace Database\Seeders;

use App\Models\{User, Team, Subunit, SubunitMember, Chapter};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PopulateSubunitsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting to populate subunits with sample data...');

        $chapters = Chapter::all();

        foreach ($chapters as $chapter) {
            $this->command->info("\n📍 Processing Chapter: {$chapter->name}");
            
            $teams = Team::where('chapter_id', $chapter->id)->get();

            if ($teams->isEmpty()) {
                $this->command->warn("  No teams found for this chapter. Skipping...");
                continue;
            }

            foreach ($teams as $team) {
                $this->command->info("\n  👥 Team: {$team->name}");

                // Step 1: Add 20 members to the team
                $teamMemberIds = $this->addMembersToTeam($team, 20);
                
                if (empty($teamMemberIds)) {
                    $this->command->warn("    No members added. Skipping subunits...");
                    continue;
                }

                // Step 2: Create 3-5 subunits for this team
                $subunitCount = rand(3, 5);
                $this->command->info("    Creating {$subunitCount} subunits...");

                for ($i = 1; $i <= $subunitCount; $i++) {
                    $this->createSubunit($team, $teamMemberIds, $i);
                }

                $this->command->info("    ✅ Done! Created {$subunitCount} subunits for {$team->name}");
            }
        }

        $this->command->info("\n🎉 Population complete! All teams now have members and subunits.");
    }

    /**
     * Add members to a team
     */
    private function addMembersToTeam(Team $team, int $count = 20): array
    {
        $chapterId = $team->chapter_id;
        $memberIds = [];

        // Get existing users in this chapter or create new ones
        $existingUsers = User::where('chapter_id', $chapterId)
            ->whereHas('teams', fn($q) => $q->where('team_id', $team->id))
            ->pluck('id')
            ->toArray();

        $memberIds = array_merge($memberIds, $existingUsers);

        // Create additional users if needed
        $usersToCreate = $count - count($memberIds);

        if ($usersToCreate > 0) {
            $this->command->info("    Creating {$usersToCreate} new members...");
            
            for ($i = 0; $i < $usersToCreate; $i++) {
                $firstName = $this->getRandomFirstName();
                $lastName = $this->getRandomLastName();
                $name = "{$firstName} {$lastName}";
                
                $user = User::create([
                    'name' => $name,
                    'email' => strtolower(Str::slug($name) . '.' . rand(1000, 9999)) . '@example.com',
                    'password' => Hash::make('password'),
                    'chapter_id' => $chapterId,
                ]);

                // Attach user to team
                $user->teams()->attach($team->id, [
                    'role_in_team' => 'member',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $memberIds[] = $user->id;
            }
        }

        $this->command->info("    Team now has " . count($memberIds) . " members");
        
        return $memberIds;
    }

    /**
     * Create a subunit with members and leader
     */
    private function createSubunit(Team $team, array $memberIds, int $index): void
    {
        // Select 5 random members for this subunit
        shuffle($memberIds);
        $selectedMembers = array_slice($memberIds, 0, min(5, count($memberIds)));

        if (empty($selectedMembers)) {
            $this->command->warn("      ✗ No members available for subunit");
            return;
        }

        // Pick first one as leader
        $leaderId = $selectedMembers[0];

        // Create subunit name
        $subunitNames = [
            "Unit A", "Unit B", "Unit C", "Unit D", "Unit E",
            "Group 1", "Group 2", "Group 3",
            "Team Alpha", "Team Beta", "Team Gamma",
            "Section A", "Section B", "Section C",
            "Division 1", "Division 2",
            "Wing A", "Wing B",
            "Squad 1", "Squad 2", "Squad 3",
        ];

        $subunitName = $subunitNames[array_rand($subunitNames)] . " " . $index;

        // Create the subunit
        $subunit = Subunit::create([
            'team_id' => $team->id,
            'chapter_id' => $team->chapter_id,
            'name' => $subunitName,
            'description' => "This is {$subunitName} under {$team->name}",
            'leader_id' => $leaderId,
            'is_active' => true,
        ]);

        // Add all selected members to the subunit
        foreach ($selectedMembers as $userId) {
            SubunitMember::firstOrCreate([
                'subunit_id' => $subunit->id,
                'user_id' => $userId,
            ]);
        }

        $leader = User::find($leaderId);
        $this->command->info("      ✓ Created {$subunitName} with " . count($selectedMembers) . " members (Leader: {$leader->name})");
    }

    /**
     * Get random first names
     */
    private function getRandomFirstName(): string
    {
        $firstNames = [
            'John', 'Mary', 'David', 'Sarah', 'Michael', 'Grace', 'Peter', 'Joy',
            'James', 'Elizabeth', 'Robert', 'Patricia', 'Joseph', 'Jennifer',
            'Thomas', 'Linda', 'Charles', 'Barbara', 'Daniel', 'Susan',
            'Matthew', 'Jessica', 'Anthony', 'Karen', 'Donald', 'Nancy',
            'Mark', 'Betty', 'Paul', 'Margaret', 'Steven', 'Sandra',
            'Andrew', 'Ashley', 'Kenneth', 'Dorothy', 'Joshua', 'Kimberly',
            'Emmanuel', 'Chidinma', 'Chukwudi', 'Ngozi', 'Oluwaseun', 'Funmilayo',
            'Adebayo', 'Blessing', 'Chinedu', 'Damilola', 'Ebere', 'Folake',
            'Godwin', 'Happiness', 'Ifeanyi', 'Joke', 'Kolawole', 'Lateef',
            'Mobolaji', 'Nneka', 'Obinna', 'Patience', 'Quincy', 'Rachael',
            'Samuel', 'Titilayo', 'Uchenna', 'Victoria', 'Williams', 'Xavier',
            'Yemisi', 'Zainab', 'Alexander', 'Belinda', 'Christopher', 'Diana',
        ];

        return $firstNames[array_rand($firstNames)];
    }

    /**
     * Get random last names
     */
    private function getRandomLastName(): string
    {
        $lastNames = [
            'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller',
            'Davis', 'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez',
            'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson',
            'Martin', 'Lee', 'Perez', 'Thompson', 'White', 'Harris', 'Sanchez',
            'Clark', 'Ramirez', 'Lewis', 'Robinson', 'Walker', 'Young',
            'Allen', 'King', 'Wright', 'Scott', 'Torres', 'Nguyen', 'Hill',
            'Flores', 'Green', 'Adams', 'Nelson', 'Baker', 'Hall', 'Rivera',
            'Campbell', 'Mitchell', 'Carter', 'Roberts', 'Gomez', 'Phillips',
            'Evans', 'Turner', 'Diaz', 'Parker', 'Cruz', 'Edwards', 'Collins',
            'Reyes', 'Stewart', 'Morris', 'Morales', 'Murphy', 'Cook', 'Rogers',
            'Gutierrez', 'Ortiz', 'Morgan', 'Cooper', 'Peterson', 'Bailey',
            'Reed', 'Kelly', 'Howard', 'Ramos', 'Kim', 'Cox', 'Ward',
            'Richardson', 'Watson', 'Brooks', 'Chavez', 'Wood', 'James',
            'Bennett', 'Gray', 'Mendoza', 'Ruiz', 'Hughes', 'Price', 'Alvarez',
            'Castillo', 'Sanders', 'Patel', 'Myers', 'Long', 'Ross', 'Foster',
            'Jimenez', 'Powell', 'Jenkins', 'Perry', 'Russell', 'Sullivan',
            'Bell', 'Coleman', 'Butler', 'Henderson', 'Barnes', 'Gonzales',
            'Fisher', 'Vasquez', 'Simmons', 'Romero', 'Jordan', 'Patterson',
            'Alexander', 'Hamilton', 'Graham', 'Reynolds', 'Griffin', 'Wallace',
            'Moreno', 'West', 'Cole', 'Hayes', 'Bryant', 'Herrera', 'Gibson',
            'Ellis', 'Tran', 'Medina', 'Aguilar', 'Stevens', 'Murray',
            'Ford', 'Castro', 'Marshall', 'Owen', 'Harrison', 'Fernandez',
            'Mcdonald', 'Woods', 'Washington', 'Kennedy', 'Wells', 'Vargas',
            'Henry', 'Chen', 'Freeman', 'Webb', 'Tucker', 'Guzman', 'Burns',
            'Crawford', 'Olson', 'Porter', 'Hunter', 'Gordon', 'Mendez',
            'Silva', 'Shaw', 'Snyder', 'Mason', 'Dixon', 'Munoz', 'Hunt',
            'Hicks', 'Holmes', 'Palmer', 'Wagner', 'Black', 'Robertson',
            'Boyd', 'Rose', 'Stone', 'Salazar', 'Fox', 'Warren', 'Mills',
            'Meyer', 'Rice', 'Schmidt', 'Garza', 'Daniels', 'Ferguson',
            'Nichols', 'Stephens', 'Soto', 'Weaver', 'Ryan', 'Gardner',
            'Payne', 'Grant', 'Dunn', 'Kelley', 'Spencer', 'Hawkins',
            'Arnold', 'Pierce', 'Vazquez', 'Hansen', 'Peters', 'Santos',
            'Hart', 'Bradley', 'Knight', 'Elliott', 'Cunningham', 'Duncan',
            'Armstrong', 'Hudson', 'Carroll', 'Lane', 'Riley', 'Andrews',
            'Alvarado', 'Ray', 'Delgado', 'Berry', 'Perkins', 'Hoffman',
            'Johnston', 'Matthews', 'Pena', 'Richards', 'Contreras', 'Willis',
            'Carpenter', 'Lawrence', 'Sandoval', 'Guerrero', 'George', 'Chapman',
            'Rios', 'Estrada', 'Ortega', 'Watkins', 'Greene', 'Nunez',
            'Wheeler', 'Valdez', 'Harper', 'Burke', 'Larson', 'Santiago',
            'Maldonado', 'Morrison', 'Franklin', 'Carlson', 'Austin', 'Dominguez',
            'Adewale', 'Okafor', 'Adeyemi', 'Okonkwo', 'Adebisi', 'Nwankwo',
            'Oladipo', 'Eze', 'Akinwale', 'Umar', 'Yusuf', 'Ibrahim',
            'Musa', 'Suleiman', 'Mohammed', 'Aliyu', 'Idris', 'Abubakar',
            'Usman', 'Adamu', 'Musa', 'Yakubu', 'Danjuma', 'Oladimeji',
            'Adeleke', 'Ojo', 'Ajayi', 'Bello', 'Ogunleye', 'Adeyemo',
            'Owoade', 'Fagbenro', 'Akintunde', 'Oyelade', 'Adeniyi', 'Ogunsanya',
        ];

        return $lastNames[array_rand($lastNames)];
    }
}
