<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\Data\AiResponse;
use App\Services\Ai\Exceptions\AiProviderException;

class OpenAiProvider extends HttpAiProvider
{
    public function complete(string $systemPrompt, string $userPrompt, string $model, string $apiKey, int $maxTokens = 1024): AiResponse
    {
        $messages = [];

        if ($systemPrompt !== '') {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }

        $messages[] = ['role' => 'user', 'content' => $userPrompt];

        $body = $this->send($this->client()->withToken($apiKey), '/chat/completions', [
            'model' => $model,
            $this->tokenLimitField() => $maxTokens,
            'messages' => $messages,
        ]);

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
