<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyInvitation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CompanyInvitation>
 */
class CompanyInvitationFactory extends Factory
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
            'created_by' => null,
            'email' => fake()->safeEmail(),
            'role' => 'viewer',
            'token_hash' => hash('sha256', Str::random(64)),
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
        ];
    }
}
