<?php

namespace Database\Factories;

use App\Models\Export;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Export>
 */
class ExportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'filename' => fake()->word().'.xlsx',
            'path' => 'exports/'.fake()->word().'.xlsx',
            'status' => fake()->randomElement(['queued', 'processing', 'completed', 'failed']),
        ];
    }
}
