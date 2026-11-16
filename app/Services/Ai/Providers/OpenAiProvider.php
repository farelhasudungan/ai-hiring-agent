<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\Data\AiResponse;
use App\Services\Ai\Data\AiToolResponse;
use App\Services\Ai\Data\ChatTurn;
use App\Services\Ai\Data\ToolCall;
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

    public function chat(string $systemPrompt, array $turns, array $tools, string $model, string $apiKey, int $maxTokens = 1024): AiToolResponse
    {
        $messages = $systemPrompt === '' ? [] : [['role' => 'system', 'content' => $systemPrompt]];

        foreach ($turns as $turn) {
            $messages[] = match ($turn->role) {
                ChatTurn::TOOL => ['role' => 'tool', 'tool_call_id' => $turn->toolCallId, 'content' => (string) $turn->content],
                ChatTurn::ASSISTANT => array_filter([
                    'role' => 'assistant',
                    'content' => $turn->content,
                    'tool_calls' => $turn->toolCalls === [] ? null : array_map(fn (ToolCall $call): array => [
                        'id' => $call->id,
                        'type' => 'function',
                        'function' => ['name' => $call->name, 'arguments' => $call->arguments === [] ? '{}' : json_encode($call->arguments)],
                    ], $turn->toolCalls),
                ], fn (mixed $value): bool => $value !== null),
                default => ['role' => 'user', 'content' => (string) $turn->content],
            };
        }

        $payload = [
            'model' => $model,
            $this->tokenLimitField() => $maxTokens,
            'messages' => $messages,
        ];

        if ($tools !== []) {
            $payload['tools'] = array_map(fn ($tool): array => [
                'type' => 'function',
                'function' => ['name' => $tool->name, 'description' => $tool->description, 'parameters' => $tool->parameters],
            ], $tools);
        }

        $body = $this->send($this->client()->withToken($apiKey), '/chat/completions', $payload);

        $text = data_get($body, 'choices.0.message.content');
        $text = is_string($text) && trim($text) !== '' ? $text : null;

        $calls = collect(data_get($body, 'choices.0.message.tool_calls', []))
            ->filter(fn (mixed $call): bool => is_array($call) && is_string(data_get($call, 'function.name')))
            ->map(function (array $call): ToolCall {
                $arguments = json_decode((string) data_get($call, 'function.arguments', '{}'), true);

                return new ToolCall((string) ($call['id'] ?? ''), $call['function']['name'], is_array($arguments) ? $arguments : []);
            })
            ->values()
            ->all();

        if ($text === null && $calls === []) {
            throw AiProviderException::emptyResponse();
        }

        return new AiToolResponse($text, $calls, data_get($body, 'usage.prompt_tokens'), data_get($body, 'usage.completion_tokens'));
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
