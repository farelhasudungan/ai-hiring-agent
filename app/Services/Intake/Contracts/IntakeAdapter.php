<?php

namespace App\Services\Intake\Contracts;

use App\Services\Intake\Data\IntakeBatch;

/**
 * Turns a raw input source (PDF upload, Drive, email, sheet, ...) into normalized documents.
 */
interface IntakeAdapter
{
    /**
     * @param  array<int, mixed>  $inputs  Source specific inputs (e.g. uploaded files).
     */
    public function collect(array $inputs, int $companyId): IntakeBatch;
}
