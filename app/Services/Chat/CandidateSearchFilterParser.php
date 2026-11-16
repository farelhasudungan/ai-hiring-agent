<?php

namespace App\Services\Chat;

use App\Services\Chat\Data\CandidateSearchFilters;
use App\Services\Screening\ScreeningResultParser;

class CandidateSearchFilterParser
{
    public const MAX_TERMS = 10;

    public const MAX_TERM_LENGTH = 60;

    /**
     * Reads the JSON object out of the AI text and keeps only the known,
     * valid fields. Anything unusable yields an empty filter set instead of
     * an error, so a badly formatted reply never blocks the search.
     *
     * @param  list<int>  $allowedJobIds  job postings of the company the AI may pick from
     */
    public function parse(string $text, array $allowedJobIds): CandidateSearchFilters
    {
        return $this->fromArray($this->decode($text), $allowedJobIds);
    }

    /**
     * Same sanitising for arguments that already arrive as an array (tool calls).
     *
     * @param  array<string, mixed>  $data
     * @param  list<int>  $allowedJobIds
     */
    public function fromArray(array $data, array $allowedJobIds): CandidateSearchFilters
    {
        $terms = collect([...$this->strings($data['skills'] ?? []), ...$this->strings($data['keywords'] ?? [])])
            ->map(fn (string $term): string => trim(str_replace('"', '', $term)))
            ->filter(fn (string $term): bool => $term !== '' && mb_strlen($term) <= self::MAX_TERM_LENGTH)
            ->unique(fn (string $term): string => mb_strtolower($term))
            ->take(self::MAX_TERMS)
            ->values()
            ->all();

        $jobId = $this->integer($data['job_id'] ?? null, 1);

        return new CandidateSearchFilters(
            terms: $terms,
            minExperienceYears: $this->integer($data['min_experience_years'] ?? null, 0, 60),
            minScore: $this->integer($data['min_score'] ?? null, 0, 100),
            recommendations: array_values(array_intersect($this->strings($data['recommendations'] ?? []), ScreeningResultParser::RECOMMENDATIONS)),
            jobId: $jobId !== null && in_array($jobId, $allowedJobIds, true) ? $jobId : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $text): array
    {
        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start === false || $end === false || $end < $start) {
            return [];
        }

        $data = json_decode(substr($text, $start, $end - $start + 1), true);

        return is_array($data) ? $data : [];
    }

    /**
     * @return list<string>
     */
    private function strings(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, 'is_string'));
    }

    private function integer(mixed $value, int $min, ?int $max = null): ?int
    {
        if (is_string($value) && is_numeric($value)) {
            $value = (int) $value;
        }

        if (! is_int($value) || $value < $min || ($max !== null && $value > $max)) {
            return null;
        }

        return $value;
    }
}
