<?php

namespace App\Services\Chat\Tools;

use App\Models\AiScreening;
use App\Models\Application;
use App\Models\JobPosting;
use App\Services\Ai\Data\AiTool;
use App\Services\Chat\CandidateRetriever;
use App\Services\Chat\CandidateSearchFilterParser;
use Illuminate\Database\Eloquent\Builder;

class CountCandidatesTool implements ChatTool
{
    private const GROUPS = ['job', 'status', 'recommendation'];

    public function __construct(
        private CandidateRetriever $retriever,
        private CandidateSearchFilterParser $parser,
    ) {}

    public function definition(): AiTool
    {
        return new AiTool(
            'count_candidates',
            'Count candidates that match the criteria, optionally grouped. Use it for "how many" questions instead of listing candidates.',
            [
                'type' => 'object',
                'properties' => [
                    'skills' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'keywords' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'min_experience_years' => ['type' => 'integer'],
                    'min_score' => ['type' => 'integer', 'description' => 'Minimum AI match score from 0 to 100.'],
                    'recommendations' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['strong_match', 'shortlist', 'review', 'reject']]],
                    'job_id' => ['type' => 'integer'],
                    'group_by' => ['type' => 'string', 'enum' => self::GROUPS, 'description' => 'Split the count by job posting, application status or AI recommendation.'],
                ],
            ],
        );
    }

    public function label(): string
    {
        return 'Counting candidates…';
    }

    public function execute(ChatToolContext $context, array $arguments): ChatToolResult
    {
        $groupBy = $arguments['group_by'] ?? null;

        if ($groupBy !== null && ! in_array($groupBy, self::GROUPS, true)) {
            return ChatToolResult::error('group_by must be one of: '.implode(', ', self::GROUPS).'.');
        }

        $filters = $this->parser->fromArray($arguments, $context->company->jobPostings()->pluck('id')->all());
        $query = $this->retriever->query($context->company, $filters, $context->lockedJobId);

        $data = ['applied_filters' => $filters->toArray(), 'total' => (clone $query)->count()];

        if ($groupBy !== null) {
            $data['group_by'] = $groupBy;
            $data['groups'] = match ($groupBy) {
                'job' => $this->byJob($query),
                'status' => $this->byStatus($query),
                default => $this->byRecommendation($query),
            };
        }

        return new ChatToolResult($data);
    }

    /**
     * @param  Builder<Application>  $query
     * @return array<string, int>
     */
    private function byJob(Builder $query): array
    {
        $counts = (clone $query)->selectRaw('job_id, count(*) as total')->groupBy('job_id')->pluck('total', 'job_id');
        $titles = JobPosting::query()->whereIn('id', $counts->keys())->pluck('title', 'id');

        return $counts->mapWithKeys(fn (mixed $total, mixed $jobId): array => [(string) ($titles[$jobId] ?? "job {$jobId}") => (int) $total])->all();
    }

    /**
     * @param  Builder<Application>  $query
     * @return array<string, int>
     */
    private function byStatus(Builder $query): array
    {
        return (clone $query)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->map(fn (mixed $total): int => (int) $total)->all();
    }

    /**
     * Counts the latest successful screening of each matching candidate.
     *
     * @param  Builder<Application>  $query
     * @return array<string, int>
     */
    private function byRecommendation(Builder $query): array
    {
        $applicationIds = (clone $query)->select('applications.id');

        $latestScreeningIds = AiScreening::query()
            ->selectRaw('max(id)')
            ->where('status', 'success')
            ->whereIn('application_id', $applicationIds)
            ->groupBy('application_id');

        $groups = AiScreening::query()
            ->whereIn('id', $latestScreeningIds)
            ->selectRaw('recommendation, count(*) as total')
            ->groupBy('recommendation')
            ->pluck('total', 'recommendation')
            ->map(fn (mixed $total): int => (int) $total);

        $unscreened = (clone $query)->count() - $groups->sum();

        return $groups->all() + ($unscreened > 0 ? ['not_screened' => $unscreened] : []);
    }
}
