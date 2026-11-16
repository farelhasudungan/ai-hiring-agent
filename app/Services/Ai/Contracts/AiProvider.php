<?php

namespace App\Services\Ai\Contracts;

use App\Services\Ai\Data\AiResponse;
use App\Services\Ai\Exceptions\AiProviderException;

/**
 * Sends a prompt to one AI vendor and returns the normalized answer.
 */
interface AiProvider
{
    /**
     * @throws AiProviderException
     */
    public function complete(string $systemPrompt, string $userPrompt, string $model, string $apiKey, int $maxTokens = 1024): AiResponse;

    /**
     * Model ids the API key can use, sorted alphabetically.
     *
     * @return list<string>
     *
     * @throws AiProviderException
     */
    public function listModels(string $apiKey): array;
}
