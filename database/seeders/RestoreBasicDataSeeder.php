<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\User;
use App\Models\Profile;
use App\Models\Team;
use App\Models\TeamUser;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RestoreBasicDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "Starting data restoration...\n";

        // Create Roles if they don't exist
        $roles = ['super-admin', 'admin', 'team-lead', 'member'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
        echo "✓ Roles created\n";

        // 1. Create Super Admin
        $superAdmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@dcg.com',
            'password' => Hash::make('password'),
            'chapter_id' => null,
        ]);
        $superAdmin->assignRole('super-admin');

        Profile::create([
            'user_id' => $superAdmin->id,
            'chapter_id' => null,
            'first_name' => 'Super',
            'last_name' => 'Administrator',
            'gender' => 'male',
            'dob' => '1990-01-01',
            'phone' => '+2348012345678',
            'email' => 'superadmin@dcg.com',
            'address' => 'Headquarters',
            'city' => 'Calabar',
            'state' => 'Cross River',
            'country' => 'Nigeria',
            'marital_status' => 'single',
            'occupation' => 'Administrator',
            'baptism_status' => 'baptized',
            'membership_date' => '2020-01-01',
        ]);
        echo "✓ Super Admin created (superadmin@dcg.com)\n";

        // 2. Create Lagos Chapter
        $lagosChapter = Chapter::create([
            'name' => 'Lagos',
            'data' => json_encode([
                'location' => 'Lagos',
                'contact_email' => 'admin-lagos@dcg.com',
                'contact_phone' => '+2348012345679',
            ]),
        ]);

        $lagosAdmin = User::create([
            'name' => 'Lagos Administrator',
            'email' => 'admin-lagos@dcg.com',
            'password' => Hash::make('password'),
            'chapter_id' => $lagosChapter->id,
        ]);
        $lagosAdmin->assignRole('admin');

        Profile::create([
            'user_id' => $lagosAdmin->id,
            'chapter_id' => $lagosChapter->id,
            'first_name' => 'Lagos',
            'last_name' => 'Administrator',
            'gender' => 'male',
            'dob' => '1990-01-01',
            'phone' => '+2348012345679',
            'email' => 'admin-lagos@dcg.com',
            'address' => 'Lagos Office',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'country' => 'Nigeria',
            'marital_status' => 'single',
            'occupation' => 'Chapter Administrator',
            'baptism_status' => 'baptized',
            'membership_date' => '2020-01-01',
        ]);
        echo "✓ Lagos Chapter created (admin-lagos@dcg.com)\n";

        // 3. Create Calabar Chapter
        $calabarChapter = Chapter::create([
            'name' => 'Calabar Branch',
            'data' => json_encode([
                'location' => 'Calabar',
                'contact_email' => 'admin-calabar@dcg.com',
                'contact_phone' => '+2348012345680',
            ]),
        ]);

        $calabarAdmin = User::create([
            'name' => 'Calabar Administrator',
            'email' => 'admin-calabar@dcg.com',
            'password' => Hash::make('password'),
            'chapter_id' => $calabarChapter->id,
        ]);
        $calabarAdmin->assignRole('admin');

        Profile::create([
            'user_id' => $calabarAdmin->id,
            'chapter_id' => $calabarChapter->id,
            'first_name' => 'Calabar',
            'last_name' => 'Administrator',
            'gender' => 'male',
            'dob' => '1990-01-01',
            'phone' => '+2348012345680',
            'email' => 'admin-calabar@dcg.com',
            'address' => 'Calabar Office',
            'city' => 'Calabar',
            'state' => 'Cross River',
            'country' => 'Nigeria',
            'marital_status' => 'single',
            'occupation' => 'Chapter Administrator',
            'baptism_status' => 'baptized',
            'membership_date' => '2020-01-01',
        ]);
        echo "✓ Calabar Chapter created (admin-calabar@dcg.com)\n";

        // 4. Create 50 Members for Calabar Chapter
        $firstNames = ['John', 'Mary', 'Peter', 'Grace', 'David', 'Sarah', 'Michael', 'Elizabeth', 'James', 'Ruth', 'Joseph', 'Esther', 'Daniel', 'Martha', 'Samuel', 'Anna', 'Thomas', 'Rebecca', 'Matthew', 'Felicia'];
        $lastNames = ['Ogar', 'Bassey', 'Edet', 'Okon', 'Etim', 'Udo', 'Essien', 'Akpan', 'Umar', 'Ibrahim', 'Yusuf', 'Musa', 'Adamu', 'Sani', 'Mohammed', 'Bello', 'Ahmed', 'Aliyu', 'Idris', 'Abubakar'];

        for ($i = 1; $i <= 50; $i++) {
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $email = strtolower($firstName . $i . '@gmail.com');
            
            $member = User::create([
                'name' => "$firstName $lastName",
                'email' => $email,
                'password' => Hash::make('password'),
                'chapter_id' => $calabarChapter->id,
            ]);
            $member->assignRole('member');

            Profile::create([
                'user_id' => $member->id,
                'chapter_id' => $calabarChapter->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'gender' => $i % 2 === 0 ? 'female' : 'male',
                'dob' => date('Y-m-d', strtotime('-' . (20 + rand(0, 40)) . ' years')),
                'phone' => '+23480' . str_pad($i, 7, '0', STR_PAD_LEFT),
                'email' => $email,
                'address' => rand(1, 100) . ' Main Street',
                'city' => 'Calabar',
                'state' => 'Cross River',
                'country' => 'Nigeria',
                'marital_status' => rand(0, 1) ? 'single' : 'married',
                'occupation' => ['Teacher', 'Engineer', 'Doctor', 'Business Owner', 'Student', 'Civil Servant'][rand(0, 5)],
                'baptism_status' => rand(0, 1) ? 'baptized' : 'not baptized',
                'membership_date' => date('Y-m-d', strtotime('-' . rand(0, 5) . ' years')),
            ]);
        }
        echo "✓ 50 members created for Calabar Chapter\n";

        // 5. Create Basic Teams for Calabar
        $teams = [
            ['name' => 'Choir', 'short' => 'CHOIR'],
            ['name' => 'Ushering', 'short' => 'USH'],
            ['name' => 'Media', 'short' => 'MEDIA'],
            ['name' => 'Children Church', 'short' => 'CC'],
            ['name' => 'Youth', 'short' => 'YOUTH'],
        ];

        foreach ($teams as $teamData) {
            $team = Team::create([
                'name' => $teamData['name'],
                'short' => $teamData['short'],
                'chapter_id' => $calabarChapter->id,
                'has_team_lead' => false,
            ]);
        }
        echo "✓ Basic teams created for Calabar\n";

        echo "\n✅ Data restoration completed successfully!\n";
        echo "\nLogin Credentials:\n";
        echo "Super Admin: superadmin@dcg.com / password\n";
        echo "Lagos Admin: admin-lagos@dcg.com / password\n";
        echo "Calabar Admin: admin-calabar@dcg.com / password\n";
        echo "Members: username1@gmail.com to username50@gmail.com / password\n";
    }
}
