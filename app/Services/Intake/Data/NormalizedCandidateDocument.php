<?php

namespace App\Services\Intake\Data;

/**
 * A candidate document in the common shape every intake adapter must produce.
 */
final readonly class NormalizedCandidateDocument
{
    public function __construct(
        public string $fileName,
        public ?string $originName,
        public string $storedPath,
        public string $mimeType,
        public int $sizeBytes,
        public string $contentHash,
    ) {}
}
