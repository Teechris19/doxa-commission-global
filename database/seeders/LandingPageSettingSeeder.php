<?php

namespace Database\Seeders;

use App\Models\LandingPageSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class LandingPageSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample carousel data with up to 7 items using real image URLs
        $carouselData = [
            [
                'image' => 'https://images.unsplash.com/photo-1438232992991-995b7058bbb3?w=1200&h=600&fit=crop',
                'title' => 'Welcome to Doxa Commission Global',
                'subtitle' => 'Join us in worship and fellowship',
            ],
            [
                'image' => 'https://images.unsplash.com/photo-1511632765486-a01980e01a18?w=1200&h=600&fit=crop',
                'title' => 'Growing in Faith Together',
                'subtitle' => 'Be part of our loving community',
            ],
            [
                'image' => 'https://images.unsplash.com/photo-1507692049790-de58290a4334?w=1200&h=600&fit=crop',
                'title' => 'Sunday Services',
                'subtitle' => 'Worship with us every Sunday at 7AM, 8:30AM, 10AM & 4PM',
            ],
            [
                'image' => 'https://images.unsplash.com/photo-1504052434569-70ad5836ab65?w=1200&h=600&fit=crop',
                'title' => 'Thursday Glory Experience',
                'subtitle' => 'Join us every Thursday at 5:30 PM for a powerful service',
            ],
            [
                'image' => 'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=1200&h=600&fit=crop',
                'title' => 'Believers Academy',
                'subtitle' => 'Deepen your faith through our comprehensive training program',
            ],
            [
                'image' => 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?w=1200&h=600&fit=crop',
                'title' => 'Get Connected',
                'subtitle' => 'Book appointments, submit prayer requests, and more',
            ],
            [
                'image' => 'https://images.unsplash.com/photo-1582213782179-e0d53f98f2ca?w=1200&h=600&fit=crop',
                'title' => 'Partner With Us',
                'subtitle' => 'Be a part of something greater - Explore partnership opportunities',
            ],
        ];

        // Create the landing page setting
        LandingPageSetting::create([
            'carousel' => $carouselData,
        ]);
    }
}
