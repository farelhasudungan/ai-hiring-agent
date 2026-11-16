<?php

use App\Jobs\ScreenApplication;
use App\Models\AiProviderSetting;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Company;
use App\Models\JobPosting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Http::preventStrayRequests();
});

function parsedApplication(array $applicationOverrides = [], bool $withProvider = true): Application
{
    $company = Company::factory()->create();

    if ($withProvider) {
        AiProviderSetting::factory()->for($company)->forProvider('openrouter', 'openai/gpt-4o-mini')->create(['api_key' => 'sk-or-test']);
    }

    $job = JobPosting::factory()->for($company, 'company')->create([
        'title' => 'Backend Engineer',
        'requirements' => 'Five years building Laravel APIs.',
        'required_skills' => ['PHP', 'Laravel'],
    ]);

    $application = Application::factory()->create(array_merge([
        'company_id' => $company->id,
        'job_id' => $job->id,
        'full_name' => null,
        'email' => null,
        'phone' => null,
    ], $applicationOverrides));

    ApplicationDocument::factory()->for($application)->create([
        'parsing_status' => 'success',
        'parsed_text' => 'Jane Doe, jane@example.com, 0812345. Senior PHP developer.',
    ]);

    return $application;
}

function aiReply(string $content): void
{
    Http::fake(['https://openrouter.ai/api/v1/chat/completions' => Http::response([
        'model' => 'openai/gpt-4o-mini',
        'choices' => [['message' => ['content' => $content]]],
        'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 50],
    ])]);
}

function validReply(array $overrides = []): string
{
    return json_encode(array_merge([
        'candidate' => ['full_name' => 'Jane Doe', 'email' => 'jane@example.com', 'phone' => '0812345'],
        'score' => 82,
        'recommendation' => 'shortlist',
        'summary' => 'Strong Laravel background.',
        'strengths' => ['Laravel', 'APIs'],
        'gaps' => ['No Docker'],
        'matched_skills' => ['PHP', 'Laravel'],
        'missing_skills' => [],
        'risk_flags' => [],
    ], $overrides));
}

test('a screening stores the core result and the raw markdown', function () {
    $application = parsedApplication();
    $reply = "Here is the result:\n```json\n".validReply()."\n```";
    aiReply($reply);

    app()->call([new ScreenApplication($application), 'handle']);

    $screening = $application->aiScreenings()->sole();

    expect($screening->status)->toBe('success')
        ->and($screening->match_score)->toBe(82)
        ->and($screening->recommendation)->toBe('shortlist')
        ->and($screening->parsed_response['summary'])->toBe('Strong Laravel background.')
        ->and($screening->parsed_response['strengths'])->toBe(['Laravel', 'APIs'])
        ->and($screening->parsed_response['gaps'])->toBe(['No Docker'])
        ->and($screening->matched_skills)->toBe(['PHP', 'Laravel'])
        ->and($screening->summaryMarkdown())->toBe($reply)
        ->and($application->fresh()->status)->toBe('scored')
        ->and($application->fresh()->latest_match_score)->toBe(82);
});

test('the prompt carries the job requirements, the resume and the candidate details', function () {
    $application = parsedApplication(['full_name' => 'Jane D', 'email' => 'jd@example.com', 'phone' => '0800']);
    aiReply(validReply());

    app()->call([new ScreenApplication($application), 'handle']);

    Http::assertSent(function (Request $request) {
        $prompt = collect($request['messages'])->pluck('content')->implode("\n");

        return str_contains($prompt, 'Five years building Laravel APIs.')
            && str_contains($prompt, 'PHP, Laravel')
            && str_contains($prompt, 'Senior PHP developer')
            && str_contains($prompt, 'jd@example.com')
            && str_contains($prompt, '0800');
    });
});

test('candidate details are filled from the resume only when empty', function () {
    $application = parsedApplication(['full_name' => null, 'email' => 'kept@example.com', 'phone' => null]);
    aiReply(validReply());

    app()->call([new ScreenApplication($application), 'handle']);

    expect($application->fresh()->full_name)->toBe('Jane Doe')
        ->and($application->fresh()->email)->toBe('kept@example.com')
        ->and($application->fresh()->phone)->toBe('0812345');
});

test('missing optional fields do not fail a screening', function () {
    $application = parsedApplication();
    aiReply(json_encode(['score' => 40, 'recommendation' => 'review', 'summary' => 'Okay.', 'strengths' => [], 'gaps' => ['Thin']]));

    app()->call([new ScreenApplication($application), 'handle']);

    expect($application->aiScreenings()->sole()->status)->toBe('success');
});

test('an invalid reply fails the screening but keeps the raw markdown', function (string $reply) {
    $application = parsedApplication();
    aiReply($reply);

    expect(fn () => app()->call([new ScreenApplication($application), 'handle']))->toThrow(RuntimeException::class);

    $screening = $application->aiScreenings()->sole();

    expect($screening->status)->toBe('failed')
        ->and($screening->error_message)->not->toBeEmpty()
        ->and($screening->summaryMarkdown())->toBe($reply);
})->with([
    'not json' => 'Sorry, I cannot help.',
    'score out of range' => fn () => validReply(['score' => 150]),
    'unknown recommendation' => fn () => validReply(['recommendation' => 'maybe']),
    'missing gaps' => fn () => json_encode(['score' => 50, 'recommendation' => 'review', 'summary' => 'x', 'strengths' => []]),
]);

test('a company without an ai provider is marked failed without retrying', function () {
    $application = parsedApplication(withProvider: false);
    $job = new ScreenApplication($application);

    app()->call([$job, 'handle']);

    expect($application->fresh()->status)->toBe('failed')
        ->and($application->fresh()->latest_ai_status)->toBe('failed');
    Http::assertNothingSent();
});
