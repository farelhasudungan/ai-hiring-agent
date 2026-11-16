<?php

namespace App\Services\Chat\Tools;

use App\Services\Ai\Data\AiTool;

class ChatToolRegistry
{
    /**
     * @var array<string, ChatTool>
     */
    private array $tools = [];

    public function __construct(SearchCandidatesTool $search, GetCandidateDetailTool $detail, CountCandidatesTool $count)
    {
        foreach ([$search, $detail, $count] as $tool) {
            $this->tools[$tool->definition()->name] = $tool;
        }
    }

    /**
     * @return list<AiTool>
     */
    public function definitions(): array
    {
        return array_values(array_map(fn (ChatTool $tool): AiTool => $tool->definition(), $this->tools));
    }

    public function find(string $name): ?ChatTool
    {
        return $this->tools[$name] ?? null;
    }
}
