<?php

namespace Database\Factories;

use App\Models\Chapter;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['Media', 'Ushering', 'Protocol', 'Music', 'Technical', 'Counseling']) . ' Team',
            'short' => strtoupper(fake()->unique()->lexify('????')),
            'banner' => 'teams/' . fake()->unique()->word() . '.png',
            'has_team_lead' => true,
            'chapter_id' => Chapter::factory(),
        ];
    }
}
