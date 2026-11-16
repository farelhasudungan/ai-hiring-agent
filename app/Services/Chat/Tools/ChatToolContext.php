<?php

namespace App\Services\Chat\Tools;

use App\Models\Company;

/**
 * What a tool may touch. Filled by the server from the conversation, never
 * from arguments the AI sends.
 */
final readonly class ChatToolContext
{
    public function __construct(
        public Company $company,
        public ?int $lockedJobId = null,
    ) {}
}
