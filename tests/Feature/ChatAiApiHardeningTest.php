<?php

use App\Models\ChatLog;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Company;
use App\Models\User;
use App\Services\Llm\Exceptions\LlmException;
use App\Services\Llm\LlmClientInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create([
        'company_id' => $this->company->id,
        'email' => 'hardening@roku.test',
        'password' => 'secret123',
    ]);
    $this->token = $this->user->createToken('hardening-suite')->plainTextToken;
});

// ── Authentication edge cases ───────────────────────────────────────────────

test('chat-ai rejects an invalid bearer token with 401 json', function () {
    $this->withHeader('Authorization', 'Bearer token-yang-tidak-valid')
        ->postJson('/api/chat-ai', ['prompt' => 'Halo'])
        ->assertStatus(401)
        ->assertJson([
            'status' => 'error',
            'message' => 'Unauthenticated. Token otentikasi tidak ditemukan atau tidak valid.',
        ]);
});

test('a revoked token can no longer access chat-ai', function () {
    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson('/api/logout')
        ->assertOk();

    // Sanctum caches the resolved guard per application instance; reset it between test requests.
    $this->app['auth']->forgetGuards();

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson('/api/chat-ai', ['prompt' => 'Halo setelah logout'])
        ->assertStatus(401);

    expect(ChatLog::query()->count())->toBe(0);
});

// ── Validation edge cases ───────────────────────────────────────────────────

test('chat-ai rejects an empty prompt with 422', function () {
    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson('/api/chat-ai', ['prompt' => ''])
        ->assertStatus(422)
        ->assertJson(["status" => "error", "message" => "Validasi gagal."])
        ->assertJsonValidationErrors(['prompt']);
});

test('chat-ai rejects a non-string prompt with 422', function () {
    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson('/api/chat-ai', ['prompt' => ['bukan', 'teks']])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['prompt']);
});

test('chat-ai accepts a prompt of exactly 1000 characters', function () {
    Http::fake([
        'http://localhost:11434/api/chat' => Http::response([
            'model' => 'qwen3:14b',
            'message' => ['role' => 'assistant', 'content' => 'Diterima.'],
            'done' => true,
        ], 200),
    ]);

    $prompt = str_repeat('a', 1000);

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson('/api/chat-ai', ['prompt' => $prompt])
        ->assertOk()
        ->assertJson(['status' => 'success', 'data' => ['prompt' => $prompt]]);
});

test('chat-ai rejects a session id that does not exist', function () {
    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson('/api/chat-ai', ['prompt' => 'Halo', 'session_id' => 999999])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['session_id']);
});

// ── LLM failure handling ────────────────────────────────────────────────────

test('a failed llm call does not create session, message, or log records', function () {
    Http::fake([
        'http://localhost:11434/api/chat' => Http::response('Ollama down', 503),
        'https://senopati.its.ac.id/v1/chat/completions' => Http::response('Senopati down', 503),
    ]);

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson('/api/chat-ai', ['prompt' => 'Permintaan yang gagal total'])
        ->assertStatus(503)
        ->assertJson(['status' => 'error']);

    expect(ChatSession::query()->count())->toBe(0)
        ->and(ChatMessage::query()->count())->toBe(0)
        ->and(ChatLog::query()->count())->toBe(0);
});

test('an invalid provider response returns a structured error without persisting data', function () {
    Http::fake([
        'http://localhost:11434/api/chat' => Http::response('bukan json', 200),
        'https://senopati.its.ac.id/v1/chat/completions' => Http::response('bukan json', 200),
    ]);

    $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson('/api/chat-ai', ['prompt' => 'Respons penyedia rusak']);

    $response->assertStatus(503)
        ->assertJson([
            'status' => 'error',
            'message' => 'Layanan LLM utama (Ollama) dan fallback (Senopati) tidak dapat dihubungi.',
        ])
        ->assertJsonMissingPath('data');

    expect(ChatLog::query()->count())->toBe(0);
});

test('a connection timeout on ollama triggers the senopati fallback', function () {
    Log::spy();

    Http::fake([
        'http://localhost:11434/api/chat' => fn () => throw new ConnectionException('cURL error 28: Operation timed out after 3001 milliseconds'),
        'https://senopati.its.ac.id/v1/chat/completions' => Http::response([
            'model' => 'qwen3:14b',
            'choices' => [
                ['message' => ['role' => 'assistant', 'content' => 'Jawaban fallback setelah timeout.']],
            ],
            'usage' => ['prompt_tokens' => 7, 'completion_tokens' => 9, 'total_tokens' => 16],
        ], 200),
    ]);

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson('/api/chat-ai', ['prompt' => 'Uji timeout koneksi'])
        ->assertOk()
        ->assertJson(['data' => ['ai_response' => 'Jawaban fallback setelah timeout.']]);

    $this->assertDatabaseHas('chat_logs', [
        'provider' => 'senopati',
        'prompt_tokens' => 7,
        'completion_tokens' => 9,
        'total_tokens' => 16,
    ]);
});

test('a connection refused on ollama also triggers the senopati fallback', function () {
    Http::fake([
        'http://localhost:11434/api/chat' => fn () => throw new ConnectionException('cURL error 7: Failed to connect to localhost port 11434'),
        'https://senopati.its.ac.id/v1/chat/completions' => Http::response([
            'model' => 'qwen3:14b',
            'choices' => [
                ['message' => ['role' => 'assistant', 'content' => 'Jawaban fallback setelah connection refused.']],
            ],
        ], 200),
    ]);

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson('/api/chat-ai', ['prompt' => 'Uji connection refused'])
        ->assertOk()
        ->assertJson(['data' => ['ai_response' => 'Jawaban fallback setelah connection refused.']]);

    $this->assertDatabaseHas('chat_logs', [
        'prompt' => 'Uji connection refused',
        'provider' => 'senopati',
    ]);
});

