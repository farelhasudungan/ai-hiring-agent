<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\Data\AiResponse;
use App\Services\Ai\Exceptions\AiProviderException;

class ClaudeProvider extends HttpAiProvider
{
    private const API_VERSION = '2023-06-01';

    public function complete(string $systemPrompt, string $userPrompt, string $model, string $apiKey, int $maxTokens = 1024): AiResponse
    {
        $payload = [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'messages' => [['role' => 'user', 'content' => $userPrompt]],
        ];

        if ($systemPrompt !== '') {
            $payload['system'] = $systemPrompt;
        }

        $client = $this->client()->withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => self::API_VERSION,
        ]);

        $body = $this->send($client, '/messages', $payload);

        $text = collect(data_get($body, 'content', []))
            ->where('type', 'text')
            ->pluck('text')
            ->implode('');

        if (trim($text) === '') {
            throw AiProviderException::emptyResponse();
        }

        return new AiResponse(
            text: $text,
            model: (string) data_get($body, 'model', $model),
            inputTokens: data_get($body, 'usage.input_tokens'),
            outputTokens: data_get($body, 'usage.output_tokens'),
        );
    }

    public function listModels(string $apiKey): array
    {
        $client = $this->client()->withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => self::API_VERSION,
        ]);

        $body = $this->fetch($client, '/models', ['limit' => 1000]);

        return $this->sortedUnique(collect(data_get($body, 'data', []))->pluck('id')->filter(fn (mixed $id): bool => is_string($id))->all());
    }
}
