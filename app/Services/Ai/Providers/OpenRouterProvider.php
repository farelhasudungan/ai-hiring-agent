<?php

namespace App\Services\Ai\Providers;

/**
 * OpenRouter speaks the OpenAI chat completions protocol.
 */
class OpenRouterProvider extends OpenAiProvider
{
    protected function isChatModel(string $id): bool
    {
        return true;
    }

    protected function tokenLimitField(): string
    {
        return 'max_tokens';
    }
}
