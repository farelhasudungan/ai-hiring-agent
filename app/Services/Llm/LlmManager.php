<?php

namespace App\Services\Llm;

use App\Services\Llm\Exceptions\LlmException;
use Closure;
use Illuminate\Support\Facades\Log;
use Throwable;

class LlmManager implements LlmClientInterface
{
    /** @var array<string, LlmClientInterface> */
    protected array $clients = [];

    /**
     * @param  array<string, mixed>|null  $config
     */
    public function __construct(
        protected ?string $defaultProvider = null,
        protected ?array $config = null
    ) {
        $this->defaultProvider = $defaultProvider ?? (string) config('llm.default', 'ollama');
        $this->config = $config ?? config('llm', []);
    }

    public function client(?string $provider = null): LlmClientInterface
    {
        $provider = $provider ?? $this->defaultProvider;

        if (isset($this->clients[$provider])) {
            return $this->clients[$provider];
        }

        $timeout = (int) ($this->config['timeout'] ?? 30);
        $connectTimeout = (int) ($this->config['connect_timeout'] ?? 3);

        return $this->clients[$provider] = match ($provider) {
            'ollama' => new OllamaClient(
                baseUrl: (string) ($this->config['providers']['ollama']['base_url'] ?? 'http://localhost:11434'),
                model: (string) ($this->config['providers']['ollama']['model'] ?? 'qwen3:14b'),
                timeout: $timeout,
                connectTimeout: $connectTimeout,
            ),
            'senopati' => new SenopatiClient(
                baseUrl: (string) ($this->config['providers']['senopati']['base_url'] ?? 'https://senopati.its.ac.id/llm-gateway/v1'),
                model: (string) ($this->config['providers']['senopati']['model'] ?? 'qwen3:14b'),
                apiKey: (string) ($this->config['providers']['senopati']['api_key'] ?? ''),
                timeout: $timeout,
                connectTimeout: $connectTimeout,
            ),
            default => throw new LlmException("Provider LLM [{$provider}] tidak didukung.", 500, null, $provider, false),
        };
    }

    /**
     * Send chat messages with automatic fallback support.
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
        $primary = $this->defaultProvider;

        if ($primary !== 'ollama') {
            return $this->callWithoutFallback($primary, $messages);
        }

        try {
            return $this->client('ollama')->chat($messages);
        } catch (Throwable $e) {
            $failure = $this->normalize($e, 'ollama');
            $this->rethrowUnlessRetryable($failure);
            $this->logFallback('chat', $failure);

            try {
                return $this->client('senopati')->chat($messages);
            } catch (Throwable $fallbackError) {
                throw $this->bothProvidersFailed('chat', $failure, $this->normalize($fallbackError, 'senopati'));
            }
        }
    }

    /**
     * Stream chat completion with automatic fallback support.
     *
     * Fallback is only attempted before the first chunk reaches the client, so a failed
     * Ollama stream can never be continued with a Senopati answer in the same response.
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
        $primary = $this->defaultProvider;
        $emittedChunks = 0;

        $guardedChunk = function (string $chunk) use ($onChunk, &$emittedChunks): void {
            $emittedChunks++;
            $onChunk($chunk);
        };

        if ($primary !== 'ollama') {
            try {
                return $this->client($primary)->stream($messages, $guardedChunk);
            } catch (Throwable $e) {
                $failure = $this->normalize($e, $primary);

                if ($emittedChunks > 0) {
                    throw $this->interruptedStream($failure, $emittedChunks);
                }

                throw $failure;
            }
        }

        try {
            return $this->client('ollama')->stream($messages, $guardedChunk);
        } catch (Throwable $e) {
            $failure = $this->normalize($e, 'ollama');

            if ($emittedChunks > 0) {
                throw $this->interruptedStream($failure, $emittedChunks);
            }

            $this->rethrowUnlessRetryable($failure);
            $this->logFallback('stream', $failure);

            try {
                return $this->client('senopati')->stream($messages, $guardedChunk);
            } catch (Throwable $fallbackError) {
                throw $this->bothProvidersFailed('stream', $failure, $this->normalize($fallbackError, 'senopati'));
            }
        }
    }

    /**
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
    protected function callWithoutFallback(string $provider, array $messages): array
    {
        try {
            return $this->client($provider)->chat($messages);
        } catch (Throwable $e) {
            throw $this->normalize($e, $provider);
        }
    }

    /**
     * Wrap unexpected errors as non-retryable so a programming error never hides behind a fallback.
     */
    protected function normalize(Throwable $e, string $provider): LlmException
    {
        if ($e instanceof LlmException) {
            return $e;
        }

        return new LlmException('Terjadi kesalahan internal saat memproses permintaan LLM.', 500, $e, $provider, false);
    }

    protected function rethrowUnlessRetryable(LlmException $failure): void
    {
        if (! $failure->shouldFallback()) {
            throw $failure;
        }
    }

    protected function interruptedStream(LlmException $failure, int $emittedChunks): LlmException
    {
        Log::error('Streaming provider utama gagal setelah sebagian jawaban terkirim. Fallback dibatalkan agar respons tidak tercampur.', [
            'provider' => $failure->provider,
            'chunks_emitted' => $emittedChunks,
            'reason' => $failure->getMessage(),
            'exception' => $failure->getPrevious(),
        ]);

        return new LlmException(
            'Respons streaming terputus sebelum selesai. Silakan ulangi permintaan.',
            502,
            $failure,
            $failure->provider,
            false
        );
    }

    protected function logFallback(string $operation, LlmException $failure): void
    {
        Log::warning('Provider LLM utama gagal. Fallback otomatis ke Senopati AI ITS.', [
            'operation' => $operation,
            'provider' => 'ollama',
            'status' => $failure->getCode(),
            'reason' => $failure->getMessage(),
            'exception' => $failure->getPrevious(),
        ]);
    }

    protected function bothProvidersFailed(string $operation, LlmException $primaryFailure, LlmException $fallbackFailure): LlmException
    {
        Log::error('Provider LLM utama dan fallback sama-sama gagal.', [
            'operation' => $operation,
            'primary_reason' => $primaryFailure->getMessage(),
            'primary_exception' => $primaryFailure->getPrevious(),
            'fallback_reason' => $fallbackFailure->getMessage(),
            'fallback_exception' => $fallbackFailure->getPrevious(),
        ]);

        $message = $operation === 'stream'
            ? 'Layanan streaming LLM utama (Ollama) dan fallback (Senopati) tidak dapat dihubungi.'
            : 'Layanan LLM utama (Ollama) dan fallback (Senopati) tidak dapat dihubungi.';

        return new LlmException($message, 503, $fallbackFailure);
    }
}
