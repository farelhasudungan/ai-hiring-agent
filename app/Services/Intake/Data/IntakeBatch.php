<?php

namespace App\Services\Intake\Data;

/**
 * Result of an adapter run: normalized documents plus the inputs that were rejected.
 */
final readonly class IntakeBatch
{
    /**
     * @param  list<NormalizedCandidateDocument>  $documents
     * @param  list<string>  $rejected  Human readable reasons, one per rejected input.
     */
    public function __construct(
        public array $documents,
        public array $rejected = [],
    ) {}
}
