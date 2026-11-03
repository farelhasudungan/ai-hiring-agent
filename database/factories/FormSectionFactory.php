<?php

namespace Database\Factories;

use App\Models\Form;
use App\Models\FormSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormSection>
 */
class FormSectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'form_id' => Form::factory(),
            'title' => fake()->randomElement([
                'Personal Information',
                'Work Experience',
                'Skills',
                'Additional Questions',
            ]),
            'description' => fake()->optional()->sentence(),
            'sort_order' => 0,
        ];
    }
}
