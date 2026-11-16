<?php

namespace App\Services\Llm;

use App\Services\Llm\Exceptions\LlmException;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
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
        $response = $this->post($messages, stream: false);

        $data = $response->json();
        if (! is_array($data)) {
            throw new LlmException('Respons dari Ollama tidak dapat dibaca.', 502, null, 'ollama', true);
        }

        if (isset($data['error'])) {
            throw new LlmException('Ollama gagal memproses permintaan.', 502, null, 'ollama', true);
        }

        if (! is_string($data['message']['content'] ?? null)) {
            throw new LlmException('Respons Ollama tidak memuat konten jawaban.', 502, null, 'ollama', true);
        }

        $promptTokens = isset($data['prompt_eval_count']) ? (int) $data['prompt_eval_count'] : null;
        $completionTokens = isset($data['eval_count']) ? (int) $data['eval_count'] : null;

        return [
            'content' => $data['message']['content'],
            'model' => (string) ($data['model'] ?? $this->model),
            'provider' => 'ollama',
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $this->totalTokens($promptTokens, $completionTokens),
        ];
    }

    /**
     * Stream chat completion from Ollama line by line (NDJSON).
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
        $response = $this->post($messages, stream: true);

        $body = $response->toPsrResponse()->getBody();
        $accumulated = '';
        $promptTokens = null;
        $completionTokens = null;
        $modelUsed = $this->model;
        $buffer = '';
        $finished = false;

        while (! $finished && ! $body->eof()) {
            $buffer .= $body->read(1024);

            while (($newlinePos = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $newlinePos));
                $buffer = substr($buffer, $newlinePos + 1);

                if ($line === '') {
                    continue;
                }

                $data = json_decode($line, true);
                if (! is_array($data)) {
                    continue;
                }

                if (isset($data['error'])) {
                    throw new LlmException('Ollama gagal memproses permintaan streaming.', 502, null, 'ollama', true);
                }

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
                    $finished = true;
                    break;
                }
            }
        }

        if (! $finished && trim($buffer) !== '') {
            $data = json_decode(trim($buffer), true);
            if (is_array($data) && is_string($data['message']['content'] ?? null)) {
                $accumulated .= $data['message']['content'];
                $onChunk($data['message']['content']);
                $promptTokens = isset($data['prompt_eval_count']) ? (int) $data['prompt_eval_count'] : $promptTokens;
                $completionTokens = isset($data['eval_count']) ? (int) $data['eval_count'] : $completionTokens;
            }
        }

        return [
            'content' => $accumulated,
            'model' => $modelUsed,
            'provider' => 'ollama',
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $this->totalTokens($promptTokens, $completionTokens),
        ];
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    protected function post(array $messages, bool $stream): Response
    {
        try {
            $pending = Http::timeout($this->timeout)
                ->connectTimeout($this->connectTimeout)
                ->acceptJson()
                ->asJson();

            if ($stream) {
                $pending = $pending->withOptions(['stream' => true]);
            }

            $response = $pending->post(rtrim($this->baseUrl, '/').'/api/chat', [
                'model' => $this->model,
                'messages' => $messages,
                'stream' => $stream,
            ]);
        } catch (ConnectionException $e) {
            throw new LlmException('Layanan Ollama tidak dapat dihubungi.', 503, $e, 'ollama', true);
        } catch (Throwable $e) {
            throw new LlmException('Terjadi kesalahan internal saat menghubungi Ollama.', 500, $e, 'ollama', false);
        }

        if ($response->serverError()) {
            throw new LlmException('Layanan Ollama sedang mengalami gangguan.', 503, null, 'ollama', true);
        }

        if ($response->clientError()) {
            // 404 means the model/endpoint is missing on the Ollama server: a provider-side
            // configuration problem worth retrying on the fallback. Other 4xx are request errors.
            $missingModel = $response->status() === 404;

            throw new LlmException(
                $missingModel
                    ? 'Model Ollama yang dikonfigurasi tidak tersedia pada server lokal.'
                    : 'Ollama menolak permintaan yang dikirim.',
                502,
                null,
                'ollama',
                $missingModel
            );
        }

        if (! $response->successful()) {
            throw new LlmException('Respons Ollama tidak dapat diproses.', 502, null, 'ollama', true);
        }

        return $response;
    }

    protected function totalTokens(?int $promptTokens, ?int $completionTokens): ?int
    {
        if ($promptTokens === null && $completionTokens === null) {
            return null;
        }

        return ($promptTokens ?? 0) + ($completionTokens ?? 0);
    }
}
