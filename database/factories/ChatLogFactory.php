<?php

namespace Database\Factories;

use App\Models\ChatLog;
use App\Models\ChatSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatLog>
 */
class ChatLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $promptTokens = fake()->numberBetween(10, 200);
        $completionTokens = fake()->numberBetween(20, 500);

        return [
            'chat_session_id' => ChatSession::factory(),
            'user_id' => fn (array $attributes) => ChatSession::find($attributes['chat_session_id'])?->user_id ?? User::factory(),
            'prompt' => fake()->sentence(),
            'ai_response' => fake()->paragraphs(2, true),
            'model_used' => 'qwen3:14b',
            'provider' => 'ollama',
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $promptTokens + $completionTokens,
        ];
    }

    public function senopati(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'senopati',
            'model_used' => 'qwen3:14b',
        ]);
    }
}
