<?php

namespace Database\Factories;

use App\Models\AiProviderSetting;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiProviderSetting>
 */
class AiProviderSettingFactory extends Factory
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
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'api_key' => 'sk-'.fake()->sha256(),
        ];
    }

    public function forProvider(string $provider, string $model): static
    {
        return $this->state(['provider' => $provider, 'model' => $model]);
    }
}
