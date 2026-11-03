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
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'linkedin_url' => 'https://www.linkedin.com/in/'.fake()->slug(2),
            'portfolio_url' => fake()->optional()->url(),
            'source' => fake()->randomElement(['form', 'gmail', 'manual', 'import']),
            'source_meta' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
