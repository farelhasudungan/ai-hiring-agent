<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreScreeningRequest;
use App\Jobs\ScreenApplication;
use App\Models\Application;
use App\Models\JobPosting;
use Illuminate\Http\RedirectResponse;

class ScreeningController extends Controller
{
    /**
     * Starts AI screening for the selected applications, or for every parsed
     * application of the job that has not been screened yet when none are
     * selected. Nothing is sent to the AI until the user asks for it.
     */
    public function store(StoreScreeningRequest $request, JobPosting $jobPosting): RedirectResponse
    {
        if ($request->user()->company->aiProviderSetting === null) {
            return redirect()->route('job-postings.show', $jobPosting)
                ->withErrors(['screening' => 'Set up an AI provider in AI settings before running a screening.']);
        }

        $started = 0;

        $jobPosting->screenableApplications(includeScored: $request->filled('application_ids'))
            ->when($request->validated('application_ids'), fn ($query, array $ids) => $query->whereKey($ids))
            ->get()
            ->each(function (Application $application) use (&$started): void {
                $claimed = Application::query()
                    ->whereKey($application->id)
                    ->whereIn('status', Application::SELECTABLE_STATUSES)
                    ->update(['status' => 'analyzing', 'latest_ai_status' => 'queued']);

                if ($claimed === 1) {
                    ScreenApplication::dispatch($application);
                    $started++;
                }
            });

        return redirect()->route('job-postings.show', $jobPosting)
            ->with('status', $started === 0 ? 'No candidates are waiting for AI screening.' : "AI screening started for {$started} candidate(s).");
    }
}
