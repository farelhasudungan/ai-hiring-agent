<?php

use App\Actions\CancelApplicationScreening;
use App\Actions\ReviewApplication;
use App\Actions\Screening\AnalyzeCv;
use App\Actions\Screening\CalculateMatchScore;
use App\Actions\Screening\ExtractCvText;
use App\Actions\Screening\GatewayRequestException;
use App\Actions\Screening\ScreeningRun;
use App\Actions\StartApplicationScreening;
use App\Filament\Resources\Applications\Pages\EditApplication;
use App\Filament\Resources\Applications\Pages\ListApplications;
use App\Filament\Resources\Applications\Pages\ViewApplication;
use App\Jobs\ParseCvJob;
use App\Jobs\ScoreApplicationJob;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Job;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['app.env' => 'local', 'ai.key' => 'test-secret-key', 'ai.url' => 'https://generativelanguage.googleapis.com/v1beta/models', 'ai.model' => 'gemini-3.8-flash']);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Http::preventStrayRequests();
});

function pipelineApplication(?User $user = null): Application
{
    $job = Job::factory()->create([
        ...($user ? ['company_id' => $user->company_id] : []),
        'required_skills' => ['PHP', 'Laravel'], 'nice_to_have' => ['PostgreSQL'], 'min_experience_years' => 2,
    ]);
    $application = Application::factory()->create(['company_id' => $job->company_id, 'job_id' => $job->id]);
    $document = ApplicationDocument::factory()->create([
        'application_id' => $application->id,
        'file_path' => "applications/{$application->company_id}/{$application->id}/cv.docx",
        'file_name' => 'cv.docx', 'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ]);
    Storage::disk('local')->makeDirectory(dirname($document->file_path));
    $word = new PhpWord;
    $word->addSection()->addText('PHP Laravel developer with 3 years experience.');
    IOFactory::createWriter($word, 'Word2007')->save(Storage::disk('local')->path($document->file_path));

    return $application;
}

function pipelineEndpoint(): string
{
    return config('ai.url').'/'.config('ai.model').':generateContent';
}

function pipelineCompletion(): array
{
    return ['candidates' => [['finishReason' => 'STOP', 'content' => ['role' => 'model', 'parts' => [
        ['text' => 'PRIVATE_REASONING', 'thought' => true],
        ['text' => json_encode([
            'candidate_skills' => [
                ['skill' => 'PHP', 'evidence' => 'PHP Laravel developer'],
                ['skill' => 'Laravel', 'evidence' => 'PHP Laravel developer'],
            ],
            'experience_years' => 3, 'experience_evidence' => '3 years experience',
            'education_level' => null, 'risk_flags' => [], 'summary' => 'Pengembang PHP dan Laravel.',
            'match_score' => 100, 'recommendation' => 'hired',
        ])],
    ]]]]];
}

function parsePipeline(Application $application): void
{
    (new ParseCvJob($application->id, $application->processing_run_id))->handle(new ExtractCvText, new ScreeningRun);
}

function scorePipeline(Application $application): void
{
    (new ScoreApplicationJob($application->id, $application->processing_run_id))->handle(new AnalyzeCv, new CalculateMatchScore, new ScreeningRun);
}

test('screening starts with a database queue chain and a snapshot of job criteria', function () {
    Storage::fake('local');
    $application = pipelineApplication();
    Bus::fake([ParseCvJob::class, ScoreApplicationJob::class]);

    (new StartApplicationScreening)->handle($application);

    expect($application->status)->toBe('queued');
    expect($application->processing_run_id)->toBeUuid();
    expect($application->currentScreening->status)->toBe('queued');
    expect($application->currentScreening->model_name)->toBe('gemini-3.8-flash');
    expect($application->currentScreening->input_snapshot['ai_provider'])->toBe('gemini');
    expect($application->currentScreening->input_snapshot['job']['required_skills'])->toBe(['PHP', 'Laravel']);
    Bus::assertChained([ParseCvJob::class, ScoreApplicationJob::class]);
    Bus::assertDispatched(ParseCvJob::class, fn ($job) => $job->connection === 'database' && $job->runId === $application->processing_run_id);
});

test('an actual database worker processes the chain from DOCX to a PHP calculated score', function () {
    Storage::fake('local');
    Http::fake([pipelineEndpoint() => Http::response(pipelineCompletion())]);
    $application = pipelineApplication();
    (new StartApplicationScreening)->handle($application);
    $this->assertDatabaseCount('queue_jobs', 1);

    $this->artisan('queue:work', ['connection' => 'database', '--once' => true, '--sleep' => 0])->assertSuccessful();
    expect($application->fresh()->status)->toBe('parsing');
    $this->artisan('queue:work', ['connection' => 'database', '--once' => true, '--sleep' => 0])->assertSuccessful();

    $application->refresh();
    expect($application->status)->toBe('scored');
    expect($application->latest_ai_status)->toBe('success');
    expect($application->latest_match_score)->toBe(85);
    expect($application->reviewed_by)->toBeNull();
    expect($application->currentScreening->recommendation)->toBe('strong_match');
    expect($application->currentScreening->raw_response)->not->toContain('PRIVATE_REASONING');
    expect($application->currentScreening->prompt_text)->toContain('BEGIN_UNTRUSTED_CV_JSON', 'PHP Laravel developer');
    $this->assertDatabaseCount('queue_jobs', 0);
    Http::assertSentCount(1);
});

test('an active Gemini run keeps its snapshotted model when configuration changes', function () {
    Storage::fake('local');
    Http::fake([pipelineEndpoint() => Http::response(pipelineCompletion())]);
    $application = pipelineApplication();
    (new StartApplicationScreening)->handle($application);
    parsePipeline($application);
    config(['ai.model' => 'gemini-3.5-flash']);

    scorePipeline($application);

    expect($application->fresh()->status)->toBe('scored');
    expect($application->fresh()->currentScreening->model_name)->toBe('gemini-3.8-flash');
    Http::assertSent(fn ($request) => $request->url() === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent');
    Http::assertSentCount(1);
});

test('failed parsing stops the actual chain and records a safe error', function () {
    Storage::fake('local');
    Http::fake();
    $application = pipelineApplication();
    Storage::disk('local')->put($application->documents()->sole()->file_path, 'broken DOCX');
    (new StartApplicationScreening)->handle($application);

    $this->artisan('queue:work', ['connection' => 'database', '--once' => true, '--sleep' => 0])->assertSuccessful();

    expect($application->fresh()->status)->toBe('failed');
    expect($application->fresh()->processing_error)->toContain('DOCX rusak');
    expect($application->documents()->sole()->parsing_status)->toBe('failed');
    expect($application->fresh()->currentScreening->status)->toBe('failed');
    $this->assertDatabaseCount('queue_jobs', 0);
    Http::assertNothingSent();
});

test('redelivering successful jobs neither duplicates screenings nor calls the model again', function () {
    Storage::fake('local');
    Http::fake([pipelineEndpoint() => Http::response(pipelineCompletion())]);
    $application = pipelineApplication();
    (new StartApplicationScreening)->handle($application);
    parsePipeline($application);
    parsePipeline($application);
    scorePipeline($application);

    parsePipeline($application);
    scorePipeline($application);

    $this->assertDatabaseCount('ai_screenings', 1);
    expect($application->fresh()->status)->toBe('scored');
    Http::assertSentCount(1);
});

test('transient failures can retry the same screening row and later succeed', function () {
    Storage::fake('local');
    Http::fake([pipelineEndpoint() => Http::sequence()->push('provider unavailable', 503)->push(pipelineCompletion())]);
    $application = pipelineApplication();
    (new StartApplicationScreening)->handle($application);
    parsePipeline($application);

    expect(fn () => scorePipeline($application))->toThrow(RuntimeException::class, 'Gateway AI sedang sibuk');
    expect($application->fresh()->status)->toBe('analyzing');
    scorePipeline($application);

    expect($application->fresh()->status)->toBe('scored');
    $this->assertDatabaseCount('ai_screenings', 1);
    Http::assertSentCount(2);
});

test('invalid model JSON fails the run without assigning a score', function () {
    Storage::fake('local');
    Http::fake([pipelineEndpoint() => Http::response(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => 'Halo!']]]]]])]);
    $application = pipelineApplication();
    (new StartApplicationScreening)->handle($application);
    parsePipeline($application);

    scorePipeline($application);

    expect($application->fresh()->status)->toBe('failed');
    expect($application->fresh()->latest_match_score)->toBeNull();
    expect($application->fresh()->processing_error)->toContain('JSON');
    expect($application->fresh()->currentScreening->status)->toBe('failed');
});

