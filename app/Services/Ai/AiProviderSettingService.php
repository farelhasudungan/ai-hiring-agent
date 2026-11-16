<?php

namespace App\Services\Ai;

use App\Models\AiProviderSetting;
use App\Models\Company;
use App\Services\Ai\Exceptions\AiProviderException;
use App\Services\Ai\Exceptions\EmptyAiResponseException;
use DomainException;

class AiProviderSettingService
{
    public function __construct(private AiProviderManager $providers) {}

    /**
     * Saves the company's provider choice. A null API key keeps the stored one.
     *
     * @throws DomainException when no API key exists yet.
     */
    public function save(Company $company, string $provider, string $model, ?string $apiKey): AiProviderSetting
    {
        $apiKey = $apiKey ?: $company->aiProviderSetting?->api_key;

        if ($apiKey === null) {
            throw new DomainException('An API key is required.');
        }

        return $company->aiProviderSetting()->updateOrCreate([], [
            'provider' => $provider,
            'model' => $model,
            'api_key' => $apiKey,
        ]);
    }

    /**
     * Sends a tiny prompt to prove the key and model work.
     *
     * @throws AiProviderException
     */
    public function testConnection(string $provider, string $model, string $apiKey): void
    {
        try {
            $this->providers->driver($provider)->complete('', 'Reply with the single word OK.', $model, $apiKey, 64);
        } catch (EmptyAiResponseException) {
            // Reasoning models may spend the whole token budget thinking; the provider still accepted key and model.
        }
    }

    /**
     * Asks the provider which models this key can use.
     *
     * @return list<string>
     *
     * @throws AiProviderException
     */
    public function availableModels(string $provider, string $apiKey): array
    {
        return $this->providers->driver($provider)->listModels($apiKey);
    }
}
