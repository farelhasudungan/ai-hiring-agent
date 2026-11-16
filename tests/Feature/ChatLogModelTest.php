<?php

use App\Models\ChatLog;
use App\Models\ChatSession;
use App\Models\User;
use Database\Seeders\ChatLogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('chat log belongs to user and chat session', function () {
    $user = User::factory()->create();
    $session = ChatSession::factory()->create(['user_id' => $user->id]);

    $log = ChatLog::factory()->create([
        'chat_session_id' => $session->id,
        'user_id' => $user->id,
    ]);

    expect($log->user->id)->toBe($user->id)
        ->and($log->chatSession->id)->toBe($session->id);
});

test('user and chat session have many chat logs', function () {
    $user = User::factory()->create();
    $session = ChatSession::factory()->create(['user_id' => $user->id]);

    ChatLog::factory()->count(3)->create([
        'chat_session_id' => $session->id,
        'user_id' => $user->id,
    ]);

    expect($user->chatLogs)->toHaveCount(3)
        ->and($session->chatLogs)->toHaveCount(3);
});

test('chat log seeder runs successfully', function () {
    $this->seed(ChatLogSeeder::class);

    expect(ChatLog::query()->count())->toBe(2);
});
