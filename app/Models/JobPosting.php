<?php

namespace App\Models;

use Database\Factories\JobPostingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'title',
    'department',
    'location',
    'status',
    'description',
    'requirements',
    'required_skills',
    'nice_to_have',
    'min_experience_years',
])]
class JobPosting extends Model
{
    /** @use HasFactory<JobPostingFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    public const STATUSES = ['draft', 'open', 'closed'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'required_skills' => 'array',
            'nice_to_have' => 'array',
            'min_experience_years' => 'integer',
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
     * @return HasMany<Application, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'job_id');
    }

    /**
     * Applications whose resume is parsed and that still wait for (or failed)
     * an AI screening.
     *
     * @return HasMany<Application, $this>
     */
    public function screenableApplications(): HasMany
    {
        return $this->applications()
            ->whereIn('status', Application::SCREENABLE_STATUSES)
            ->whereHas('documents', fn ($query) => $query->where('parsing_status', 'success')->whereNotNull('parsed_text'));
    }

    /**
     * @return HasMany<AiScreening, $this>
     */
    public function aiScreenings(): HasMany
    {
        return $this->hasMany(AiScreening::class, 'job_id');
    }
}
