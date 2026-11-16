<?php

namespace App\Services\Ai;

use App\Models\Company;
use App\Services\Ai\Data\AiResponse;
use App\Services\Ai\Exceptions\AiProviderException;
use DomainException;
use Generator;

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

    /**
     * Streams the answer in text chunks. The setting check runs immediately,
     * the vendor call starts when the generator is first iterated.
     *
     * @return Generator<int, string>
     *
     * @throws DomainException when the company has not configured an AI provider.
     * @throws AiProviderException when the provider call fails while iterating.
     */
    public function stream(Company $company, string $systemPrompt, string $userPrompt, int $maxTokens = 1024): Generator
    {
        $setting = $company->aiProviderSetting;

        if ($setting === null) {
            throw new DomainException('Your company has not configured an AI provider yet.');
        }

        return $this->providers
            ->driver($setting->provider)
            ->stream($systemPrompt, $userPrompt, $setting->model, $setting->api_key, $maxTokens);
    }
}
