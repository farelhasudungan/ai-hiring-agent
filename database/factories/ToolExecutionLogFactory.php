<?php

namespace Database\Factories;

use App\Models\ChatMessage;
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
            'tool_name' => fake()->randomElement(['parse_resume', 'score_candidate', 'recommend_hire']),
            'input' => ['file' => 'resume.pdf'],
            'result' => ['score' => fake()->numberBetween(60, 100)],
            'status' => fake()->randomElement(['success', 'failed', 'pending']),
            'duration_ms' => fake()->numberBetween(100, 5000),
            'error_message' => null,
        ];
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
