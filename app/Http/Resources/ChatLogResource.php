<?php

namespace App\Http\Resources;

use App\Models\ChatLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ChatLog
 */
class ChatLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'session_id' => (int) $this->chat_session_id,
            'model_used' => (string) $this->model_used,
            'prompt' => (string) $this->prompt,
            'ai_response' => (string) $this->ai_response,
            'timestamp' => $this->created_at?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'status' => 'success',
        ];
    }
}
