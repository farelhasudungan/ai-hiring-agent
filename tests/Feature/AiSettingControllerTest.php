<?php

use App\Models\AiProviderSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Http::preventStrayRequests();
});

function aiPayload(array $overrides = []): array
{
    return array_merge(['provider' => 'openai', 'model' => 'gpt-4o-mini', 'api_key' => 'sk-live-secret'], $overrides);
}

test('guests are redirected to login', function () {
    $this->get(route('settings.ai.edit'))->assertRedirectToRoute('login');
    $this->post(route('settings.ai.update'), aiPayload())->assertRedirectToRoute('login');
    $this->post(route('settings.ai.test'), aiPayload())->assertRedirectToRoute('login');
});

test('users without a company cannot open AI settings', function () {
    $this->actingAs(User::factory()->withoutCompany()->create())->get(route('settings.ai.edit'))->assertForbidden();
});

test('hr saves settings and the key is stored encrypted', function () {
    $user = User::factory()->create(['role' => 'hr']);

    $this->actingAs($user)->post(route('settings.ai.update'), aiPayload(['company_id' => 999]))
        ->assertRedirectToRoute('settings.ai.edit')->assertSessionHas('status', 'AI settings saved.');

    $setting = AiProviderSetting::query()->sole();
    expect($setting->company_id)->toBe($user->company_id)
        ->and($setting->provider)->toBe('openai')
        ->and($setting->api_key)->toBe('sk-live-secret')
        ->and(DB::table('ai_provider_settings')->value('api_key'))->not->toContain('sk-live-secret')
        ->and($setting->toArray())->not->toHaveKey('api_key');
});

test('the page shows a summary first and the form only when editing', function () {
    $user = User::factory()->create(['role' => 'admin']);
    AiProviderSetting::factory()->for($user->company)->create(['model' => 'gpt-4o-mini', 'api_key' => 'sk-live-secret']);

    $this->actingAs($user)->get(route('settings.ai.edit'))->assertOk()
        ->assertSee('gpt-4o-mini')->assertSee(AiProviderSetting::KEY_MASK)->assertSee('Edit settings')
        ->assertDontSee('Save settings')->assertDontSee('sk-live-secret');

    $this->actingAs($user)->get(route('settings.ai.edit', ['edit' => 1]))->assertOk()
        ->assertSee('Save settings')->assertDontSee('sk-live-secret')
        ->assertSee('value="'.AiProviderSetting::KEY_MASK.'"', false);
});

test('a company without settings is invited to set up, not dropped into the form', function () {
    $this->actingAs(User::factory()->create(['role' => 'hr']))->get(route('settings.ai.edit'))->assertOk()
        ->assertSee('Set up AI provider')->assertDontSee('Save settings');
});

test('the form reopens when validation fails', function () {
    $user = User::factory()->create(['role' => 'hr']);

    $this->actingAs($user)->followingRedirects()->from(route('settings.ai.edit'))
        ->post(route('settings.ai.update'), aiPayload(['provider' => 'skynet']))
        ->assertSee('Save settings');
});

test('submitting the masked placeholder keeps the stored key', function () {
    $user = User::factory()->create(['role' => 'hr']);
    AiProviderSetting::factory()->for($user->company)->create(['api_key' => 'old-key']);

    $this->actingAs($user)->post(route('settings.ai.update'), aiPayload(['model' => 'gpt-4o', 'api_key' => AiProviderSetting::KEY_MASK]))
        ->assertSessionHasNoErrors();

    $setting = $user->company->aiProviderSetting()->first();
    expect($setting->model)->toBe('gpt-4o')->and($setting->api_key)->toBe('old-key');
});

test('the masked placeholder is not accepted as a first key', function () {
    $user = User::factory()->create(['role' => 'hr']);

    $this->actingAs($user)->post(route('settings.ai.update'), aiPayload(['api_key' => AiProviderSetting::KEY_MASK]))
        ->assertSessionHasErrors('api_key');

    $this->assertDatabaseCount('ai_provider_settings', 0);
});

test('connection test and model list treat the masked placeholder as the stored key', function () {
    $user = User::factory()->create(['role' => 'hr']);
    AiProviderSetting::factory()->for($user->company)->create(['api_key' => 'stored-key']);
    Http::fake([
        'https://api.openai.com/v1/models' => Http::response(['data' => [['id' => 'gpt-4o']]]),
        'https://api.openai.com/v1/chat/completions' => Http::response(['choices' => [['message' => ['content' => 'OK']]]]),
    ]);

    $this->actingAs($user)->postJson(route('settings.ai.models'), ['provider' => 'openai', 'api_key' => AiProviderSetting::KEY_MASK])->assertOk();
    $this->actingAs($user)->post(route('settings.ai.test'), aiPayload(['api_key' => AiProviderSetting::KEY_MASK]))->assertSessionHas('connection_status');

    Http::assertSentCount(2);
    Http::assertSent(fn (Request $request) => $request->header('Authorization')[0] === 'Bearer stored-key');
});

