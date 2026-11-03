<?php

namespace App\Models;

use Database\Factories\FormAnswerFileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'form_answer_id',
    'application_document_id',
    'file_name',
    'file_path',
    'mime_type',
    'size_bytes',
])]
class FormAnswerFile extends Model
{
    /** @use HasFactory<FormAnswerFileFactory> */
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
     * @return BelongsTo<FormAnswer, $this>
     */
    public function answer(): BelongsTo
    {
        return $this->belongsTo(FormAnswer::class, 'form_answer_id');
    }

    /**
     * @return BelongsTo<ApplicationDocument, $this>
     */
    public function applicationDocument(): BelongsTo
    {
        return $this->belongsTo(ApplicationDocument::class, 'application_document_id');
    }
}
