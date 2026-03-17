<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\User;
use App\Models\Profile;
use App\Models\Team;
use App\Models\TeamUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class CalabarUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        // Get Calabar Branch chapter
        $calabar = Chapter::where('name', 'Calabar Branch')->first();
        
        if (!$calabar) {
            $this->command->error('Calabar Branch not found!');
            return;
        }
        
        $this->command->info("Adding 50 users to Calabar Branch...");
        
        // Get existing teams in Calabar
        $teams = Team::where('chapter_id', $calabar->id)->get();
        
        for ($i = 0; $i < 50; $i++) {
            $firstName = $faker->firstName();
            $lastName = $faker->lastName();
            $email = strtolower(str_replace(' ', '.', $firstName) . '.' . str_replace(' ', '.', $lastName) . $i . '@gmail.com');
            
            // Check if email already exists
            if (User::where('email', $email)->exists()) {
                $email = strtolower(str_replace(' ', '.', $firstName) . '.' . str_replace(' ', '.', $lastName) . '.' . $i . '@gmail.com');
            }
            
            // Create user
            $user = User::create([
                'name' => $firstName . ' ' . $lastName,
                'email' => $email,
                'password' => Hash::make('password'),
                'chapter_id' => $calabar->id,
            ]);
            
            // Create profile
            Profile::create([
                'user_id' => $user->id,
                'chapter_id' => $calabar->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'gender' => $faker->randomElement(['male', 'female']),
                'dob' => $faker->date('Y-m-d', '1990-01-01'),
                'phone' => $faker->phoneNumber(),
                'email' => $email,
                'address' => $faker->streetAddress(),
                'city' => 'Calabar',
                'state' => 'Cross River',
                'country' => 'Nigeria',
                'marital_status' => $faker->randomElement(['single', 'married']),
                'occupation' => $faker->jobTitle(),
                'baptism_status' => $faker->randomElement(['baptized', 'not baptized']),
                'membership_date' => $faker->date('Y-m-d', '2020-01-01'),
            ]);
            
            // Assign to a random team (80% of users)
            if ($faker->boolean(80) && $teams->isNotEmpty()) {
                $randomTeam = $teams->random();
                TeamUser::create([
                    'team_id' => $randomTeam->id,
                    'user_id' => $user->id,
                    'chapter_id' => $calabar->id,
                    'role_in_team' => 'member',
                ]);
            }
            
            $this->command->info("  ✓ Created: {$user->name} ({$user->email})");
        }
        
        $this->command->info('');
        $this->command->info('Successfully created 50 users for Calabar Branch!');
    }
}
