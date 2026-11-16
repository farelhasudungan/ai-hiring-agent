<?php

namespace App\Models;

use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'job_id',
    'full_name',
    'email',
    'phone',
    'linkedin_url',
    'portfolio_url',
    'source',
    'status',
    'latest_match_score',
    'latest_ai_status',
    'latest_ai_at',
    'submitted_at',
    'reviewed_at',
    'reviewed_by',
    'notes',
])]
class Application extends Model
{
    /** @use HasFactory<ApplicationFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latest_match_score' => 'integer',
            'latest_ai_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<JobPosting, $this>
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class, 'job_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return HasMany<ApplicationDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    /**
     * @return HasMany<AiScreening, $this>
     */
    public function aiScreenings(): HasMany
    {
        return $this->hasMany(AiScreening::class);
    }

    /**
     * @return HasMany<ActivityLog, $this>
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
