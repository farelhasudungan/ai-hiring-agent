<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'application_id' => null,
            'candidate_id' => null,
            'action' => fake()->randomElement(['created', 'updated', 'ai_started', 'status_changed']),
            'description' => fake()->sentence(),
            'meta' => null,
        ];
    }
}
