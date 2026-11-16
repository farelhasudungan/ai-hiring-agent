<?php

namespace App\Services\Llm\Exceptions;

use Exception;
use Throwable;

class LlmException extends Exception
{
    public function __construct(
        string $message = 'Terjadi kesalahan pada layanan LLM.',
        int $code = 503,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
