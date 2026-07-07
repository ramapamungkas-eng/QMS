<?php

namespace Database\Factories;

use App\Models\InspectionRecord;
use App\Models\Part;
use App\Models\User;
use App\Models\WorkStation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InspectionRecord>
 */
class InspectionRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'part_id' => Part::factory(),
            'work_station_id' => WorkStation::factory(),
            'stage' => fake()->randomElement(['start', 'middle', 'end']),
            'checker_id' => User::factory(),
            'checked_at' => fake()->dateTimeBetween('-1 month'),
            'shift' => fake()->randomElement(['day', 'night']),
            'production_date' => fake()->date(),
        ];
    }
}
