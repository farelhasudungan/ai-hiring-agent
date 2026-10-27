<?php

use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Candidate;
use App\Models\Job;
use Aws\MockHandler;
use Aws\Result;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToWriteFile;
use Symfony\Component\HttpFoundation\File\File;

uses(RefreshDatabase::class);

function validPublicApplication(array $overrides = []): array
{
    return [
        'full_name' => 'Nadia Putri',
        'email' => 'nadia@example.com',
        'phone' => '+628123456789',
        'resume' => UploadedFile::fake()->createWithContent('resume.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF"),
        'consent' => '1',
        ...$overrides,
    ];
}

test('an open job has a public application form without requiring an account', function () {
    $job = Job::factory()->create(['status' => 'open']);

    $this->get(route('jobs.apply', $job))->assertOk()
        ->assertSee($job->title)
        ->assertSee($job->company->name)
        ->assertSee('enctype="multipart/form-data"', false)
        ->assertSee('name="_token"', false)
        ->assertSee('name="resume"', false)
        ->assertSee('name="consent"', false)
        ->assertDontSee('name="company_id"', false);
});

test('application and confirmation pages have no navigation footer or job detail panels', function (bool $submitted) {
    $job = Job::factory()->create(['status' => 'open']);
    $response = $this->withSession(['application_submitted' => $submitted])->get(route('jobs.apply', $job));
    $response->assertOk()->assertDontSee('Tentang posisi ini')->assertDontSee('Kembali ke beranda');

    $document = new DOMDocument;
    $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);

    expect($xpath->query('//nav | //footer | //aside | //article')->length)->toBe(0);
    expect($xpath->query('//form')->length)->toBe($submitted ? 0 : 1);
    expect($xpath->query('//script[@data-roku-theme-script]')->length)->toBe(1);
})->with(['form' => [false], 'confirmation' => [true]]);

test('non open jobs neither display a form nor accept applications', function (string $status) {
    Storage::fake('r2');
    $job = Job::factory()->create(['status' => $status]);

    $this->get(route('jobs.apply', $job))->assertNotFound();
    $this->post(route('jobs.applications.store', $job), validPublicApplication())->assertNotFound();

    $this->assertDatabaseCount('candidates', 0);
    $this->assertDatabaseCount('applications', 0);
    expect(Storage::disk('r2')->allFiles())->toBe([]);
})->with(['draft', 'closed']);

test('unknown jobs return 404 on both public endpoints', function () {
    $this->get(route('jobs.apply', 999))->assertNotFound();
    $this->post(route('jobs.applications.store', 999), validPublicApplication())->assertNotFound();
});

test('submission creates company scoped records and private documents with a consent audit', function () {
    Storage::fake('r2');
    Storage::fake('public');
    $this->travelTo(now()->startOfDay());
    $job = Job::factory()->create(['status' => 'open']);
    $anotherJob = Job::factory()->create(['status' => 'open']);
    $data = validPublicApplication([
        'email' => ' NADIA@EXAMPLE.COM ',
        'linkedin_url' => 'https://linkedin.com/in/nadia',
        'portfolio_url' => 'https://example.com/portfolio',
        'cover_letter' => 'Saya tertarik membangun produk rekrutmen.',
        'company_id' => $anotherJob->company_id,
        'job_id' => $anotherJob->id,
        'candidate_id' => 999,
        'status' => 'hired',
        'latest_match_score' => 100,
        'reviewed_by' => 999,
    ]);

    $this->post(route('jobs.applications.store', $job), $data)
        ->assertRedirect(route('jobs.apply', $job))
        ->assertSessionHas('application_submitted', true)
        ->assertSessionHasNoErrors();

    $application = Application::query()->sole();
    $candidate = Candidate::query()->sole();
    $resume = $application->documents()->where('type', 'resume')->sole();
    $coverLetter = $application->documents()->where('type', 'cover_letter')->sole();
    $log = ActivityLog::query()->sole();

    expect($application->company_id)->toBe($job->company_id);
    expect($application->job_id)->toBe($job->id);
    expect($application->candidate_id)->toBe($candidate->id);
    expect($application->status)->toBe('received');
    expect($application->source)->toBe('form');
    expect($application->submitted_at->eq(now()))->toBeTrue();
    expect($application->latest_match_score)->toBeNull();
    expect($application->reviewed_by)->toBeNull();
    expect($candidate->company_id)->toBe($job->company_id);
    expect($candidate->email)->toBe('nadia@example.com');
    expect($candidate->phone)->toBe('+628123456789');
    expect($candidate->linkedin_url)->toBe('https://linkedin.com/in/nadia');
    expect($candidate->portfolio_url)->toBe('https://example.com/portfolio');
    expect($resume->parsing_status)->toBe('pending');
    expect($resume->mime_type)->toBe('application/pdf');
    expect($resume->file_path)->toStartWith("applications/{$job->company_id}/{$application->id}/");
    expect($coverLetter->parsed_text)->toBe($data['cover_letter']);
    expect($log->application_id)->toBe($application->id);
    expect($log->candidate_id)->toBe($candidate->id);
    expect($log->meta)->toBe(['data_processing_consent' => true, 'consented_at' => now()->toIso8601String()]);
    Storage::disk('r2')->assertExists([$resume->file_path, $coverLetter->file_path]);
    expect(Storage::disk('public')->allFiles())->toBe([]);

    $this->get(route('jobs.apply', $job))->assertSee('Lamaran terkirim.')->assertDontSee('name="resume"', false);
});

