<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\Data\AiResponse;
use App\Services\Ai\Exceptions\AiProviderException;

class GeminiProvider extends HttpAiProvider
{
    public function complete(string $systemPrompt, string $userPrompt, string $model, string $apiKey, int $maxTokens = 1024): AiResponse
    {
        $payload = [
            'contents' => [['role' => 'user', 'parts' => [['text' => $userPrompt]]]],
            'generationConfig' => ['maxOutputTokens' => $maxTokens],
        ];

        if ($systemPrompt !== '') {
            $payload['systemInstruction'] = ['parts' => [['text' => $systemPrompt]]];
        }

        // The key travels in a header, never in the URL, so it cannot leak into logs.
        $body = $this->send(
            $this->client()->withHeaders(['x-goog-api-key' => $apiKey]),
            '/models/'.rawurlencode($model).':generateContent',
            $payload,
        );

        $text = collect(data_get($body, 'candidates.0.content.parts', []))->pluck('text')->implode('');

        if (trim($text) === '') {
            throw AiProviderException::emptyResponse();
        }

        return new AiResponse(
            text: $text,
            model: $model,
            inputTokens: data_get($body, 'usageMetadata.promptTokenCount'),
            outputTokens: data_get($body, 'usageMetadata.candidatesTokenCount'),
        );
    }

    public function listModels(string $apiKey): array
    {
        $body = $this->fetch($this->client()->withHeaders(['x-goog-api-key' => $apiKey]), '/models', ['pageSize' => 1000]);

        $ids = collect(data_get($body, 'models', []))
            ->filter(fn (array $model): bool => in_array('generateContent', $model['supportedGenerationMethods'] ?? [], true))
            ->map(fn (array $model): string => preg_replace('#^models/#', '', (string) ($model['name'] ?? '')))
            ->all();

        return $this->sortedUnique($ids);
    }
}
