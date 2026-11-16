<?php

namespace App\Services\Chat;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Company;
use App\Services\Chat\Data\CandidateSearchFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Finds the candidates of one company that match the AI-derived filters. The
 * company is always enforced here and never comes from the AI.
 */
class CandidateRetriever
{
    public const RESULT_LIMIT = 8;

    private const POOL_SIZE = 40;

    /**
     * @return Collection<int, Application>
     */
    public function search(Company $company, CandidateSearchFilters $filters, ?int $jobId = null): Collection
    {
        $jobId ??= $filters->jobId;

        $pool = Application::query()
            ->where('company_id', $company->id)
            ->when($jobId !== null, fn (Builder $query) => $query->where('job_id', $jobId))
            ->when($filters->minScore !== null, fn (Builder $query) => $query->where('latest_match_score', '>=', $filters->minScore))
            ->when($filters->minExperienceYears !== null || $filters->recommendations !== [], fn (Builder $query) => $query->whereHas('aiScreenings', function (Builder $screenings) use ($filters): void {
                $screenings->where('status', 'success')
                    ->when($filters->minExperienceYears !== null, fn (Builder $q) => $q->where('experience_years', '>=', $filters->minExperienceYears))
                    ->when($filters->recommendations !== [], fn (Builder $q) => $q->whereIn('recommendation', $filters->recommendations));
            }))
            ->when($filters->terms !== [], fn (Builder $query) => $query->whereHas('documents', function (Builder $documents) use ($filters): void {
                $documents->where('parsing_status', 'success')->where(fn (Builder $q) => $this->matchAnyTerm($q, $filters->terms));
            }))
            ->when($filters->terms === [], fn (Builder $query) => $query->whereHas('documents', fn (Builder $documents) => $documents->where('parsing_status', 'success')))
            ->with([
                'job:id,title',
                'documents' => fn ($documents) => $documents->where('parsing_status', 'success')->latest('id'),
                'aiScreenings' => fn ($screenings) => $screenings->where('status', 'success')->latest('id'),
            ])
            ->orderByRaw('latest_match_score IS NULL')
            ->orderByDesc('latest_match_score')
            ->limit(self::POOL_SIZE)
            ->get();

        return $pool
            ->sortByDesc(fn (Application $application): int => $this->termHits($application, $filters->terms))
            ->take(self::RESULT_LIMIT)
            ->values();
    }

    /**
     * @param  list<string>  $terms
     */
    private function matchAnyTerm(Builder $query, array $terms): void
    {
        foreach ($terms as $term) {
            if (DB::getDriverName() === 'pgsql' && preg_match('/^[\p{L}\p{N} ]+$/u', $term) === 1) {
                $query->orWhereRaw("to_tsvector('simple', coalesce(parsed_text, '')) @@ phraseto_tsquery('simple', ?)", [$term]);

                continue;
            }

            $query->orWhere('parsed_text', DB::getDriverName() === 'pgsql' ? 'ilike' : 'like', '%'.addcslashes($term, '%_\\').'%');
        }
    }

    /**
     * @param  list<string>  $terms
     */
    private function termHits(Application $application, array $terms): int
    {
        /** @var ApplicationDocument|null $document */
        $document = $application->documents->first();
        $text = mb_strtolower((string) $document?->parsed_text);

        return count(array_filter($terms, fn (string $term): bool => str_contains($text, mb_strtolower($term))));
    }
}