test('failed callbacks update the current run without storing exception secrets', function (string $jobClass) {
    Storage::fake('local');
    $application = pipelineApplication();
    (new StartApplicationScreening)->handle($application);

    (new $jobClass($application->id, $application->processing_run_id))->failed(new RuntimeException('SECRET_API_KEY_OR_CV'));

    expect($application->fresh()->status)->toBe('failed');
    expect($application->fresh()->processing_error)->not->toContain('SECRET_API_KEY_OR_CV');
    expect($application->fresh()->currentScreening->status)->toBe('failed');
})->with([ParseCvJob::class, ScoreApplicationJob::class]);

test('terminal gateway failures retain the safe HTTP status in the application error', function () {
    Storage::fake('local');
    $application = pipelineApplication();
    (new StartApplicationScreening)->handle($application);

    (new ScoreApplicationJob($application->id, $application->processing_run_id))->failed(
        new GatewayRequestException('Gateway melewati batas waktu (HTTP 504).', 504, 60000),
    );

    expect($application->fresh()->status)->toBe('failed');
    expect($application->fresh()->processing_error)->toContain('HTTP 504');
    expect($application->fresh()->currentScreening->error_message)->toContain('HTTP 504');
});

test('manual reprocessing creates a new history row but stale workers cannot update it', function () {
    Storage::fake('local');
    Http::fake();
    $application = pipelineApplication();
    $start = new StartApplicationScreening;
    $start->handle($application);
    $oldId = $application->processing_run_id;
    (new ScoreApplicationJob($application->id, $oldId))->failed(null);
    $start->handle($application);
    $newId = $application->processing_run_id;

    (new ParseCvJob($application->id, $oldId))->failed(null);
    (new ScoreApplicationJob($application->id, $oldId))->handle(new AnalyzeCv, new CalculateMatchScore, new ScreeningRun);

    expect($newId)->not->toBe($oldId);
    expect($application->fresh()->status)->toBe('queued');
    expect($application->fresh()->processing_error)->toBeNull();
    $this->assertDatabaseCount('ai_screenings', 2);
    Http::assertNothingSent();
});

