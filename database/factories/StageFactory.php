<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class StageFactory extends Factory
{
    protected $model = \App\Models\Stage::class;

    public function definition(): array
    {
        $key = fake()->unique()->word();

        return [
            'key' => $key,
            'name' => ucfirst($key),
            'is_active' => true,
        ];
    }
}
