<?php

namespace Database\Factories;

use App\Models\FormAnswer;
use App\Models\FormAnswerFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormAnswerFile>
 */
class FormAnswerFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'form_answer_id' => FormAnswer::factory(),
            'application_document_id' => null,
            'file_name' => 'resume.pdf',
            'file_path' => 'forms/resume.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(50_000, 5_000_000),
        ];
    }
}
