<?php

namespace App\Models;

use Database\Factories\AiProviderSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['company_id', 'provider', 'model', 'api_key'])]
#[Hidden(['api_key'])]
class AiProviderSetting extends Model
{
    /** @use HasFactory<AiProviderSettingFactory> */
    use HasFactory;

    /**
     * Placeholder shown in the form instead of the real key. Submitting it
     * unchanged means "keep the stored key".
     */
    public const KEY_MASK = '••••••••••••';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
