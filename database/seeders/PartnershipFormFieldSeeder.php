<?php

namespace Database\Seeders;

use App\Models\PartnershipFormField;
use Illuminate\Database\Seeder;

class PartnershipFormFieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultFields = [
            [
                'label' => 'Partnership Type',
                'name' => 'partnership_type',
                'type' => 'select',
                'options' => json_encode(['Financial Partner', 'Strategic Partner', 'Ministry Partner', 'Prayer Partner']),
                'description' => 'Select the type of partnership you are interested in',
                'placeholder' => 'Select partnership type',
                'is_required' => true,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'label' => 'Organization/Church Name',
                'name' => 'organization_name',
                'type' => 'text',
                'options' => null,
                'description' => 'Name of your organization or church (if applicable)',
                'placeholder' => 'e.g., Grace Ministries International',
                'is_required' => false,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'label' => 'Position/Role',
                'name' => 'position_role',
                'type' => 'text',
                'options' => null,
                'description' => 'Your position or role in the organization',
                'placeholder' => 'e.g., Senior Pastor, Director',
                'is_required' => false,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'label' => 'Country',
                'name' => 'country',
                'type' => 'select',
                'options' => json_encode(['Nigeria', 'Ghana', 'Kenya', 'South Africa', 'United States', 'United Kingdom', 'Canada', 'Other']),
                'description' => 'Select your country of residence',
                'placeholder' => 'Select country',
                'is_required' => true,
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'label' => 'How did you hear about us?',
                'name' => 'referral_source',
                'type' => 'select',
                'options' => json_encode(['Church Service', 'Social Media', 'Friend/Family', 'Website', 'Event', 'Other']),
                'description' => 'Let us know how you found out about this partnership',
                'placeholder' => 'Select option',
                'is_required' => false,
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'label' => 'Additional Comments',
                'name' => 'additional_comments',
                'type' => 'textarea',
                'options' => null,
                'description' => 'Any additional information or questions you would like to share',
                'placeholder' => 'Type your comments here...',
                'is_required' => false,
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'label' => 'I agree to the partnership terms and conditions',
                'name' => 'agree_to_terms',
                'type' => 'checkbox',
                'options' => null,
                'description' => null,
                'placeholder' => 'Check to agree',
                'is_required' => true,
                'is_active' => true,
                'sort_order' => 7,
            ],
        ];

        foreach ($defaultFields as $field) {
            PartnershipFormField::firstOrCreate(
                ['name' => $field['name']],
                $field
            );
        }
    }
}
