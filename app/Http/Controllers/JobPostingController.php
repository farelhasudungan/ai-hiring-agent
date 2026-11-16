<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJobPostingRequest;
use App\Http\Requests\UpdateJobPostingRequest;
use App\Models\JobPosting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class JobPostingController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', JobPosting::class);

        $jobPostings = JobPosting::query()
            ->where('company_id', $request->user()->company_id)
            ->withCount('applications')
            ->when(
                in_array($request->query('status'), JobPosting::STATUSES, true),
                fn ($query) => $query->where('status', $request->query('status')),
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('job-postings.index', ['jobPostings' => $jobPostings]);
    }

    public function create(): View
    {
        Gate::authorize('create', JobPosting::class);

        return view('job-postings.create');
    }

    public function store(StoreJobPostingRequest $request): RedirectResponse
    {
        $jobPosting = JobPosting::query()->create([
            ...$request->validated(),
            'company_id' => $request->user()->company_id,
        ]);

        return redirect()->route('job-postings.show', $jobPosting)->with('status', 'Job posting created.');
    }

    public function show(JobPosting $jobPosting): View
    {
        Gate::authorize('view', $jobPosting);

        return view('job-postings.show', ['jobPosting' => $jobPosting->loadCount('applications')]);
    }

    public function edit(JobPosting $jobPosting): View
    {
        Gate::authorize('update', $jobPosting);

        return view('job-postings.edit', ['jobPosting' => $jobPosting]);
    }

    public function update(UpdateJobPostingRequest $request, JobPosting $jobPosting): RedirectResponse
    {
        $jobPosting->update($request->validated());

        return redirect()->route('job-postings.show', $jobPosting)->with('status', 'Job posting updated.');
    }

    public function destroy(JobPosting $jobPosting): RedirectResponse
    {
        Gate::authorize('delete', $jobPosting);

        if ($jobPosting->applications()->exists()) {
            return redirect()->route('job-postings.show', $jobPosting)
                ->with('status', 'This job posting has applications, so it cannot be deleted. Close it instead.');
        }

        $jobPosting->delete();

        return redirect()->route('job-postings.index')->with('status', 'Job posting deleted.');
    }
}
