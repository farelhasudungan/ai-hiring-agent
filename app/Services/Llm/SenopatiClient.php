<?php

namespace App\Services\Llm;

use App\Services\Llm\Exceptions\LlmException;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
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
        $response = $this->post($messages, stream: false);

        $data = $response->json();
        if (! is_array($data)) {
            throw new LlmException('Respons dari Senopati AI ITS tidak dapat dibaca.', 502, null, 'senopati', true);
        }

        if (isset($data['error'])) {
            throw new LlmException('Senopati AI ITS gagal memproses permintaan.', 502, null, 'senopati', true);
        }

        $content = $data['choices'][0]['message']['content'] ?? null;
        if (! is_string($content)) {
            throw new LlmException('Respons Senopati AI ITS tidak memuat konten jawaban.', 502, null, 'senopati', true);
        }

        $usage = is_array($data['usage'] ?? null) ? $data['usage'] : [];
        $promptTokens = isset($usage['prompt_tokens']) ? (int) $usage['prompt_tokens'] : null;
        $completionTokens = isset($usage['completion_tokens']) ? (int) $usage['completion_tokens'] : null;
        $totalTokens = isset($usage['total_tokens'])
            ? (int) $usage['total_tokens']
            : $this->totalTokens($promptTokens, $completionTokens);

        return [
            'content' => $content,
            'model' => (string) ($data['model'] ?? $this->model),
            'provider' => 'senopati',
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $totalTokens,
        ];
    }

    /**
     * Stream chat completion from Senopati (OpenAI-compatible SSE).
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

                if ($line === '' || str_starts_with($line, ':') || ! str_starts_with($line, 'data:')) {
                    continue;
                }

                $payload = trim(substr($line, 5));
                if ($payload === '[DONE]') {
                    // Stop reading the upstream stream entirely, not just this line.
                    $finished = true;
                    break;
                }

                $data = json_decode($payload, true);
                if (! is_array($data)) {
                    continue;
                }

                if (isset($data['error'])) {
                    throw new LlmException('Senopati AI ITS gagal memproses permintaan streaming.', 502, null, 'senopati', true);
                }

                if (isset($data['model'])) {
                    $modelUsed = (string) $data['model'];
                }

                $chunk = $data['choices'][0]['delta']['content'] ?? '';
                if ($chunk !== '') {
                    $accumulated .= $chunk;
                    $onChunk($chunk);
                }

                if (isset($data['usage']) && is_array($data['usage'])) {
                    $promptTokens = isset($data['usage']['prompt_tokens']) ? (int) $data['usage']['prompt_tokens'] : $promptTokens;
                    $completionTokens = isset($data['usage']['completion_tokens']) ? (int) $data['usage']['completion_tokens'] : $completionTokens;
                }
            }
        }

        return [
            'content' => $accumulated,
            'model' => $modelUsed,
            'provider' => 'senopati',
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
        $headers = ['Accept' => $stream ? 'text/event-stream' : 'application/json'];
        if ($this->apiKey !== '') {
            $headers['Authorization'] = 'Bearer '.$this->apiKey;
        }

        try {
            $pending = Http::timeout($this->timeout)
                ->connectTimeout($this->connectTimeout)
                ->withHeaders($headers)
                ->asJson();

            if ($stream) {
                $pending = $pending->withOptions(['stream' => true]);
            }

            $payload = [
                'model' => $this->model,
                'messages' => $messages,
                'stream' => $stream,
            ];

            if ($stream) {
                // Verified on the ITS LLM gateway: usage is only returned on streamed
                // responses when the OpenAI-compatible stream_options flag is sent.
                $payload['stream_options'] = ['include_usage' => true];
            }

            $response = $pending->post(rtrim($this->baseUrl, '/').'/chat/completions', $payload);
        } catch (ConnectionException $e) {
            throw new LlmException('Layanan Senopati AI ITS tidak dapat dihubungi.', 503, $e, 'senopati', true);
        } catch (Throwable $e) {
            throw new LlmException('Terjadi kesalahan internal saat menghubungi Senopati AI ITS.', 500, $e, 'senopati', false);
        }

        if ($response->serverError()) {
            throw new LlmException('Layanan Senopati AI ITS sedang mengalami gangguan.', 503, null, 'senopati', true);
        }

        if ($response->clientError()) {
            throw new LlmException(
                $response->status() === 404
                    ? 'Endpoint Senopati AI ITS tidak ditemukan. Periksa SENOPATI_BASE_URL.'
                    : 'Senopati AI ITS menolak permintaan yang dikirim.',
                502,
                null,
                'senopati',
                false
            );
        }

        if (! $response->successful()) {
            throw new LlmException('Respons Senopati AI ITS tidak dapat diproses.', 502, null, 'senopati', true);
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
