<?php

namespace Database\Factories;

use App\Models\MeasurementStandard;
use App\Models\PartHardwareMapping;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeasurementStandard>
 */
class MeasurementStandardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'part_hardware_mapping_id' => PartHardwareMapping::factory(),
            'min_value' => fake()->randomFloat(2, 1, 50),
            'max_value' => fake()->randomFloat(2, 51, 100),
            'unit' => fake()->randomElement(['Nm', 'mm']),
        ];
    }
}
