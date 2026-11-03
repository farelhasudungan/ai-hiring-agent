<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Form;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Form>
 */
class FormFactory extends Factory
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
            'created_by' => null,
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'status' => 'published',
            'version' => 1,
            'settings' => [
                'confirmation_message' => 'Thank you for applying. We will review your submission shortly.',
            ],
            'published_at' => now(),
            'closes_at' => null,
        ];
    }
}
