<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\Data\AiResponse;
use App\Services\Ai\Data\AiToolResponse;
use App\Services\Ai\Data\ChatTurn;
use App\Services\Ai\Data\ToolCall;
use App\Services\Ai\Exceptions\AiProviderException;
use Generator;

class GeminiProvider extends HttpAiProvider
{
    public function complete(string $systemPrompt, string $userPrompt, string $model, string $apiKey, int $maxTokens = 1024): AiResponse
    {
        // The key travels in a header, never in the URL, so it cannot leak into logs.
        $body = $this->send(
            $this->client()->withHeaders(['x-goog-api-key' => $apiKey]),
            '/models/'.rawurlencode($model).':generateContent',
            $this->payload($systemPrompt, $userPrompt, $maxTokens),
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

    public function chat(string $systemPrompt, array $turns, array $tools, string $model, string $apiKey, int $maxTokens = 1024): AiToolResponse
    {
        $contents = [];

        foreach ($turns as $turn) {
            if ($turn->role === ChatTurn::TOOL) {
                $part = ['functionResponse' => ['name' => $turn->toolName, 'response' => ['result' => (string) $turn->content]]];
                $last = array_key_last($contents);

                if ($last !== null && $contents[$last]['role'] === 'user' && isset($contents[$last]['parts'][0]['functionResponse'])) {
                    $contents[$last]['parts'][] = $part;
                } else {
                    $contents[] = ['role' => 'user', 'parts' => [$part]];
                }

                continue;
            }

            if ($turn->role === ChatTurn::ASSISTANT) {
                $parts = $turn->raw ?? array_values(array_filter([
                    $turn->content !== null && trim($turn->content) !== '' ? ['text' => $turn->content] : null,
                    ...array_map(fn (ToolCall $call): array => ['functionCall' => ['name' => $call->name, 'args' => $call->arguments === [] ? (object) [] : $call->arguments]], $turn->toolCalls),
                ]));

                $contents[] = ['role' => 'model', 'parts' => $parts];

                continue;
            }

            $contents[] = ['role' => 'user', 'parts' => [['text' => (string) $turn->content]]];
        }

        $payload = ['contents' => $contents, 'generationConfig' => ['maxOutputTokens' => $maxTokens]];

        if ($systemPrompt !== '') {
            $payload['systemInstruction'] = ['parts' => [['text' => $systemPrompt]]];
        }

        if ($tools !== []) {
            $payload['tools'] = [['functionDeclarations' => array_map(fn ($tool): array => ['name' => $tool->name, 'description' => $tool->description, 'parameters' => $tool->parameters], $tools)]];
        }

        $body = $this->send(
            $this->client()->withHeaders(['x-goog-api-key' => $apiKey]),
            '/models/'.rawurlencode($model).':generateContent',
            $payload,
        );

        $parts = collect(data_get($body, 'candidates.0.content.parts', []))->filter(fn (mixed $part): bool => is_array($part))->values();

        $text = $parts->filter(fn (array $part): bool => isset($part['text']) && empty($part['thought']))->pluck('text')->implode('');
        $text = trim($text) === '' ? null : $text;

        $calls = $parts->filter(fn (array $part): bool => isset($part['functionCall']['name']))
            ->values()
            ->map(fn (array $part, int $index): ToolCall => new ToolCall(
                'call_'.$index.'_'.$part['functionCall']['name'],
                $part['functionCall']['name'],
                is_array($part['functionCall']['args'] ?? null) ? $part['functionCall']['args'] : [],
            ))
            ->all();

        if ($text === null && $calls === []) {
            throw AiProviderException::emptyResponse();
        }

        return new AiToolResponse(
            $text,
            $calls,
            data_get($body, 'usageMetadata.promptTokenCount'),
            data_get($body, 'usageMetadata.candidatesTokenCount'),
            $calls === [] ? null : $parts->all(),
        );
    }

    public function stream(string $systemPrompt, string $userPrompt, string $model, string $apiKey, int $maxTokens = 1024): Generator
    {
        $events = $this->streamData(
            $this->client()->withHeaders(['x-goog-api-key' => $apiKey]),
            '/models/'.rawurlencode($model).':streamGenerateContent?alt=sse',
            $this->payload($systemPrompt, $userPrompt, $maxTokens),
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
            yield collect(data_get(json_decode($event, true), 'candidates.0.content.parts', []))->pluck('text')->implode('');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $systemPrompt, string $userPrompt, int $maxTokens): array
    {
        $payload = [
            'contents' => [['role' => 'user', 'parts' => [['text' => $userPrompt]]]],
            'generationConfig' => ['maxOutputTokens' => $maxTokens],
        ];

        if ($systemPrompt !== '') {
            $payload['systemInstruction'] = ['parts' => [['text' => $systemPrompt]]];
        }

        return $payload;
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
