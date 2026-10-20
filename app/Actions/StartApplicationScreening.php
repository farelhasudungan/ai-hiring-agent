<?php

namespace App\Actions;

use App\Actions\Screening\ScreeningRun;
use App\Jobs\ParseCvJob;
use App\Jobs\ScoreApplicationJob;
use App\Models\Application;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class StartApplicationScreening
{
    public function handle(Application $application, ?User $user = null, bool $aiProcessingConsent = false): void
    {
        if ($user) {
            Gate::forUser($user)->authorize('update', $application);
            if (! $aiProcessingConsent) {
                throw ValidationException::withMessages(['ai_processing_consent' => 'Konfirmasi persetujuan kandidat untuk pemrosesan melalui Google Gemini wajib diberikan.']);
            }
        }

        DB::transaction(function () use ($application, $user): void {
            $record = Application::query()->forCompany($application->company_id)
                ->whereKey($application->id)->lockForUpdate()->firstOrFail();

            if ($user) {
                Gate::forUser($user)->authorize('update', $record);
            }

            if (! $record->canStartScreening()) {
                throw ValidationException::withMessages(['screening' => 'Lamaran sedang diproses atau sudah ditangani HR.']);
            }

            $resumes = $record->documents()->where('type', 'resume')->get();
            if ($resumes->count() !== 1) {
                throw ValidationException::withMessages(['screening' => 'Lamaran harus memiliki tepat satu dokumen CV utama.']);
            }
            $resume = $resumes->sole();
            $runId = (string) Str::uuid();
            $record->forceFill([
                'processing_run_id' => $runId, 'processing_error' => null, 'status' => 'queued',
                'latest_ai_status' => 'queued', 'latest_ai_at' => null, 'latest_match_score' => null,
            ])->save();
            $resume->update(['parsing_status' => 'pending', 'parsed_text' => null, 'error_message' => null]);
            $record->aiScreenings()->create([
                'run_id' => $runId, 'job_id' => $record->job_id, 'document_id' => $resume->id,
                'model_name' => config('ai.model'), 'status' => 'queued', 'prompt_text' => '',
                'input_snapshot' => [
                    'ai_provider' => config('ai.provider'),
                    'consent_confirmed_by' => $user?->id,
                    'job' => $record->job->only(['title', 'description', 'requirements', 'required_skills', 'nice_to_have', 'min_experience_years']),
                    'scoring' => config('ai.scoring'),
                ],
            ]);

            // Enqueue failure must not delete files belonging to a committed application.
            DB::afterCommit(function () use ($record, $runId): void {
                try {
                    Bus::chain([new ParseCvJob($record->id, $runId), new ScoreApplicationJob($record->id, $runId)])
                        ->onConnection('database')->dispatch();
                } catch (Throwable) {
                    app(ScreeningRun::class)->fail($record->id, $runId, 'Antrean tidak tersedia. Silakan proses ulang lamaran.');
                }
            });
        });

        $application->refresh();
    }
}
