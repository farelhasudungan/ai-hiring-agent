<?php

namespace Database\Factories;

use App\Models\ChatConversation;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatConversation>
 */
class ChatConversationFactory extends Factory
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
            'user_id' => fn (array $attributes) => User::factory()->state(['company_id' => $attributes['company_id']]),
            'job_id' => null,
            'title' => fake()->sentence(4),
        ];
    }
}
