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
                baseUrl: (string) ($this->config['providers']['senopati']['base_url'] ?? 'https://senopati.its.ac.id/v1'),
                model: (string) ($this->config['providers']['senopati']['model'] ?? 'qwen3:14b'),
                apiKey: (string) ($this->config['providers']['senopati']['api_key'] ?? ''),
                timeout: $timeout,
                connectTimeout: $connectTimeout,
            ),
            default => throw new LlmException("Provider LLM [{$provider}] tidak didukung.", 500),
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

        if ($primary === 'ollama') {
            try {
                return $this->client('ollama')->chat($messages);
            } catch (Throwable $e) {
                Log::warning('Ollama provider mengalami kegagalan. Melakukan fallback otomatis ke Senopati AI ITS.', [
                    'error' => $e->getMessage(),
                ]);

                try {
                    return $this->client('senopati')->chat($messages);
                } catch (Throwable $fallbackError) {
                    throw new LlmException(
                        'Layanan LLM utama (Ollama) dan fallback (Senopati) tidak dapat dihubungi.',
                        503,
                        $fallbackError
                    );
                }
            }
        }

        try {
            return $this->client($primary)->chat($messages);
        } catch (Throwable $e) {
            if ($e instanceof LlmException) {
                throw $e;
            }
            throw new LlmException('Gagal memproses permintaan chat LLM: '.$e->getMessage(), 503, $e);
        }
    }

    /**
     * Stream chat completion with automatic fallback support.
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

        if ($primary === 'ollama') {
            try {
                return $this->client('ollama')->stream($messages, $onChunk);
            } catch (Throwable $e) {
                Log::warning('Ollama streaming gagal. Melakukan fallback otomatis ke Senopati AI ITS.', [
                    'error' => $e->getMessage(),
                ]);

                try {
                    return $this->client('senopati')->stream($messages, $onChunk);
                } catch (Throwable $fallbackError) {
                    throw new LlmException(
                        'Layanan streaming LLM utama (Ollama) dan fallback (Senopati) tidak dapat dihubungi.',
                        503,
                        $fallbackError
                    );
                }
            }
        }

        try {
            return $this->client($primary)->stream($messages, $onChunk);
        } catch (Throwable $e) {
            if ($e instanceof LlmException) {
                throw $e;
            }
            throw new LlmException('Gagal memproses streaming LLM: '.$e->getMessage(), 503, $e);
        }
    }
}
