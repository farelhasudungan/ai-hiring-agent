<?php

namespace Database\Factories;

use App\Models\FormAnswer;
use App\Models\FormField;
use App\Models\FormSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormAnswer>
 */
class FormAnswerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'form_submission_id' => FormSubmission::factory(),
            'form_field_id' => FormField::factory(),
            'value' => fake()->sentence(),
        ];
    }
}