test('leaving the key blank keeps the stored key', function () {
    $user = User::factory()->create(['role' => 'hr']);
    AiProviderSetting::factory()->for($user->company)->create(['api_key' => 'old-key']);

    $this->actingAs($user)->post(route('settings.ai.update'), aiPayload(['provider' => 'gemini', 'model' => 'gemini-2.0-flash', 'api_key' => '']))
        ->assertRedirectToRoute('settings.ai.edit');

    $setting = $user->company->aiProviderSetting()->first();
    expect($setting->provider)->toBe('gemini')->and($setting->api_key)->toBe('old-key');
    $this->assertDatabaseCount('ai_provider_settings', 1);
});

test('a new key replaces the stored key', function () {
    $user = User::factory()->create(['role' => 'hr']);
    AiProviderSetting::factory()->for($user->company)->create(['api_key' => 'old-key']);

    $this->actingAs($user)->post(route('settings.ai.update'), aiPayload(['api_key' => 'new-key']));

    expect($user->company->aiProviderSetting()->first()->api_key)->toBe('new-key');
});

test('settings validate input and never flash the api key', function (array $overrides, string $field) {
    $user = User::factory()->create(['role' => 'hr']);

    $this->actingAs($user)->post(route('settings.ai.update'), aiPayload($overrides))
        ->assertSessionHasErrors($field)->assertSessionMissing('_old_input.api_key');

    $this->assertDatabaseCount('ai_provider_settings', 0);
})->with([
    'unknown provider' => [['provider' => 'skynet'], 'provider'],
    'missing model' => [['model' => ''], 'model'],
    'model with path traversal' => [['model' => '../admin'], 'model'],
    'missing key on first save' => [['api_key' => ''], 'api_key'],
]);

test('viewers and interviewers can see the provider but not change it', function (string $role) {
    $user = User::factory()->create(['role' => $role]);
    AiProviderSetting::factory()->for($user->company)->create(['model' => 'gpt-4o-mini', 'api_key' => 'sk-live-secret']);

    $this->actingAs($user)->get(route('settings.ai.edit'))->assertOk()
        ->assertSee('gpt-4o-mini')->assertDontSee('sk-live-secret')->assertDontSee('Save settings');
    $this->actingAs($user)->post(route('settings.ai.update'), aiPayload(['model' => 'other']))->assertForbidden();
    $this->actingAs($user)->post(route('settings.ai.test'), aiPayload())->assertForbidden();

    expect(AiProviderSetting::query()->sole()->model)->toBe('gpt-4o-mini');
})->with(['viewer', 'interviewer']);

test('a company never sees or changes another company setting', function () {
    $other = AiProviderSetting::factory()->create(['model' => 'other-model', 'api_key' => 'other-key']);
    $user = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user)->get(route('settings.ai.edit'))->assertOk()->assertDontSee('other-model');
    $this->actingAs($user)->post(route('settings.ai.update'), aiPayload());

    expect($other->fresh()->api_key)->toBe('other-key')->and(AiProviderSetting::query()->count())->toBe(2);
});

