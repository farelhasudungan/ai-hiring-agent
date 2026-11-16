<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePdfIntakeRequest;
use App\Models\JobPosting;
use App\Services\Intake\IntakeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class PdfIntakeController extends Controller
{
    public function __construct(public IntakeService $intakeService) {}

    public function store(StorePdfIntakeRequest $request, JobPosting $jobPosting): JsonResponse|RedirectResponse
    {
        $result = $this->intakeService->ingest($request->file('files'), $jobPosting, $request->user());

        if ($request->wantsJson()) {
            return response()->json($result, 201);
        }

        return redirect()
            ->route('job-postings.show', $jobPosting)
            ->with('status', "{$result['created']} candidate(s) uploaded, {$result['duplicates']} duplicate(s) skipped.")
            ->with('intake_rejected', $result['rejected']);
    }
}
