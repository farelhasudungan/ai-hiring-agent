<?php

namespace App\Models;

use Database\Factories\ToolExecutionLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'chat_message_id', 'loggable_type', 'loggable_id', 'tool_name',
    'input', 'result', 'status', 'duration_ms', 'error_message',
])]
class ToolExecutionLog extends Model
{
    /** @use HasFactory<ToolExecutionLogFactory> */
    use HasFactory;

    public function chatMessage(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class);
    }

    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'chat_message_id' => 'integer',
            'loggable_id' => 'integer',
            'input' => 'array',
            'result' => 'array',
            'duration_ms' => 'integer',
        ];
    }
}