test('optional fields may be omitted and DOCX resumes are accepted', function () {
    Storage::fake('r2');
    $job = Job::factory()->create(['status' => 'open']);

    $this->post(route('jobs.applications.store', $job), validPublicApplication([
        'resume' => UploadedFile::fake()->create('resume.docx', 20, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
    ]))->assertSessionHasNoErrors()->assertRedirect();

    $this->assertDatabaseCount('application_documents', 1);
    expect(Candidate::query()->sole()->portfolio_url)->toBeNull();
    Storage::disk('r2')->assertExists(ApplicationDocument::query()->sole()->file_path);
});

test('required public application fields and consent are validated', function () {
    Storage::fake('r2');
    $job = Job::factory()->create(['status' => 'open']);

    $this->from(route('jobs.apply', $job))->post(route('jobs.applications.store', $job), [])
        ->assertRedirect(route('jobs.apply', $job))
        ->assertSessionHasErrors([
            'full_name' => 'Nama lengkap wajib diisi.',
            'email' => 'Email wajib diisi.',
            'phone' => 'Nomor telepon wajib diisi.',
            'resume' => 'CV wajib diunggah.',
            'consent' => 'Persetujuan pemrosesan data wajib diberikan.',
        ]);

    $this->assertDatabaseCount('applications', 0);
    expect(Storage::disk('r2')->allFiles())->toBe([]);
});

test('malformed or oversized application fields are rejected without saving records', function (array $overrides, string $field) {
    Storage::fake('r2');
    $job = Job::factory()->create(['status' => 'open']);

    $this->post(route('jobs.applications.store', $job), validPublicApplication($overrides))
        ->assertSessionHasErrors($field);

    $this->assertDatabaseCount('candidates', 0);
    $this->assertDatabaseCount('applications', 0);
    expect(Storage::disk('r2')->allFiles())->toBe([]);
})->with([
    'invalid email' => [['email' => 'invalid'], 'email'],
    'invalid phone type' => [['phone' => ['123']], 'phone'],
    'missing consent' => [['consent' => '0'], 'consent'],
    'invalid LinkedIn scheme' => [['linkedin_url' => 'javascript:alert(1)'], 'linkedin_url'],
    'invalid portfolio URL' => [['portfolio_url' => 'not-a-url'], 'portfolio_url'],
    'long name' => [['full_name' => str_repeat('a', 256)], 'full_name'],
    'long cover letter' => [['cover_letter' => str_repeat('a', 10001)], 'cover_letter'],
]);

test('resume validation rejects disallowed types oversized files and disguised content', function (string $case, string $message) {
    Storage::fake('r2');
    $job = Job::factory()->create(['status' => 'open']);
    $resume = match ($case) {
        'oversized' => UploadedFile::fake()->create('resume.pdf', 5121, 'application/pdf'),
        'wrong type' => UploadedFile::fake()->create('resume.txt', 1, 'text/plain'),
        'disguised content' => UploadedFile::fake()->createWithContent('resume.pdf', '<?php echo "not a PDF";'),
        'wrong extension' => UploadedFile::fake()->createWithContent('resume.php', "%PDF-1.4\n%%EOF"),
    };
    // Unlike the fake upload, a real upload detects MIME from content, not the filename.
    if (in_array($case, ['disguised content', 'wrong extension'], true)) {
        $resume->mimeType((new File($resume->getPathname()))->getMimeType());
    }

    $this->post(route('jobs.applications.store', $job), validPublicApplication(['resume' => $resume]))
        ->assertSessionHasErrors(['resume' => $message]);

    $this->assertDatabaseCount('applications', 0);
    expect(Storage::disk('r2')->allFiles())->toBe([]);
})->with([
    'over 5 MB' => ['oversized', 'Ukuran CV maksimal 5 MB.'],
    'plain text' => ['wrong type', 'CV harus berupa berkas PDF atau DOCX.'],
    'PHP disguised as PDF' => ['disguised content', 'CV harus berupa berkas PDF atau DOCX.'],
    'PDF with PHP extension' => ['wrong extension', 'Ekstensi CV harus PDF atau DOCX.'],
]);

test('a resume at the 5 MB limit is accepted', function () {
    Storage::fake('r2');
    $job = Job::factory()->create(['status' => 'open']);

    $this->post(route('jobs.applications.store', $job), validPublicApplication([
        'resume' => UploadedFile::fake()->create('resume.pdf', 5120, 'application/pdf'),
    ]))->assertSessionHasNoErrors();

    $this->assertDatabaseCount('applications', 1);
    Storage::disk('r2')->assertExists(ApplicationDocument::query()->sole()->file_path);
});

test('server rejected uploads explain the upload limit without saving an application', function () {
    Storage::fake('r2');
    $job = Job::factory()->create(['status' => 'open']);
    $temporaryFile = UploadedFile::fake()->create('resume.pdf', 1, 'application/pdf');
    $resume = new UploadedFile($temporaryFile->getPathname(), 'resume.pdf', 'application/pdf', UPLOAD_ERR_INI_SIZE, true);

    $this->post(route('jobs.applications.store', $job), validPublicApplication(['resume' => $resume]))
        ->assertSessionHasErrors(['resume' => 'CV gagal diunggah. Periksa batas upload server dan coba lagi.']);

    $this->assertDatabaseCount('applications', 0);
    expect(Storage::disk('r2')->allFiles())->toBe([]);
});

test('a public email does not allow overwriting an existing candidate profile', function () {
    Storage::fake('r2');
    $job = Job::factory()->create(['status' => 'open']);
    $existing = Candidate::factory()->create([
        'company_id' => $job->company_id,
        'email' => 'nadia@example.com',
        'full_name' => 'Verified existing name',
    ]);

    $this->post(route('jobs.applications.store', $job), validPublicApplication())->assertSessionHasNoErrors();

    expect($existing->fresh()->full_name)->toBe('Verified existing name');
    expect(Application::query()->sole()->candidate_id)->not->toBe($existing->id);
    Storage::disk('r2')->assertExists(ApplicationDocument::query()->sole()->file_path);
});

test('a persistence failure rolls back candidate and application records and cleans up the uploaded file', function () {
    Storage::fake('r2');
    Exceptions::fake();
    $job = Job::factory()->create(['status' => 'open']);
    ApplicationDocument::creating(function (): never {
        throw new RuntimeException('Document persistence failed.');
    });

    $this->post(route('jobs.applications.store', $job), validPublicApplication())->assertInternalServerError();

    $this->assertDatabaseCount('candidates', 0);
    $this->assertDatabaseCount('applications', 0);
    $this->assertDatabaseCount('application_documents', 0);
    expect(Storage::disk('r2')->allFiles())->toBe([]);
    Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'Document persistence failed.');
});

