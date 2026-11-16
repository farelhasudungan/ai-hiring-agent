<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\Data\AiResponse;
use App\Services\Ai\Exceptions\AiProviderException;
use Generator;

class OpenAiProvider extends HttpAiProvider
{
    public function complete(string $systemPrompt, string $userPrompt, string $model, string $apiKey, int $maxTokens = 1024): AiResponse
    {
        $body = $this->send(
            $this->client()->withToken($apiKey),
            '/chat/completions',
            $this->payload($systemPrompt, $userPrompt, $model, $maxTokens),
        );

        $text = data_get($body, 'choices.0.message.content');

        if (! is_string($text) || trim($text) === '') {
            throw AiProviderException::emptyResponse();
        }

        return new AiResponse(
            text: $text,
            model: (string) data_get($body, 'model', $model),
            inputTokens: data_get($body, 'usage.prompt_tokens'),
            outputTokens: data_get($body, 'usage.completion_tokens'),
        );
    }

    public function stream(string $systemPrompt, string $userPrompt, string $model, string $apiKey, int $maxTokens = 1024): Generator
    {
        $events = $this->streamData(
            $this->client()->withToken($apiKey),
            '/chat/completions',
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
            if ($event === '[DONE]') {
                return;
            }

            $text = data_get(json_decode($event, true), 'choices.0.delta.content');

            if (is_string($text)) {
                yield $text;
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $systemPrompt, string $userPrompt, string $model, int $maxTokens): array
    {
        $messages = [];

        if ($systemPrompt !== '') {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }

        $messages[] = ['role' => 'user', 'content' => $userPrompt];

        return [
            'model' => $model,
            $this->tokenLimitField() => $maxTokens,
            'messages' => $messages,
        ];
    }

    /**
     * Only chat capable models are kept: embeddings, audio, image and
     * moderation models cannot answer a screening prompt.
     */
    public function listModels(string $apiKey): array
    {
        $client = $apiKey === '' ? $this->client() : $this->client()->withToken($apiKey);
        $body = $this->fetch($client, '/models');

        $ids = collect(data_get($body, 'data', []))
            ->pluck('id')
            ->filter(fn (mixed $id): bool => is_string($id) && $this->isChatModel($id))
            ->all();

        return $this->sortedUnique($ids);
    }

    protected function isChatModel(string $id): bool
    {
        return (bool) preg_match('/^(gpt-|chatgpt-|o\d)/', $id)
            && ! preg_match('/(embedding|audio|realtime|tts|transcribe|image|moderation|search|instruct)/', $id);
    }

    protected function tokenLimitField(): string
    {
        return 'max_completion_tokens';
    }
}
