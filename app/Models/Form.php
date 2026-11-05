<?php

namespace App\Models;

use Database\Factories\FormFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'created_by',
    'title',
    'description',
    'status',
    'public_token',
    'version',
    'settings',
    'published_at',
    'closes_at',
])]
class Form extends Model
{
    /** @use HasFactory<FormFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'settings' => 'array',
            'published_at' => 'datetime',
            'closes_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<FormSection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(FormSection::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<FormField, $this>
     */
    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<FormSubmission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    /**
     * @return HasMany<JobPosting, $this>
     */
    public function jobPostings(): HasMany
    {
        return $this->hasMany(JobPosting::class);
    }

    /**
     * Determine whether the form can accept new public responses.
     */
    public function isAcceptingResponses(): bool
    {
        return $this->status === 'published'
            && ($this->closes_at === null || $this->closes_at->isFuture());
    }

    /**
     * Build the public application URL, optionally scoped to a job posting.
     */
    public function publicUrl(?JobPosting $jobPosting = null): ?string
    {
        if (blank($this->public_token)) {
            return null;
        }

        $parameters = ['token' => $this->public_token];

        if ($jobPosting !== null) {
            $parameters['job'] = $jobPosting->id;
        }

        return route('public.apply.show', $parameters);
    }
}
