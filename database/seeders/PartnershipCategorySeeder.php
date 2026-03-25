<?php

namespace Database\Seeders;

use App\Models\Accounts;
use App\Models\Chapter;
use App\Models\PartnershipCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PartnershipCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $chapters = Chapter::all();

        $categories = [
            [
                'name' => 'Building Project',
                'description' => 'Support our church building and infrastructure development projects',
            ],
            [
                'name' => 'Missions & Evangelism',
                'description' => 'Fund missionary work and evangelistic outreach programs',
            ],
            [
                'name' => 'Youth Ministry',
                'description' => 'Support programs and activities for young people in our church',
            ],
            [
                'name' => 'Children Ministry',
                'description' => 'Fund children\'s programs, materials, and activities',
            ],
            [
                'name' => 'Worship & Music',
                'description' => 'Support the worship ministry including instruments, sound system, and music training',
            ],
            [
                'name' => 'Community Outreach',
                'description' => 'Fund community service projects and humanitarian aid',
            ],
            [
                'name' => 'Education & Training',
                'description' => 'Support educational programs, seminars, and leadership training',
            ],
            [
                'name' => 'Media & Technology',
                'description' => 'Fund livestreaming equipment, media production, and technology upgrades',
            ],
        ];

        foreach ($chapters as $chapter) {
            // Get an active account for the chapter
            $account = Accounts::where('chapter_id', $chapter->id)
                ->where('is_active', true)
                ->first();

            // Skip chapter if no account exists
            if (!$account) {
                continue;
            }

            // Create categories for this chapter
            foreach ($categories as $categoryData) {
                $slug = Str::slug($categoryData['name']);

                // Ensure unique slug
                $baseSlug = $slug;
                $counter = 2;
                while (PartnershipCategory::where('chapter_id', $chapter->id)->where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }

                PartnershipCategory::firstOrCreate(
                    [
                        'chapter_id' => $chapter->id,
                        'slug' => $slug,
                    ],
                    [
                        'name' => $categoryData['name'],
                        'account_id' => $account->id,
                        'description' => $categoryData['description'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
