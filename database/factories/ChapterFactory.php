<?php

namespace Database\Factories;

use App\Models\Chapter;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChapterFactory extends Factory
{
    protected $model = Chapter::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' Chapter',
            'data' => [
                'short' => strtoupper(fake()->unique()->lexify('???')),
                'type' => 'chapter',
            ],
        ];
    }
}
