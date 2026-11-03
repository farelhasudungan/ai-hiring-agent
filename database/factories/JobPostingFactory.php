<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\JobPosting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobPosting>
 */
class JobPostingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $skills = [
            'PHP', 'Laravel', 'MySQL', 'PostgreSQL', 'JavaScript', 'Vue.js',
            'React', 'Docker', 'AWS', 'Git', 'Redis', 'REST API',
        ];

        return [
            'company_id' => Company::factory(),
            'title' => fake()->jobTitle(),
            'department' => fake()->randomElement(['Engineering', 'Product', 'Design', 'Marketing', 'Human Resources']),
            'location' => fake()->city(),
            'status' => 'open',
            'description' => fake()->paragraphs(3, true),
            'requirements' => fake()->paragraphs(2, true),
            'required_skills' => fake()->randomElements($skills, 4),
            'nice_to_have' => fake()->randomElements($skills, 2),
            'min_experience_years' => fake()->numberBetween(0, 10),
        ];
    }
}
