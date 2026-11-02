<?php

namespace Database\Factories;

use App\Models\ChatMessage;
use App\Models\CandidateScoringAgent;
use App\Models\HiringRecommendationAgent;
use App\Models\ResumeParserAgent;
use App\Models\ToolExecutionLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ToolExecutionLog>
 */
class ToolExecutionLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chat_message_id' => ChatMessage::factory(),
            'loggable_type' => 'resume_parser',
            'loggable_id' => ResumeParserAgent::factory(),
            'tool_name' => 'parse_resume',
            'input' => ['file' => 'resume.pdf'],
            'result' => ['skills' => ['PHP', 'Laravel']],
            'status' => 'success',
            'duration_ms' => fake()->numberBetween(100, 5000),
            'error_message' => null,
        ];
    }

    public function resumeParser(): static
    {
        return $this->state(fn (array $attributes) => [
            'loggable_type' => 'resume_parser',
            'loggable_id' => ResumeParserAgent::factory(),
            'tool_name' => 'parse_resume',
            'input' => ['file' => 'resume.pdf'],
            'result' => ['skills' => ['PHP', 'Laravel']],
        ]);
    }

    public function candidateScoring(): static
    {
        return $this->state(fn (array $attributes) => [
            'loggable_type' => 'candidate_scoring',
            'loggable_id' => CandidateScoringAgent::factory(),
            'tool_name' => 'score_candidate',
            'input' => ['candidate_id' => fake()->numberBetween(1, 100)],
            'result' => ['score' => fake()->numberBetween(0, 100)],
        ]);
    }

    public function hiringRecommendation(): static
    {
        return $this->state(fn (array $attributes) => [
            'loggable_type' => 'hiring_recommendation',
            'loggable_id' => HiringRecommendationAgent::factory(),
            'tool_name' => 'generate_shortlist',
            'input' => ['job_title' => fake()->jobTitle()],
            'result' => ['shortlisted_candidate_ids' => [1, 2, 3]],
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'result' => null,
            'error_message' => fake()->sentence(),
        ]);
    }
}
