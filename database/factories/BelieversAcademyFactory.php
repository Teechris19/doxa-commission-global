<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BelieversAcademy>
 */
class BelieversAcademyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => 'open',
            'start_at' => now()->addDays(7),
            'chapter_id' => 1, // Assume chapter 1 exists
        ];
    }
}
