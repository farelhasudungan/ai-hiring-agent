<?php

namespace App\Services\Chat;

use App\Models\Application;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\Ai\AiService;
use App\Services\Ai\Data\ChatTurn;
use App\Services\Ai\Exceptions\AiProviderException;
use App\Services\Chat\Tools\ChatToolContext;
use App\Services\Chat\Tools\ChatToolRegistry;
use Generator;

/**
 * Answers a recruiter's question with an agent loop: the AI calls tools to
 * read candidate data, then the final answer is streamed. Candidates shown as
 * cards come from tool results only, never from what the AI writes.
 */
class CandidateChatService
{
    public const MAX_STEPS = 5;

    public const STEP_MAX_TOKENS = 1500;

    public const ANSWER_MAX_TOKENS = 1200;

    public const MAX_CARDS = 8;

    public const MAX_TOOL_RESULT_CHARACTERS = 12000;

    public function __construct(
        private AiService $ai,
        private ChatToolRegistry $tools,
        private CandidateChatPromptBuilder $prompts,
    ) {}

    /**
     * Yields events in order: `tool` per tool call, `candidates` once, many
     * `chunk`, then `done`. The assistant message is stored only after the
     * whole answer arrived.
     *
     * @return Generator<int, array{event: string, data: array<string, mixed>}>
     *
     * @throws AiProviderException
     */
    public function respond(ChatConversation $conversation, ChatMessage $question): Generator
    {
        $company = $conversation->company;
        $history = $conversation->messages()
            ->where('id', '<', $question->id)
            ->latest('id')
            ->limit(CandidateChatPromptBuilder::HISTORY_MESSAGES)
            ->get()
            ->reverse()
            ->values();

        $turns = $history
            ->map(fn (ChatMessage $message): ChatTurn => $message->role === ChatMessage::ROLE_ASSISTANT
                ? ChatTurn::assistant($message->content)
                : ChatTurn::user($message->content))
            ->push(ChatTurn::user($question->content))
            ->all();

        $context = new ChatToolContext($company, $conversation->job_id);
        $system = $this->prompts->agentSystemPrompt($conversation->job?->title);

        $gathered = [];
        $toolLog = [];
        $applicationIds = [];
        $directAnswer = null;

        for ($step = 0; $step < self::MAX_STEPS; $step++) {
            $response = $this->ai->chat($company, $system, $turns, $this->tools->definitions(), self::STEP_MAX_TOKENS);

            if (! $response->wantsTools()) {
                $directAnswer = $response->text;

                break;
            }

            $turns[] = $response->toTurn();

            foreach ($response->toolCalls as $call) {
                $tool = $this->tools->find($call->name);

                yield ['event' => 'tool', 'data' => ['name' => $call->name, 'label' => $tool?->label() ?? 'Working…']];

                $result = $tool?->execute($context, $call->arguments)
                    ?? Tools\ChatToolResult::error("Unknown tool {$call->name}.");

                $encoded = mb_substr(json_encode($result->data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR) ?: '{}', 0, self::MAX_TOOL_RESULT_CHARACTERS);

                $turns[] = ChatTurn::toolResult($call->id, $call->name, $encoded);
                $gathered[] = ['tool' => $call->name, 'arguments' => $call->arguments, 'result' => $encoded];
                $toolLog[] = ['name' => $call->name, 'arguments' => $call->arguments];
                $applicationIds = [...$applicationIds, ...$result->applicationIds];
            }
        }

        $applicationIds = array_slice(array_values(array_unique($applicationIds)), 0, self::MAX_CARDS);

        yield ['event' => 'candidates', 'data' => ['candidates' => $this->cards($conversation, $applicationIds)]];

        $answer = '';

        // A direct reply is already complete, so it is replayed in pieces instead of asking again.
        $chunks = $directAnswer !== null && $gathered === []
            ? mb_str_split($directAnswer, 24)
            : $this->ai->stream(
                $company,
                $this->prompts->answerSystemPrompt(),
                $this->prompts->answerUserPrompt($question->content, $history, $gathered),
                self::ANSWER_MAX_TOKENS,
            );

        foreach ($chunks as $chunk) {
            $answer .= $chunk;

            yield ['event' => 'chunk', 'data' => ['text' => $chunk]];
        }

        $conversation->messages()->create([
            'role' => ChatMessage::ROLE_ASSISTANT,
            'content' => $answer,
            'tool_calls' => $toolLog,
            'candidate_ids' => $applicationIds,
        ]);
        $conversation->touch();

        yield ['event' => 'done', 'data' => []];
    }

    /**
     * @param  list<int>  $applicationIds
     * @return list<array<string, mixed>>
     */
    private function cards(ChatConversation $conversation, array $applicationIds): array
    {
        if ($applicationIds === []) {
            return [];
        }

        $applications = Application::query()
            ->where('company_id', $conversation->company_id)
            ->whereKey($applicationIds)
            ->with(['job:id,title', 'aiScreenings' => fn ($screenings) => $screenings->where('status', 'success')->latest('id')])
            ->get()
            ->keyBy('id');

        return collect($applicationIds)
            ->map(fn (int $id) => $applications->get($id))
            ->filter()
            ->map(fn (Application $application): array => $this->card($application))
            ->values()
            ->all();
    }

    /**
     * @return array{id: int, name: string, job: ?string, score: ?int, recommendation: ?string, matched_skills: list<string>, url: string}
     */
    public function card(Application $application): array
    {
        $screening = $application->aiScreenings->first();

        return [
            'id' => $application->id,
            'name' => $application->full_name ?? 'Unknown candidate',
            'job' => $application->job?->title,
            'score' => $application->latest_match_score,
            'recommendation' => $screening?->recommendation,
            'matched_skills' => array_values($screening?->matched_skills ?? []),
            'url' => route('job-postings.candidates.show', [$application->job_id, $application]),
        ];
    }
}
