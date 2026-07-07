<?php

namespace Database\Factories;

use App\Models\Part;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Part>
 */
class PartFactory extends Factory
{
    public function definition(): array
    {
        return [
            'part_number' => fake()->unique()->regexify('PRT-[A-Z0-9]{4,8}'),
            'part_name' => fake()->word(),
            'model' => fake()->randomElement(['Model A', 'Model B', 'Model C']),
            'variant' => fake()->randomElement(['Standard', 'Premium', 'Economy']),
            'image' => null,
        ];
    }
}
