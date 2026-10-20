<?php

namespace App\Actions;

use App\Models\Application;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CancelApplicationScreening
{
    public function handle(Application $application, User $user, string $runId): Application
    {
        Gate::forUser($user)->authorize('update', $application);

        DB::transaction(function () use ($application, $user, $runId): void {
            $record = Application::query()->forCompany($user->company_id)
                ->whereKey($application->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($user)->authorize('update', $record);

            // Pin confirmation to a run: an old modal must never cancel its replacement.
            if ($record->processing_run_id !== $runId || ! $record->canCancelScreening()) {
                throw ValidationException::withMessages(['run_id' => 'Proses sudah selesai, dibatalkan, atau diganti. Muat ulang halaman.']);
            }

            $screening = $record->currentScreening()->where('application_id', $record->id)
                ->whereIn('status', ['queued', 'running'])->first();
            if (! $screening) {
                throw ValidationException::withMessages(['run_id' => 'Tidak ada proses AI aktif yang dapat dibatalkan.']);
            }

            $screening->update(['status' => 'cancelled', 'completed_at' => now(), 'error_message' => null]);
            $record->documents()->where('type', 'resume')->where('parsing_status', 'pending')
                ->update(['parsing_status' => 'cancelled', 'error_message' => null]);

            // Preserve the application and reviewer metadata; late workers are fenced by AI status.
            $record->forceFill([
                'status' => 'received', 'latest_ai_status' => 'cancelled', 'latest_ai_at' => now(),
                'latest_match_score' => null, 'processing_error' => null,
            ])->save();

            $record->activityLogs()->create([
                'user_id' => $user->id, 'candidate_id' => $record->candidate_id,
                'action' => 'ai_cancelled', 'description' => 'Pemrosesan CV dan analisis AI dibatalkan oleh HR.',
                'meta' => ['run_id' => $runId],
            ]);
        });

        return $application->refresh();
    }
}
