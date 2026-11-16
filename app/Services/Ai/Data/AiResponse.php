<?php

namespace App\Services\Ai\Data;

final readonly class AiResponse
{
    public function __construct(
        public string $text,
        public string $model,
        public ?int $inputTokens = null,
        public ?int $outputTokens = null,
    ) {}
}
