<?php

use App\Livewire\AgenticChatWorkspace;
use App\Models\ChatLog;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

function fakeOllamaStream(string $first = 'Chunk 1 ', string $second = 'Chunk 2'): void
{
    Http::fake([
        'http://localhost:11434/api/chat' => Http::response(
            "{\"model\":\"qwen3:14b\",\"message\":{\"content\":\"{$first}\"},\"done\":false}\n"
            ."{\"model\":\"qwen3:14b\",\"message\":{\"content\":\"{$second}\"},\"done\":true,\"prompt_eval_count\":10,\"eval_count\":15}\n",
            200,
            ['Content-Type' => 'application/x-ndjson']
        ),
    ]);
}

// ── Page access and ownership ───────────────────────────────────────────────

test('guests are redirected from the chat workspace', function () {
    $this->get(route('agentic-chat.workspace'))
        ->assertRedirect(route('filament.admin.auth.login'));
});

test('the workspace lists only the authenticated user sessions', function () {
    ChatSession::factory()->create(['user_id' => $this->user->id, 'title' => 'Sesi milik saya']);
    ChatSession::factory()->create(['title' => 'Sesi pengguna lain']);

    $this->actingAs($this->user)
        ->get(route('agentic-chat.workspace'))
        ->assertOk()
        ->assertSee('Sesi milik saya')
        ->assertDontSee('Sesi pengguna lain')
        ->assertSee(route('agentic-chat.stream'), false)
        ->assertSee('data-update-uri', false)
        ->assertSee('wire:id', false)
        ->assertSee('data-chat-form', false)
        ->assertSee('data-chat-input', false)
        ->assertSee('data-chat-stream-text', false);
});

test('a foreign session in the url is not accessible', function () {
    $foreign = ChatSession::factory()->create();

    $this->actingAs($this->user)
        ->get(route('agentic-chat.workspace', ['session' => $foreign->id]))
        ->assertNotFound();
});

test('the workspace renders persisted messages in order', function () {
    $session = ChatSession::factory()->create(['user_id' => $this->user->id]);
    ChatMessage::factory()->create(['chat_session_id' => $session->id, 'role' => 'user', 'content' => 'Pertanyaan pertama']);
    ChatMessage::factory()->create(['chat_session_id' => $session->id, 'role' => 'assistant', 'content' => 'Jawaban pertama']);

    $this->actingAs($this->user)
        ->get(route('agentic-chat.workspace', ['session' => $session->id]))
        ->assertOk()
        ->assertSeeInOrder(['Pertanyaan pertama', 'Jawaban pertama']);
});

test('the workspace shows provider and token metadata from the latest chat log', function () {
    $session = ChatSession::factory()->create(['user_id' => $this->user->id]);
    ChatLog::factory()->senopati()->create([
        'chat_session_id' => $session->id,
        'user_id' => $this->user->id,
        'model_used' => 'qwen3:14b',
        'prompt_tokens' => 12,
        'completion_tokens' => 34,
        'total_tokens' => 46,
    ]);

    $this->actingAs($this->user)
        ->get(route('agentic-chat.workspace', ['session' => $session->id]))
        ->assertOk()
        ->assertSee('senopati')
        ->assertSee('qwen3:14b')
        ->assertSee('46');
});

test('the workspace shows the empty state for a new conversation', function () {
    $this->actingAs($this->user)
        ->get(route('agentic-chat.workspace'))
        ->assertOk()
        ->assertSee('Mulai obrolan baru');
});

test('the legacy session list redirects to the workspace', function () {
    $this->actingAs($this->user)
        ->get(route('agentic-chat.sessions.index'))
        ->assertRedirect(route('agentic-chat.workspace'));
});

// ── Livewire state ─────────────────────────────────────────────────────────

test('selecting a session loads its messages', function () {
    $session = ChatSession::factory()->create(['user_id' => $this->user->id]);
    ChatMessage::factory()->create([
        'chat_session_id' => $session->id,
        'role' => 'user',
        'content' => 'Isi sesi terpilih',
    ]);

    $this->actingAs($this->user);

    Livewire::test(AgenticChatWorkspace::class)
        ->call('selectSession', $session->id)
        ->assertSet('sessionId', $session->id)
        ->assertSee('Isi sesi terpilih');
});

test('selecting another user session is rejected', function () {
    $foreign = ChatSession::factory()->create();

    $this->actingAs($this->user);

    Livewire::test(AgenticChatWorkspace::class)
        ->call('selectSession', $foreign->id)
        ->assertNotFound();
});

