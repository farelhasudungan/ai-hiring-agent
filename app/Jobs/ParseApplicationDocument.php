<?php

namespace App\Jobs;

use App\Models\ApplicationDocument;
use App\Services\Intake\PdfTextExtractor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ParseApplicationDocument implements ShouldQueue
{
    use Queueable;

    public function __construct(public ApplicationDocument $document) {}

    public function handle(PdfTextExtractor $extractor): void
    {
        $this->document->application->update(['status' => 'parsing']);

        try {
            $content = Storage::disk(config('filesystems.intake_disk'))->get($this->document->file_path);

            if ($content === null) {
                throw new RuntimeException('Stored file is missing.');
            }

            $text = $extractor->extract($content);

            $this->document->update([
                'parsing_status' => 'success',
                'parsed_text' => $text,
                'error_message' => null,
            ]);
            $this->document->application->update(['status' => 'queued']);
        } catch (RuntimeException $exception) {
            $this->document->update([
                'parsing_status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
            $this->document->application->update(['status' => 'failed']);
        }
    }
}
