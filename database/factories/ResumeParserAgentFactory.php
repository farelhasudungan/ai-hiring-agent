<?php

namespace Database\Factories;

use App\Models\ResumeParserAgent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResumeParserAgent>
 */
class ResumeParserAgentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Resume Parser',
            'slug' => 'resume-parser-'.fake()->unique()->numerify('###'),
            'description' => 'Parses and extracts structured data from candidate resumes.',
        ];
    }
}
