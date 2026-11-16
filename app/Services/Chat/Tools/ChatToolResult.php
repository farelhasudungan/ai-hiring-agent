<?php

namespace App\Services\Chat\Tools;

final readonly class ChatToolResult
{
    /**
     * @param  array<string, mixed>  $data  what the AI gets to read
     * @param  list<int>  $applicationIds  candidates to show as cards
     */
    public function __construct(
        public array $data,
        public array $applicationIds = [],
    ) {}

    public static function error(string $message): self
    {
        return new self(['error' => $message]);
    }
}
