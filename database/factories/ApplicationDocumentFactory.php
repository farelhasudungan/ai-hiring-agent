<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\ApplicationDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationDocument>
 */
class ApplicationDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'type' => 'resume',
            'file_name' => 'resume.pdf',
            'file_path' => 'applications/'.fake()->uuid().'/resume.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(1024, 1048576),
            'parsing_status' => 'pending',
            'parsed_text' => null,
            'error_message' => null,
        ];
    }
}
