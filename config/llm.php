<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default LLM Provider
    |--------------------------------------------------------------------------
    |
    | Supported providers: "ollama", "senopati"
    |
    */
    'default' => env('LLM_PROVIDER', 'ollama'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeouts (in seconds)
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('LLM_TIMEOUT', 30),
    'connect_timeout' => (int) env('LLM_CONNECT_TIMEOUT', 3),

    /*
    |--------------------------------------------------------------------------
    | System Prompt Injection
    |--------------------------------------------------------------------------
    |
    | Recruitment assistant system prompt injected on every LLM call.
    |
    */
    'system_prompt' => env(
        'LLM_SYSTEM_PROMPT',
        'Anda adalah asisten rekrutmen (HR) Roku. Bantu menganalisis CV, menilai kandidat, dan menyusun rekomendasi perekrutan secara objektif. Keputusan akhir tetap berada di tangan manusia dan rekomendasi ini bersifat advisory.'
    ),

    /*
    |--------------------------------------------------------------------------
    | Context Window History Limit
    |--------------------------------------------------------------------------
    |
    | Maximum number of previous messages sent as conversation context.
    |
    */
    'context_history_limit' => (int) env('LLM_CONTEXT_HISTORY_LIMIT', 6),

    /*
    |--------------------------------------------------------------------------
    | Provider Configurations
    |--------------------------------------------------------------------------
    */
    'providers' => [
        'ollama' => [
            'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
            'model' => env('OLLAMA_MODEL', 'qwen3:14b'),
        ],

        'senopati' => [
            'base_url' => env('SENOPATI_BASE_URL', 'https://senopati.its.ac.id/llm-gateway/v1'),
            'api_key' => env('SENOPATI_API_KEY', ''),
            'model' => env('SENOPATI_MODEL', 'qwen3:14b'),
        ],
    ],
];
