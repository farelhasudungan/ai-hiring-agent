<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\JobPosting;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CandidateController extends Controller
{
    public function show(JobPosting $jobPosting, Application $application): View
    {
        Gate::authorize('view', $jobPosting);

        $application->load(['documents', 'aiScreenings' => fn ($query) => $query->latest('id')]);

        $screening = $application->aiScreenings->firstWhere('status', 'success')
            ?? $application->aiScreenings->first();

        return view('job-postings.candidates.show', [
            'jobPosting' => $jobPosting,
            'application' => $application,
            'screening' => $screening,
        ]);
    }
}