test('starting a run twice while active does not queue duplicates', function () {
    Storage::fake('local');
    $application = pipelineApplication();
    $start = new StartApplicationScreening;
    $start->handle($application);

    expect(fn () => $start->handle($application))->toThrow(ValidationException::class);

    $this->assertDatabaseCount('ai_screenings', 1);
    $this->assertDatabaseCount('queue_jobs', 1);
});

test('a result from an earlier run cannot overwrite a replacement run started during the request', function () {
    Storage::fake('local');
    $application = pipelineApplication();
    (new StartApplicationScreening)->handle($application);
    $oldId = $application->processing_run_id;
    parsePipeline($application);
    Http::fake([pipelineEndpoint() => function () use ($application, $oldId) {
        (new ScoreApplicationJob($application->id, $oldId))->failed(null);
        (new StartApplicationScreening)->handle($application);

        return Http::response(pipelineCompletion());
    }]);

    scorePipeline($application);

    expect($application->fresh()->processing_run_id)->not->toBe($oldId);
    expect($application->fresh()->status)->toBe('queued');
    expect($application->fresh()->latest_match_score)->toBeNull();
    expect($application->fresh()->currentScreening->status)->toBe('queued');
    $this->assertDatabaseCount('ai_screenings', 2);
});

test('tenant integrity is rechecked by the worker before sending CV text', function () {
    Storage::fake('local');
    Http::fake();
    $application = pipelineApplication();
    (new StartApplicationScreening)->handle($application);
    $application->candidate->update(['company_id' => Job::factory()->create()->company_id]);

    parsePipeline($application);
    scorePipeline($application);

    expect($application->fresh()->status)->toBe('failed');
    expect($application->fresh()->processing_error)->toContain('Relasi perusahaan');
    Http::assertNothingSent();
});

