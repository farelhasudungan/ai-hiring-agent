<?php

namespace App\Services\Ai;

use App\Models\Company;
use App\Services\Ai\Data\AiResponse;
use App\Services\Ai\Exceptions\AiProviderException;
use DomainException;

/**
 * Entry point for AI features: runs a prompt with the provider, model and API
 * key the company configured.
 */
class AiService
{
    public function __construct(private AiProviderManager $providers) {}

    /**
     * @throws DomainException when the company has not configured an AI provider.
     * @throws AiProviderException when the provider call fails.
     */
    public function complete(Company $company, string $systemPrompt, string $userPrompt, int $maxTokens = 1024): AiResponse
    {
        $setting = $company->aiProviderSetting;

        if ($setting === null) {
            throw new DomainException('Your company has not configured an AI provider yet.');
        }

        return $this->providers
            ->driver($setting->provider)
            ->complete($systemPrompt, $userPrompt, $setting->model, $setting->api_key, $maxTokens);
    }
}
