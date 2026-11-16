<?php

namespace App\Models;

use Database\Factories\ChatLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'chat_session_id',
    'user_id',
    'prompt',
    'ai_response',
    'model_used',
    'provider',
    'prompt_tokens',
    'completion_tokens',
    'total_tokens',
])]
class ChatLog extends Model
{
    /** @use HasFactory<ChatLogFactory> */
    use HasFactory;

    public function chatSession(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'chat_session_id' => 'integer',
            'user_id' => 'integer',
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
            'total_tokens' => 'integer',
        ];
    }
}
