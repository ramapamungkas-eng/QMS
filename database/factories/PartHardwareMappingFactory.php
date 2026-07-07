<?php

namespace Database\Factories;

use App\Models\HardwareType;
use App\Models\Part;
use App\Models\PartHardwareMapping;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PartHardwareMapping>
 */
class PartHardwareMappingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'part_id' => Part::factory(),
            'hardware_type_id' => HardwareType::factory(),
            'measurement_type' => fake()->randomElement(['torque', 'nugget']),
            'usage_qty' => fake()->numberBetween(1, 10),
        ];
    }
}
