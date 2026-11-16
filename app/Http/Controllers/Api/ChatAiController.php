<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChatAiRequest;
use App\Http\Resources\ChatLogResource;
use App\Models\ChatLog;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\User;
use App\Services\Llm\Exceptions\LlmException;
use App\Services\Llm\LlmClientInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ChatAiController extends Controller
{
    public function __construct(
        protected LlmClientInterface $llmClient
    ) {
    }

    /**
     * Handle non-streaming chat request.
     */
    public function chat(ChatAiRequest $request): JsonResponse
    {
        $prompt = (string) $request->validated('prompt');
        $sessionId = $request->validated('session_id') ? (int) $request->validated('session_id') : null;
        $user = $request->user();

        $messages = $this->buildMessages($prompt, $sessionId);

        try {
            $llmResult = $this->llmClient->chat($messages);
        } catch (LlmException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 503);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem saat menghubungi layanan LLM.',
            ], 503);
        }

        $chatLog = DB::transaction(function () use ($user, $prompt, $sessionId, $llmResult): ChatLog {
            $session = $this->resolveChatSession($user, $prompt, $sessionId);

            ChatMessage::create([
                'chat_session_id' => $session->id,
                'role' => 'user',
                'content' => $prompt,
            ]);

            ChatMessage::create([
                'chat_session_id' => $session->id,
                'role' => 'assistant',
                'content' => $llmResult['content'],
            ]);

            return ChatLog::create([
                'chat_session_id' => $session->id,
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

        return (new ChatLogResource($chatLog))->response()->setStatusCode(200);
    }

    /**
     * Handle SSE streaming chat request.
     */
    public function stream(ChatAiRequest $request): StreamedResponse
    {
        $prompt = (string) $request->validated('prompt');
        $sessionId = $request->validated('session_id') ? (int) $request->validated('session_id') : null;
        $user = $request->user();

        $messages = $this->buildMessages($prompt, $sessionId);

        return response()->stream(function () use ($user, $prompt, $sessionId, $messages): void {
            try {
                $llmResult = $this->llmClient->stream($messages, function (string $chunk): void {
                    echo 'data: '.json_encode(['chunk' => $chunk], JSON_UNESCAPED_UNICODE)."\n\n";
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                });

                $session = DB::transaction(function () use ($user, $prompt, $sessionId, $llmResult): ChatSession {
                    $session = $this->resolveChatSession($user, $prompt, $sessionId);

                    ChatMessage::create([
                        'chat_session_id' => $session->id,
                        'role' => 'user',
                        'content' => $prompt,
                    ]);

                    ChatMessage::create([
                        'chat_session_id' => $session->id,
                        'role' => 'assistant',
                        'content' => $llmResult['content'],
                    ]);

                    ChatLog::create([
                        'chat_session_id' => $session->id,
                        'user_id' => $user->id,
                        'prompt' => $prompt,
                        'ai_response' => $llmResult['content'],
                        'model_used' => $llmResult['model'],
                        'provider' => $llmResult['provider'],
                        'prompt_tokens' => $llmResult['prompt_tokens'],
                        'completion_tokens' => $llmResult['completion_tokens'],
                        'total_tokens' => $llmResult['total_tokens'],
                    ]);

                    return $session;
                });

                echo 'data: '.json_encode([
                    'session_id' => $session->id,
                    'model_used' => $llmResult['model'],
                    'provider' => $llmResult['provider'],
                    'tokens' => [
                        'prompt' => $llmResult['prompt_tokens'],
                        'completion' => $llmResult['completion_tokens'],
                        'total' => $llmResult['total_tokens'],
                    ],
                    'status' => '[DONE]',
                ], JSON_UNESCAPED_UNICODE)."\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            } catch (Throwable $e) {
                echo 'data: '.json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ], JSON_UNESCAPED_UNICODE)."\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    /**
     * Build messages payload with server-side system prompt injection and context history.
     *
     * @return array<int, array{role: string, content: string}>
     */
    protected function buildMessages(string $prompt, ?int $sessionId): array
    {
        $systemPrompt = (string) config(
            'llm.system_prompt',
            'Anda adalah asisten rekrutmen (HR) Roku. Bantu menganalisis CV, menilai kandidat, dan menyusun rekomendasi perekrutan secara objektif. Keputusan akhir tetap berada di tangan manusia dan rekomendasi ini bersifat advisory.'
        );

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        if ($sessionId) {
            $limit = (int) config('llm.context_history_limit', 6);
            $history = ChatMessage::query()
                ->where('chat_session_id', $sessionId)
                ->whereIn('role', ['user', 'assistant'])
                ->orderByDesc('id')
                ->limit($limit)
                ->get()
                ->reverse()
                ->values();

            foreach ($history as $msg) {
                $messages[] = [
                    'role' => $msg->role,
                    'content' => $msg->content,
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
     * Resolve existing chat session or create a new one.
     */
    protected function resolveChatSession(User $user, string $prompt, ?int $sessionId): ChatSession
    {
        if ($sessionId) {
            return ChatSession::query()
                ->whereBelongsTo($user)
                ->findOrFail($sessionId);
        }

        $title = Str::limit(trim($prompt), 50, '...');

        return ChatSession::create([
            'user_id' => $user->id,
            'title' => $title,
        ]);
    }
}
