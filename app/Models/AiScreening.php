<?php

namespace App\Models;

use Database\Factories\AiScreeningFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'application_id', 'job_id', 'document_id', 'model_name', 'status', 'prompt_text',
    'raw_response', 'parsed_response', 'match_score', 'recommendation', 'summary',
    'candidate_skills', 'matched_skills', 'missing_skills', 'experience_years',
    'education_level', 'risk_flags', 'error_message', 'started_at', 'completed_at', 'duration_ms',
])]
class AiScreening extends Model
{
    /** @use HasFactory<AiScreeningFactory> */
    use HasFactory;

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(ApplicationDocument::class, 'document_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'application_id' => 'integer',
            'job_id' => 'integer',
            'document_id' => 'integer',
            'parsed_response' => 'array',
            'match_score' => 'integer',
            'candidate_skills' => 'array',
            'matched_skills' => 'array',
            'missing_skills' => 'array',
            'experience_years' => 'integer',
            'risk_flags' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'duration_ms' => 'integer',
        ];
    }
}
