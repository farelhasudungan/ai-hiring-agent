<?php

use App\Models\CandidateScoringAgent;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\HiringRecommendationAgent;
use App\Models\ResumeParserAgent;
use App\Models\ToolExecutionLog;
use App\Models\User;
use Database\Seeders\AgenticChatSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// ── Relationship structure ──────────────────────────────────────────

test('chat session belongs to user', function () {
    $user = User::factory()->create();
    $session = ChatSession::factory()->create(['user_id' => $user->id]);

    expect($session->user->id)->toBe($user->id);
});

test('user has many chat sessions', function () {
    $user = User::factory()->create();
    ChatSession::factory()->count(3)->create(['user_id' => $user->id]);

    expect($user->chatSessions)->toHaveCount(3);
});

test('chat session has many messages', function () {
    $session = ChatSession::factory()->create();
    ChatMessage::factory()->count(4)->create(['chat_session_id' => $session->id]);

    expect($session->messages)->toHaveCount(4);
});

test('chat message belongs to chat session', function () {
    $session = ChatSession::factory()->create();
    $message = ChatMessage::factory()->create(['chat_session_id' => $session->id]);

    expect($message->chatSession->id)->toBe($session->id);
});

test('chat message has many tool execution logs', function () {
    $message = ChatMessage::factory()->create();
    $agent = ResumeParserAgent::factory()->create();

    ToolExecutionLog::factory()->count(2)->create([
        'chat_message_id' => $message->id,
        'loggable_type' => 'resume_parser',
        'loggable_id' => $agent->id,
    ]);

    expect($message->toolExecutionLogs)->toHaveCount(2);
});

test('tool execution log belongs to chat message', function () {
    $message = ChatMessage::factory()->create();
    $agent = ResumeParserAgent::factory()->create();

    $log = ToolExecutionLog::factory()->create([
        'chat_message_id' => $message->id,
        'loggable_type' => 'resume_parser',
        'loggable_id' => $agent->id,
    ]);

    expect($log->chatMessage->id)->toBe($message->id);
});

// ── Polymorphic relationships ───────────────────────────────────────

test('polymorphic loggable resolves resume parser agent', function () {
    $agent = ResumeParserAgent::factory()->create();
    $log = ToolExecutionLog::factory()->create([
        'loggable_type' => 'resume_parser',
        'loggable_id' => $agent->id,
    ]);

    expect($log->loggable)->toBeInstanceOf(ResumeParserAgent::class);
    expect($log->loggable->id)->toBe($agent->id);
});

test('polymorphic loggable resolves candidate scoring agent', function () {
    $agent = CandidateScoringAgent::factory()->create();
    $log = ToolExecutionLog::factory()->create([
        'loggable_type' => 'candidate_scoring',
        'loggable_id' => $agent->id,
    ]);

    expect($log->loggable)->toBeInstanceOf(CandidateScoringAgent::class);
    expect($log->loggable->id)->toBe($agent->id);
});

test('polymorphic loggable resolves hiring recommendation agent', function () {
    $agent = HiringRecommendationAgent::factory()->create();
    $log = ToolExecutionLog::factory()->create([
        'loggable_type' => 'hiring_recommendation',
        'loggable_id' => $agent->id,
    ]);

    expect($log->loggable)->toBeInstanceOf(HiringRecommendationAgent::class);
    expect($log->loggable->id)->toBe($agent->id);
});

test('agent models have morph many tool execution logs', function () {
    $resumeParser = ResumeParserAgent::factory()->create();
    $scorer = CandidateScoringAgent::factory()->create();
    $recommender = HiringRecommendationAgent::factory()->create();

    $message = ChatMessage::factory()->create();

    ToolExecutionLog::factory()->count(2)->create([
        'chat_message_id' => $message->id,
        'loggable_type' => 'resume_parser',
        'loggable_id' => $resumeParser->id,
    ]);

    ToolExecutionLog::factory()->create([
        'chat_message_id' => $message->id,
        'loggable_type' => 'candidate_scoring',
        'loggable_id' => $scorer->id,
    ]);

    ToolExecutionLog::factory()->create([
        'chat_message_id' => $message->id,
        'loggable_type' => 'hiring_recommendation',
        'loggable_id' => $recommender->id,
    ]);

    expect($resumeParser->toolExecutionLogs)->toHaveCount(2);
    expect($scorer->toolExecutionLogs)->toHaveCount(1);
    expect($recommender->toolExecutionLogs)->toHaveCount(1);
});

// ── Cascade deletion ────────────────────────────────────────────────

test('deleting user cascades to chat sessions, messages, and tool logs', function () {
    $user = User::factory()->create();
    $session = ChatSession::factory()->create(['user_id' => $user->id]);
    $message = ChatMessage::factory()->create(['chat_session_id' => $session->id]);
    $agent = ResumeParserAgent::factory()->create();

    ToolExecutionLog::factory()->create([
        'chat_message_id' => $message->id,
        'loggable_type' => 'resume_parser',
        'loggable_id' => $agent->id,
    ]);

    $user->delete();

    expect(ChatSession::query()->where('id', $session->id)->exists())->toBeFalse();
    expect(ChatMessage::query()->where('id', $message->id)->exists())->toBeFalse();
    expect(ToolExecutionLog::query()->where('chat_message_id', $message->id)->exists())->toBeFalse();
});

test('deleting agent through eloquent cleans up its polymorphic logs', function () {
    $agent = ResumeParserAgent::factory()->create();
    $message = ChatMessage::factory()->create();

    $log = ToolExecutionLog::factory()->create([
        'chat_message_id' => $message->id,
        'loggable_type' => 'resume_parser',
        'loggable_id' => $agent->id,
    ]);

    $agent->delete();

    expect(ToolExecutionLog::query()->where('id', $log->id)->exists())->toBeFalse();
});

