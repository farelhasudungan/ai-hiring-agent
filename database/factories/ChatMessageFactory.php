<?php

namespace Database\Factories;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatMessage>
 */
class ChatMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => ChatConversation::factory(),
            'role' => ChatMessage::ROLE_USER,
            'content' => fake()->sentence(),
            'filters' => null,
            'candidate_ids' => null,
        ];
    }

    public function fromAssistant(): static
    {
        return $this->state(['role' => ChatMessage::ROLE_ASSISTANT]);
    }
}
