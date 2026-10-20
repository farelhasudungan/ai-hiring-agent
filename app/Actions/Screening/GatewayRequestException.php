<?php

namespace App\Actions\Screening;

use RuntimeException;

class GatewayRequestException extends RuntimeException
{
    public function __construct(string $message, public readonly ?int $httpStatus, public readonly int $durationMs, public readonly ?int $providerErrorCode = null)
    {
        parent::__construct($message);
    }

    /** @return array<string, array<string, int|null>> */
    public function context(): array
    {
        // Never attach the HTTP exception: it can carry authorization headers or CV content.
        $context = ['http_status' => $this->httpStatus, 'duration_ms' => $this->durationMs];
        if ($this->providerErrorCode !== null) {
            $context['provider_error_code'] = $this->providerErrorCode;
        }

        return ['ai_gateway' => $context];
    }
}
