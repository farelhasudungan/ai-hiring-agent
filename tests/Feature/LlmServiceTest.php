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

test('senopati streaming stops at the done marker and keeps usage metadata', function () {
    $payload = "data: {\"model\":\"qwen3:14b\",\"choices\":[{\"delta\":{\"content\":\"Halo \"}}]}\n\n"
        ."data: {\"choices\":[{\"delta\":{\"content\":\"dunia\"}}],\"usage\":{\"prompt_tokens\":5,\"completion_tokens\":6}}\n\n"
        ."data: [DONE]\n\n"
        ."data: {\"choices\":[{\"delta\":{\"content\":\"SETELAH DONE\"}}]}\n\n";

    Http::fake([
        'https://senopati.its.ac.id/v1/chat/completions' => Http::response($payload, 200, [
            'Content-Type' => 'text/event-stream',
        ]),
    ]);

    $chunks = [];
    $client = new SenopatiClient('https://senopati.its.ac.id/v1', 'qwen3:14b', 'test-api-key');
    $result = $client->stream([
        ['role' => 'user', 'content' => 'Halo'],
    ], function (string $chunk) use (&$chunks): void {
        $chunks[] = $chunk;
    });

    expect($chunks)->toBe(['Halo ', 'dunia'])
        ->and($result['content'])->toBe('Halo dunia')
        ->and($result['prompt_tokens'])->toBe(5)
        ->and($result['completion_tokens'])->toBe(6)
        ->and($result['total_tokens'])->toBe(11)
        ->and($result['provider'])->toBe('senopati');
});

test('ollama client reports malformed responses as llm exceptions', function () {
    Http::fake([
        'http://localhost:11434/api/chat' => Http::response(['model' => 'qwen3:14b', 'done' => true], 200),
    ]);

    $client = new OllamaClient('http://localhost:11434', 'qwen3:14b');

    expect(fn () => $client->chat([['role' => 'user', 'content' => 'Halo']]))
        ->toThrow(LlmException::class, 'Respons Ollama tidak memuat konten jawaban.');
});

test('llm manager streams through senopati when ollama fails before the first chunk', function () {
    $primary = new class () implements LlmClientInterface {
        public function chat(array $messages): array
        {
            throw new LlmException('Layanan Ollama tidak dapat dihubungi.', 503, null, 'ollama', true);
        }

        public function stream(array $messages, Closure $onChunk): array
        {
            throw new LlmException('Layanan Ollama tidak dapat dihubungi.', 503, null, 'ollama', true);
        }
    };

    $fallback = new class () implements LlmClientInterface {
        public bool $streamUsed = false;

        public function chat(array $messages): array
        {
            throw new LlmException('chat tidak dipakai pada test ini.');
        }

        public function stream(array $messages, Closure $onChunk): array
        {
            $this->streamUsed = true;
            $onChunk('jawaban senopati');

            return [
                'content' => 'jawaban senopati',
                'model' => 'qwen3:14b',
                'provider' => 'senopati',
                'prompt_tokens' => 1,
                'completion_tokens' => 2,
                'total_tokens' => 3,
            ];
        }
    };

    $chunks = [];
    $result = llmManagerWithClients($primary, $fallback)->stream(
        [['role' => 'user', 'content' => 'Halo']],
        function (string $chunk) use (&$chunks): void {
            $chunks[] = $chunk;
        }
    );

    expect($fallback->streamUsed)->toBeTrue()
        ->and($chunks)->toBe(['jawaban senopati'])
        ->and($result['provider'])->toBe('senopati');
});

test('llm manager never switches provider after ollama already emitted a chunk', function () {
    $primary = new class () implements LlmClientInterface {
        public function chat(array $messages): array
        {
            throw new LlmException('chat tidak dipakai pada test ini.');
        }

        public function stream(array $messages, Closure $onChunk): array
        {
            $onChunk('sebagian jawaban ollama');

            throw new LlmException('Layanan Ollama tidak dapat dihubungi.', 503, null, 'ollama', true);
        }
    };

    $fallback = new class () implements LlmClientInterface {
        public bool $streamUsed = false;

        public function chat(array $messages): array
        {
            throw new LlmException('chat tidak dipakai pada test ini.');
        }

        public function stream(array $messages, Closure $onChunk): array
        {
            $this->streamUsed = true;
            $onChunk('jawaban senopati');

            return [];
        }
    };

    $chunks = [];
    $manager = llmManagerWithClients($primary, $fallback);

    expect(function () use ($manager, &$chunks): void {
        $manager->stream(
            [['role' => 'user', 'content' => 'Halo']],
            function (string $chunk) use (&$chunks): void {
                $chunks[] = $chunk;
            }
        );
    })->toThrow(LlmException::class, 'Respons streaming terputus sebelum selesai. Silakan ulangi permintaan.');

    expect($chunks)->toBe(['sebagian jawaban ollama'])
        ->and($fallback->streamUsed)->toBeFalse();
});

test('llm manager does not hide unexpected programming errors behind a fallback', function () {
    $primary = new class () implements LlmClientInterface {
        public function chat(array $messages): array
        {
            throw new TypeError('Unexpected internal type error');
        }

        public function stream(array $messages, Closure $onChunk): array
        {
            throw new TypeError('Unexpected internal type error');
        }
    };

    $fallback = new class () implements LlmClientInterface {
        public bool $chatUsed = false;

        public function chat(array $messages): array
        {
            $this->chatUsed = true;

            return [];
        }

        public function stream(array $messages, Closure $onChunk): array
        {
            return [];
        }
    };

    $manager = llmManagerWithClients($primary, $fallback);

    expect(fn () => $manager->chat([['role' => 'user', 'content' => 'Halo']]))
        ->toThrow(LlmException::class, 'Terjadi kesalahan internal saat memproses permintaan LLM.');

    expect($fallback->chatUsed)->toBeFalse();
});

/**
 * Build a manager whose provider clients are stubbed instead of built from config.
 */
function llmManagerWithClients(LlmClientInterface $primary, LlmClientInterface $fallback): LlmManager
{
    return new class ($primary, $fallback) extends LlmManager {
        public function __construct(
            private readonly LlmClientInterface $primaryClient,
            private readonly LlmClientInterface $fallbackClient
        ) {
            parent::__construct('ollama');
        }

        public function client(?string $provider = null): LlmClientInterface
        {
            return $provider === 'senopati' ? $this->fallbackClient : $this->primaryClient;
        }
    };
}