test('existing human decisions cannot be replaced by starting a new screening', function (string $status) {
    Storage::fake('local');
    $application = pipelineApplication();
    $application->update(['status' => $status]);

    expect(fn () => (new StartApplicationScreening)->handle($application))->toThrow(ValidationException::class);

    $this->assertDatabaseCount('ai_screenings', 0);
    $this->assertDatabaseCount('queue_jobs', 0);
})->with(['archived', 'hired', 'rejected', 'shortlisted', 'interview']);

test('missing CV documents cause a clear validation error before queueing', function () {
    Storage::fake('local');
    $application = pipelineApplication();
    $application->documents()->delete();

    expect(fn () => (new StartApplicationScreening)->handle($application))
        ->toThrow(ValidationException::class, 'tepat satu dokumen');

    expect($application->fresh()->status)->toBe('received');
    $this->assertDatabaseCount('ai_screenings', 0);
});

test('archive during a gateway request is preserved when the result arrives', function () {
    Storage::fake('local');
    $user = User::factory()->create(['role' => 'admin']);
    $application = pipelineApplication($user);
    (new StartApplicationScreening)->handle($application, $user, aiProcessingConsent: true);
    parsePipeline($application);
    Http::fake([pipelineEndpoint() => function () use ($application, $user) {
        (new ReviewApplication)->handle($application, $user, ['status' => 'archived']);

        return Http::response(pipelineCompletion());
    }]);

    scorePipeline($application);

    expect($application->fresh()->status)->toBe('archived');
    expect($application->fresh()->latest_match_score)->toBeNull();
    expect($application->fresh()->currentScreening->status)->toBe('failed');
    expect($application->fresh()->reviewed_by)->toBe($user->id);
});

test('scoring uses the queued criteria snapshot even when the job is edited', function () {
    Storage::fake('local');
    Http::fake([pipelineEndpoint() => Http::response(pipelineCompletion())]);
    $application = pipelineApplication();
    (new StartApplicationScreening)->handle($application);
    $application->job->update(['required_skills' => ['Python'], 'min_experience_years' => 20]);
    parsePipeline($application);

    scorePipeline($application);

    expect($application->fresh()->latest_match_score)->toBe(85);
    expect($application->fresh()->currentScreening->matched_skills)->toBe(['php', 'laravel']);
});

test('rolled back submissions do not dispatch screening jobs', function () {
    Storage::fake('local');
    $application = pipelineApplication();
    Bus::fake([ParseCvJob::class, ScoreApplicationJob::class]);
    DB::beginTransaction();
    (new StartApplicationScreening)->handle($application);
    DB::rollBack();

    expect($application->fresh()->status)->toBe('received');
    $this->assertDatabaseCount('ai_screenings', 0);
    Bus::assertNotDispatched(ParseCvJob::class);
});

test('queue insertion failure preserves uploaded documents and records a retryable failure', function () {
    Storage::fake('local');
    $application = pipelineApplication();
    $document = $application->documents()->sole();
    config(['queue.connections.database.table' => 'nonexistent_queue_table']);

    (new StartApplicationScreening)->handle($application);

    expect($application->fresh()->status)->toBe('failed');
    expect($application->fresh()->processing_error)->toContain('Antrean tidak tersedia');
    Storage::disk('local')->assertExists($document->file_path);
    $this->assertModelExists($document);
});

test('authorized managers can reprocess from the application view', function () {
    Storage::fake('local');
    $user = User::factory()->create(['role' => 'hr']);
    $application = pipelineApplication($user);

    Livewire::actingAs($user)->test(ViewApplication::class, ['record' => $application->id])
        ->assertActionVisible('processAgain')->callAction('processAgain', ['ai_processing_consent' => true])->assertActionHidden('processAgain');

    expect($application->fresh()->status)->toBe('queued');
    expect($application->fresh()->reviewed_by)->toBeNull();
    expect($application->fresh()->currentScreening->input_snapshot['consent_confirmed_by'])->toBe($user->id);
});

test('reprocessing requires confirmation of candidate consent for Gemini', function () {
    Storage::fake('local');
    $user = User::factory()->create(['role' => 'hr']);
    $application = pipelineApplication($user);

    Livewire::actingAs($user)->test(ViewApplication::class, ['record' => $application->id])
        ->callAction('processAgain', ['ai_processing_consent' => false])
        ->assertHasActionErrors(['ai_processing_consent' => 'accepted']);

    expect($application->fresh()->status)->toBe('received');
    $this->assertDatabaseCount('ai_screenings', 0);
    $this->assertDatabaseCount('queue_jobs', 0);
});

