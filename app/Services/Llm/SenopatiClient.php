<?php

namespace App\Services\Llm;

use App\Services\Llm\Exceptions\LlmException;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

class SenopatiClient implements LlmClientInterface
{
    public function __construct(
        public string $baseUrl,
        public string $model,
        public string $apiKey = '',
        public int $timeout = 30,
        public int $connectTimeout = 3
    ) {
    }

    /**
     * Send chat messages to Senopati (OpenAI-compatible) and return structured output.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array{
     *     content: string,
     *     model: string,
     *     provider: string,
     *     prompt_tokens: ?int,
     *     completion_tokens: ?int,
     *     total_tokens: ?int
     * }
     */
    public function chat(array $messages): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if (! empty($this->apiKey)) {
            $headers['Authorization'] = 'Bearer '.$this->apiKey;
        }

        try {
            $response = Http::timeout($this->timeout)
                ->connectTimeout($this->connectTimeout)
                ->withHeaders($headers)
                ->post(rtrim($this->baseUrl, '/').'/chat/completions', [
                    'model' => $this->model,
                    'messages' => $messages,
                    'stream' => false,
                ]);
        } catch (ConnectionException $e) {
            throw new LlmException('Koneksi ke Senopati AI ITS gagal atau batas waktu terlampaui.', 503, $e);
        } catch (Throwable $e) {
            throw new LlmException('Gagal menghubungi Senopati AI ITS: '.$e->getMessage(), 503, $e);
        }

        if (! $response->successful()) {
            throw new LlmException('Senopati AI ITS mengembalikan status error: '.$response->status(), $response->status() >= 500 ? 503 : 422);
        }

        $data = $response->json();
        if (! is_array($data)) {
            throw new LlmException('Respons dari Senopati AI ITS tidak valid.', 502);
        }

        $content = $data['choices'][0]['message']['content'] ?? '';
        $usage = $data['usage'] ?? [];
        $promptTokens = isset($usage['prompt_tokens']) ? (int) $usage['prompt_tokens'] : null;
        $completionTokens = isset($usage['completion_tokens']) ? (int) $usage['completion_tokens'] : null;
        $totalTokens = isset($usage['total_tokens']) ? (int) $usage['total_tokens'] : (
            ($promptTokens !== null || $completionTokens !== null)
                ? (($promptTokens ?? 0) + ($completionTokens ?? 0))
                : null
        );

        return [
            'content' => (string) $content,
            'model' => (string) ($data['model'] ?? $this->model),
            'provider' => 'senopati',
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $totalTokens,
        ];
    }

    /**
     * Stream chat completion from Senopati (OpenAI SSE).
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  Closure(string $chunk): void  $onChunk
     * @return array{
     *     content: string,
     *     model: string,
     *     provider: string,
     *     prompt_tokens: ?int,
     *     completion_tokens: ?int,
     *     total_tokens: ?int
     * }
     */
    public function stream(array $messages, Closure $onChunk): array
    {
        $headers = [
            'Accept' => 'text/event-stream',
            'Content-Type' => 'application/json',
        ];

        if (! empty($this->apiKey)) {
            $headers['Authorization'] = 'Bearer '.$this->apiKey;
        }

        try {
            $response = Http::timeout($this->timeout)
                ->connectTimeout($this->connectTimeout)
                ->withHeaders($headers)
                ->withOptions(['stream' => true])
                ->post(rtrim($this->baseUrl, '/').'/chat/completions', [
                    'model' => $this->model,
                    'messages' => $messages,
                    'stream' => true,
                ]);
        } catch (ConnectionException $e) {
            throw new LlmException('Koneksi streaming ke Senopati AI ITS gagal atau batas waktu terlampaui.', 503, $e);
        } catch (Throwable $e) {
            throw new LlmException('Gagal memulai streaming Senopati AI ITS: '.$e->getMessage(), 503, $e);
        }

        if (! $response->successful()) {
            throw new LlmException('Senopati AI ITS streaming mengembalikan status error: '.$response->status(), 503);
        }

        $body = $response->toPsrResponse()->getBody();
        $accumulated = '';
        $promptTokens = null;
        $completionTokens = null;
        $modelUsed = $this->model;
        $buffer = '';

        while (! $body->eof()) {
            $buffer .= $body->read(1024);
            while (($newlinePos = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $newlinePos));
                $buffer = substr($buffer, $newlinePos + 1);

                if ($line === '' || str_starts_with($line, ':')) {
                    continue;
                }

                if (str_starts_with($line, 'data:')) {
                    $payload = trim(substr($line, 5));
                    if ($payload === '[DONE]') {
                        break;
                    }

                    $data = json_decode($payload, true);
                    if (is_array($data)) {
                        if (isset($data['model'])) {
                            $modelUsed = (string) $data['model'];
                        }
                        $chunk = $data['choices'][0]['delta']['content'] ?? '';
                        if ($chunk !== '') {
                            $accumulated .= $chunk;
                            $onChunk($chunk);
                        }
                        if (isset($data['usage'])) {
                            $promptTokens = isset($data['usage']['prompt_tokens']) ? (int) $data['usage']['prompt_tokens'] : $promptTokens;
                            $completionTokens = isset($data['usage']['completion_tokens']) ? (int) $data['usage']['completion_tokens'] : $completionTokens;
                        }
                    }
                }
            }
        }

        $totalTokens = ($promptTokens !== null || $completionTokens !== null)
            ? (($promptTokens ?? 0) + ($completionTokens ?? 0))
            : null;

        return [
            'content' => $accumulated,
            'model' => $modelUsed,
            'provider' => 'senopati',
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $totalTokens,
        ];
    }
}
