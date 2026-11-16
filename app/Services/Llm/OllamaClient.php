<?php

namespace App\Services\Llm;

use App\Services\Llm\Exceptions\LlmException;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

class OllamaClient implements LlmClientInterface
{
    public function __construct(
        public string $baseUrl,
        public string $model,
        public int $timeout = 30,
        public int $connectTimeout = 3
    ) {
    }

    /**
     * Send chat messages to Ollama and return structured output.
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
        try {
            $response = Http::timeout($this->timeout)
                ->connectTimeout($this->connectTimeout)
                ->post(rtrim($this->baseUrl, '/').'/api/chat', [
                    'model' => $this->model,
                    'messages' => $messages,
                    'stream' => false,
                ]);
        } catch (ConnectionException $e) {
            throw new LlmException('Koneksi ke Ollama gagal atau batas waktu terlampaui.', 503, $e);
        } catch (Throwable $e) {
            throw new LlmException('Gagal menghubungi Ollama: '.$e->getMessage(), 503, $e);
        }

        if (! $response->successful()) {
            throw new LlmException('Ollama mengembalikan status error: '.$response->status(), $response->status() >= 500 ? 503 : 422);
        }

        $data = $response->json();
        if (! is_array($data)) {
            throw new LlmException('Respons dari Ollama tidak valid.', 502);
        }

        $content = $data['message']['content'] ?? '';
        $promptTokens = isset($data['prompt_eval_count']) ? (int) $data['prompt_eval_count'] : null;
        $completionTokens = isset($data['eval_count']) ? (int) $data['eval_count'] : null;
        $totalTokens = ($promptTokens !== null || $completionTokens !== null)
            ? (($promptTokens ?? 0) + ($completionTokens ?? 0))
            : null;

        return [
            'content' => (string) $content,
            'model' => (string) ($data['model'] ?? $this->model),
            'provider' => 'ollama',
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $totalTokens,
        ];
    }

    /**
     * Stream chat completion from Ollama line by line.
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
        try {
            $response = Http::timeout($this->timeout)
                ->connectTimeout($this->connectTimeout)
                ->withOptions(['stream' => true])
                ->post(rtrim($this->baseUrl, '/').'/api/chat', [
                    'model' => $this->model,
                    'messages' => $messages,
                    'stream' => true,
                ]);
        } catch (ConnectionException $e) {
            throw new LlmException('Koneksi streaming ke Ollama gagal atau batas waktu terlampaui.', 503, $e);
        } catch (Throwable $e) {
            throw new LlmException('Gagal memulai streaming Ollama: '.$e->getMessage(), 503, $e);
        }

        if (! $response->successful()) {
            throw new LlmException('Ollama streaming mengembalikan status error: '.$response->status(), 503);
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

                if ($line === '') {
                    continue;
                }

                $data = json_decode($line, true);
                if (is_array($data)) {
                    if (isset($data['model'])) {
                        $modelUsed = (string) $data['model'];
                    }
                    $chunk = $data['message']['content'] ?? '';
                    if ($chunk !== '') {
                        $accumulated .= $chunk;
                        $onChunk($chunk);
                    }
                    if (! empty($data['done'])) {
                        $promptTokens = isset($data['prompt_eval_count']) ? (int) $data['prompt_eval_count'] : $promptTokens;
                        $completionTokens = isset($data['eval_count']) ? (int) $data['eval_count'] : $completionTokens;
                    }
                }
            }
        }

        if (trim($buffer) !== '') {
            $data = json_decode(trim($buffer), true);
            if (is_array($data)) {
                $chunk = $data['message']['content'] ?? '';
                if ($chunk !== '') {
                    $accumulated .= $chunk;
                    $onChunk($chunk);
                }
                if (! empty($data['done'])) {
                    $promptTokens = isset($data['prompt_eval_count']) ? (int) $data['prompt_eval_count'] : $promptTokens;
                    $completionTokens = isset($data['eval_count']) ? (int) $data['eval_count'] : $completionTokens;
                }
            }
        }

        $totalTokens = ($promptTokens !== null || $completionTokens !== null)
            ? (($promptTokens ?? 0) + ($completionTokens ?? 0))
            : null;

        return [
            'content' => $accumulated,
            'model' => $modelUsed,
            'provider' => 'ollama',
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $totalTokens,
        ];
    }
}
