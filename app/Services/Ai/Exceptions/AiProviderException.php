<?php

namespace App\Services\Ai\Exceptions;

use RuntimeException;

/**
 * Failure while talking to an AI vendor. The message is safe to show to users
 * and never contains the API key or the raw vendor response.
 */
class AiProviderException extends RuntimeException
{
    public static function fromStatus(int $status): self
    {
        return new self(match (true) {
            $status === 401, $status === 403 => 'The provider rejected the API key.',
            $status === 402 => 'The provider account has insufficient credits.',
            $status === 400, $status === 404 => 'The provider could not use that model. Pick a model from the list.',
            $status === 429 => 'The provider rate limit was reached. Try again shortly.',
            $status >= 500 => 'The provider is unavailable right now. Try again later.',
            default => "The provider rejected the request (HTTP {$status}).",
        }, $status);
    }

    public static function emptyResponse(): EmptyAiResponseException
    {
        return new EmptyAiResponseException('The provider returned an empty response.');
    }

    public static function unreachable(): self
    {
        return new self('Could not reach the provider. Check your connection and try again.');
    }
}
