<?php

namespace App\Services\Screening\Data;

/**
 * The validated outcome of one screening. The first five fields are always
 * present; the rest are optional extras the AI may add.
 */
final readonly class ScreeningResult
{
    /**
     * @param  list<string>  $strengths
     * @param  list<string>  $gaps
     * @param  list<string>  $matchedSkills
     * @param  list<string>  $missingSkills
     * @param  list<string>  $riskFlags
     * @param  array{full_name: ?string, email: ?string, phone: ?string}  $candidate
     * @param  array<string, mixed>  $payload  The full decoded JSON.
     */
    public function __construct(
        public int $score,
        public string $recommendation,
        public string $summary,
        public array $strengths,
        public array $gaps,
        public array $matchedSkills = [],
        public array $missingSkills = [],
        public array $riskFlags = [],
        public array $candidate = ['full_name' => null, 'email' => null, 'phone' => null],
        public array $payload = [],
    ) {}
}
