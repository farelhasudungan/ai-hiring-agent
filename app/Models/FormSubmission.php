<?php

namespace App\Models;

use Database\Factories\FormSubmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'form_id',
    'job_posting_id',
    'candidate_id',
    'application_id',
    'status',
    'schema_snapshot',
    'meta',
    'submitted_at',
])]
class FormSubmission extends Model
{
    /** @use HasFactory<FormSubmissionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'schema_snapshot' => 'array',
            'meta' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Form, $this>
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * @return BelongsTo<JobPosting, $this>
     */
    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    /**
     * @return BelongsTo<Candidate, $this>
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return HasMany<FormAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(FormAnswer::class);
    }

    /**
     * @return HasMany<AiScreening, $this>
     */
    public function aiScreenings(): HasMany
    {
        return $this->hasMany(AiScreening::class);
    }

    /**
     * Get the best available name for the person who submitted the form.
     */
    public function respondentName(): string
    {
        if ($this->candidate?->full_name) {
            return $this->candidate->full_name;
        }

        $storedName = $this->meta['respondent_name'] ?? null;

        if (is_string($storedName) && trim($storedName) !== '') {
            return trim($storedName);
        }

        $nameAnswer = $this->answers->first(function (FormAnswer $answer): bool {
            $field = $answer->field;

            return $field !== null
                && ($field->maps_to === 'candidate.full_name'
                    || in_array(strtolower($field->field_key), ['full_name', 'name'], true));
        }) ?? $this->answers->first(function (FormAnswer $answer): bool {
            $field = $answer->field;

            return $field !== null && str_contains(strtolower($field->field_key), 'name');
        });

        $value = $nameAnswer?->value;

        if (is_scalar($value) && trim((string) $value) !== '') {
            return trim((string) $value);
        }

        return 'Unnamed applicant';
    }

    /**
     * Get the best available email for the person who submitted the form.
     */
    public function respondentEmail(): ?string
    {
        return $this->candidate?->email
            ?? (is_string($this->meta['respondent_email'] ?? null) ? $this->meta['respondent_email'] : null);
    }
}
