<?php

namespace Database\Factories;

use App\Models\HardwareType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HardwareType>
 */
class HardwareTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'part_number' => fake()->unique()->regexify('HWT-[A-Z0-9]{4,8}'),
            'part_name' => fake()->word(),
            'image' => null,
        ];
    }
}
