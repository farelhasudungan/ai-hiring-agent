<?php

use App\Jobs\ScreenApplication;
use App\Models\AiProviderSetting;
use App\Models\AiScreening;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Company;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
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

test('screening only starts when the user triggers it, for parsed applications of that job', function () {
    Queue::fake();
    $user = User::factory()->create(['role' => 'hr']);
    AiProviderSetting::factory()->for($user->company)->forProvider('openrouter', 'openai/gpt-4o-mini')->create();
    $job = JobPosting::factory()->for($user->company)->create();

    $ready = Application::factory()->create(['company_id' => $user->company_id, 'job_id' => $job->id, 'status' => 'queued']);
    ApplicationDocument::factory()->for($ready)->create(['parsing_status' => 'success', 'parsed_text' => 'CV text']);
    $unparsed = Application::factory()->create(['company_id' => $user->company_id, 'job_id' => $job->id, 'status' => 'failed']);
    ApplicationDocument::factory()->for($unparsed)->create(['parsing_status' => 'failed']);
    $scored = Application::factory()->create(['company_id' => $user->company_id, 'job_id' => $job->id, 'status' => 'scored']);
    ApplicationDocument::factory()->for($scored)->create(['parsing_status' => 'success', 'parsed_text' => 'CV text']);

    $this->actingAs($user)->post(route('job-postings.screening.store', $job))
        ->assertRedirectToRoute('job-postings.show', $job)->assertSessionHas('status');

    Queue::assertPushed(ScreenApplication::class, 1);
    Queue::assertPushed(ScreenApplication::class, fn (ScreenApplication $queued) => $queued->application->is($ready));
    expect($ready->fresh()->status)->toBe('analyzing');

    $this->actingAs($user)->post(route('job-postings.screening.store', $job));
    Queue::assertPushed(ScreenApplication::class, 1);
});

test('screening cannot start without an ai provider or for another company', function () {
    Queue::fake();
    $user = User::factory()->create(['role' => 'hr']);
    $job = JobPosting::factory()->for($user->company)->create();
    $foreign = JobPosting::factory()->create();

    $this->actingAs($user)->post(route('job-postings.screening.store', $job))->assertSessionHasErrors('screening');
    $this->actingAs($user)->post(route('job-postings.screening.store', $foreign))->assertForbidden();

    Queue::assertNothingPushed();
});

test('only the selected applications are screened', function () {
    Queue::fake();
    $user = User::factory()->create(['role' => 'hr']);
    AiProviderSetting::factory()->for($user->company)->forProvider('openrouter', 'openai/gpt-4o-mini')->create();
    $job = JobPosting::factory()->for($user->company)->create();

    [$first, $second] = collect(range(1, 2))->map(function () use ($user, $job) {
        $application = Application::factory()->create(['company_id' => $user->company_id, 'job_id' => $job->id, 'status' => 'queued']);
        ApplicationDocument::factory()->for($application)->create(['parsing_status' => 'success', 'parsed_text' => 'CV text']);

        return $application;
    })->all();

    $this->actingAs($user)->post(route('job-postings.screening.store', $job), ['application_ids' => [$first->id]]);

    Queue::assertPushed(ScreenApplication::class, 1);
    Queue::assertPushed(ScreenApplication::class, fn (ScreenApplication $queued) => $queued->application->is($first));
    expect($second->fresh()->status)->toBe('queued');
});

test('the job page lists candidates with selection checkboxes', function () {
    $user = User::factory()->create(['role' => 'hr']);
    $job = JobPosting::factory()->for($user->company)->create();
    $application = Application::factory()->create(['company_id' => $user->company_id, 'job_id' => $job->id, 'status' => 'queued', 'full_name' => 'Jane Doe']);
    ApplicationDocument::factory()->for($application)->create(['parsing_status' => 'success', 'parsed_text' => 'CV text']);

    $this->actingAs($user)->get(route('job-postings.show', $job))
        ->assertOk()->assertSee('Jane Doe')->assertSee('name="application_ids[]"', false)->assertSee('Run AI on selected');
});

test('the candidate page shows the screening result and the raw markdown', function () {
    Storage::fake('local');
    $user = User::factory()->create(['role' => 'hr']);
    $job = JobPosting::factory()->for($user->company)->create();
    $application = Application::factory()->create(['company_id' => $user->company_id, 'job_id' => $job->id, 'status' => 'scored', 'full_name' => 'Jane Doe']);
    $screening = AiScreening::factory()->create([
        'application_id' => $application->id,
        'job_id' => $job->id,
        'status' => 'success',
        'match_score' => 82,
        'recommendation' => 'shortlist',
        'parsed_response' => ['summary' => 'Strong Laravel background.', 'strengths' => ['APIs'], 'gaps' => ['No Docker']],
        'matched_skills' => ['PHP'],
    ]);
    Storage::disk('local')->put($screening->summaryPathForStorage(), '<script>alert(1)</script> raw reply');
    $screening->update(['summary_path' => $screening->summaryPathForStorage()]);

    $this->actingAs($user)->get(route('job-postings.candidates.show', [$job, $application]))
        ->assertOk()
        ->assertSee('Jane Doe')->assertSee('82')->assertSee('Strong Laravel background.')
        ->assertSee('APIs')->assertSee('No Docker')->assertSee('shortlist')
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt; raw reply', false)
        ->assertDontSee('<script>alert(1)</script>', false);
});

test('the candidate page shows a failed screening reason', function () {
    $user = User::factory()->create(['role' => 'hr']);
    $job = JobPosting::factory()->for($user->company)->create();
    $application = Application::factory()->create(['company_id' => $user->company_id, 'job_id' => $job->id, 'status' => 'failed']);
    AiScreening::factory()->create(['application_id' => $application->id, 'job_id' => $job->id, 'status' => 'failed', 'error_message' => 'The provider rejected the API key.']);

    $this->actingAs($user)->get(route('job-postings.candidates.show', [$job, $application]))
        ->assertOk()->assertSee('The provider rejected the API key.');
});

test('the candidate page is limited to the same company and the same job', function () {
    $user = User::factory()->create(['role' => 'hr']);
    $job = JobPosting::factory()->for($user->company)->create();
    $otherJob = JobPosting::factory()->for($user->company)->create();
    $foreign = JobPosting::factory()->create();
    $application = Application::factory()->create(['company_id' => $user->company_id, 'job_id' => $job->id]);
    $foreignApplication = Application::factory()->create(['company_id' => $foreign->company_id, 'job_id' => $foreign->id]);

    $this->actingAs($user)->get(route('job-postings.candidates.show', [$otherJob, $application]))->assertNotFound();
    $this->actingAs($user)->get(route('job-postings.candidates.show', [$foreign, $foreignApplication]))->assertForbidden();
});
