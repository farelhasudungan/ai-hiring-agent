<?php

namespace App\Models;

use Database\Factories\JobFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id', 'title', 'department', 'location', 'status', 'description',
    'requirements', 'required_skills', 'nice_to_have', 'min_experience_years',
])]
class Job extends Model
{
    /** @use HasFactory<JobFactory> */
    use HasFactory;

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function aiScreenings(): HasMany
    {
        return $this->hasMany(AiScreening::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'required_skills' => 'array',
            'nice_to_have' => 'array',
            'min_experience_years' => 'integer',
        ];
    }
}
