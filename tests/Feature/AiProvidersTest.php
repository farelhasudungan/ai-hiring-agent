<?php

use App\Models\AiProviderSetting;
use App\Models\Company;
use App\Services\Ai\AiProviderManager;
use App\Services\Ai\AiService;
use App\Services\Ai\Exceptions\AiProviderException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Http::preventStrayRequests();
});

/**
 * @return array<string, array{0: string, 1: string, 2: string, 3: array<string, mixed>, 4: string}>
 */
dataset('providers', [
    'openai' => ['openai', 'https://api.openai.com/v1/chat/completions', 'gpt-4o-mini', [
        'model' => 'gpt-4o-mini-2024',
        'choices' => [['message' => ['content' => 'Hello']]],
        'usage' => ['prompt_tokens' => 11, 'completion_tokens' => 3],
    ], 'Authorization'],
    'openrouter' => ['openrouter', 'https://openrouter.ai/api/v1/chat/completions', 'openai/gpt-4o-mini', [
        'model' => 'openai/gpt-4o-mini',
        'choices' => [['message' => ['content' => 'Hello']]],
        'usage' => ['prompt_tokens' => 11, 'completion_tokens' => 3],
    ], 'Authorization'],
    'claude' => ['claude', 'https://api.anthropic.com/v1/messages', 'claude-haiku-5-5', [
        'model' => 'claude-haiku-5-5',
        'content' => [['type' => 'text', 'text' => 'Hel'], ['type' => 'text', 'text' => 'lo']],
        'usage' => ['input_tokens' => 11, 'output_tokens' => 3],
    ], 'x-api-key'],
    'gemini' => ['gemini', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent', 'gemini-2.0-flash', [
        'candidates' => [['content' => ['parts' => [['text' => 'Hel'], ['text' => 'lo']]]]],
        'usageMetadata' => ['promptTokenCount' => 11, 'candidatesTokenCount' => 3],
    ], 'x-goog-api-key'],
]);

test('each provider sends the key in a header and returns normalized text', function (string $provider, string $url, string $model, array $body, string $authHeader) {
    Http::fake([$url => Http::response($body)]);

    $response = app(AiProviderManager::class)->driver($provider)->complete('Be brief.', 'Say hello', $model, 'secret-key', 50);

    expect($response->text)->toBe('Hello')
        ->and($response->inputTokens)->toBe(11)
        ->and($response->outputTokens)->toBe(3);

    Http::assertSent(function (Request $request) use ($url, $authHeader) {
        return $request->url() === $url
            && str_contains($request->header($authHeader)[0], 'secret-key')
            && ! str_contains($request->url(), 'secret-key')
            && str_contains($request->body(), 'Be brief.')
            && str_contains($request->body(), 'Say hello');
    });
})->with('providers');

test('providers omit the system prompt when it is empty', function (string $provider, string $url, string $model, array $body) {
    Http::fake([$url => Http::response($body)]);

    app(AiProviderManager::class)->driver($provider)->complete('', 'Say hello', $model, 'secret-key');

    Http::assertSent(fn (Request $request) => ! str_contains($request->body(), '"system"') && ! str_contains($request->body(), 'systemInstruction'));
})->with('providers');

test('provider failures become safe exceptions without leaking the key or body', function (int $status, string $message) {
    Http::fake(['https://api.openai.com/*' => Http::response(['error' => 'bad key secret-key'], $status)]);

    try {
        app(AiProviderManager::class)->driver('openai')->complete('', 'hi', 'gpt-4o-mini', 'secret-key');
        $this->fail('Expected an AiProviderException.');
    } catch (AiProviderException $exception) {
        expect($exception->getMessage())->toBe($message)->not->toContain('secret-key');
    }
})->with([
    'unauthorized' => [401, 'The provider rejected the API key.'],
    'missing model' => [404, 'The provider could not use that model. Pick a model from the list.'],
    'rate limited' => [429, 'The provider rate limit was reached. Try again shortly.'],
    'server error' => [503, 'The provider is unavailable right now. Try again later.'],
]);

