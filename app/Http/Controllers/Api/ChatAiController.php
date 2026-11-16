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
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
        $user = $request->user();
        $prompt = (string) $request->validated('prompt');
        $sessionId = $this->validatedSessionId($request);

        try {
            $llmResult = $this->llmClient->chat($this->buildMessages($user, $prompt, $sessionId));
        } catch (LlmException $e) {
            return $this->llmErrorResponse($e, $user);
        } catch (Throwable $e) {
            return $this->unexpectedErrorResponse($e, $user);
        }

        try {
            $chatLog = $this->persistExchange($user, $prompt, $sessionId, $llmResult);
        } catch (Throwable $e) {
            return $this->persistenceErrorResponse($e, $user);
        }

        return (new ChatLogResource($chatLog))->response()->setStatusCode(200);
    }

    /**
     * Handle SSE streaming chat request.
     */
    public function stream(ChatAiRequest $request): StreamedResponse
    {
        $user = $request->user();
        $prompt = (string) $request->validated('prompt');
        $sessionId = $this->validatedSessionId($request);
        $messages = $this->buildMessages($user, $prompt, $sessionId);

        return response()->stream(function () use ($user, $prompt, $sessionId, $messages): void {
            try {
                $llmResult = $this->llmClient->stream($messages, function (string $chunk): void {
                    $this->sendSseEvent(['chunk' => $chunk]);
                });
            } catch (Throwable $e) {
                $this->logLlmFailure($e, $user, 'stream');

                $this->sendSseEvent([
                    'status' => 'error',
                    'message' => $this->streamErrorMessage($e),
                ], event: 'error');

                return;
            }

            try {
                $chatLog = $this->persistExchange($user, $prompt, $sessionId, $llmResult);
            } catch (Throwable $e) {
                Log::error('Chat AI streaming gagal menyimpan riwayat obrolan.', [
                    'user_id' => $user->id,
                    'exception' => $e,
                ]);

                $this->sendSseEvent([
                    'status' => 'error',
                    'message' => 'Jawaban diterima tetapi gagal disimpan ke riwayat obrolan.',
                ], event: 'error');

                return;
            }

            $this->sendSseEvent([
                'session_id' => $chatLog->chat_session_id,
                'model_used' => $llmResult['model'],
                'provider' => $llmResult['provider'],
                'tokens' => [
                    'prompt' => $llmResult['prompt_tokens'],
                    'completion' => $llmResult['completion_tokens'],
                    'total' => $llmResult['total_tokens'],
                ],
                'status' => '[DONE]',
            ]);

            $this->sendSseEvent('[DONE]');
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    /**
     * List the authenticated user's chat history.
     */
    public function history(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $validated = $request->validate([
            'session_id' => [
                'nullable',
                'integer',
                Rule::exists('chat_sessions', 'id')->where(fn ($query) => $query->where('user_id', $user->id)),
            ],
        ], [
            'session_id.integer' => 'Session ID harus berupa bilangan bulat.',
            'session_id.exists' => 'Sesi obrolan tidak ditemukan atau bukan milik pengguna yang sedang login.',
        ]);

        $logs = ChatLog::query()
            ->whereBelongsTo($user)
            ->when($validated['session_id'] ?? null, fn ($query, $sessionId) => $query->where('chat_session_id', $sessionId))
            ->latest('id')
            ->limit(50)
            ->get();

        return ChatLogResource::collection($logs)->additional(['status' => 'success']);
    }

    /**
     * Build messages payload with server-side system prompt injection and context history.
     *
     * @return array<int, array{role: string, content: string}>
     */
    protected function buildMessages(User $user, string $prompt, ?int $sessionId): array
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

    protected function systemPrompt(): string
    {
        return (string) config(
            'llm.system_prompt',
            'Anda adalah asisten rekrutmen (HR) Roku. Bantu menganalisis CV, menilai kandidat, dan menyusun rekomendasi perekrutan secara objektif. Keputusan akhir tetap berada di tangan manusia dan rekomendasi ini bersifat advisory.'
        );
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
    protected function persistExchange(User $user, string $prompt, ?int $sessionId, array $llmResult): ChatLog
    {
        return DB::transaction(function () use ($user, $prompt, $sessionId, $llmResult): ChatLog {
            $session = $this->resolveChatSession($user, $prompt, $sessionId);

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
     * Resolve existing chat session or create a new one for the authenticated user.
     */
    protected function resolveChatSession(User $user, string $prompt, ?int $sessionId): ChatSession
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

    protected function validatedSessionId(ChatAiRequest $request): ?int
    {
        $sessionId = $request->validated('session_id');

        return $sessionId !== null ? (int) $sessionId : null;
    }

    protected function llmErrorResponse(LlmException $e, User $user): JsonResponse
    {
        $this->logLlmFailure($e, $user, 'chat');

        $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 503;

        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], $status);
    }

    protected function unexpectedErrorResponse(Throwable $e, User $user): JsonResponse
    {
        $this->logLlmFailure($e, $user, 'chat');

        return response()->json([
            'status' => 'error',
            'message' => 'Terjadi kesalahan sistem saat menghubungi layanan LLM.',
        ], 500);
    }

    protected function persistenceErrorResponse(Throwable $e, User $user): JsonResponse
    {
        Log::error('Chat AI gagal menyimpan riwayat obrolan.', [
            'user_id' => $user->id,
            'exception' => $e,
        ]);

        return response()->json([
            'status' => 'error',
            'message' => 'Gagal menyimpan riwayat obrolan.',
        ], 500);
    }

    /**
     * Client-safe streaming error text; low-level details stay in the logs.
     */
    protected function streamErrorMessage(Throwable $e): string
    {
        if ($e instanceof LlmException && $e->getCode() === 502) {
            return 'Respons streaming dari penyedia LLM terputus sebelum selesai. Silakan ulangi permintaan.';
        }

        return 'Layanan streaming LLM tidak dapat dihubungi. Silakan coba lagi.';
    }

    protected function logLlmFailure(Throwable $e, User $user, string $operation): void
    {
        Log::error('Chat AI gagal memproses permintaan LLM.', [
            'operation' => $operation,
            'user_id' => $user->id,
            'provider' => $e instanceof LlmException ? $e->provider : null,
            'reason' => $e->getMessage(),
            'exception' => $e->getPrevious() ?? $e,
        ]);
    }

    /**
     * @param  array<string, mixed>|string  $payload
     */
    protected function sendSseEvent(array|string $payload, ?string $event = null): void
    {
        $data = is_string($payload) ? $payload : json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        echo ($event !== null ? "event: {$event}\n" : '').'data: '.$data."\n\n";

        if (ob_get_level() > 0) {
            ob_flush();
        }

        flush();
    }
}