test('job titles are escaped and descriptions are omitted on the standalone form', function () {
    $job = Job::factory()->create([
        'status' => 'open',
        'title' => '</title><script>alert("title")</script>',
        'description' => '<script>alert("description")</script>',
    ]);

    $this->get(route('jobs.apply', $job))->assertOk()
        ->assertSee($job->title)
        ->assertDontSee($job->description)
        ->assertDontSee($job->title, false)
        ->assertDontSee($job->description, false);
});

test('a late failure removes both private documents and rolls back the consent audit', function () {
    Storage::fake('r2');
    Exceptions::fake();
    $job = Job::factory()->create(['status' => 'open']);
    ActivityLog::creating(function (): never {
        throw new RuntimeException('Consent audit persistence failed.');
    });

    $this->post(route('jobs.applications.store', $job), validPublicApplication([
        'cover_letter' => 'I would like to join the team.',
    ]))->assertInternalServerError();

    $this->assertDatabaseCount('candidates', 0);
    $this->assertDatabaseCount('applications', 0);
    $this->assertDatabaseCount('application_documents', 0);
    $this->assertDatabaseCount('activity_logs', 0);
    expect(Storage::disk('r2')->allFiles())->toBe([]);
    Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'Consent audit persistence failed.');
});

test('a job closed after route binding is rechecked before any records or files are stored', function () {
    Storage::fake('r2');
    $job = Job::factory()->create(['status' => 'open']);
    Job::retrieved(function (Job $record): void {
        DB::table('jobs')->where('id', $record->id)->update(['status' => 'closed']);
    });

    $this->post(route('jobs.applications.store', $job), validPublicApplication())->assertNotFound();

    $this->assertDatabaseCount('candidates', 0);
    $this->assertDatabaseCount('applications', 0);
    expect(Storage::disk('r2')->allFiles())->toBe([]);
});

