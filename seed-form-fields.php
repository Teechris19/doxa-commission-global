<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PartnershipFormField;

echo "=== Adding Default Partnership Form Fields ===" . PHP_EOL . PHP_EOL;

$defaultFields = [
    [
        'label' => 'Organization Name',
        'name' => 'organization',
        'type' => 'text',
        'options' => null,
        'description' => 'Name of your organization or company',
        'placeholder' => 'e.g., ABC Company Ltd',
        'is_required' => false,
        'is_active' => true,
        'sort_order' => 1,
    ],
    [
        'label' => 'Phone Number',
        'name' => 'phone_number',
        'type' => 'tel',
        'options' => null,
        'description' => 'Your contact phone number',
        'placeholder' => '+234 801 234 5678',
        'is_required' => true,
        'is_active' => true,
        'sort_order' => 2,
    ],
    [
        'label' => 'Partnership Duration',
        'name' => 'partnership_duration',
        'type' => 'select',
        'options' => ['6 months', '1 year', '2 years', '3 years', '5 years', 'Indefinite'],
        'description' => 'How long do you intend to maintain this partnership?',
        'placeholder' => null,
        'is_required' => true,
        'is_active' => true,
        'sort_order' => 3,
    ],
    [
        'label' => 'Payment Method Preference',
        'name' => 'payment_method',
        'type' => 'select',
        'options' => ['Bank Transfer', 'Direct Debit', 'Cash', 'Online Payment'],
        'description' => 'Preferred method for making contributions',
        'placeholder' => null,
        'is_required' => false,
        'is_active' => true,
        'sort_order' => 4,
    ],
    [
        'label' => 'Additional Notes',
        'name' => 'additional_notes',
        'type' => 'textarea',
        'options' => null,
        'description' => 'Any additional information or special requests',
        'placeholder' => 'Enter any additional notes here...',
        'is_required' => false,
        'is_active' => true,
        'sort_order' => 5,
    ],
];

$created = 0;
foreach ($defaultFields as $fieldData) {
    // Create as global field (NULL chapter_id) so all chapters can use it
    $field = PartnershipFormField::create($fieldData);
    echo "✓ Created: {$fieldData['label']} (ID: {$field->id})" . PHP_EOL;
    $created++;
}

echo PHP_EOL . "=== Done! Created {$created} default form fields ===" . PHP_EOL;
echo PHP_EOL . "These fields will now appear in:" . PHP_EOL;
echo "  - Form Builder preview" . PHP_EOL;
echo "  - Public partnership form" . PHP_EOL;
echo PHP_EOL;
