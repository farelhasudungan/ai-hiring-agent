<?php

use App\Models\CandidateScoringAgent;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\HiringRecommendationAgent;
use App\Models\ResumeParserAgent;
use App\Models\ToolExecutionLog;
use App\Models\User;
use Database\Seeders\AgenticChatSeeder;
use Illuminate\Database\Events\QueryExecuted;
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

test('authenticated navigation links to agentic chat', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Agentic Chat')
        ->assertSee(route('agentic-chat.sessions.index'), false);
});

test('naive chat history route is not available outside local', function () {
    $this->app['env'] = 'production';
    $user = User::factory()->create();
    $session = ChatSession::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('agentic-chat.sessions.show-naive', $session->id))
        ->assertNotFound();
});

test('unauthenticated users are redirected from chat sessions', function () {
    $this->get(route('agentic-chat.sessions.index'))
        ->assertRedirect(route('filament.admin.auth.login'));
});

// ── Eager loading prevents N+1 ──────────────────────────────────────

test('chat detail controller keeps eager loaded query count bounded and naive route demonstrates N plus one', function () {
    $user = User::factory()->create();
    $resumeParser = ResumeParserAgent::factory()->create();
    $candidateScorer = CandidateScoringAgent::factory()->create();
    $hiringRecommender = HiringRecommendationAgent::factory()->create();
    $agents = [
        'resumeParser' => $resumeParser,
        'candidateScoring' => $candidateScorer,
        'hiringRecommendation' => $hiringRecommender,
    ];
    $states = array_keys($agents);

    $createHistory = function (int $messageCount, int $logsPerMessage) use ($user, $agents, $states): ChatSession {
        $session = ChatSession::factory()->create(['user_id' => $user->id]);

        for ($messageIndex = 0; $messageIndex < $messageCount; $messageIndex++) {
            $message = ChatMessage::factory()->create(['chat_session_id' => $session->id]);

            for ($logIndex = 0; $logIndex < $logsPerMessage; $logIndex++) {
                $state = $states[($messageIndex + $logIndex) % count($states)];
                ToolExecutionLog::factory()->{$state}()->create([
                    'chat_message_id' => $message->id,
                    'loggable_id' => $agents[$state]->id,
                ]);
            }
        }

        return $session;
    };

    $smallSession = $createHistory(3, 1);
    $largeSession = $createHistory(18, 3);
    $queries = [];

    DB::listen(function (QueryExecuted $event) use (&$queries): void {
        $queries[] = $event->sql;
    });

    $testCase = $this;
    $measureRoute = function (string $routeName, ChatSession $session) use (&$queries, $testCase, $user): int {
        $queryOffset = count($queries);

        $testCase->actingAs($user)
            ->get(route($routeName, $session->id))
            ->assertOk();

        return count($queries) - $queryOffset;
    };

    $this->app['env'] = 'local';

    $smallEagerQueries = $measureRoute('agentic-chat.sessions.show', $smallSession);
    $largeEagerQueries = $measureRoute('agentic-chat.sessions.show', $largeSession);
    $smallNaiveQueries = $measureRoute('agentic-chat.sessions.show-naive', $smallSession);
    $largeNaiveQueries = $measureRoute('agentic-chat.sessions.show-naive', $largeSession);

    expect($largeEagerQueries)->toBeLessThanOrEqual($smallEagerQueries + 4);
    expect($smallNaiveQueries)->toBeGreaterThan($smallEagerQueries);
    expect($largeNaiveQueries)->toBeGreaterThan($smallNaiveQueries);
    expect($largeNaiveQueries - $smallNaiveQueries)->toBeGreaterThan($largeEagerQueries - $smallEagerQueries);
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

test('tool execution log factory default state matches its resume parser tool', function () {
    $log = ToolExecutionLog::factory()->create();

    expect($log->loggable_type)->toBe('resume_parser');
    expect($log->tool_name)->toBe('parse_resume');
    expect($log->loggable)->toBeInstanceOf(ResumeParserAgent::class);
});

test('tool execution log factory states match their polymorphic agents and tools', function (string $state, string $type, string $toolName, string $agentClass) {
    $log = ToolExecutionLog::factory()->{$state}()->create();

    expect($log->loggable_type)->toBe($type);
    expect($log->tool_name)->toBe($toolName);
    expect($log->loggable)->toBeInstanceOf($agentClass);
})->with([
    'resume parser' => ['resumeParser', 'resume_parser', 'parse_resume', ResumeParserAgent::class],
    'candidate scoring' => ['candidateScoring', 'candidate_scoring', 'score_candidate', CandidateScoringAgent::class],
    'hiring recommendation' => ['hiringRecommendation', 'hiring_recommendation', 'generate_shortlist', HiringRecommendationAgent::class],
]);

test('creating a chat message touches its chat session timestamp', function () {
    $session = ChatSession::factory()->create();
    DB::table('chat_sessions')
        ->where('id', $session->id)
        ->update(['updated_at' => now()->subDay()]);
    $previousUpdatedAt = $session->fresh()->updated_at;

    ChatMessage::factory()->create(['chat_session_id' => $session->id]);

    expect($session->fresh()->updated_at)->toBeGreaterThan($previousUpdatedAt);
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
    $configuredEmail = 'agentic-chat-demo@example.test';
    config(['agentic-chat.demo_user_email' => $configuredEmail]);
    $unrelatedUser = User::factory()->create();

    $this->seed(AgenticChatSeeder::class);

    $demoUser = User::query()->where('email', $configuredEmail)->firstOrFail();

    expect(ChatSession::query()->where('user_id', $demoUser->id)->count())->toBe(2);
    expect(ChatSession::query()->where('user_id', $unrelatedUser->id)->exists())->toBeFalse();
    expect(ChatSession::query()->count())->toBeGreaterThanOrEqual(2);
    expect(ChatMessage::query()->count())->toBeGreaterThanOrEqual(4);
    expect(ToolExecutionLog::query()->count())->toBeGreaterThanOrEqual(3);
    expect(ResumeParserAgent::query()->count())->toBeGreaterThanOrEqual(1);
    expect(CandidateScoringAgent::query()->count())->toBeGreaterThanOrEqual(1);
    expect(HiringRecommendationAgent::query()->count())->toBeGreaterThanOrEqual(1);

    $expectedTypes = [
        'parse_resume' => 'resume_parser',
        'score_candidate' => 'candidate_scoring',
        'compare_candidates' => 'candidate_scoring',
        'generate_shortlist' => 'hiring_recommendation',
        'schedule_interview' => 'hiring_recommendation',
    ];

    expect(ToolExecutionLog::query()->get()->every(
        fn (ToolExecutionLog $log): bool => ($expectedTypes[$log->tool_name] ?? null) === $log->loggable_type,
    ))->toBeTrue();
});

test('seeder is idempotent', function () {
    $this->seed(AgenticChatSeeder::class);
    $firstRunCounts = [
        User::query()->count(),
        ChatSession::query()->count(),
        ChatMessage::query()->count(),
        ToolExecutionLog::query()->count(),
        ResumeParserAgent::query()->count(),
        CandidateScoringAgent::query()->count(),
        HiringRecommendationAgent::query()->count(),
    ];

    $this->seed(AgenticChatSeeder::class);
    $secondRunCounts = [
        User::query()->count(),
        ChatSession::query()->count(),
        ChatMessage::query()->count(),
        ToolExecutionLog::query()->count(),
        ResumeParserAgent::query()->count(),
        CandidateScoringAgent::query()->count(),
        HiringRecommendationAgent::query()->count(),
    ];

    expect($secondRunCounts)->toBe($firstRunCounts);
});

test('agentic chat seeder refuses to create demo accounts outside local and testing', function () {
    $this->app['env'] = 'production';

    expect(fn () => $this->seed(AgenticChatSeeder::class))
        ->toThrow(LogicException::class);
    expect(User::query()->count())->toBe(0);
});