// ── Authorization ───────────────────────────────────────────────────

test('users cannot access chat sessions owned by other users', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $session = ChatSession::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($other)
        ->get(route('agentic-chat.sessions.show', $session->id))
        ->assertNotFound();
});

test('authenticated user can access own chat sessions', function () {
    $user = User::factory()->create();
    $session = ChatSession::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('agentic-chat.sessions.show', $session->id))
        ->assertOk();
});

test('unauthenticated users are redirected from chat sessions', function () {
    $this->get(route('agentic-chat.sessions.index'))
        ->assertRedirect(route('filament.admin.auth.login'));
});

// ── Eager loading prevents N+1 ──────────────────────────────────────

test('eager loading keeps query count stable as message and log counts grow', function () {
    $user = User::factory()->create();
    $agent = ResumeParserAgent::factory()->create();

    // Create a small session with few messages
    $smallSession = ChatSession::factory()->create(['user_id' => $user->id]);
    ChatMessage::factory()->count(3)->create(['chat_session_id' => $smallSession->id])
        ->each(function ($message) use ($agent) {
            ToolExecutionLog::factory()->create([
                'chat_message_id' => $message->id,
                'loggable_type' => 'resume_parser',
                'loggable_id' => $agent->id,
            ]);
        });

    // Create a larger session with more messages
    $largeSession = ChatSession::factory()->create(['user_id' => $user->id]);
    ChatMessage::factory()->count(10)->create(['chat_session_id' => $largeSession->id])
        ->each(function ($message) use ($agent) {
            ToolExecutionLog::factory()->count(2)->create([
                'chat_message_id' => $message->id,
                'loggable_type' => 'resume_parser',
                'loggable_id' => $agent->id,
            ]);
        });

    DB::connection()->enableQueryLog();
    DB::flushQueryLog();

    ChatSession::query()
        ->whereBelongsTo($user)
        ->with([
            'messages' => fn ($query) => $query->orderBy('created_at')->orderBy('id'),
            'messages.toolExecutionLogs.loggable',
        ])
        ->findOrFail($smallSession->id);

    $smallQueries = count(DB::getQueryLog());
    DB::flushQueryLog();

    ChatSession::query()
        ->whereBelongsTo($user)
        ->with([
            'messages' => fn ($query) => $query->orderBy('created_at')->orderBy('id'),
            'messages.toolExecutionLogs.loggable',
        ])
        ->findOrFail($largeSession->id);

    $largeQueries = count(DB::getQueryLog());
    DB::disableQueryLog();

    // With eager loading, query count should be approximately the same
    // regardless of data size (bounded by number of relationship levels, not row count)
    expect($largeQueries)->toBeLessThanOrEqual($smallQueries + 2);
});

// ── Factories and seeders generate valid data ───────────────────────

test('chat session factory creates valid relational data', function () {
    $session = ChatSession::factory()->create();

    expect($session->exists)->toBeTrue();
    expect($session->user)->toBeInstanceOf(User::class);
    expect($session->title)->toBeString();
});

test('chat message factory creates valid relational data', function () {
    $message = ChatMessage::factory()->create();

    expect($message->exists)->toBeTrue();
    expect($message->chatSession)->toBeInstanceOf(ChatSession::class);
    expect($message->role)->toBeIn(['user', 'assistant', 'system', 'tool']);
});

test('tool execution log factory creates valid relational data', function () {
    $log = ToolExecutionLog::factory()->create();

    expect($log->exists)->toBeTrue();
    expect($log->chatMessage)->toBeInstanceOf(ChatMessage::class);
    expect($log->loggable)->not->toBeNull();
    expect($log->input)->toBeArray();
    expect($log->status)->toBeString();
});

test('chat message factory states produce correct roles', function () {
    $user = ChatMessage::factory()->user()->make(['chat_session_id' => 1]);
    $assistant = ChatMessage::factory()->assistant()->make(['chat_session_id' => 1]);
    $system = ChatMessage::factory()->system()->make(['chat_session_id' => 1]);

    expect($user->role)->toBe('user');
    expect($assistant->role)->toBe('assistant');
    expect($system->role)->toBe('system');
});

test('tool execution log json fields round trip correctly', function () {
    $input = ['file' => 'resume.pdf', 'format' => 'pdf'];
    $result = ['score' => 85, 'skills' => ['PHP', 'Laravel']];

    $log = new ToolExecutionLog([
        'input' => $input,
        'result' => $result,
    ]);

    expect($log->input)->toBe($input);
    expect($log->result)->toBe($result);
});

test('tool execution log duration is cast to integer', function () {
    $log = new ToolExecutionLog(['duration_ms' => '1250']);

    expect($log->duration_ms)->toBe(1250);
});

test('seeder runs without errors', function () {
    $this->seed(AgenticChatSeeder::class);

    expect(ChatSession::query()->count())->toBeGreaterThanOrEqual(2);
    expect(ChatMessage::query()->count())->toBeGreaterThanOrEqual(4);
    expect(ToolExecutionLog::query()->count())->toBeGreaterThanOrEqual(3);
    expect(ResumeParserAgent::query()->count())->toBeGreaterThanOrEqual(1);
    expect(CandidateScoringAgent::query()->count())->toBeGreaterThanOrEqual(1);
    expect(HiringRecommendationAgent::query()->count())->toBeGreaterThanOrEqual(1);
});

test('seeder is idempotent', function () {
    $this->seed(AgenticChatSeeder::class);
    $firstCount = ChatSession::query()->count();

    $this->seed(AgenticChatSeeder::class);
    $secondCount = ChatSession::query()->count();

    expect($secondCount)->toBe($firstCount);
});
