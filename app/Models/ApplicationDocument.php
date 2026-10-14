<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApplicationDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'type',
        'file_name',
        'file_path',
        'mime_type',
        'size_bytes',
        'parsing_status',
        'parsed_text',
        'error_message',
    ];

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return HasMany<AiScreening, $this>
     */
    public function aiScreenings(): HasMany
    {
        return $this->hasMany(AiScreening::class, 'document_id');
    }
}
