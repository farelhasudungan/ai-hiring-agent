<?php

namespace App\Services\Chat;

use App\Models\Application;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\Ai\AiService;
use App\Services\Ai\Exceptions\AiProviderException;
use Generator;

/**
 * Answers a recruiter's question in three steps: the AI turns the question
 * into filters, SQL finds the candidates, then the AI explains the shortlist.
 * Candidates always come from the database, never from the AI.
 */
class CandidateChatService
{
    public const FILTER_MAX_TOKENS = 400;

    public const ANSWER_MAX_TOKENS = 1200;

    public function __construct(
        private AiService $ai,
        private CandidateSearchFilterParser $filterParser,
        private CandidateRetriever $retriever,
        private CandidateChatPromptBuilder $prompts,
    ) {}

    /**
     * Yields events in order: `candidates` once, many `chunk`, then `done`.
     * The assistant message is stored only after the whole answer arrived.
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

        $jobTitles = $company->jobPostings()->orderBy('title')->limit(50)->pluck('title', 'id')->all();

        $filterText = $this->ai->complete(
            $company,
            $this->prompts->filterSystemPrompt(),
            $this->prompts->filterUserPrompt($question->content, $history, $jobTitles),
            self::FILTER_MAX_TOKENS,
        )->text;

        $filters = $this->filterParser->parse($filterText, array_keys($jobTitles));
        $candidates = $this->retriever->search($company, $filters, $conversation->job_id);

        yield ['event' => 'candidates', 'data' => ['candidates' => $candidates->map(fn (Application $application): array => $this->card($application))->all()]];

        $answer = '';

        foreach ($this->ai->stream(
            $company,
            $this->prompts->answerSystemPrompt(),
            $this->prompts->answerUserPrompt($question->content, $history, $candidates),
            self::ANSWER_MAX_TOKENS,
        ) as $chunk) {
            $answer .= $chunk;

            yield ['event' => 'chunk', 'data' => ['text' => $chunk]];
        }

        $conversation->messages()->create([
            'role' => ChatMessage::ROLE_ASSISTANT,
            'content' => $answer,
            'filters' => $filters->toArray(),
            'candidate_ids' => $candidates->pluck('id')->all(),
        ]);
        $conversation->touch();

        yield ['event' => 'done', 'data' => []];
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