test('candidate consent confirmation cannot be bypassed by calling the start operation directly', function () {
    Storage::fake('local');
    $user = User::factory()->create(['role' => 'admin']);
    $application = pipelineApplication($user);

    expect(fn () => (new StartApplicationScreening)->handle($application, $user))
        ->toThrow(ValidationException::class, 'Konfirmasi persetujuan kandidat');

    $this->assertDatabaseCount('ai_screenings', 0);
    $this->assertDatabaseCount('queue_jobs', 0);
});

test('queued screenings from the old provider cannot be silently sent to Gemini', function (?string $provider) {
    Storage::fake('local');
    Http::fake([pipelineEndpoint() => Http::response(pipelineCompletion())]);
    $application = pipelineApplication();
    (new StartApplicationScreening)->handle($application);
    $screening = $application->currentScreening;
    $snapshot = $screening->input_snapshot;
    $snapshot['ai_provider'] = $provider;
    $screening->update(['model_name' => 'qwen3:14b', 'input_snapshot' => $snapshot]);
    parsePipeline($application);

    scorePipeline($application);

    expect($application->fresh()->status)->toBe('failed');
    expect($application->fresh()->processing_error)->toContain('Provider AI telah berubah');
    expect($application->fresh()->latest_match_score)->toBeNull();
    Http::assertNothingSent();
})->with([null, 'openrouter', 'its']);

test('read only roles cannot invoke reprocessing', function (string $role) {
    Storage::fake('local');
    $user = User::factory()->create(['role' => $role]);
    $application = pipelineApplication($user);

    Livewire::actingAs($user)->test(ViewApplication::class, ['record' => $application->id])
        ->assertActionHidden('processAgain')->call('mountAction', 'processAgain')->call('callMountedAction');

    expect($application->fresh()->status)->toBe('received');
    $this->assertDatabaseCount('ai_screenings', 0);
})->with(['interviewer', 'viewer']);

test('another company application cannot be queued by manipulating its row key', function () {
    Storage::fake('local');
    $user = User::factory()->create(['role' => 'admin']);
    $application = pipelineApplication();

    Livewire::actingAs($user)->test(ListApplications::class)
        ->call('mountAction', 'processAgain', [], ['table' => true, 'recordKey' => (string) $application->id])
        ->call('callMountedAction');

    expect($application->fresh()->status)->toBe('received');
    $this->assertDatabaseCount('ai_screenings', 0);
});

test('AI output is visible and escaped on the view without exposing reasoning or prompts', function () {
    Storage::fake('local');
    $user = User::factory()->create(['role' => 'admin']);
    $application = pipelineApplication($user);
    (new StartApplicationScreening)->handle($application, $user, aiProcessingConsent: true);
    parsePipeline($application);
    Http::fake([pipelineEndpoint() => Http::response(pipelineCompletion())]);
    scorePipeline($application);
    $application->fresh()->currentScreening->update(['summary' => '<script>alert(1)</script>']);

    Livewire::actingAs($user)->test(ViewApplication::class, ['record' => $application->id])
        ->assertSee('strong_match')->assertSee('Poin skill wajib')->assertSee('85')
        ->assertSee('<script>alert(1)</script>')->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('PRIVATE_REASONING')->assertDontSee('BEGIN_UNTRUSTED_CV_JSON');
});

test('a tampered current screening reference cannot expose another company AI results', function () {
    Storage::fake('local');
    $user = User::factory()->create(['role' => 'admin']);
    $own = pipelineApplication($user);
    $other = pipelineApplication();
    (new StartApplicationScreening)->handle($other);
    $other->currentScreening->update(['summary' => 'PRIVATE_OTHER_COMPANY_SUMMARY']);
    $own->forceFill(['processing_run_id' => $other->processing_run_id])->save();

    Livewire::actingAs($user)->test(ViewApplication::class, ['record' => $own->id])
        ->assertDontSee('PRIVATE_OTHER_COMPANY_SUMMARY');
});

