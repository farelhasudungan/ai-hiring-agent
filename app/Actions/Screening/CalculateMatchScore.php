<?php

namespace App\Actions\Screening;

use UnexpectedValueException;

class CalculateMatchScore
{
    public function normalize(string $skill, array $aliases): string
    {
        $skill = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $skill)));

        return $aliases[$skill] ?? $skill;
    }

    public function handle(array $data, array $snapshot): array
    {
        $job = $snapshot['job'];
        $settings = $snapshot['scoring'];
        $aliases = $settings['aliases'];
        $normalize = fn (array $skills): array => array_values(array_unique(array_filter(array_map(fn (string $skill): string => $this->normalize($skill, $aliases), $skills), fn (string $skill): bool => $skill !== '')));
        $candidate = $normalize(array_column($data['candidate_skills'], 'skill'));
        $required = $normalize($job['required_skills'] ?? []);
        $nice = $normalize($job['nice_to_have'] ?? []);
        $matched = array_values(array_intersect($required, $candidate));
        $missing = array_values(array_diff($required, $candidate));
        $niceMatched = array_values(array_intersect($nice, $candidate));
        $minimum = $job['min_experience_years'] ?? 0;
        $weights = $settings['weights'];
        $active = [
            'required' => count($required) > 0 ? $weights['required'] : 0,
            'experience' => $minimum > 0 ? $weights['experience'] : 0,
            'nice' => count($nice) > 0 ? $weights['nice'] : 0,
        ];
        $ratios = [
            'required' => count($required) ? count($matched) / count($required) : 0,
            'experience' => $minimum > 0 ? min(($data['experience_years'] ?? 0) / $minimum, 1) : 0,
            'nice' => count($nice) ? count($niceMatched) / count($nice) : 0,
        ];
        $components = [];
        $sum = array_sum($active);
        if ($sum === 0) {
            throw new UnexpectedValueException('Lowongan belum memiliki kriteria penilaian yang dapat digunakan.');
        }
        foreach ($active as $key => $weight) {
            $effective = $sum > 0 ? 100 * $weight / $sum : 0;
            $components[$key] = ['weight' => round($effective, 2), 'points' => round($effective * $ratios[$key], 2)];
        }
        $score = (int) round(array_sum(array_column($components, 'points')));
        $recommendation = 'reject';
        foreach ($settings['thresholds'] as $name => $threshold) {
            if ($score >= $threshold) {
                $recommendation = $name;
                break;
            }
        }

        return [
            'match_score' => $score, 'recommendation' => $recommendation,
            'candidate_skills' => $candidate, 'matched_skills' => $matched, 'missing_skills' => $missing,
            'score_breakdown' => [
                'version' => $settings['version'], 'components' => $components,
                'required_matched' => count($matched), 'required_total' => count($required),
                'nice_matched' => count($niceMatched), 'nice_total' => count($nice),
                'minimum_experience' => $minimum, 'experience_years' => $data['experience_years'],
            ],
        ];
    }
}
