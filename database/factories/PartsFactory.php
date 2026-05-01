<?php

namespace Database\Factories;

use App\Models\Parts;
use Illuminate\Database\Eloquent\Factories\Factory;

class PartsFactory extends Factory
{
    protected $model = Parts::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
        ];
    }
}
