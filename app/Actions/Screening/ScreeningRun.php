<?php

namespace App\Actions\Screening;

use App\Models\Application;
use Closure;
use Illuminate\Support\Facades\DB;

class ScreeningRun
{
    public function locked(int $applicationId, string $runId, Closure $callback): mixed
    {
        return DB::transaction(function () use ($applicationId, $runId, $callback) {
            $application = Application::query()->whereKey($applicationId)->lockForUpdate()->first();

            if (! $application || $application->processing_run_id !== $runId
                || ! in_array($application->status, ['queued', 'parsing', 'analyzing'], true)
                || ! in_array($application->latest_ai_status, ['queued', 'running'], true)) {
                return null;
            }

            if (! Application::query()->forCompany($application->company_id)->whereKey($applicationId)->exists()) {
                $this->markFailed($application, 'Relasi perusahaan lamaran tidak valid.');

                return null;
            }

            return $callback($application);
        });
    }

    public function fail(int $applicationId, string $runId, string $message, bool $parsing = false): void
    {
        $this->locked($applicationId, $runId, function (Application $application) use ($message, $parsing): void {
            $this->markFailed($application, $message);

            if ($parsing) {
                $application->documents()->where('type', 'resume')->update([
                    'parsing_status' => 'failed', 'parsed_text' => null, 'error_message' => $message,
                ]);
            }
        });
    }

    private function markFailed(Application $application, string $message): void
    {
        $application->forceFill([
            'status' => 'failed', 'latest_ai_status' => 'failed', 'latest_ai_at' => now(), 'processing_error' => $message,
        ])->save();
        $application->currentScreening()->where('application_id', $application->id)->where('status', '!=', 'success')->update([
            'status' => 'failed', 'error_message' => $message, 'completed_at' => now(),
        ]);
    }
}
