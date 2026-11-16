<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\Data\AiResponse;
use App\Services\Ai\Data\AiToolResponse;
use App\Services\Ai\Data\ChatTurn;
use App\Services\Ai\Data\ToolCall;
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

    public function chat(string $systemPrompt, array $turns, array $tools, string $model, string $apiKey, int $maxTokens = 1024): AiToolResponse
    {
        $messages = [];

        foreach ($turns as $turn) {
            if ($turn->role === ChatTurn::TOOL) {
                $block = ['type' => 'tool_result', 'tool_use_id' => $turn->toolCallId, 'content' => (string) $turn->content];
                $last = array_key_last($messages);

                // Results of one round must share a single user message.
                if ($last !== null && $messages[$last]['role'] === 'user' && is_array($messages[$last]['content']) && ($messages[$last]['content'][0]['type'] ?? null) === 'tool_result') {
                    $messages[$last]['content'][] = $block;
                } else {
                    $messages[] = ['role' => 'user', 'content' => [$block]];
                }

                continue;
            }

            if ($turn->role === ChatTurn::ASSISTANT) {
                $blocks = [];

                if ($turn->content !== null && trim($turn->content) !== '') {
                    $blocks[] = ['type' => 'text', 'text' => $turn->content];
                }

                foreach ($turn->toolCalls as $call) {
                    $blocks[] = ['type' => 'tool_use', 'id' => $call->id, 'name' => $call->name, 'input' => $call->arguments === [] ? (object) [] : $call->arguments];
                }

                $messages[] = ['role' => 'assistant', 'content' => $blocks];

                continue;
            }

            $messages[] = ['role' => 'user', 'content' => (string) $turn->content];
        }

        $payload = ['model' => $model, 'max_tokens' => $maxTokens, 'messages' => $messages];

        if ($systemPrompt !== '') {
            $payload['system'] = $systemPrompt;
        }

        if ($tools !== []) {
            $payload['tools'] = array_map(fn ($tool): array => ['name' => $tool->name, 'description' => $tool->description, 'input_schema' => $tool->parameters], $tools);
        }

        $body = $this->send($this->authenticatedClient($apiKey), '/messages', $payload);
        $blocks = collect(data_get($body, 'content', []));

        $text = $blocks->where('type', 'text')->pluck('text')->implode('');
        $text = trim($text) === '' ? null : $text;

        $calls = $blocks->where('type', 'tool_use')
            ->map(fn (array $block): ToolCall => new ToolCall((string) $block['id'], (string) $block['name'], is_array($block['input'] ?? null) ? $block['input'] : []))
            ->values()
            ->all();

        if ($text === null && $calls === []) {
            throw AiProviderException::emptyResponse();
        }

        return new AiToolResponse($text, $calls, data_get($body, 'usage.input_tokens'), data_get($body, 'usage.output_tokens'));
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
