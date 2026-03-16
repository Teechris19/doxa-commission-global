<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$migrationsToSkip = [
    '2025_09_05_171405_create_finance_report_table',
    '2025_09_05_171717_create_attendance_report_table',
    '2025_09_06_193040_add_display_name_to_teams_table',
    '2025_09_10_011342_create_reports_table',
    '2025_09_14_231459_create_appointment_teams_table',
    '2025_09_19_055653_create_appointment_table',
    '2025_09_19_060000_create_appointment_report_table',
    '2025_09_20_094367_create_prayer_requests_table',
    '2025_09_20_111849_create_prayer_request_teams_table',
    '2025_09_22_150854_create_belivers_academies_table',
    '2025_09_22_150855_create_academy_clases_table',
    '2025_09_22_150856_create_student_classes_table',
    '2025_09_22_160939_create_believers_academy_teams_table',
    '2025_09_23_225224_add_to_student_class_table',
    '2025_09_23_230411_add_phone_to_student_classes_table',
    '2025_09_24_073123_add_academy_id_to_student_classes_table',
    '2025_09_24_124615_create_partnerships_table',
    '2025_09_24_203433_create_partnerships_settings_table',
    '2025_09_24_204047_create_events_table',
    '2025_09_24_204454_create_event_forms_table',
    '2025_09_24_204510_create_event_galleries_table',
    '2025_09_24_213206_create_event_teams_table',
    '2025_09_26_040157_create_accounts_table',
    '2025_09_26_041410_create_account_events_table',
    '2025_09_26_072900_create_notifications_table',
    '2025_10_13_080501_create_testimonies_table',
    '2025_10_30_231102_create_sermon_series_table',
    '2025_10_30_231103_create_sermons_table',
    '2025_10_30_231116_create_sermon_media_table',
    '2025_11_06_013654_add-chapter_id_to_accounts_table',
    '2025_11_06_222756_add_form_schema_to_events_table',
    '2025_11_14_065831_create_transports_table',
    '2025_11_15_000001_add_registration_columns_to_account_events',
    '2025_11_15_120000_add_event_id_to_event_teams_table',
    '2025_11_15_130000_create_about_us_table',
    '2025_11_15_140000_create_cell_groups_tables',
    '2026_01_11_024215_rename_belivers_academies_table_to_believers_academies',
    '2026_01_11_034206_add_certificate_template_to_believers_academies_table',
    '2026_01_11_045320_make_interest_nullable_in_student_classes_table',
    '2026_01_11_050857_create_academy_batches_table',
    '2026_01_11_050952_add_batch_id_to_student_classes_table',
    '2026_01_11_051150_create_batch_classes_table',
    '2026_01_11_060328_add_status_to_academy_batches_table',
];

$existingMigrations = DB::table('migrations')->pluck('migration')->toArray();

foreach ($migrationsToSkip as $migration) {
    if (!in_array($migration, $existingMigrations)) {
        DB::table('migrations')->insert([
            'migration' => $migration,
            'batch' => 1,
        ]);
        echo "Marked as migrated: $migration\n";
    }
}

echo "Done!\n";
