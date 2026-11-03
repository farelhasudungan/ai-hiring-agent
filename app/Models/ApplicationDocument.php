<?php

namespace App\Models;

use Database\Factories\ApplicationDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'application_id',
    'type',
    'file_name',
    'file_path',
    'mime_type',
    'size_bytes',
    'parsing_status',
    'parsed_text',
    'error_message',
])]
class ApplicationDocument extends Model
{
    /** @use HasFactory<ApplicationDocumentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
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
     * @return HasMany<AiScreening, $this>
     */
    public function aiScreenings(): HasMany
    {
        return $this->hasMany(AiScreening::class, 'document_id');
    }

    /**
     * @return HasMany<FormAnswerFile, $this>
     */
    public function formAnswerFiles(): HasMany
    {
        return $this->hasMany(FormAnswerFile::class, 'application_document_id');
    }
}
