<?php

namespace Database\Factories;

use App\Models\Part;
use App\Models\WeldLengthStandard;
use App\Models\WorkStation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WeldLengthStandard>
 */
class WeldLengthStandardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'part_id' => Part::factory(),
            'work_station_id' => WorkStation::factory(),
            'min_length' => fake()->randomFloat(2, 1, 10),
            'max_length' => fake()->randomFloat(2, 11, 20),
            'unit' => 'mm',
        ];
    }
}
