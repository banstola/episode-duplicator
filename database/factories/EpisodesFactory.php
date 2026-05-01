<?php

namespace Database\Factories;

use App\Models\Episodes;
use Illuminate\Database\Eloquent\Factories\Factory;

class EpisodesFactory extends Factory
{
    protected $model = Episodes::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'status' => fake()->randomElement(['draft', 'active']),
        ];
    }
}
