<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Candidate>
 */
class CandidateFactory extends Factory
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
            'full_name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => null,
            'linkedin_url' => null,
            'portfolio_url' => null,
            'source' => 'manual',
            'source_meta' => null,
            'notes' => null,
        ];
    }
}
