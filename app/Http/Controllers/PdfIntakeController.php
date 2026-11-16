<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePdfIntakeRequest;
use App\Models\JobPosting;
use App\Services\Intake\IntakeService;
use Illuminate\Http\JsonResponse;

class PdfIntakeController extends Controller
{
    public function __construct(public IntakeService $intakeService) {}

    public function store(StorePdfIntakeRequest $request, JobPosting $jobPosting): JsonResponse
    {
        $result = $this->intakeService->ingest($request->file('files'), $jobPosting, $request->user());

        return response()->json($result, 201);
    }
}
