<?php

namespace App\Services\Ai;

use App\Services\Ai\Contracts\AiProvider;
use InvalidArgumentException;

class AiProviderManager
{
    /**
     * @return array<string, array{label: string, driver: class-string<AiProvider>, base_url: string, models_require_key: bool, fallback_models: list<string>}>
     */
    public function providers(): array
    {
        return config('ai.providers');
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->providers());
    }

    public function driver(string $provider): AiProvider
    {
        $config = $this->providers()[$provider] ?? throw new InvalidArgumentException("Unknown AI provider [{$provider}].");

        return new $config['driver']($config['base_url']);
    }

    /**
     * Whether the vendor refuses to list its models without an API key.
     */
    public function modelsRequireKey(string $provider): bool
    {
        return $this->providers()[$provider]['models_require_key'] ?? true;
    }
}
