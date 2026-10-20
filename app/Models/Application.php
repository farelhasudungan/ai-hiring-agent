<?php

namespace App\Models;

use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'company_id', 'candidate_id', 'job_id', 'source', 'status', 'latest_match_score',
    'latest_ai_status', 'latest_ai_at', 'submitted_at', 'reviewed_at', 'reviewed_by', 'notes',
])]
class Application extends Model
{
    /** @use HasFactory<ApplicationFactory> */
    use HasFactory;

    /** @return array<string, string> */
    public static function statusOptions(): array
    {
        return [
            'received' => 'Diterima',
            'queued' => 'Dalam antrean',
            'parsing' => 'Membaca dokumen',
            'analyzing' => 'Dianalisis',
            'scored' => 'Sudah dinilai',
            'failed' => 'Gagal',
            'shortlisted' => 'Lolos seleksi',
            'rejected' => 'Ditolak',
            'interview' => 'Wawancara',
            'hired' => 'Diterima bekerja',
            'archived' => 'Diarsipkan',
        ];
    }

    /** @return array<string, string> */
    public static function sourceOptions(): array
    {
        return ['form' => 'Formulir', 'gmail' => 'Gmail', 'manual' => 'Manual'];
    }

    public static function aiStatusOptions(): array
    {
        return ['queued' => 'Dalam antrean', 'running' => 'Diproses', 'success' => 'Selesai', 'failed' => 'Gagal', 'cancelled' => 'Dibatalkan'];
    }

    public function canStartScreening(): bool
    {
        return in_array($this->status, ['received', 'failed', 'scored'], true)
            && ! in_array($this->latest_ai_status, ['queued', 'running'], true);
    }

    public function canCancelScreening(): bool
    {
        return filled($this->processing_run_id)
            && in_array($this->status, ['queued', 'parsing', 'analyzing'], true)
            && in_array($this->latest_ai_status, ['queued', 'running'], true);
    }

    public function currentScreening(): HasOne
    {
        return $this->hasOne(AiScreening::class, 'run_id', 'processing_run_id');
    }

    #[Scope]
    protected function forCompany(Builder $query, ?int $companyId): Builder
    {
        return $query
            ->where('applications.company_id', $companyId)
            ->whereHas('candidate', fn (Builder $candidate): Builder => $candidate->where('company_id', $companyId))
            ->whereHas('job', fn (Builder $job): Builder => $job->where('company_id', $companyId));
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    public function aiScreenings(): HasMany
    {
        return $this->hasMany(AiScreening::class);
    }

    public function emailMessages(): HasMany
    {
        return $this->hasMany(EmailMessage::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'candidate_id' => 'integer',
            'job_id' => 'integer',
            'latest_match_score' => 'integer',
            'latest_ai_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'reviewed_by' => 'integer',
        ];
    }
}
