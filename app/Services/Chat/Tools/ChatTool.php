<?php

namespace App\Services\Chat\Tools;

use App\Services\Ai\Data\AiTool;

interface ChatTool
{
    public function definition(): AiTool;

    /**
     * Short progress text shown to the recruiter while the tool runs.
     */
    public function label(): string;

    /**
     * Arguments come from the AI and are untrusted: validate them and return
     * ChatToolResult::error() instead of throwing.
     *
     * @param  array<string, mixed>  $arguments
     */
    public function execute(ChatToolContext $context, array $arguments): ChatToolResult;
}
