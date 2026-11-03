<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\JobPosting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'candidate_id' => Candidate::factory(),
            'job_id' => JobPosting::factory(),
            'source' => fake()->randomElement(['form', 'gmail', 'manual']),
            'status' => 'received',
            'latest_match_score' => null,
            'latest_ai_status' => null,
            'latest_ai_at' => null,
            'submitted_at' => now(),
            'reviewed_at' => null,
            'reviewed_by' => null,
            'notes' => null,
        ];
    }
}
