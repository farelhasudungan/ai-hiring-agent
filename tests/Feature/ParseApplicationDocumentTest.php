<?php

use App\Jobs\ParseApplicationDocument;
use App\Jobs\ScreenApplication;
use App\Models\ApplicationDocument;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
    Storage::fake('r2');
    config(['filesystems.intake_disk' => 'r2']);
});

test('parsing a valid pdf stores the extracted text', function () {
    Storage::disk('r2')->put('applications/cv.pdf', fakePdfContent('Jane Doe PHP Developer'));
    $document = ApplicationDocument::factory()->create(['file_path' => 'applications/cv.pdf']);

    app()->call([new ParseApplicationDocument($document), 'handle']);

    expect($document->fresh()->parsing_status)->toBe('success')
        ->and($document->fresh()->parsed_text)->toContain('Jane Doe')
        ->and($document->application->fresh()->status)->toBe('queued');

    Queue::assertPushed(ScreenApplication::class, fn (ScreenApplication $job) => $job->application->is($document->application));
});

test('parsing a broken pdf marks the document and application as failed', function () {
    Storage::disk('r2')->put('applications/cv.pdf', '%PDF-1.4 garbage');
    $document = ApplicationDocument::factory()->create(['file_path' => 'applications/cv.pdf']);

    app()->call([new ParseApplicationDocument($document), 'handle']);

    expect($document->fresh()->parsing_status)->toBe('failed')
        ->and($document->fresh()->error_message)->not->toBeEmpty()
        ->and($document->application->fresh()->status)->toBe('failed');

    Queue::assertNotPushed(ScreenApplication::class);
});

test('a missing stored file marks the document as failed', function () {
    $document = ApplicationDocument::factory()->create(['file_path' => 'applications/gone.pdf']);

    app()->call([new ParseApplicationDocument($document), 'handle']);

    expect($document->fresh()->parsing_status)->toBe('failed');
});