test('starting a new chat clears the selected session', function () {
    $session = ChatSession::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user);

    Livewire::test(AgenticChatWorkspace::class)
        ->call('selectSession', $session->id)
        ->call('newChat')
        ->assertSet('sessionId', null);
});

test('a completed conversation event selects the persisted session', function () {
    $session = ChatSession::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user);

    Livewire::test(AgenticChatWorkspace::class)
        ->dispatch('conversation-completed', sessionId: $session->id)
        ->assertSet('sessionId', $session->id);
});

// ── Browser streaming transport ────────────────────────────────────────────

test('the browser stream endpoint streams chunks and persists the exchange', function () {
    fakeOllamaStream();

    $response = $this->actingAs($this->user)->post(
        route('agentic-chat.stream'),
        ['prompt' => 'Jelaskan middleware!'],
        ['Accept' => 'text/event-stream']
    );

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/event-stream');

    $content = $response->streamedContent();

    expect($content)->toContain('data: {"chunk":"Chunk 1 "}')
        ->and($content)->toContain('data: {"chunk":"Chunk 2"}')
        ->and($content)->toContain('"status":"[DONE]"')
        ->and($content)->toContain('data: [DONE]');

    $this->assertDatabaseHas('chat_sessions', ['user_id' => $this->user->id]);
    $this->assertDatabaseHas('chat_messages', ['role' => 'user', 'content' => 'Jelaskan middleware!']);
    $this->assertDatabaseHas('chat_messages', ['role' => 'assistant', 'content' => 'Chunk 1 Chunk 2']);
    $this->assertDatabaseHas('chat_logs', [
        'user_id' => $this->user->id,
        'provider' => 'ollama',
        'model_used' => 'qwen3:14b',
        'prompt_tokens' => 10,
        'completion_tokens' => 15,
        'total_tokens' => 25,
    ]);
});

test('the browser stream endpoint resumes a session without duplicating records', function () {
    $session = ChatSession::factory()->create(['user_id' => $this->user->id]);
    fakeOllamaStream();

    $first = $this->actingAs($this->user)->post(
        route('agentic-chat.stream'),
        ['prompt' => 'Pertanyaan lanjutan', 'session_id' => $session->id],
        ['Accept' => 'text/event-stream']
    );
    $first->assertOk();
    $first->streamedContent();

    $second = $this->actingAs($this->user)->post(
        route('agentic-chat.stream'),
        ['prompt' => 'Pertanyaan kedua', 'session_id' => $session->id],
        ['Accept' => 'text/event-stream']
    );
    $second->assertOk();
    $second->streamedContent();

    expect($session->messages()->count())->toBe(4)
        ->and($session->chatLogs()->count())->toBe(2)
        ->and(ChatSession::query()->count())->toBe(1);
});

test('the browser stream endpoint rejects a foreign session id', function () {
    $foreign = ChatSession::factory()->create();
    Http::fake();

    $this->actingAs($this->user)
        ->post(
            route('agentic-chat.stream'),
            ['prompt' => 'Halo', 'session_id' => $foreign->id],
            ['Accept' => 'text/event-stream']
        )
        ->assertStatus(422)
        ->assertJsonValidationErrors(['session_id']);

    expect(ChatLog::query()->count())->toBe(0);
});

test('the browser stream endpoint returns a safe error and persists nothing when providers fail', function () {
    Http::fake([
        'http://localhost:11434/api/chat' => Http::response('Service Unavailable', 503),
        'https://senopati.its.ac.id/llm-gateway/v1/chat/completions' => Http::response('Service Unavailable', 503),
    ]);

    $response = $this->actingAs($this->user)->post(
        route('agentic-chat.stream'),
        ['prompt' => 'Permintaan yang gagal'],
        ['Accept' => 'text/event-stream']
    );

    $response->assertOk();
    $content = $response->streamedContent();

    expect($content)->toContain('event: error')
        ->and($content)->not->toContain('Service Unavailable')
        ->and($content)->not->toContain('localhost:11434');

    expect(ChatLog::query()->count())->toBe(0)
        ->and(ChatMessage::query()->count())->toBe(0);
});

test('guests cannot use the browser stream endpoint', function () {
    $this->post(route('agentic-chat.stream'), ['prompt' => 'Halo'])
        ->assertRedirect(route('filament.admin.auth.login'));
});