test('public application submission is rate limited', function () {
    $job = Job::factory()->create(['status' => 'open']);

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->postJson(route('jobs.applications.store', $job), [])->assertUnprocessable();
    }

    $this->postJson(route('jobs.applications.store', $job), [])->assertTooManyRequests();
});

test('resumes and cover letters are uploaded through the R2 S3 adapter without local copies', function () {
    Storage::fake('local');
    $commands = [];
    $handler = new MockHandler([
        function ($command) use (&$commands) {
            $commands[] = $command;

            return new Result;
        },
        function ($command) use (&$commands) {
            $commands[] = $command;

            return new Result;
        },
    ]);
    Storage::set('r2', Storage::build([
        ...config('filesystems.disks.r2'),
        'key' => 'test-key',
        'secret' => 'test-secret',
        'bucket' => 'test-documents',
        'endpoint' => 'https://test-account.r2.cloudflarestorage.com',
        'handler' => $handler,
    ]));
    $job = Job::factory()->create(['status' => 'open']);

    $this->post(route('jobs.applications.store', $job), validPublicApplication([
        'cover_letter' => 'Saya ingin bergabung.',
    ]))->assertRedirect()->assertSessionHas('application_submitted', true);

    $documents = Application::query()->sole()->documents()->get();
    expect($commands)->toHaveCount(2);
    foreach ($commands as $index => $command) {
        expect($command->getName())->toBe('PutObject');
        expect($command['Bucket'])->toBe('test-documents');
        expect($command['Key'])->toBe($documents[$index]->file_path);
        expect($command['ACL'])->toBe('private');
    }
    expect((string) $commands[1]['Body'])->toBe('Saya ingin bergabung.');
    expect($handler->getLastRequest()->getUri()->getHost())->toBe('test-account.r2.cloudflarestorage.com');
    expect($handler->getLastRequest()->hasHeader('x-amz-sdk-checksum-algorithm'))->toBeFalse();
    expect($handler->getLastRequest()->hasHeader('x-amz-checksum-crc32'))->toBeFalse();
    expect($handler->count())->toBe(0);
    Storage::disk('local')->assertEmpty();
});

test('an R2 upload failure leaves no candidate application or document records', function () {
    $disk = Storage::fake('r2');
    $storage = Mockery::mock($disk);
    $storage->shouldReceive('putFileAs')->once()->andThrow(UnableToWriteFile::atLocation('resume.pdf'));
    Storage::set('r2', $storage);
    Exceptions::fake();
    $job = Job::factory()->create(['status' => 'open']);

    $this->post(route('jobs.applications.store', $job), validPublicApplication())->assertServiceUnavailable();

    $this->assertDatabaseCount('candidates', 0);
    $this->assertDatabaseCount('applications', 0);
    $this->assertDatabaseCount('application_documents', 0);
    $disk->assertEmpty();
    Exceptions::assertReported(UnableToWriteFile::class);
});

test('a cover letter upload failure also removes the uploaded resume', function () {
    $disk = Storage::fake('r2');
    $storage = Mockery::mock($disk);
    $storage->shouldReceive('put')->once()->andThrow(UnableToWriteFile::atLocation('cover-letter.txt'));
    Storage::set('r2', $storage);
    Exceptions::fake();
    $job = Job::factory()->create(['status' => 'open']);

    $this->post(route('jobs.applications.store', $job), validPublicApplication([
        'cover_letter' => 'Saya ingin bergabung.',
    ]))->assertServiceUnavailable();

    $this->assertDatabaseCount('candidates', 0);
    $this->assertDatabaseCount('applications', 0);
    $this->assertDatabaseCount('application_documents', 0);
    $disk->assertEmpty();
    Exceptions::assertReported(UnableToWriteFile::class);
});

test('an R2 cleanup failure is reported without hiding the original persistence failure', function () {
    $disk = Storage::fake('r2');
    $storage = Mockery::mock($disk);
    $storage->shouldReceive('delete')->once()->andThrow(UnableToDeleteFile::atLocation('resume.pdf'));
    Storage::set('r2', $storage);
    Exceptions::fake();
    $job = Job::factory()->create(['status' => 'open']);
    ApplicationDocument::creating(function (): never {
        throw new RuntimeException('Document persistence failed.');
    });

    $this->post(route('jobs.applications.store', $job), validPublicApplication())->assertInternalServerError();

    $this->assertDatabaseCount('candidates', 0);
    $this->assertDatabaseCount('applications', 0);
    $this->assertDatabaseCount('application_documents', 0);
    $disk->assertCount('/', 1, true);
    Exceptions::assertReported(UnableToDeleteFile::class);
    Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'Document persistence failed.');
});
