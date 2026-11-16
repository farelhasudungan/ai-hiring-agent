<?php

namespace App\Services\Chat\Data;

/**
 * Search criteria the AI derived from the recruiter's question. Every value is
 * sanitised by CandidateSearchFilterParser before it reaches a query.
 */
final readonly class CandidateSearchFilters
{
    /**
     * @param  list<string>  $terms  skills and keywords expected in the resume
     * @param  list<string>  $recommendations
     */
    public function __construct(
        public array $terms = [],
        public ?int $minExperienceYears = null,
        public ?int $minScore = null,
        public array $recommendations = [],
        public ?int $jobId = null,
    ) {}

    public function hasCriteria(): bool
    {
        return $this->terms !== []
            || $this->minExperienceYears !== null
            || $this->minScore !== null
            || $this->recommendations !== [];
    }

    /**
     * @return array{terms: list<string>, min_experience_years: ?int, min_score: ?int, recommendations: list<string>, job_id: ?int}
     */
    public function toArray(): array
    {
        return [
            'terms' => $this->terms,
            'min_experience_years' => $this->minExperienceYears,
            'min_score' => $this->minScore,
            'recommendations' => $this->recommendations,
            'job_id' => $this->jobId,
        ];
    }
}
