<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure roles exist
        Role::firstOrCreate(['name' => 'super-admin']);
        Role::firstOrCreate(['name' => 'admin']);

        // -------------------------------
        // Super Admin User
        // -------------------------------
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@dcg.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'chapter_id' => null,
            ]
        );
        $superAdmin->assignRole('super-admin');

        // Profile for Super Admin
        Profile::firstOrCreate(
            ['user_id' => $superAdmin->id],
            [
                'chapter_id' => null,
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'gender' => 'male',
                'dob' => '1990-01-01',
                'phone' => '+1234567890',
                'email' => 'superadmin@dcg.com',
                'address' => 'Headquarters',
                'city' => 'Calabar',
                'state' => 'Cross River',
                'country' => 'Nigeria',
                'marital_status' => 'single',
                'occupation' => 'Administrator',
                'baptism_status' => 'baptized',
                'membership_date' => '2018-01-01',
            ]
        );

        // -------------------------------
        // Get or Create Chapters
        // -------------------------------
        $chapters = [
            ['id' => 1, 'name' => 'Calabar Branch', 'location' => 'Calabar'],
            ['id' => 2, 'name' => 'Lagos', 'location' => 'Lagos'],
            ['id' => 3, 'name' => 'DOXA CITY HEADQUARTERS', 'location' => 'HQ'],
        ];

        $chapterAdmins = [
            [
                'email' => 'admin-calabar@dcg.com',
                'name' => 'Calabar Admin',
                'chapter_name' => 'Calabar Branch',
                'first_name' => 'Calabar',
                'last_name' => 'Admin',
            ],
            [
                'email' => 'admin-lagos@dcg.com',
                'name' => 'Lagos Admin',
                'chapter_name' => 'Lagos',
                'first_name' => 'Lagos',
                'last_name' => 'Admin',
            ],
            [
                'email' => 'admin-hq@dcg.com',
                'name' => 'HQ Admin',
                'chapter_name' => 'DOXA CITY HEADQUARTERS',
                'first_name' => 'HQ',
                'last_name' => 'Admin',
            ],
        ];

        foreach ($chapterAdmins as $adminData) {
            // Find the chapter
            $chapter = Chapter::firstOrCreate(
                ['name' => $adminData['chapter_name']],
                ['data' => json_encode(['location' => $adminData['chapter_name'], 'contact_email' => $adminData['email']])]
            );

            // Create or update the chapter admin
            $chapterAdmin = User::firstOrCreate(
                ['email' => $adminData['email']],
                [
                    'name' => $adminData['name'],
                    'password' => Hash::make('password'),
                    'chapter_id' => $chapter->id,
                ]
            );
            
            // Ensure admin has the admin role
            $chapterAdmin->syncRoles(['admin']);

            // Profile for Chapter Admin
            Profile::firstOrCreate(
                ['user_id' => $chapterAdmin->id],
                [
                    'chapter_id' => $chapter->id,
                    'first_name' => $adminData['first_name'],
                    'last_name' => $adminData['last_name'],
                    'gender' => 'male',
                    'dob' => '1990-01-01',
                    'phone' => '+2341234567890',
                    'email' => $adminData['email'],
                    'address' => 'Chapter Office',
                    'city' => $adminData['chapter_name'],
                    'state' => 'Nigeria',
                    'country' => 'Nigeria',
                    'marital_status' => 'single',
                    'occupation' => 'Chapter Administrator',
                    'baptism_status' => 'baptized',
                    'membership_date' => '2020-01-01',
                ]
            );
        }

        // Output credentials
        $this->command->info('=================================');
        $this->command->info('Admin Users Created Successfully!');
        $this->command->info('=================================');
        $this->command->info('Super Admin:');
        $this->command->info('  Email: superadmin@dcg.com');
        $this->command->info('  Password: password');
        $this->command->info('');
        $this->command->info('Chapter Admins:');
        $this->command->info('  Calabar Branch:');
        $this->command->info('    Email: admin-calabar@dcg.com');
        $this->command->info('    Password: password');
        $this->command->info('');
        $this->command->info('  Lagos:');
        $this->command->info('    Email: admin-lagos@dcg.com');
        $this->command->info('    Password: password');
        $this->command->info('');
        $this->command->info('  DOXA CITY HEADQUARTERS:');
        $this->command->info('    Email: admin-hq@dcg.com');
        $this->command->info('    Password: password');
        $this->command->info('=================================');
    }
}
