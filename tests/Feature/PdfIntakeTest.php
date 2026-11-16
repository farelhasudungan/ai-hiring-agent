<?php

use App\Jobs\ParseApplicationDocument;
use App\Models\Application;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Storage::fake('r2');
    config(['filesystems.intake_disk' => 'r2']);
    Queue::fake();
    $this->user = User::factory()->create(['role' => 'hr']);
    $this->job = JobPosting::factory()->for($this->user->company)->create();
});

function makeZip(array $entries): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'zip');
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::OVERWRITE);

    foreach ($entries as $name => $content) {
        $zip->addFromString($name, $content);
    }

    $zip->close();

    return new UploadedFile($path, 'cvs.zip', 'application/zip', null, true);
}

function pdfUpload(string $name, string $text = 'Jane Doe'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, fakePdfContent($text));
}

test('guests are redirected to login', function () {
    $this->post(route('job-postings.intake.pdf', $this->job), [])->assertRedirectToRoute('login');
});

test('a single pdf creates one application and queues parsing', function () {
    $this->actingAs($this->user)
        ->postJson(route('job-postings.intake.pdf', $this->job), ['files' => [pdfUpload('a.pdf')]])
        ->assertCreated()
        ->assertJson(['created' => 1, 'duplicates' => 0, 'rejected' => []]);

    $application = Application::first();
    expect($application->source)->toBe('pdf')
        ->and($application->job_id)->toBe($this->job->id)
        ->and($application->documents)->toHaveCount(1);
    Storage::disk('r2')->assertExists($application->documents->first()->file_path);
    Queue::assertPushed(ParseApplicationDocument::class, 1);
});

test('multiple pdfs create one application each', function () {
    $this->actingAs($this->user)
        ->postJson(route('job-postings.intake.pdf', $this->job), [
            'files' => [pdfUpload('a.pdf', 'Alice'), pdfUpload('b.pdf', 'Bob')],
        ])
        ->assertJson(['created' => 2]);

    expect(Application::count())->toBe(2);
});

test('a zip creates applications for pdf entries only and skips unsafe or fake entries', function () {
    $zip = makeZip([
        'folder/alice.pdf' => fakePdfContent('Alice'),
        'bob.pdf' => fakePdfContent('Bob'),
        'notes.txt' => 'ignore me',
        '__MACOSX/._alice.pdf' => 'junk',
        'fake.pdf' => 'not really a pdf',
        '../evil.pdf' => fakePdfContent('Evil'),
    ]);

    $response = $this->actingAs($this->user)
        ->postJson(route('job-postings.intake.pdf', $this->job), ['files' => [$zip]])
        ->assertCreated()
        ->assertJsonPath('created', 2);

    expect($response->json('rejected'))->toHaveCount(2)
        ->and(Application::count())->toBe(2);
});

test('duplicate content for the same job is not created twice', function () {
    $url = route('job-postings.intake.pdf', $this->job);

    $this->actingAs($this->user)->postJson($url, ['files' => [pdfUpload('a.pdf', 'Same')]])->assertJson(['created' => 1]);
    $this->actingAs($this->user)->postJson($url, ['files' => [pdfUpload('copy.pdf', 'Same')]])
        ->assertJson(['created' => 0, 'duplicates' => 1]);

    expect(Application::count())->toBe(1);
    expect(Storage::disk('r2')->allFiles())->toHaveCount(1);
});

test('the same pdf may be uploaded to a different job', function () {
    $otherJob = JobPosting::factory()->for($this->user->company)->create();

    $this->actingAs($this->user)->postJson(route('job-postings.intake.pdf', $this->job), ['files' => [pdfUpload('a.pdf', 'Same')]]);
    $this->actingAs($this->user)->postJson(route('job-postings.intake.pdf', $otherJob), ['files' => [pdfUpload('a.pdf', 'Same')]])
        ->assertJson(['created' => 1]);

    expect(Application::count())->toBe(2);
});

test('users from another company or without a manager role are forbidden', function () {
    $outsider = User::factory()->create(['role' => 'hr']);
    $viewer = User::factory()->create(['role' => 'viewer', 'company_id' => $this->user->company_id]);

    foreach ([$outsider, $viewer] as $forbidden) {
        $this->actingAs($forbidden)
            ->postJson(route('job-postings.intake.pdf', $this->job), ['files' => [pdfUpload('a.pdf')]])
            ->assertForbidden();
    }
});

test('only pdf and zip files are accepted', function () {
    $this->actingAs($this->user)
        ->postJson(route('job-postings.intake.pdf', $this->job), ['files' => [UploadedFile::fake()->create('cv.docx', 10)]])
        ->assertJsonValidationErrors('files.0');
});
