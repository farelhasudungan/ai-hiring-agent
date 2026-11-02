<?php

namespace App\Models;

use Database\Factories\ChatMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['chat_session_id', 'role', 'content'])]
class ChatMessage extends Model
{
    /** @use HasFactory<ChatMessageFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $touches = ['chatSession'];

    public function chatSession(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class);
    }

    public function toolExecutionLogs(): HasMany
    {
        return $this->hasMany(ToolExecutionLog::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'chat_session_id' => 'integer',
        ];
    }
}
