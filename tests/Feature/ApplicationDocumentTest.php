<?php

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\User;
use Aws\Command;
use Aws\MockHandler;
use Aws\Result;
use Aws\S3\Exception\S3Exception;
use Filament\Facades\Filament;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToReadFile;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['app.env' => 'local']);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

function privateApplicationDocument(Application $application, string $content = "%PDF-1.7\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF"): ApplicationDocument
{
    $document = ApplicationDocument::factory()->create([
        'application_id' => $application->id,
        'file_path' => "applications/{$application->company_id}/{$application->id}/resume.pdf",
    ]);
    Storage::disk('r2')->put($document->file_path, $content);

    return $document;
}

test('company members may download private application documents', function (string $role) {
    Storage::fake('r2');
    $user = User::factory()->create(['role' => $role]);
    $application = Application::factory()->create(['company_id' => $user->company_id]);
    $document = privateApplicationDocument($application);

    $response = $this->actingAs($user)->get(route('filament.admin.application-documents.show', [
        'application' => $application, 'document' => $document,
    ]));

    $response->assertOk()->assertDownload('resume.pdf')
        ->assertHeader('Content-Type', 'application/octet-stream')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
})->with(['admin', 'hr', 'interviewer', 'viewer']);

test('PDF documents may be previewed inline after authentication', function () {
    Storage::fake('r2');
    $user = User::factory()->create(['role' => 'admin']);
    $application = Application::factory()->create(['company_id' => $user->company_id]);
    $document = privateApplicationDocument($application);

    $response = $this->actingAs($user)->get(route('filament.admin.application-documents.show', [
        'application' => $application, 'document' => $document, 'preview' => 1,
    ]));

    $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect($response->headers->get('Content-Disposition'))->toStartWith('inline;');
    expect($response->headers->get('Content-Security-Policy'))->toBe("frame-ancestors 'self'");
});

test('non PDF content cannot be previewed even with PDF metadata', function () {
    Storage::fake('r2');
    $user = User::factory()->create(['role' => 'admin']);
    $application = Application::factory()->create(['company_id' => $user->company_id]);
    $document = privateApplicationDocument($application, '<html><script>alert(1)</script></html>');

    $this->actingAs($user)->get(route('filament.admin.application-documents.show', [
        'application' => $application, 'document' => $document, 'preview' => 1,
    ]))->assertStatus(415);
});

