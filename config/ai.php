<?php

use App\Services\Ai\Providers\ClaudeProvider;
use App\Services\Ai\Providers\GeminiProvider;
use App\Services\Ai\Providers\OpenAiProvider;
use App\Services\Ai\Providers\OpenRouterProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | AI Providers
    |--------------------------------------------------------------------------
    |
    | Vendors a company can pick from. Each company brings its own API key,
    | and the model list is fetched live from the vendor. Vendors that require
    | a key to list models show "fallback_models" until a key is entered.
    |
    */

    'providers' => [
        'openai' => [
            'label' => 'OpenAI',
            'driver' => OpenAiProvider::class,
            'base_url' => 'https://api.openai.com/v1',
            'models_require_key' => true,
            'fallback_models' => ['gpt-4o', 'gpt-4o-mini', 'o3-mini'],
        ],
        'openrouter' => [
            'label' => 'OpenRouter',
            'driver' => OpenRouterProvider::class,
            'base_url' => 'https://openrouter.ai/api/v1',
            'models_require_key' => false,
            'fallback_models' => [],
        ],
        'claude' => [
            'label' => 'Claude (Anthropic)',
            'driver' => ClaudeProvider::class,
            'base_url' => 'https://api.anthropic.com/v1',
            'models_require_key' => true,
            'fallback_models' => ['claude-haiku-5-5', 'claude-sonnet-5-5', 'claude-opus-5-5'],
        ],
        'gemini' => [
            'label' => 'Gemini (Google)',
            'driver' => GeminiProvider::class,
            'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
            'models_require_key' => true,
            'fallback_models' => ['gemini-2.0-flash', 'gemini-1.5-pro'],
        ],
    ],

];
