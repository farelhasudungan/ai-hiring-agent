<?php

namespace App\Services\Ai\Data;

/**
 * One vendor-neutral message of a tool-using conversation. Each provider maps
 * it to its own wire format.
 */
final readonly class ChatTurn
{
    public const USER = 'user';

    public const ASSISTANT = 'assistant';

    public const TOOL = 'tool';

    /**
     * @param  list<ToolCall>  $toolCalls
     * @param  list<array<string, mixed>>|null  $raw  vendor parts to replay as they were received
     */
    private function __construct(
        public string $role,
        public ?string $content = null,
        public array $toolCalls = [],
        public ?string $toolCallId = null,
        public ?string $toolName = null,
        public ?array $raw = null,
    ) {}

    public static function user(string $content): self
    {
        return new self(self::USER, $content);
    }

    /**
     * @param  list<ToolCall>  $toolCalls
     * @param  list<array<string, mixed>>|null  $raw
     */
    public static function assistant(?string $content, array $toolCalls = [], ?array $raw = null): self
    {
        return new self(self::ASSISTANT, $content, $toolCalls, raw: $raw);
    }

    public static function toolResult(string $toolCallId, string $toolName, string $content): self
    {
        return new self(self::TOOL, $content, toolCallId: $toolCallId, toolName: $toolName);
    }
}
