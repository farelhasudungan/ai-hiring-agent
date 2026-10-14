<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiScreening extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'job_posting_id',
        'document_id',
        'model_name',
        'status',
        'prompt_text',
        'raw_response',
        'parsed_response',
        'match_score',
        'recommendation',
        'summary',
        'candidate_skills',
        'matched_skills',
        'missing_skills',
        'experience_years',
        'education_level',
        'risk_flags',
        'error_message',
        'started_at',
        'completed_at',
        'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'parsed_response' => 'array',
            'candidate_skills' => 'array',
            'matched_skills' => 'array',
            'missing_skills' => 'array',
            'risk_flags' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<JobPosting, $this>
     */
    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    /**
     * @return BelongsTo<ApplicationDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(ApplicationDocument::class, 'document_id');
    }
}
