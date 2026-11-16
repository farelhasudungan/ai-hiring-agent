<?php

namespace App\Services\Chat\Tools;

use App\Models\Application;
use App\Services\Ai\Data\AiTool;
use App\Services\Chat\CandidateRetriever;
use App\Services\Chat\CandidateSearchFilterParser;

class SearchCandidatesTool implements ChatTool
{
    public const MAX_LIMIT = 10;

    private const EXCERPT_CHARACTERS = 300;

    public function __construct(
        private CandidateRetriever $retriever,
        private CandidateSearchFilterParser $parser,
    ) {}

    public function definition(): AiTool
    {
        return new AiTool(
            'search_candidates',
            'Find candidates of the company that match the criteria, best AI score first. Returns a short summary per candidate; use get_candidate_detail for more.',
            [
                'type' => 'object',
                'properties' => [
                    'skills' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Skills that should appear in the resume.'],
                    'keywords' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Other words that should appear in the resume (tools, industries, schools, cities).'],
                    'min_experience_years' => ['type' => 'integer', 'description' => 'Minimum years of experience found by the AI screening.'],
                    'min_score' => ['type' => 'integer', 'description' => 'Minimum AI match score from 0 to 100.'],
                    'recommendations' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['strong_match', 'shortlist', 'review', 'reject']]],
                    'job_id' => ['type' => 'integer', 'description' => 'Only candidates of this job posting id.'],
                    'limit' => ['type' => 'integer', 'description' => 'How many candidates to return, at most '.self::MAX_LIMIT.'.'],
                ],
            ],
        );
    }

    public function label(): string
    {
        return 'Searching candidates…';
    }

    public function execute(ChatToolContext $context, array $arguments): ChatToolResult
    {
        $filters = $this->parser->fromArray($arguments, $context->company->jobPostings()->pluck('id')->all());
        $limit = max(1, min(self::MAX_LIMIT, is_numeric($arguments['limit'] ?? null) ? (int) $arguments['limit'] : CandidateRetriever::RESULT_LIMIT));

        $candidates = $this->retriever->search($context->company, $filters, $context->lockedJobId, $limit);

        return new ChatToolResult(
            [
                'applied_filters' => $filters->toArray(),
                'returned' => $candidates->count(),
                'candidates' => $candidates->map(fn (Application $application): array => $this->summary($application))->all(),
            ],
            $candidates->pluck('id')->all(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(Application $application): array
    {
        $screening = $application->aiScreenings->first();

        return [
            'application_id' => $application->id,
            'name' => $application->full_name ?? 'Unknown candidate',
            'job' => $application->job?->title,
            'ai_score' => $application->latest_match_score,
            'recommendation' => $screening?->recommendation,
            'experience_years' => $screening?->experience_years,
            'matched_skills' => $screening?->matched_skills ?? [],
            'missing_skills' => $screening?->missing_skills ?? [],
            'risk_flags' => $screening?->risk_flags ?? [],
            'screened' => $screening !== null,
            'resume_excerpt' => mb_substr((string) $application->documents->first()?->parsed_text, 0, self::EXCERPT_CHARACTERS),
        ];
    }
}
