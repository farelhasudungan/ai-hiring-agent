<?php

namespace Database\Factories;

use App\Models\HiringRecommendationAgent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HiringRecommendationAgent>
 */
class HiringRecommendationAgentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Hiring Recommendation',
            'slug' => 'hiring-recommendation-'.fake()->unique()->numerify('###'),
            'description' => 'Generates hiring recommendations and shortlists based on candidate scores.',
        ];
    }
}
