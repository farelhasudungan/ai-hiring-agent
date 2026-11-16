<?php

namespace App\Models;

use Database\Factories\AiScreeningFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'application_id',
    'job_id',
    'document_id',
    'model_name',
    'status',
    'prompt_text',
    'input_snapshot',
    'raw_response',
    'parsed_response',
    'match_score',
    'recommendation',
    'summary_path',
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
])]
class AiScreening extends Model
{
    /** @use HasFactory<AiScreeningFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'input_snapshot' => 'array',
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
    public function job(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class, 'job_id');
    }

    /**
     * @return BelongsTo<ApplicationDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(ApplicationDocument::class, 'document_id');
    }

    /**
     * The path on the local disk where this screening's Markdown summary is stored.
     */
    public function summaryPathForStorage(): string
    {
        return "ai-screenings/{$this->application_id}/{$this->id}.md";
    }

    /**
     * Read the Markdown summary from storage, if one has been saved.
     */
    public function summaryMarkdown(): ?string
    {
        if ($this->summary_path === null || ! Storage::disk('local')->exists($this->summary_path)) {
            return null;
        }

        return Storage::disk('local')->get($this->summary_path);
    }
}
