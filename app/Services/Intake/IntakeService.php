<?php

namespace App\Services\Intake;

use App\Jobs\ParseApplicationDocument;
use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\JobPosting;
use App\Models\User;
use App\Services\Intake\Contracts\IntakeAdapter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class IntakeService
{
    public function __construct(public IntakeAdapter $adapter) {}

    /**
     * Run the adapter and create one application per new document.
     *
     * @param  array<int, mixed>  $inputs
     * @return array{created: int, duplicates: int, rejected: list<string>}
     */
    public function ingest(array $inputs, JobPosting $job, User $user): array
    {
        $batch = $this->adapter->collect($inputs, $job->company_id);

        $created = 0;
        $duplicates = 0;
        $seenHashes = [];

        foreach ($batch->documents as $document) {
            if (in_array($document->contentHash, $seenHashes, true) || $this->isDuplicate($job, $document->contentHash)) {
                Storage::disk(config('filesystems.intake_disk'))->delete($document->storedPath);
                $duplicates++;

                continue;
            }

            $seenHashes[] = $document->contentHash;

            $resume = DB::transaction(function () use ($document, $job, $user): ApplicationDocument {
                $application = Application::create([
                    'company_id' => $job->company_id,
                    'job_id' => $job->id,
                    'source' => 'pdf',
                    'status' => 'received',
                    'submitted_at' => now(),
                ]);

                ActivityLog::create([
                    'user_id' => $user->id,
                    'application_id' => $application->id,
                    'action' => 'created',
                    'description' => "Application imported from PDF {$document->fileName}",
                    'meta' => ['origin' => $document->originName],
                ]);

                return $application->documents()->create([
                    'type' => 'resume',
                    'file_name' => $document->fileName,
                    'origin_name' => $document->originName,
                    'file_path' => $document->storedPath,
                    'mime_type' => $document->mimeType,
                    'size_bytes' => $document->sizeBytes,
                    'content_hash' => $document->contentHash,
                    'parsing_status' => 'pending',
                ]);
            });

            ParseApplicationDocument::dispatch($resume);
            $created++;
        }

        return ['created' => $created, 'duplicates' => $duplicates, 'rejected' => $batch->rejected];
    }

    private function isDuplicate(JobPosting $job, string $hash): bool
    {
        return ApplicationDocument::query()
            ->where('content_hash', $hash)
            ->whereHas('application', fn ($query) => $query->where('job_id', $job->id))
            ->exists();
    }
}