test('managers can cancel active screening without withdrawing the application or changing its review', function (string $status) {
    Storage::fake('local');
    $this->travelTo(now()->startOfSecond());
    $user = User::factory()->create(['role' => 'hr']);
    $application = pipelineApplication($user);
    $application->update(['notes' => 'Verifikasi dokumen nanti.', 'reviewed_by' => $user->id, 'reviewed_at' => now()->subDay()]);
    (new StartApplicationScreening)->handle($application);
    $application->update(['status' => $status, 'latest_ai_status' => $status === 'queued' ? 'queued' : 'running']);
    $original = $application->only(['reviewed_by', 'reviewed_at', 'notes', 'candidate_id', 'job_id']);
    $document = $application->documents()->sole();
    $runId = $application->processing_run_id;

    (new CancelApplicationScreening)->handle($application, $user, $runId);

    expect($application->status)->toBe('received');
    expect($application->latest_ai_status)->toBe('cancelled');
    expect($application->latest_ai_at->equalTo(now()))->toBeTrue();
    expect($application->processing_run_id)->toBe($runId);
    expect($application->processing_error)->toBeNull();
    expect($application->only(array_keys($original)))->toEqual($original);
    expect($application->currentScreening->status)->toBe('cancelled');
    expect($application->currentScreening->completed_at->equalTo(now()))->toBeTrue();
    expect($document->fresh()->parsing_status)->toBe('cancelled');
    $this->assertDatabaseHas('activity_logs', ['application_id' => $application->id, 'user_id' => $user->id, 'action' => 'ai_cancelled']);
    expect($application->activityLogs()->sole()->meta)->toBe(['run_id' => $runId]);
    Storage::disk('local')->assertExists($document->file_path);
})->with(['queued', 'parsing', 'analyzing']);

test('cancelling before the worker starts drains the chain without parsing or contacting AI', function () {
    Storage::fake('local');
    Http::fake([pipelineEndpoint() => Http::response(pipelineCompletion())]);
    $user = User::factory()->create(['role' => 'admin']);
    $application = pipelineApplication($user);
    (new StartApplicationScreening)->handle($application);
    (new CancelApplicationScreening)->handle($application, $user, $application->processing_run_id);

    $this->artisan('queue:work', ['connection' => 'database', '--stop-when-empty' => true, '--sleep' => 0])->assertSuccessful();

    expect($application->fresh()->latest_ai_status)->toBe('cancelled');
    expect($application->documents()->sole()->parsed_text)->toBeNull();
    $this->assertDatabaseCount('queue_jobs', 0);
    Http::assertNothingSent();
});

test('cancelling during a gateway request discards its result and preserves extracted text', function () {
    Storage::fake('local');
    $user = User::factory()->create(['role' => 'admin']);
    $application = pipelineApplication($user);
    (new StartApplicationScreening)->handle($application);
    parsePipeline($application);
    $text = $application->documents()->sole()->parsed_text;
    Http::fake([pipelineEndpoint() => function () use ($application, $user) {
        (new CancelApplicationScreening)->handle($application, $user, $application->processing_run_id);

        return Http::response(pipelineCompletion());
    }]);

    scorePipeline($application);

    expect($application->fresh()->status)->toBe('received');
    expect($application->fresh()->latest_ai_status)->toBe('cancelled');
    expect($application->fresh()->latest_match_score)->toBeNull();
    expect($application->fresh()->currentScreening->only(['status', 'raw_response', 'parsed_response', 'match_score']))
        ->toBe(['status' => 'cancelled', 'raw_response' => null, 'parsed_response' => null, 'match_score' => null]);
    expect($application->documents()->sole()->parsed_text)->toBe($text);
    expect($application->documents()->sole()->parsing_status)->toBe('success');
    Http::assertSentCount(1);
});

test('cancelling while a parser is running prevents its late text from being stored', function () {
    Storage::fake('local');
    $user = User::factory()->create(['role' => 'admin']);
    $application = pipelineApplication($user);
    (new StartApplicationScreening)->handle($application);
    $extract = new class($application, $user) extends ExtractCvText
    {
        public function __construct(private Application $application, private User $user) {}

        public function handle(ApplicationDocument $document): string
        {
            $text = parent::handle($document);
            (new CancelApplicationScreening)->handle($this->application, $this->user, $this->application->processing_run_id);

            return $text;
        }
    };

    (new ParseCvJob($application->id, $application->processing_run_id))->handle($extract, new ScreeningRun);

    expect($application->fresh()->status)->toBe('received');
    expect($application->fresh()->currentScreening->status)->toBe('cancelled');
    expect($application->documents()->sole()->parsing_status)->toBe('cancelled');
    expect($application->documents()->sole()->parsed_text)->toBeNull();
});

