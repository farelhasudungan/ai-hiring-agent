<?php

namespace App\Services\Llm\Exceptions;

use Exception;
use Throwable;

/**
 * Safe, client-facing LLM failure.
 *
 * The message is always curated (no API keys, URLs, or raw exception text) because it is
 * returned to API clients. Low-level details belong in $previous and the server logs.
 */
class LlmException extends Exception
{
    public function __construct(
        string $message = 'Terjadi kesalahan pada layanan LLM.',
        int $status = 503,
        ?Throwable $previous = null,
        public readonly ?string $provider = null,
        public readonly bool $retryable = true,
    ) {
        parent::__construct($message, $status, $previous);
    }

    /**
     * Whether switching to the next provider could plausibly succeed.
     */
    public function shouldFallback(): bool
    {
        return $this->retryable;
    }
}