test('an upstream 4xx from ollama is not retried on senopati', function () {
    Http::fake([
        'http://localhost:11434/api/chat' => Http::response(['error' => 'bad request'], 400),
        'https://senopati.its.ac.id/v1/chat/completions' => Http::response([], 200),
    ]);

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson('/api/chat-ai', ['prompt' => 'Permintaan tidak valid'])
        ->assertStatus(502)
        ->assertJson([
            'status' => 'error',
            'message' => 'Ollama menolak permintaan yang dikirim.',
        ]);

    Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'senopati'));
    expect(ChatLog::query()->count())->toBe(0);
});

test('missing provider usage metadata is stored as null tokens', function () {
    Http::fake([
        'http://localhost:11434/api/chat' => Http::response([
            'model' => 'qwen3:14b',
            'message' => ['role' => 'assistant', 'content' => 'Tanpa metadata usage.'],
            'done' => true,
        ], 200),
    ]);

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson('/api/chat-ai', ['prompt' => 'Uji tanpa metadata token'])
        ->assertOk();

    $this->assertDatabaseHas('chat_logs', [
        'prompt' => 'Uji tanpa metadata token',
        'prompt_tokens' => null,
        'completion_tokens' => null,
        'total_tokens' => null,
    ]);
});

// ── SSE streaming failures ─────────────────────────────────────────────────

test('streaming failures return a safe sse error without internal details', function () {
    Log::spy();

    Http::fake([
        'http://localhost:11434/api/chat' => Http::response('Secret internal failure', 503),
        'https://senopati.its.ac.id/v1/chat/completions' => Http::response('Secret internal failure', 503),
    ]);

    $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->post('/api/chat-ai/stream', ['prompt' => 'Uji kegagalan streaming'], ['Accept' => 'text/event-stream']);

    $response->assertOk();
    $content = $response->streamedContent();

    expect($content)->toContain('event: error')
        ->and($content)->toContain('Layanan streaming LLM tidak dapat dihubungi. Silakan coba lagi.')
        ->and($content)->not->toContain('Secret internal failure')
        ->and($content)->not->toContain('localhost:11434')
        ->and($content)->not->toContain('senopati.its.ac.id');

    expect(ChatLog::query()->count())->toBe(0);
});

test('streaming does not mix providers when output starts before the provider fails', function () {
    $this->app->instance(LlmClientInterface::class, new class () implements LlmClientInterface {
        public function chat(array $messages): array
        {
            throw new LlmException('tidak dipakai');
        }

        public function stream(array $messages, Closure $onChunk): array
        {
            $onChunk('Sebagian jawaban Ollama. ');

            throw new LlmException('Koneksi ke http://localhost:11434 terputus (api_key=rahasia).', 502, null, 'ollama', true);
        }
    });

    $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->post('/api/chat-ai/stream', ['prompt' => 'Uji respons tercampur'], ['Accept' => 'text/event-stream']);

    $response->assertOk();
    $content = $response->streamedContent();

    expect($content)->toContain('data: {"chunk":"Sebagian jawaban Ollama. "}')
        ->and($content)->toContain('event: error')
        ->and($content)->toContain('Respons streaming dari penyedia LLM terputus sebelum selesai. Silakan ulangi permintaan.')
        ->and($content)->not->toContain('localhost:11434')
        ->and($content)->not->toContain('rahasia')
        ->and($content)->not->toContain('[DONE]');

    expect(ChatLog::query()->count())->toBe(0);
});

test('streaming injects the server-side system prompt', function () {
    Http::fake([
        'http://localhost:11434/api/chat' => function (Request $request) {
            $messages = $request->data()['messages'];

            expect($messages)->toHaveCount(2)
                ->and($messages[0]['role'])->toBe('system')
                ->and($messages[0]['content'])->toContain('asisten rekrutmen (HR) Roku')
                ->and($messages[1]['role'])->toBe('user')
                ->and($messages[1]['content'])->toBe('Siapa kamu?');

            return Http::response(
                "{\"model\":\"qwen3:14b\",\"message\":{\"content\":\"Asisten HR Roku.\"},\"done\":true}\n",
                200,
                ['Content-Type' => 'application/x-ndjson']
            );
        },
    ]);

    $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->post('/api/chat-ai/stream', ['prompt' => 'Siapa kamu?'], ['Accept' => 'text/event-stream']);

    $response->assertOk();
    expect($response->streamedContent())->toContain('data: {"chunk":"Asisten HR Roku."}');
});

// ── Chat history retrieval ─────────────────────────────────────────────────

test('chat history only returns the authenticated user logs', function () {
    $ownSession = ChatSession::factory()->create(['user_id' => $this->user->id]);
    ChatLog::factory()->create([
        'chat_session_id' => $ownSession->id,
        'user_id' => $this->user->id,
        'prompt' => 'Riwayat milik sendiri',
    ]);

    $otherUser = User::factory()->create(['company_id' => $this->company->id]);
    $otherSession = ChatSession::factory()->create(['user_id' => $otherUser->id]);
    ChatLog::factory()->create([
        'chat_session_id' => $otherSession->id,
        'user_id' => $otherUser->id,
        'prompt' => 'Riwayat pengguna lain',
    ]);

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->getJson('/api/chat-ai/history')
        ->assertOk()
        ->assertJson(['status' => 'success'])
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.prompt', 'Riwayat milik sendiri');

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->getJson('/api/chat-ai/history?session_id='.$otherSession->id)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['session_id']);
});

test('chat history requires authentication', function () {
    $this->getJson('/api/chat-ai/history')->assertStatus(401);
});