test('testing the connection uses the typed key and reports success', function () {
    $user = User::factory()->create(['role' => 'hr']);
    Http::fake(['https://api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => 'OK']]]])]);

    $this->actingAs($user)->post(route('settings.ai.test'), aiPayload())
        ->assertRedirectToRoute('settings.ai.edit')->assertSessionHas('connection_status');

    Http::assertSent(fn (Request $request) => $request->header('Authorization')[0] === 'Bearer sk-live-secret');
    $this->assertDatabaseCount('ai_provider_settings', 0);
});

test('testing the connection falls back to the stored key', function () {
    $user = User::factory()->create(['role' => 'hr']);
    AiProviderSetting::factory()->for($user->company)->create(['api_key' => 'stored-key']);
    Http::fake(['https://api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => 'OK']]]])]);

    $this->actingAs($user)->post(route('settings.ai.test'), aiPayload(['api_key' => '']))->assertSessionHas('connection_status');

    Http::assertSent(fn (Request $request) => $request->header('Authorization')[0] === 'Bearer stored-key');
});

test('a failed connection test shows the reason without leaking the key', function () {
    $user = User::factory()->create(['role' => 'hr']);
    Http::fake(['https://api.openai.com/*' => Http::response(['error' => 'sk-live-secret invalid'], 401)]);

    $this->actingAs($user)->post(route('settings.ai.test'), aiPayload())
        ->assertSessionHasErrors(['api_key' => 'The provider rejected the API key.'])
        ->assertSessionMissing('_old_input.api_key');
});

test('models are loaded from the provider with the typed key', function () {
    $user = User::factory()->create(['role' => 'hr']);
    Http::fake(['https://api.openai.com/v1/models' => Http::response(['data' => [['id' => 'gpt-4o'], ['id' => 'gpt-4o-mini']]])]);

    $this->actingAs($user)->postJson(route('settings.ai.models'), ['provider' => 'openai', 'api_key' => 'sk-live-secret'])
        ->assertOk()->assertExactJson(['models' => ['gpt-4o', 'gpt-4o-mini']]);

    Http::assertSent(fn (Request $request) => $request->header('Authorization')[0] === 'Bearer sk-live-secret');
});

test('models are loaded with the stored key when none is typed', function () {
    $user = User::factory()->create(['role' => 'hr']);
    AiProviderSetting::factory()->for($user->company)->create(['api_key' => 'stored-key']);
    Http::fake(['https://api.openai.com/v1/models' => Http::response(['data' => [['id' => 'gpt-4o']]])]);

    $this->actingAs($user)->postJson(route('settings.ai.models'), ['provider' => 'openai'])->assertOk();

    Http::assertSent(fn (Request $request) => $request->header('Authorization')[0] === 'Bearer stored-key');
});

test('loading models needs a key, a known provider and a manager role', function () {
    $hr = User::factory()->create(['role' => 'hr']);

    $this->actingAs($hr)->postJson(route('settings.ai.models'), ['provider' => 'openai'])->assertUnprocessable()->assertJsonValidationErrors('api_key');
    $this->actingAs($hr)->postJson(route('settings.ai.models'), ['provider' => 'skynet', 'api_key' => 'k'])->assertJsonValidationErrors('provider');
    $this->actingAs(User::factory()->create(['role' => 'viewer']))->postJson(route('settings.ai.models'), ['provider' => 'openai', 'api_key' => 'k'])->assertForbidden();
});

test('a provider failure while loading models returns a safe message', function () {
    $user = User::factory()->create(['role' => 'hr']);
    Http::fake(['https://api.openai.com/*' => Http::response(['error' => 'sk-live-secret'], 401)]);

    $this->actingAs($user)->postJson(route('settings.ai.models'), ['provider' => 'openai', 'api_key' => 'sk-live-secret'])
        ->assertUnprocessable()->assertExactJson(['message' => 'The provider rejected the API key.']);
});

test('openrouter models load without any api key', function () {
    $user = User::factory()->create(['role' => 'hr']);
    Http::fake(['https://openrouter.ai/api/v1/models' => Http::response(['data' => [['id' => 'openai/gpt-4o']]])]);

    $this->actingAs($user)->postJson(route('settings.ai.models'), ['provider' => 'openrouter'])
        ->assertOk()->assertExactJson(['models' => ['openai/gpt-4o']]);

    Http::assertSent(fn (Request $request) => ! $request->hasHeader('Authorization'));
});

test('connection test passes when a reasoning model returns no text', function () {
    $user = User::factory()->create(['role' => 'hr']);
    Http::fake(['https://openrouter.ai/api/v1/chat/completions' => Http::response(['choices' => [['message' => ['content' => '']]]])]);

    $this->actingAs($user)->post(route('settings.ai.test'), ['provider' => 'openrouter', 'model' => 'openai/gpt-5', 'api_key' => 'sk-or-test'])
        ->assertSessionHas('connection_status')
        ->assertSessionHasNoErrors();
});

test('connection test explains insufficient openrouter credits', function () {
    $user = User::factory()->create(['role' => 'hr']);
    Http::fake(['https://openrouter.ai/api/v1/chat/completions' => Http::response([], 402)]);

    $this->actingAs($user)->post(route('settings.ai.test'), ['provider' => 'openrouter', 'model' => 'openai/gpt-4o', 'api_key' => 'sk-or-test'])
        ->assertSessionHasErrors(['api_key' => 'The provider account has insufficient credits.']);
});

test('the typed api key stays in the field after testing the connection', function () {
    $user = User::factory()->create(['role' => 'hr']);
    Http::fake(['https://api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => 'OK']]]])]);

    $this->actingAs($user)->followingRedirects()->post(route('settings.ai.test'), aiPayload())
        ->assertSee('value="sk-live-secret"', false);

    expect(DB::table('sessions')->where('payload', 'like', '%sk-live-secret%')->exists())->toBeFalse();
});
