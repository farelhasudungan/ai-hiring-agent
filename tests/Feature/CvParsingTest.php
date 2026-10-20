<?php

use App\Actions\Screening\ExtractCvText;
use App\Models\Application;
use App\Models\ApplicationDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

uses(RefreshDatabase::class);

function screeningPdf(string $text): string
{
    $stream = 'BT /F1 12 Tf 50 750 Td ('.strtr($text, ['\\' => '\\\\', '(' => '\\(', ')' => '\\)']).') Tj ET';
    $objects = [
        '<< /Type /Catalog /Pages 2 0 R >>',
        '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
        '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        '<< /Length '.strlen($stream).">>\nstream\n".$stream."\nendstream",
    ];
    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $index => $object) {
        $offsets[] = strlen($pdf);
        $pdf .= ($index + 1)." 0 obj\n".$object."\nendobj\n";
    }
    $xref = strlen($pdf);
    $pdf .= "xref\n0 6\n0000000000 65535 f \n";
    foreach (array_slice($offsets, 1) as $offset) {
        $pdf .= sprintf('%010d 00000 n ', $offset)."\n";
    }

    return $pdf."trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
}

function screeningResume(Application $application, string $extension = 'pdf'): ApplicationDocument
{
    return ApplicationDocument::factory()->create([
        'application_id' => $application->id,
        'file_path' => "applications/{$application->company_id}/{$application->id}/cv.{$extension}",
        'file_name' => 'cv.'.$extension,
        'mime_type' => $extension === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ]);
}

test('text is extracted from a real PDF document', function () {
    Storage::fake('local');
    $document = screeningResume(Application::factory()->create());
    Storage::disk('local')->put($document->file_path, screeningPdf('PHP Laravel developer with 3 years experience.'));

    $text = (new ExtractCvText)->handle($document);

    expect($text)->toContain('PHP Laravel developer with 3 years experience.');
});

test('DOCX paragraphs text runs and table cells are extracted without fetching links', function () {
    Storage::fake('local');
    $document = screeningResume(Application::factory()->create(), 'docx');
    Storage::disk('local')->makeDirectory(dirname($document->file_path));
    $word = new PhpWord;
    $section = $word->addSection();
    $section->addText('PHP developer');
    $run = $section->addTextRun();
    $run->addText('Laravel');
    $run->addText('PostgreSQL');
    $section->addTable()->addRow()->addCell()->addText('3 years experience');
    $section->addLink('https://example.invalid/no-request', 'Portfolio');
    IOFactory::createWriter($word, 'Word2007')->save(Storage::disk('local')->path($document->file_path));

    $text = (new ExtractCvText)->handle($document);

    expect($text)->toContain('PHP developer', 'Laravel', 'PostgreSQL', '3 years experience', 'Portfolio');
});

test('unreadable and textless CVs fail with a useful message', function (string $extension, string $content, string $message) {
    Storage::fake('local');
    $document = screeningResume(Application::factory()->create(), $extension);
    Storage::disk('local')->put($document->file_path, $content);

    expect(fn () => (new ExtractCvText)->handle($document))->toThrow(UnexpectedValueException::class, $message);
})->with([
    'scan or textless PDF' => ['pdf', screeningPdf(''), 'Teks CV kosong'],
    'broken PDF' => ['pdf', '%PDF-1.4 broken', 'CV rusak'],
    'broken DOCX' => ['docx', 'not a Word archive', 'DOCX rusak'],
    'unsupported type' => ['txt', 'some text', 'Format CV'],
]);

test('overlong CV text is rejected rather than silently truncated', function () {
    Storage::fake('local');
    config(['ai.max_cv_characters' => 10]);
    $document = screeningResume(Application::factory()->create());
    Storage::disk('local')->put($document->file_path, screeningPdf('This CV is much longer than ten characters.'));

    expect(fn () => (new ExtractCvText)->handle($document))->toThrow(UnexpectedValueException::class, 'terlalu panjang');
});

test('DOCX archive limits and XML entity declarations are rejected before parsing', function (bool $entity) {
    Storage::fake('local');
    $document = screeningResume(Application::factory()->create(), 'docx');
    Storage::disk('local')->makeDirectory(dirname($document->file_path));
    $zip = new ZipArchive;
    $zip->open(Storage::disk('local')->path($document->file_path), ZipArchive::CREATE);
    $zip->addFromString('word/document.xml', $entity ? '<!DOCTYPE foo [<!ENTITY x SYSTEM "file:///etc/passwd">]><foo>&x;</foo>' : str_repeat('x', 100));
    $zip->close();
    if (! $entity) {
        config(['ai.max_docx_uncompressed_bytes' => 10]);
    }

    expect(fn () => (new ExtractCvText)->handle($document))->toThrow(UnexpectedValueException::class, $entity ? 'XML' : 'dekompresi');
})->with([false, true]);

test('a CV cannot read files from another application directory', function () {
    Storage::fake('local');
    $application = Application::factory()->create();
    $other = screeningResume(Application::factory()->create());
    Storage::disk('local')->put($other->file_path, screeningPdf('Private CV'));
    $document = screeningResume($application);
    $document->update(['file_path' => $other->file_path]);

    expect(fn () => (new ExtractCvText)->handle($document))->toThrow(UnexpectedValueException::class, 'penyimpanan privat');
});
