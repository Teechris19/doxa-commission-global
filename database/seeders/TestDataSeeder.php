<?php

namespace Database\Seeders;

use App\Models\{Testimony, MissionReport, Accounts, PartnershipCategory, Chapter, AboutUs, ChurchLeader, Conclave};
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // Testimonies
        $testimonies = [
            ['name' => 'Mary Johnson', 'testimony' => 'God healed me from a chronic illness after years of prayer. I am now completely healed and serving in the church.', 'status' => 'approved'],
            ['name' => 'James Smith', 'testimony' => 'I lost my job but God provided a better opportunity with double the salary within a month.', 'status' => 'approved'],
            ['name' => 'Sarah Williams', 'testimony' => 'God answered my prayer for a child after 10 years of marriage. We now have a beautiful daughter.', 'status' => 'approved'],
            ['name' => 'David Brown', 'testimony' => 'I was struggling with addiction but through the church fellowship, I have been set free for 2 years now.', 'status' => 'approved'],
            ['name' => 'Grace Miller', 'testimony' => 'God gave me the grace to forgive those who hurt me and restored my marriage.', 'status' => 'approved'],
        ];

        foreach ($testimonies as $data) {
            Testimony::create([
                'name' => $data['name'],
                'email' => $faker->safeEmail,
                'testimony' => $data['testimony'],
                'status' => $data['status'],
            ]);
        }

        // Partnership Categories
        $categories = [
            ['name' => 'Tithe', 'slug' => 'tithe', 'description' => 'Monthly tithe offering', 'is_active' => true],
            ['name' => 'Offering', 'slug' => 'offering', 'description' => 'General offering', 'is_active' => true],
            ['name' => 'Building Fund', 'slug' => 'building-fund', 'description' => 'Support church building project', 'is_active' => true],
            ['name' => 'Missions', 'slug' => 'missions', 'description' => 'Support outreach programs', 'is_active' => true],
            ['name' => 'Welfare', 'slug' => 'welfare', 'description' => 'Support church welfare ministry', 'is_active' => true],
        ];

        foreach ($categories as $cat) {
            PartnershipCategory::create($cat);
        }

        // Accounts
        $chapter = Chapter::first();
        if ($chapter) {
            Accounts::updateOrCreate(
                ['account_number' => '1234567890', 'bank_code' => '011'],
                [
                    'account_name' => 'Doxa Main Account',
                    'bank_name' => 'First Bank',
                    'account_type' => 'ministry',
                    'currency' => 'NGN',
                    'region' => 'Nigeria',
                    'country' => 'Nigeria',
                    'description' => 'Main church account for all donations',
                    'is_active' => true,
                    'accepts_online_payments' => true,
                    'chapter_id' => $chapter->id,
                ]
            );
        }

        // Mission Reports
        $chapters = Chapter::all();
        foreach ($chapters as $chapter) {
            for ($i = 0; $i < 5; $i++) {
                MissionReport::create([
                    'chapter_id' => $chapter->id,
                    'created_by' => 1,
                    'report_date' => $faker->dateTimeThisYear(),
                    'location' => $faker->city() . ' Community',
                    'number_reached' => $faker->numberBetween(20, 200),
                    'testimonies' => $faker->paragraph(),
                    'expenses' => $faker->randomFloat(2, 5000, 50000),
                    'status' => 'submitted',
                ]);
            }
        }

        // About Us data
        $aboutChapters = Chapter::all();
        foreach ($aboutChapters as $ch) {
            AboutUs::updateOrCreate(
                ['chapter_id' => $ch->id],
                [
                    'title' => 'About ' . $ch->name,
                    'description' => 'Welcome to ' . $ch->name . '. We are a community of believers committed to spreading the gospel.',
                    'mission' => 'To bring people into a relationship with Jesus Christ and help them grow in their faith.',
                    'vision' => 'To be a church that impacts nations through the gospel of Jesus Christ.',
                    'core_values' => json_encode(['Excellence', 'Integrity', 'Compassion', 'Worship', 'Discipleship']),
                    'is_active' => true,
                ]
            );
        }

        // Church Leaders
        foreach ($aboutChapters as $ch) {
            ChurchLeader::updateOrCreate(
                ['chapter_id' => $ch->id, 'name' => 'Pastor John Doe'],
                [
                    'position' => 'Senior Pastor',
                    'name' => 'Pastor John Doe',
                    'bio' => $faker->paragraph(),
                    'photo' => null,
                    'is_active' => true,
                    'order_column' => 1,
                ]
            );
        }

        echo "Test data seeded successfully!\n";
    }
}
