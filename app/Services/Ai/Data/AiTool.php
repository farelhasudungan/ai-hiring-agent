<?php

namespace App\Services\Ai\Data;

/**
 * A function the AI may call. `parameters` is a JSON Schema object limited to
 * the subset every vendor accepts: type, description, properties, items, enum.
 */
final readonly class AiTool
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    public function __construct(
        public string $name,
        public string $description,
        public array $parameters,
    ) {}
}
