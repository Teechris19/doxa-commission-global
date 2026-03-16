<?php

namespace Database\Seeders;

use App\Models\GlobalSetting;
use Illuminate\Database\Seeder;

class FooterSettingsSeeder extends Seeder
{
    public function run()
    {
        $globalSettings = GlobalSetting::updateOrCreate(
            ['id' => 1],
            [
                'church_name' => 'DOXA Church',
                'denomination' => 'Christian',
                'tagline' => 'Bringing nations into God\'s glory worldwide.',
                'footer_description' => 'Bringing nations into God\'s glory worldwide.',
                'footer_address' => '129 Goldie, Adjacent Amika Utuk, Calabar, Cross River State, Nigeria.',
                'footer_phone' => '+234 1234567890',
                'footer_email' => 'info@doxachurch.org',
                'footer_services' => [
                    ['name' => 'Sunday Glory Life Service', 'times' => '7:00am, 8:30am, 10:00am, 4:00pm'],
                    ['name' => 'Thursday Glory Experience', 'times' => '5:30pm'],
                ],
                'social_links' => [
                    'facebook' => '#',
                    'youtube' => '#',
                    'instagram' => '#',
                    'twitter' => '#',
                ],
            ]
        );

        $this->command->info('Footer settings populated successfully.');
        $this->command->info('Address: ' . $globalSettings->footer_address);
        $this->command->info('Phone: ' . $globalSettings->footer_phone);
        $this->command->info('Email: ' . $globalSettings->footer_email);
    }
}
