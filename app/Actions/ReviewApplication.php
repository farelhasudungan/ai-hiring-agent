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
                $record->reviewed_by = $reviewer->id;
                $record->reviewed_at = now();
            }

            $record->save();
        });

        return $application->refresh();
    }
}
