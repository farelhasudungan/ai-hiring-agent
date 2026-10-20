<?php

namespace App\Jobs;

use App\Actions\Screening\ExtractCvText;
use App\Actions\Screening\ScreeningRun;
use App\Models\Application;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;
use UnexpectedValueException;

class ParseCvJob implements ShouldQueue
{
    use Queueable;

    // Lock-contention releases count as attempts; allow room without repeating errors indefinitely.
    public int $tries = 10;

    public int $maxExceptions = 3;

    public int $timeout = 60;

    public bool $failOnTimeout = true;

    public function __construct(public int $applicationId, public string $runId) {}

    public function backoff(): array
    {
        return [30, 120];
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping("screening:{$this->applicationId}:{$this->runId}"))->shared()->releaseAfter(30)->expireAfter(210)];
    }

    public function handle(ExtractCvText $extract, ScreeningRun $run): void
    {
        try {
            $document = $run->locked($this->applicationId, $this->runId, function (Application $application) {
                $document = $application->documents()->where('type', 'resume')->sole();
                if ($document->parsing_status === 'success') {
                    return null;
                }
                $application->forceFill(['status' => 'parsing', 'latest_ai_status' => 'running'])->save();

                return $document;
            });
            if (! $document) {
                return;
            }

            $text = $extract->handle($document);
            $run->locked($this->applicationId, $this->runId, function (Application $application) use ($document, $text): void {
                $application->documents()->whereKey($document->id)->update([
                    'parsed_text' => $text, 'parsing_status' => 'success', 'error_message' => null,
                ]);
            });
        } catch (UnexpectedValueException $exception) {
            $run->fail($this->applicationId, $this->runId, $exception->getMessage(), parsing: true);
            $this->fail($exception);
        }
    }

    public function failed(?Throwable $exception): void
    {
        app(ScreeningRun::class)->fail($this->applicationId, $this->runId, 'Pemrosesan CV gagal atau melewati batas waktu. Silakan proses ulang.', parsing: true);
    }
}
