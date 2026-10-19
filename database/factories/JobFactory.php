<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Job;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Job>
 */
class JobFactory extends Factory
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
            'title' => fake()->jobTitle(),
            'department' => null,
            'location' => null,
            'status' => 'draft',
            'description' => fake()->paragraph(),
            'requirements' => null,
            'required_skills' => ['PHP', 'Laravel'],
            'nice_to_have' => null,
            'min_experience_years' => null,
        ];
    }
}
