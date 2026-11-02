<?php

namespace Database\Factories;

use App\Models\CandidateScoringAgent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CandidateScoringAgent>
 */
class CandidateScoringAgentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Candidate Scoring',
            'slug' => 'candidate-scoring-'.fake()->unique()->numerify('###'),
            'description' => 'Scores candidates based on job requirements and qualifications.',
        ];
    }
}
