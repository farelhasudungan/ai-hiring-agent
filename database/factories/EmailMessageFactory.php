<?php

namespace Database\Factories;

use App\Models\EmailMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailMessage>
 */
class EmailMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'message_id' => fake()->unique()->uuid(),
            'from_email' => fake()->safeEmail(),
            'subject' => fake()->sentence(),
            'has_attachment' => false,
            'status' => 'received',
            'candidate_id' => null,
            'application_id' => null,
            'received_at' => now(),
        ];
    }
}