test('an empty provider answer is an error', function () {
    Http::fake(['https://api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => '  ']]]])]);

    app(AiProviderManager::class)->driver('openai')->complete('', 'hi', 'gpt-4o-mini', 'k');
})->throws(AiProviderException::class, 'The provider returned an empty response.');

test('an unknown provider name is rejected', function () {
    app(AiProviderManager::class)->driver('skynet');
})->throws(InvalidArgumentException::class);

test('the ai service uses the provider, model and key the company configured', function () {
    $company = Company::factory()->create();
    AiProviderSetting::factory()->for($company)->forProvider('claude', 'claude-sonnet-5-5')->create(['api_key' => 'company-key']);
    Http::fake(['https://api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => 'Done']]])]);

    $response = app(AiService::class)->complete($company, 'sys', 'task');

    expect($response->text)->toBe('Done');
    Http::assertSent(fn (Request $request) => $request->header('x-api-key')[0] === 'company-key'
        && $request['model'] === 'claude-sonnet-5-5');
});

test('the ai service fails clearly when the company has no provider', function () {
    app(AiService::class)->complete(Company::factory()->create(), 'sys', 'task');
})->throws(DomainException::class, 'has not configured an AI provider');

test('each provider lists the models its key can use', function (string $provider, string $url, array $body, array $expected) {
    Http::fake([$url.'*' => Http::response($body)]);

    expect(app(AiProviderManager::class)->driver($provider)->listModels('secret-key'))->toBe($expected);

    Http::assertSent(fn (Request $request) => $request->method() === 'GET' && ! str_contains($request->url(), 'secret-key'));
})->with([
    'openai keeps chat models only' => ['openai', 'https://api.openai.com/v1/models', ['data' => [
        ['id' => 'text-embedding-3-small'], ['id' => 'gpt-4o'], ['id' => 'whisper-1'], ['id' => 'o3-mini'], ['id' => 'gpt-4o'], ['id' => 'gpt-4o-audio-preview'],
    ]], ['gpt-4o', 'o3-mini']],
    'openrouter keeps everything' => ['openrouter', 'https://openrouter.ai/api/v1/models', ['data' => [
        ['id' => 'openai/gpt-4o'], ['id' => 'anthropic/claude-3.5-sonnet'],
    ]], ['anthropic/claude-3.5-sonnet', 'openai/gpt-4o']],
    'claude' => ['claude', 'https://api.anthropic.com/v1/models', ['data' => [
        ['id' => 'claude-sonnet-5-5'], ['id' => 'claude-haiku-5-5'],
    ]], ['claude-haiku-5-5', 'claude-sonnet-5-5']],
    'gemini strips prefix and needs generateContent' => ['gemini', 'https://generativelanguage.googleapis.com/v1beta/models', ['models' => [
        ['name' => 'models/gemini-2.0-flash', 'supportedGenerationMethods' => ['generateContent']],
        ['name' => 'models/text-embedding-004', 'supportedGenerationMethods' => ['embedContent']],
    ]], ['gemini-2.0-flash']],
]);

test('listing models surfaces provider errors safely', function () {
    Http::fake(['https://api.openai.com/*' => Http::response(['error' => 'secret-key'], 401)]);

    app(AiProviderManager::class)->driver('openai')->listModels('secret-key');
})->throws(AiProviderException::class, 'The provider rejected the API key.');

/**
 * @return array<string, array{0: string, 1: string, 2: string, 3: string}>
 */
dataset('streaming providers', [
    'openai' => ['openai', 'https://api.openai.com/v1/chat/completions', 'gpt-4o-mini',
        "data: {\"choices\":[{\"delta\":{\"role\":\"assistant\"}}]}\n\ndata: {\"choices\":[{\"delta\":{\"content\":\"Hel\"}}]}\n\ndata: {\"choices\":[{\"delta\":{\"content\":\"lo\"}}]}\n\ndata: [DONE]\n\n"],
    'openrouter' => ['openrouter', 'https://openrouter.ai/api/v1/chat/completions', 'openai/gpt-4o-mini',
        ": OPENROUTER PROCESSING\n\ndata: {\"choices\":[{\"delta\":{\"content\":\"Hel\"}}]}\n\ndata: {\"choices\":[{\"delta\":{\"content\":\"lo\"}}]}\n\ndata: [DONE]\n\n"],
    'claude' => ['claude', 'https://api.anthropic.com/v1/messages', 'claude-haiku-5-5',
        "event: message_start\ndata: {\"type\":\"message_start\"}\n\nevent: content_block_delta\ndata: {\"type\":\"content_block_delta\",\"delta\":{\"type\":\"text_delta\",\"text\":\"Hel\"}}\n\nevent: content_block_delta\ndata: {\"type\":\"content_block_delta\",\"delta\":{\"type\":\"text_delta\",\"text\":\"lo\"}}\n\nevent: message_stop\ndata: {\"type\":\"message_stop\"}\n\n"],
    'gemini' => ['gemini', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:streamGenerateContent*', 'gemini-2.0-flash',
        "data: {\"candidates\":[{\"content\":{\"parts\":[{\"text\":\"Hel\"}]}}]}\r\n\r\ndata: {\"candidates\":[{\"content\":{\"parts\":[{\"text\":\"lo\"}]}}]}\r\n\r\n"],
]);

test('each provider streams text chunks from server sent events', function (string $provider, string $url, string $model, string $stream) {
    Http::fake([$url => Http::response($stream, 200, ['Content-Type' => 'text/event-stream'])]);

    $chunks = iterator_to_array(app(AiProviderManager::class)->driver($provider)->stream('Be brief.', 'Say hello', $model, 'secret-key', 50), false);

    expect(implode('', $chunks))->toBe('Hello');

    Http::assertSent(fn (Request $request) => str_contains($request->body(), 'Say hello')
        && ! str_contains($request->url(), 'secret-key'));
})->with('streaming providers');

test('a stream without any text fails as an empty response', function () {
    Http::fake(['https://api.openai.com/v1/chat/completions' => Http::response("data: [DONE]\n\n")]);

    iterator_to_array(app(AiProviderManager::class)->driver('openai')->stream('', 'Hi', 'gpt-4o-mini', 'key'));
})->throws(AiProviderException::class);

test('a rejected stream request surfaces the provider error', function () {
    Http::fake(['https://api.openai.com/v1/chat/completions' => Http::response('', 401)]);

    iterator_to_array(app(AiProviderManager::class)->driver('openai')->stream('', 'Hi', 'gpt-4o-mini', 'key'));
})->throws(AiProviderException::class, 'rejected the API key');

test('the ai service streams with the company provider and requires a setting', function () {
    $company = Company::factory()->create();

    expect(fn () => app(AiService::class)->stream($company, '', 'Hi'))->toThrow(DomainException::class);

    AiProviderSetting::factory()->forProvider('openai', 'gpt-4o-mini')->for($company)->create();
    Http::fake(['https://api.openai.com/v1/chat/completions' => Http::response("data: {\"choices\":[{\"delta\":{\"content\":\"Hi\"}}]}\n\ndata: [DONE]\n\n")]);

    expect(implode('', iterator_to_array(app(AiService::class)->stream($company->fresh(), '', 'Hi'), false)))->toBe('Hi');
});
