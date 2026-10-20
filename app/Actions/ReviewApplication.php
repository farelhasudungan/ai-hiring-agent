<?php

namespace App\Actions;

use App\Models\Application;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ReviewApplication
{
    /** @param array<string, mixed> $data */
    public function handle(Application $application, User $reviewer, array $data): Application
    {
        Gate::forUser($reviewer)->authorize('update', $application);

        $validated = Validator::make(Arr::only($data, ['status', 'notes']), [
            'status' => ['sometimes', 'required', Rule::in(array_keys(Application::statusOptions()))],
            'notes' => ['nullable', 'string', 'max:10000'],
        ])->validate();

        DB::transaction(function () use ($application, $reviewer, $validated): void {
            $record = Application::query()->forCompany($reviewer->company_id)
                ->whereKey($application->getKey())->lockForUpdate()->firstOrFail();
            Gate::forUser($reviewer)->authorize('update', $record);

            $record->fill($validated);

            if ($record->isDirty('status')) {
                if ($record->processing_run_id && in_array($record->latest_ai_status, ['queued', 'running'], true)) {
                    $message = 'Pemrosesan AI dihentikan karena status diubah oleh HR.';
                    $record->currentScreening()->where('application_id', $record->id)->whereIn('status', ['queued', 'running'])->update([
                        'status' => 'failed', 'error_message' => $message, 'completed_at' => now(),
                    ]);
                    $record->forceFill(['latest_ai_status' => 'failed', 'processing_error' => $message, 'latest_ai_at' => now()]);
                }
                $record->reviewed_by = $reviewer->id;
                $record->reviewed_at = now();
            }

            $record->save();
        });

        return $application->refresh();
    }
}