test('DOCX files can be downloaded without sending them to an external preview service', function () {
    Storage::fake('r2');
    $user = User::factory()->create(['role' => 'admin']);
    $application = Application::factory()->create(['company_id' => $user->company_id]);
    $document = privateApplicationDocument($application, 'private DOCX content');
    $document->update(['file_name' => 'resume.docx', 'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);

    $this->actingAs($user)->get(route('filament.admin.application-documents.show', [
        'application' => $application, 'document' => $document,
    ]))->assertOk()->assertDownload('resume.docx');
});

test('another company application documents cannot be read', function (bool $preview) {
    Storage::fake('r2');
    $user = User::factory()->create(['role' => 'admin']);
    $application = Application::factory()->create();
    $document = privateApplicationDocument($application);

    $this->actingAs($user)->get(route('filament.admin.application-documents.show', [
        'application' => $application, 'document' => $document, 'preview' => (int) $preview,
    ]))->assertNotFound();
})->with([false, true]);

test('a document from another application cannot be substituted into an authorized URL', function () {
    Storage::fake('r2');
    $user = User::factory()->create(['role' => 'admin']);
    $own = Application::factory()->create(['company_id' => $user->company_id]);
    $other = Application::factory()->create(['company_id' => $user->company_id]);
    $document = privateApplicationDocument($other);

    $this->actingAs($user)->get(route('filament.admin.application-documents.show', [
        'application' => $own, 'document' => $document,
    ]))->assertNotFound();
});

test('applications with cross company candidate or job links cannot expose documents', function (string $foreignKey) {
    Storage::fake('r2');
    $user = User::factory()->create(['role' => 'admin']);
    $other = Application::factory()->create();
    $application = Application::factory()->create(['company_id' => $user->company_id, $foreignKey => $other->{$foreignKey}]);
    $document = privateApplicationDocument($application);

    $this->actingAs($user)->get(route('filament.admin.application-documents.show', [
        'application' => $application, 'document' => $document,
    ]))->assertNotFound();
})->with(['candidate_id', 'job_id']);

test('missing document files return not found', function () {
    Storage::fake('r2');
    $user = User::factory()->create(['role' => 'admin']);
    $application = Application::factory()->create(['company_id' => $user->company_id]);
    $document = privateApplicationDocument($application);
    Storage::disk('r2')->delete($document->file_path);

    $this->actingAs($user)->get(route('filament.admin.application-documents.show', [
        'application' => $application, 'document' => $document,
    ]))->assertNotFound();
});

test('document paths cannot escape their application private directory', function (bool $useTraversal) {
    Storage::fake('r2');
    $user = User::factory()->create(['role' => 'admin']);
    $application = Application::factory()->create(['company_id' => $user->company_id]);
    $other = Application::factory()->create(['company_id' => $user->company_id]);
    $target = privateApplicationDocument($other);
    $document = privateApplicationDocument($application);
    $document->update(['file_path' => $useTraversal
        ? "applications/{$user->company_id}/{$application->id}/../{$other->id}/resume.pdf"
        : $target->file_path]);

    $this->actingAs($user)->get(route('filament.admin.application-documents.show', [
        'application' => $application, 'document' => $document,
    ]))->assertNotFound();
})->with([false, true]);

test('unauthenticated document requests redirect to panel login', function () {
    Storage::fake('r2');
    $application = Application::factory()->create();
    $document = privateApplicationDocument($application);

    $this->get(route('filament.admin.application-documents.show', [
        'application' => $application, 'document' => $document,
    ]))->assertRedirectToRoute('filament.admin.auth.login');
});

test('an unknown role cannot read private documents', function () {
    Storage::fake('r2');
    $user = User::factory()->create(['role' => 'unknown']);
    $application = Application::factory()->create(['company_id' => $user->company_id]);
    $document = privateApplicationDocument($application);

    $this->actingAs($user)->get(route('filament.admin.application-documents.show', [
        'application' => $application, 'document' => $document,
    ]))->assertForbidden();
});

test('document routes preserve the panel production access restriction', function () {
    Storage::fake('r2');
    $user = User::factory()->create(['role' => 'admin']);
    $application = Application::factory()->create(['company_id' => $user->company_id]);
    $document = privateApplicationDocument($application);
    config(['app.env' => 'production']);

    $this->actingAs($user)->get(route('filament.admin.application-documents.show', [
        'application' => $application, 'document' => $document,
    ]))->assertForbidden();
});

test('symlinks cannot expose files from another application directory', function () {
    Storage::fake('r2');
    $user = User::factory()->create(['role' => 'admin']);
    $application = Application::factory()->create(['company_id' => $user->company_id]);
    $other = Application::factory()->create(['company_id' => $user->company_id]);
    $target = privateApplicationDocument($other);
    $document = privateApplicationDocument($application);
    $linkPath = "applications/{$user->company_id}/{$application->id}/linked.pdf";
    symlink(Storage::disk('r2')->path($target->file_path), Storage::disk('r2')->path($linkPath));
    $document->update(['file_path' => $linkPath]);

    $this->actingAs($user)->get(route('filament.admin.application-documents.show', [
        'application' => $application, 'document' => $document,
    ]))->assertNotFound();
});

test('R2 objects are streamed in full for authenticated downloads and PDF previews', function (bool $preview) {
    $user = User::factory()->create(['role' => 'admin']);
    $application = Application::factory()->create(['company_id' => $user->company_id]);
    $document = ApplicationDocument::factory()->create([
        'application_id' => $application->id,
        'file_path' => "applications/{$application->company_id}/{$application->id}/resume.pdf",
    ]);
    $content = "%PDF-1.7\n".str_repeat('document content ', 1000)."\n%%EOF";
    $handler = new MockHandler([
        new Result,
        new Result(['Body' => Utils::streamFor($content)]),
    ]);
    Storage::set('r2', Storage::build([
        ...config('filesystems.disks.r2'),
        'key' => 'test-key',
        'secret' => 'test-secret',
        'bucket' => 'test-documents',
        'endpoint' => 'https://test-account.r2.cloudflarestorage.com',
        'handler' => $handler,
    ]));

    $response = $this->actingAs($user)->get(route('filament.admin.application-documents.show', [
        'application' => $application, 'document' => $document, 'preview' => (int) $preview,
    ]));

    $response->assertOk()->assertStreamedContent($content)
        ->assertHeader('Content-Type', $preview ? 'application/pdf' : 'application/octet-stream')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
    expect($response->headers->get('Content-Disposition'))->toStartWith($preview ? 'inline;' : 'attachment;');
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
    expect($handler->getLastCommand()->getName())->toBe('GetObject');
    expect($handler->getLastCommand()['Key'])->toBe($document->file_path);
    expect($handler->getLastRequest()->hasHeader('x-amz-checksum-mode'))->toBeFalse();
    expect($handler->count())->toBe(0);
})->with([false, true]);

test('a missing R2 object returns not found without exposing storage details', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $application = Application::factory()->create(['company_id' => $user->company_id]);
    $document = ApplicationDocument::factory()->create([
        'application_id' => $application->id,
        'file_path' => "applications/{$application->company_id}/{$application->id}/resume.pdf",
    ]);
    $handler = new MockHandler([
        new S3Exception('Missing object', new Command('HeadObject'), ['code' => 'NotFound', 'response' => new Response(404)]),
    ]);
    Storage::set('r2', Storage::build([
        ...config('filesystems.disks.r2'),
        'key' => 'test-key',
        'secret' => 'test-secret',
        'bucket' => 'test-documents',
        'endpoint' => 'https://test-account.r2.cloudflarestorage.com',
        'handler' => $handler,
    ]));

    $this->actingAs($user)->get(route('filament.admin.application-documents.show', [
        'application' => $application, 'document' => $document,
    ]))->assertNotFound();

    expect($handler->getLastCommand()->getName())->toBe('HeadObject');
    expect($handler->count())->toBe(0);
});

test('a failed R2 read returns service unavailable instead of a successful empty document', function () {
    $disk = Storage::fake('r2');
    $user = User::factory()->create(['role' => 'admin']);
    $application = Application::factory()->create(['company_id' => $user->company_id]);
    $document = privateApplicationDocument($application);
    $storage = Mockery::mock($disk);
    $storage->shouldReceive('readStream')->once()->andThrow(UnableToReadFile::fromLocation($document->file_path));
    Storage::set('r2', $storage);
    Exceptions::fake();

    $this->actingAs($user)->get(route('filament.admin.application-documents.show', [
        'application' => $application, 'document' => $document,
    ]))->assertServiceUnavailable();

    Exceptions::assertReported(UnableToReadFile::class);
    $disk->assertExists($document->file_path);
});
