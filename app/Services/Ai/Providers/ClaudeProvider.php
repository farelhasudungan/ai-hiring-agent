<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\Data\AiResponse;
use App\Services\Ai\Exceptions\AiProviderException;
use Generator;
use Illuminate\Http\Client\PendingRequest;

class ClaudeProvider extends HttpAiProvider
{
    private const API_VERSION = '2023-06-01';

    public function complete(string $systemPrompt, string $userPrompt, string $model, string $apiKey, int $maxTokens = 1024): AiResponse
    {
        $body = $this->send($this->authenticatedClient($apiKey), '/messages', $this->payload($systemPrompt, $userPrompt, $model, $maxTokens));

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

    public function stream(string $systemPrompt, string $userPrompt, string $model, string $apiKey, int $maxTokens = 1024): Generator
    {
        $events = $this->streamData(
            $this->authenticatedClient($apiKey),
            '/messages',
            $this->payload($systemPrompt, $userPrompt, $model, $maxTokens) + ['stream' => true],
        );

        return $this->requireChunks($this->textChunks($events));
    }

    /**
     * @param  Generator<int, string>  $events
     * @return Generator<int, string>
     */
    private function textChunks(Generator $events): Generator
    {
        foreach ($events as $event) {
            $data = json_decode($event, true);

            if (data_get($data, 'type') === 'content_block_delta' && is_string($text = data_get($data, 'delta.text'))) {
                yield $text;
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $systemPrompt, string $userPrompt, string $model, int $maxTokens): array
    {
        $payload = [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'messages' => [['role' => 'user', 'content' => $userPrompt]],
        ];

        if ($systemPrompt !== '') {
            $payload['system'] = $systemPrompt;
        }

        return $payload;
    }

    private function authenticatedClient(string $apiKey): PendingRequest
    {
        return $this->client()->withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => self::API_VERSION,
        ]);
    }

    public function listModels(string $apiKey): array
    {
        $client = $this->authenticatedClient($apiKey);

        $body = $this->fetch($client, '/models', ['limit' => 1000]);

        return $this->sortedUnique(collect(data_get($body, 'data', []))->pluck('id')->filter(fn (mixed $id): bool => is_string($id))->all());
    }
}
