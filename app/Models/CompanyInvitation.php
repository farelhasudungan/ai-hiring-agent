<?php

namespace App\Models;

use Database\Factories\CompanyInvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Hidden(['token_hash'])]
class CompanyInvitation extends Model
{
    /** @use HasFactory<CompanyInvitationFactory> */
    use HasFactory;

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function canBeAcceptedBy(string $email): bool
    {
        return $this->accepted_at === null
            && $this->expires_at?->isFuture()
            && Str::lower($this->email) === Str::lower($email)
            && in_array($this->role, ['hr', 'interviewer', 'viewer'], true);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'created_by' => 'integer',
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }
}