test('late failure callbacks cannot turn a cancelled run into a failure', function (string $jobClass) {
    Storage::fake('local');
    $user = User::factory()->create(['role' => 'admin']);
    $application = pipelineApplication($user);
    (new StartApplicationScreening)->handle($application);
    (new CancelApplicationScreening)->handle($application, $user, $application->processing_run_id);

    (new $jobClass($application->id, $application->processing_run_id))->failed(new RuntimeException('late failure'));

    expect($application->fresh()->latest_ai_status)->toBe('cancelled');
    expect($application->fresh()->processing_error)->toBeNull();
    expect($application->fresh()->currentScreening->status)->toBe('cancelled');
    expect($application->documents()->sole()->parsing_status)->toBe('cancelled');
})->with([ParseCvJob::class, ScoreApplicationJob::class]);

test('cancelled screening can be reprocessed with its cancelled history retained', function () {
    Storage::fake('local');
    $user = User::factory()->create(['role' => 'admin']);
    $application = pipelineApplication($user);
    (new StartApplicationScreening)->handle($application);
    $oldId = $application->processing_run_id;
    (new CancelApplicationScreening)->handle($application, $user, $oldId);

    (new StartApplicationScreening)->handle($application, $user, aiProcessingConsent: true);

    expect($application->status)->toBe('queued');
    expect($application->processing_run_id)->not->toBe($oldId);
    expect($application->currentScreening->status)->toBe('queued');
    expect($application->aiScreenings()->where('run_id', $oldId)->sole()->status)->toBe('cancelled');
    expect($application->documents()->sole()->parsing_status)->toBe('pending');
    $this->assertDatabaseCount('ai_screenings', 2);
});

test('an old cancellation request cannot cancel a replacement screening', function () {
    Storage::fake('local');
    $user = User::factory()->create(['role' => 'admin']);
    $application = pipelineApplication($user);
    (new StartApplicationScreening)->handle($application);
    $oldId = $application->processing_run_id;
    (new CancelApplicationScreening)->handle($application, $user, $oldId);
    (new StartApplicationScreening)->handle($application);

    expect(fn () => (new CancelApplicationScreening)->handle($application, $user, $oldId))
        ->toThrow(ValidationException::class, 'Proses sudah selesai, dibatalkan, atau diganti');

    expect($application->fresh()->latest_ai_status)->toBe('queued');
    expect($application->fresh()->currentScreening->status)->toBe('queued');
    $this->assertDatabaseCount('activity_logs', 1);
});

test('completed or human handled applications cannot be reset by cancellation', function (string $status) {
    Storage::fake('local');
    $user = User::factory()->create(['role' => 'admin']);
    $application = pipelineApplication($user);
    (new StartApplicationScreening)->handle($application);
    $application->update(['status' => $status]);

    expect(fn () => (new CancelApplicationScreening)->handle($application, $user, $application->processing_run_id))
        ->toThrow(ValidationException::class);

    expect($application->fresh()->status)->toBe($status);
    $this->assertDatabaseCount('activity_logs', 0);
})->with(['scored', 'failed', 'archived', 'hired', 'interview', 'shortlisted', 'rejected']);

test('an inactive screening row cannot be cancelled even if application fields still say queued', function () {
    Storage::fake('local');
    $user = User::factory()->create(['role' => 'admin']);
    $application = pipelineApplication($user);
    (new StartApplicationScreening)->handle($application);
    $application->currentScreening->update(['status' => 'success']);

    expect(fn () => (new CancelApplicationScreening)->handle($application, $user, $application->processing_run_id))
        ->toThrow(ValidationException::class, 'Tidak ada proses AI aktif');

    expect($application->fresh()->currentScreening->status)->toBe('success');
    expect($application->fresh()->status)->toBe('queued');
    expect($application->documents()->sole()->parsing_status)->toBe('pending');
    $this->assertDatabaseCount('activity_logs', 0);
});

