<?php

namespace App\Services\Ai\Data;

final readonly class AiToolResponse
{
    /**
     * @param  list<ToolCall>  $toolCalls
     * @param  list<array<string, mixed>>|null  $raw
     */
    public function __construct(
        public ?string $text,
        public array $toolCalls = [],
        public ?int $inputTokens = null,
        public ?int $outputTokens = null,
        public ?array $raw = null,
    ) {}

    public function wantsTools(): bool
    {
        return $this->toolCalls !== [];
    }

    public function toTurn(): ChatTurn
    {
        return ChatTurn::assistant($this->text, $this->toolCalls, $this->raw);
    }
}
