<?php

namespace App\Services\Ai\Contracts;

use App\Services\Ai\Data\AiResponse;
use App\Services\Ai\Exceptions\AiProviderException;
use Generator;

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
     * Same as complete() but yields the answer in text chunks as the vendor
     * produces them. Throws while iterating if the vendor fails or sends nothing.
     *
     * @return Generator<int, string>
     *
     * @throws AiProviderException
     */
    public function stream(string $systemPrompt, string $userPrompt, string $model, string $apiKey, int $maxTokens = 1024): Generator;

    /**
     * Model ids the API key can use, sorted alphabetically.
     *
     * @return list<string>
     *
     * @throws AiProviderException
     */
    public function listModels(string $apiKey): array;
}
