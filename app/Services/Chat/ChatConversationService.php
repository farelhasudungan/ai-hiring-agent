<?php

namespace App\Services\Chat;

use App\Models\ChatLog;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Single source of truth for chat conversation orchestration shared by the
 * JSON API (ChatAiController) and the browser workspace.
 */
class ChatConversationService
{
    /**
     * System prompt injected server-side; never taken from request input.
     */
    public function systemPrompt(): string
    {
        return (string) config(
            'llm.system_prompt',
            'Anda adalah asisten rekrutmen (HR) Roku. Bantu menganalisis CV, menilai kandidat, dan menyusun rekomendasi perekrutan secara objektif. Keputusan akhir tetap berada di tangan manusia dan rekomendasi ini bersifat advisory.'
        );
    }

    /**
     * Build the provider message payload: system prompt, bounded history, then the new prompt.
     *
     * @return array<int, array{role: string, content: string}>
     */
    public function buildMessages(User $user, string $prompt, ?int $sessionId): array
    {
        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt()],
        ];

        if ($sessionId !== null) {
            $limit = (int) config('llm.context_history_limit', 6);
            $history = ChatMessage::query()
                ->whereHas('chatSession', fn ($query) => $query->whereBelongsTo($user))
                ->where('chat_session_id', $sessionId)
                ->whereIn('role', ['user', 'assistant'])
                ->orderByDesc('id')
                ->limit($limit)
                ->get()
                ->reverse()
                ->values();

            foreach ($history as $message) {
                $messages[] = [
                    'role' => $message->role,
                    'content' => $message->content,
                ];
            }
        }

        $messages[] = [
            'role' => 'user',
            'content' => $prompt,
        ];

        return $messages;
    }

    /**
     * Persist the user message, assistant message, and chat log in a single transaction.
     *
     * @param  array{
     *     content: string,
     *     model: string,
     *     provider: string,
     *     prompt_tokens: ?int,
     *     completion_tokens: ?int,
     *     total_tokens: ?int
     * }  $llmResult
     */
    public function persist(User $user, string $prompt, ?int $sessionId, array $llmResult): ChatLog
    {
        return DB::transaction(function () use ($user, $prompt, $sessionId, $llmResult): ChatLog {
            $session = $this->resolveSession($user, $prompt, $sessionId);

            $session->messages()->create([
                'role' => 'user',
                'content' => $prompt,
            ]);

            $session->messages()->create([
                'role' => 'assistant',
                'content' => $llmResult['content'],
            ]);

            return $session->chatLogs()->create([
                'user_id' => $user->id,
                'prompt' => $prompt,
                'ai_response' => $llmResult['content'],
                'model_used' => $llmResult['model'],
                'provider' => $llmResult['provider'],
                'prompt_tokens' => $llmResult['prompt_tokens'],
                'completion_tokens' => $llmResult['completion_tokens'],
                'total_tokens' => $llmResult['total_tokens'],
            ]);
        });
    }

    /**
     * Resume the user's own session or start a new one titled from the prompt.
     */
    public function resolveSession(User $user, string $prompt, ?int $sessionId): ChatSession
    {
        if ($sessionId !== null) {
            return ChatSession::query()
                ->whereBelongsTo($user)
                ->findOrFail($sessionId);
        }

        return ChatSession::create([
            'user_id' => $user->id,
            'title' => Str::limit(trim($prompt), 50, '...'),
        ]);
    }
}
