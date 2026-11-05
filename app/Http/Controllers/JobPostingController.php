<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJobPostingRequest;
use App\Http\Requests\UpdateJobPostingRequest;
use App\Models\JobPosting;
use App\Repositories\JobPostingRepository;
use App\Services\JobPostingService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JobPostingController extends Controller
{
    public function index(Request $request, JobPostingRepository $repository): View
    {
        Gate::authorize('viewAny', JobPosting::class);

        $user = $request->user();
        $status = $request->query('status');
        $status = in_array($status, JobPostingService::STATUSES, true) ? $status : null;

        return view('job-postings.index', [
            'jobPostings' => $repository->paginateForCompany($user->company_id, $status),
            'statusCounts' => $repository->statusCounts($user->company_id),
            'activeStatus' => $status,
        ]);
    }

    public function create(Request $request, JobPostingRepository $repository): View
    {
        Gate::authorize('create', JobPosting::class);

        return view('job-postings.create', [
            'forms' => $repository->formsForCompany($request->user()->company_id),
        ]);
    }

    public function store(StoreJobPostingRequest $request, JobPostingService $service): RedirectResponse
    {
        $jobPosting = $service->create($request->user(), $request->validated());

        return redirect()->route('job-postings.show', $jobPosting)
            ->with('status', 'Job posting created.');
    }

    public function show(JobPosting $jobPosting): View
    {
        Gate::authorize('view', $jobPosting);

        $jobPosting->load('form')->loadCount('applications');

        return view('job-postings.show', [
            'jobPosting' => $jobPosting,
            'recentApplications' => $jobPosting->applications()
                ->with('candidate')
                ->latest('submitted_at')
                ->limit(5)
                ->get(),
        ]);
    }

    public function edit(Request $request, JobPosting $jobPosting, JobPostingRepository $repository): View
    {
        Gate::authorize('update', $jobPosting);

        return view('job-postings.edit', [
            'jobPosting' => $jobPosting,
            'forms' => $repository->formsForCompany($jobPosting->company_id),
        ]);
    }

    public function update(UpdateJobPostingRequest $request, JobPosting $jobPosting, JobPostingService $service): RedirectResponse
    {
        $service->update($request->user(), $jobPosting, $request->validated());

        return redirect()->route('job-postings.show', $jobPosting)
            ->with('status', 'Job posting updated.');
    }

    public function updateStatus(Request $request, JobPosting $jobPosting, JobPostingService $service): RedirectResponse
    {
        Gate::authorize('update', $jobPosting);

        $validated = $request->validate([
            'status' => ['required', Rule::in(JobPostingService::STATUSES)],
        ]);

        $service->updateStatus($request->user(), $jobPosting, $validated['status']);

        return back()->with('status', 'Job posting status updated.');
    }

    public function destroy(Request $request, JobPosting $jobPosting, JobPostingService $service): RedirectResponse
    {
        Gate::authorize('delete', $jobPosting);

        try {
            $service->delete($request->user(), $jobPosting);
        } catch (DomainException $exception) {
            return back()->withErrors(['job_posting' => $exception->getMessage()]);
        }

        return redirect()->route('job-postings.index')
            ->with('status', 'Job posting deleted.');
    }
}
