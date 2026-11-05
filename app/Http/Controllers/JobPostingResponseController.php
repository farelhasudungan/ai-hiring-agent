<?php

namespace App\Http\Controllers;

use App\Models\FormSubmission;
use App\Models\JobPosting;
use App\Repositories\FormRepository;
use App\Services\FormSubmissionService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class JobPostingResponseController extends Controller
{
    public function index(Request $request, JobPosting $jobPosting, FormRepository $repository): View
    {
        Gate::authorize('view', $jobPosting);

        $jobPosting->loadMissing('form');

        $status = $request->query('status');
        $status = in_array($status, FormSubmissionService::STATUSES, true) ? $status : null;

        return view('job-postings.responses.index', [
            'jobPosting' => $jobPosting,
            'responses' => $repository->paginateSubmissionsForJobPosting($jobPosting->id, $status),
            'statusCounts' => $repository->submissionStatusCounts($jobPosting->id),
            'activeStatus' => $status,
        ]);
    }

    public function show(JobPosting $jobPosting, FormSubmission $formSubmission): View
    {
        Gate::authorize('view', $jobPosting);

        abort_unless($formSubmission->job_posting_id === $jobPosting->id, 404);

        $formSubmission->load([
            'candidate',
            'application',
            'answers.field.section',
            'answers.files',
            'aiScreenings',
        ]);

        $answers = $formSubmission->answers
            ->sortBy(fn ($answer): array => [
                $answer->field?->section?->sort_order ?? PHP_INT_MAX,
                $answer->field?->sort_order ?? PHP_INT_MAX,
                $answer->id,
            ]);

        return view('job-postings.responses.show', [
            'jobPosting' => $jobPosting,
            'response' => $formSubmission,
            'answersBySection' => $answers->groupBy(fn ($answer): string => $answer->field?->section?->title ?? 'General'),
        ]);
    }

    public function updateStatus(Request $request, JobPosting $jobPosting, FormSubmission $formSubmission, FormSubmissionService $service): RedirectResponse
    {
        Gate::authorize('update', $jobPosting);

        abort_unless($formSubmission->job_posting_id === $jobPosting->id, 404);

        $validated = $request->validate([
            'status' => ['required', Rule::in(FormSubmissionService::STATUSES)],
        ]);

        try {
            $service->updateStatus($request->user(), $jobPosting, $formSubmission, $validated['status']);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['status' => $exception->getMessage()]);
        }

        return back()->with('status', 'Response status updated.');
    }

    public function destroy(Request $request, JobPosting $jobPosting, FormSubmission $formSubmission, FormSubmissionService $service): RedirectResponse
    {
        Gate::authorize('delete', $jobPosting);

        abort_unless($formSubmission->job_posting_id === $jobPosting->id, 404);

        $service->delete($request->user(), $jobPosting, $formSubmission);

        return redirect()->route('job-postings.responses.index', $jobPosting)
            ->with('status', 'Response deleted.');
    }
}
