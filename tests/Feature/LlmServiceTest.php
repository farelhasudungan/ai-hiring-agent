<?php

use App\Services\Llm\Exceptions\LlmException;
use App\Services\Llm\LlmClientInterface;
use App\Services\Llm\LlmManager;
use App\Services\Llm\OllamaClient;
use App\Services\Llm\SenopatiClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

test('ollama client parses response and tokens correctly', function () {
    Http::fake([
        'http://localhost:11434/api/chat' => Http::response([
            'model' => 'qwen3:14b',
            'created_at' => '2026-11-16T00:00:00Z',
            'message' => [
                'role' => 'assistant',
                'content' => 'Halo! Saya asisten rekrutmen Roku.',
            ],
            'done' => true,
            'prompt_eval_count' => 12,
            'eval_count' => 25,
        ], 200),
    ]);

    $client = new OllamaClient('http://localhost:11434', 'qwen3:14b');
    $result = $client->chat([
        ['role' => 'user', 'content' => 'Halo!'],
    ]);

    expect($result)->toBe([
        'content' => 'Halo! Saya asisten rekrutmen Roku.',
        'model' => 'qwen3:14b',
        'provider' => 'ollama',
        'prompt_tokens' => 12,
        'completion_tokens' => 25,
        'total_tokens' => 37,
    ]);
});

test('senopati client parses openai format and usage correctly', function () {
    Http::fake([
        'https://senopati.its.ac.id/v1/chat/completions' => Http::response([
            'id' => 'chatcmpl-123',
            'object' => 'chat.completion',
            'model' => 'qwen3:14b',
            'choices' => [
                [
                    'index' => 0,
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Senopati AI siap membantu evaluasi kandidat.',
                    ],
                    'finish_reason' => 'stop',
                ],
            ],
            'usage' => [
                'prompt_tokens' => 18,
                'completion_tokens' => 32,
                'total_tokens' => 50,
            ],
        ], 200),
    ]);

    $client = new SenopatiClient('https://senopati.its.ac.id/v1', 'qwen3:14b', 'test-api-key');
    $result = $client->chat([
        ['role' => 'user', 'content' => 'Siapa kamu?'],
    ]);

    expect($result)->toBe([
        'content' => 'Senopati AI siap membantu evaluasi kandidat.',
        'model' => 'qwen3:14b',
        'provider' => 'senopati',
        'prompt_tokens' => 18,
        'completion_tokens' => 32,
        'total_tokens' => 50,
    ]);
});

test('llm manager automatically falls back to senopati when ollama fails', function () {
    Log::spy();

    Http::fake([
        'http://localhost:11434/api/chat' => Http::response('Connection refused', 503),
        'https://senopati.its.ac.id/v1/chat/completions' => Http::response([
            'id' => 'chatcmpl-fallback',
            'model' => 'qwen3:14b',
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Respons berhasil diperoleh via fallback Senopati.',
                    ],
                ],
            ],
            'usage' => [
                'prompt_tokens' => 15,
                'completion_tokens' => 20,
                'total_tokens' => 35,
            ],
        ], 200),
    ]);

    $manager = app(LlmManager::class);
    $result = $manager->chat([
        ['role' => 'user', 'content' => 'Tes fallback'],
    ]);

    expect($result['provider'])->toBe('senopati')
        ->and($result['content'])->toBe('Respons berhasil diperoleh via fallback Senopati.')
        ->and($result['total_tokens'])->toBe(35);

    Log::shouldHaveReceived('warning')->once();
});

test('llm manager throws exception when both providers fail', function () {
    Log::spy();

    Http::fake([
        'http://localhost:11434/api/chat' => Http::response('Ollama down', 503),
        'https://senopati.its.ac.id/v1/chat/completions' => Http::response('Senopati down', 503),
    ]);

    $manager = app(LlmManager::class);

    expect(fn () => $manager->chat([
        ['role' => 'user', 'content' => 'Tes gagal total'],
    ]))->toThrow(LlmException::class);
});

test('llm client interface resolves to llm manager from service container', function () {
    $client = app(LlmClientInterface::class);

    expect($client)->toBeInstanceOf(LlmManager::class);
});