test('managers can cancel from the view and edit page', function (string $pageClass, string $role) {
    Storage::fake('local');
    $user = User::factory()->create(['role' => $role]);
    $application = pipelineApplication($user);
    (new StartApplicationScreening)->handle($application);

    $page = Livewire::actingAs($user)->test($pageClass, ['record' => $application->id])
        ->assertActionVisible('cancelProcessing')->assertActionHidden('processAgain')
        ->callAction('cancelProcessing')->assertHasNoActionErrors()
        ->assertNotified('Pemrosesan dibatalkan')
        ->assertActionHidden('cancelProcessing')->assertActionVisible('processAgain');

    expect($application->fresh()->latest_ai_status)->toBe('cancelled');
    if ($pageClass === EditApplication::class) {
        $page->assertFormSet(['status' => 'received']);
    } else {
        $page->assertSee('Dibatalkan');
    }
})->with([[ViewApplication::class, 'admin'], [EditApplication::class, 'hr']]);

test('the application table offers cancellation for active runs', function () {
    Storage::fake('local');
    $user = User::factory()->create(['role' => 'hr']);
    $application = pipelineApplication($user);
    (new StartApplicationScreening)->handle($application);

    Livewire::actingAs($user)->test(ListApplications::class)
        ->assertTableActionVisible('cancelProcessing', $application)
        ->callTableAction('cancelProcessing', $application)->assertHasNoActionErrors()
        ->assertNotified('Pemrosesan dibatalkan');

    expect($application->fresh()->status)->toBe('received');
    expect($application->fresh()->latest_ai_status)->toBe('cancelled');
});

test('a stale confirmation dialog cannot cancel a newer run', function () {
    Storage::fake('local');
    $user = User::factory()->create(['role' => 'admin']);
    $application = pipelineApplication($user);
    (new StartApplicationScreening)->handle($application);
    $oldId = $application->processing_run_id;
    $page = Livewire::actingAs($user)->test(ViewApplication::class, ['record' => $application->id])
        ->mountAction('cancelProcessing')->assertActionDataSet(['run_id' => $oldId]);
    (new CancelApplicationScreening)->handle($application, $user, $oldId);
    (new StartApplicationScreening)->handle($application);

    $page->callMountedAction()->assertNotified('Proses tidak dapat dibatalkan');

    expect($application->fresh()->latest_ai_status)->toBe('queued');
    expect($application->fresh()->currentScreening->status)->toBe('queued');
    $this->assertDatabaseCount('activity_logs', 1);
});

test('read only roles cannot cancel by invoking a hidden action', function (string $role) {
    Storage::fake('local');
    $user = User::factory()->create(['role' => $role]);
    $application = pipelineApplication($user);
    (new StartApplicationScreening)->handle($application);

    Livewire::actingAs($user)->test(ViewApplication::class, ['record' => $application->id])
        ->assertActionHidden('cancelProcessing')->call('mountAction', 'cancelProcessing')->call('callMountedAction');

    expect($application->fresh()->latest_ai_status)->toBe('queued');
    $this->assertDatabaseCount('activity_logs', 0);
})->with(['interviewer', 'viewer']);

test('the cancellation operation requires company management authorization', function (string $scenario) {
    Storage::fake('local');
    $user = User::factory()->create(['role' => $scenario === 'foreign company' ? 'admin' : 'viewer']);
    $application = pipelineApplication($scenario === 'foreign company' ? null : $user);
    (new StartApplicationScreening)->handle($application);

    expect(fn () => (new CancelApplicationScreening)->handle($application, $user, $application->processing_run_id))
        ->toThrow(AuthorizationException::class);

    expect($application->fresh()->latest_ai_status)->toBe('queued');
    $this->assertDatabaseCount('activity_logs', 0);
})->with(['foreign company', 'read only']);

test('another company run cannot be cancelled by manipulating the table record key', function () {
    Storage::fake('local');
    $user = User::factory()->create(['role' => 'admin']);
    $application = pipelineApplication();
    (new StartApplicationScreening)->handle($application);

    Livewire::actingAs($user)->test(ListApplications::class)
        ->call('mountAction', 'cancelProcessing', [], ['table' => true, 'recordKey' => (string) $application->id])
        ->call('callMountedAction');

    expect($application->fresh()->latest_ai_status)->toBe('queued');
    $this->assertDatabaseCount('activity_logs', 0);
});
