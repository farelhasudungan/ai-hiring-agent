<?php

namespace Database\Factories;

use App\Models\AiScreening;
use App\Models\Application;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiScreening>
 */
class AiScreeningFactory extends Factory
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
            'job_id' => fn (array $attributes): int => Application::query()
                ->findOrFail($attributes['application_id'])->job_id,
            'document_id' => null,
            'model_name' => 'qwen2.5:7b',
            'status' => 'queued',
            'prompt_text' => 'Compare the candidate resume with the job requirements.',
            'raw_response' => null,
            'parsed_response' => null,
            'match_score' => null,
            'recommendation' => null,
            'summary' => null,
            'candidate_skills' => null,
            'matched_skills' => null,
            'missing_skills' => null,
            'experience_years' => null,
            'education_level' => null,
            'risk_flags' => null,
            'error_message' => null,
            'started_at' => null,
            'completed_at' => null,
            'duration_ms' => null,
        ];
    }
}
