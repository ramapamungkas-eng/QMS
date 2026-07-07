<?php

namespace Database\Factories;

use App\Models\Process;
use App\Models\StationType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StationType>
 */
class StationTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'process_id' => Process::factory(),
            'slug' => fake()->unique()->word(),
            'name' => fake()->word(),
            'description' => fake()->sentence(),
            'icon' => null,
        ];
    }
}
