<?php

namespace App\Services\Screening;

use App\Services\Screening\Data\ScreeningResult;
use App\Services\Screening\Exceptions\InvalidScreeningResponseException;

class ScreeningResultParser
{
    /**
     * @var list<string>
     */
    public const RECOMMENDATIONS = ['strong_match', 'shortlist', 'review', 'reject'];

    /**
     * Pulls the JSON object out of the AI text (it may be wrapped in a code
     * fence or surrounded by prose) and validates the five core fields.
     *
     * @throws InvalidScreeningResponseException
     */
    public function parse(string $text): ScreeningResult
    {
        $data = $this->decode($text);

        $score = $data['score'] ?? null;

        if (is_string($score) && is_numeric($score)) {
            $score = (int) $score;
        }

        if (! is_int($score) || $score < 0 || $score > 100) {
            throw new InvalidScreeningResponseException('The AI score must be a whole number from 0 to 100.');
        }

        $recommendation = $data['recommendation'] ?? null;

        if (! is_string($recommendation) || ! in_array($recommendation, self::RECOMMENDATIONS, true)) {
            throw new InvalidScreeningResponseException('The AI recommendation is missing or not recognised.');
        }

        $summary = $data['summary'] ?? null;

        if (! is_string($summary) || trim($summary) === '') {
            throw new InvalidScreeningResponseException('The AI summary is missing.');
        }

        $candidate = is_array($data['candidate'] ?? null) ? $data['candidate'] : [];

        return new ScreeningResult(
            score: $score,
            recommendation: $recommendation,
            summary: trim($summary),
            strengths: $this->stringList($data, 'strengths', required: true),
            gaps: $this->stringList($data, 'gaps', required: true),
            matchedSkills: $this->stringList($data, 'matched_skills'),
            missingSkills: $this->stringList($data, 'missing_skills'),
            riskFlags: $this->stringList($data, 'risk_flags'),
            candidate: [
                'full_name' => $this->nullableString($candidate['full_name'] ?? null),
                'email' => $this->nullableString($candidate['email'] ?? null),
                'phone' => $this->nullableString($candidate['phone'] ?? null),
            ],
            payload: $data,
        );
    }

    /**
     * @return array<string, mixed>
     *
     * @throws InvalidScreeningResponseException
     */
    private function decode(string $text): array
    {
        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start === false || $end === false || $end < $start) {
            throw new InvalidScreeningResponseException('The AI reply did not contain a JSON result.');
        }

        $data = json_decode(substr($text, $start, $end - $start + 1), true);

        if (! is_array($data)) {
            throw new InvalidScreeningResponseException('The AI reply contained invalid JSON.');
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     *
     * @throws InvalidScreeningResponseException
     */
    private function stringList(array $data, string $key, bool $required = false): array
    {
        $value = $data[$key] ?? null;

        if (! is_array($value)) {
            if ($required) {
                throw new InvalidScreeningResponseException("The AI field \"{$key}\" must be a list.");
            }

            return [];
        }

        return array_values(array_filter(
            array_map(fn (mixed $item): string => is_scalar($item) ? trim((string) $item) : '', $value),
            fn (string $item): bool => $item !== '',
        ));
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
