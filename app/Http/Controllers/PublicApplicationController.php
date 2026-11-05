<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePublicApplicationRequest;
use App\Models\Form;
use App\Models\JobPosting;
use App\Services\PublicApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PublicApplicationController extends Controller
{
    public function show(Request $request, string $token): View|Response
    {
        $form = $this->resolveForm($token);

        if (! $form->isAcceptingResponses()) {
            return response()->view('public.closed', ['form' => $form], 410);
        }

        $form->loadMissing(['sections.fields.options', 'fields.options']);

        $jobPostings = $form->jobPostings()
            ->where('company_id', $form->company_id)
            ->orderBy('title')
            ->get();
        $jobPosting = $this->resolveJobPosting($form, $request);

        if ($jobPosting === null && $jobPostings->count() > 1) {
            return view('public.choose-job', [
                'form' => $form,
                'jobPostings' => $jobPostings,
            ]);
        }

        return view('public.apply', [
            'form' => $form,
            'jobPosting' => $jobPosting,
            'jobPostings' => $jobPostings,
        ]);
    }

    public function store(StorePublicApplicationRequest $request, string $token, PublicApplicationService $service): RedirectResponse
    {
        $form = $this->resolveForm($token);

        abort_unless($form->isAcceptingResponses(), 410);

        $form->loadMissing(['sections.fields.options', 'fields.options']);

        $jobPosting = $this->resolveJobPosting($form, $request);

        abort_if($jobPosting === null && $form->jobPostings()->where('company_id', $form->company_id)->exists(), 404);

        $service->submit($form, $jobPosting, $request->validated('answers') ?? [], $request);

        return redirect()->route('public.apply.success', array_filter([
            'token' => $token,
            'job' => $jobPosting?->id,
        ], fn (mixed $value): bool => $value !== null));
    }

    public function success(Request $request, string $token): View
    {
        $form = $this->resolveForm($token);
        $jobPosting = null;

        if ($request->integer('job') > 0) {
            $jobPosting = $form->jobPostings()
                ->where('company_id', $form->company_id)
                ->whereKey($request->integer('job'))
                ->first();
        }

        return view('public.success', [
            'form' => $form,
            'jobPosting' => $jobPosting,
        ]);
    }

    private function resolveForm(string $token): Form
    {
        return Form::query()
            ->where('public_token', $token)
            ->with('company')
            ->firstOrFail();
    }

    private function resolveJobPosting(Form $form, Request $request): ?JobPosting
    {
        $jobId = $request->query('job');

        if ($jobId !== null && $jobId !== '') {
            $jobId = is_scalar($jobId) ? filter_var($jobId, FILTER_VALIDATE_INT) : false;

            abort_if($jobId === false, 404);

            $jobPosting = $form->jobPostings()
                ->where('company_id', $form->company_id)
                ->whereKey($jobId)
                ->first();

            abort_if($jobPosting === null, 404);

            return $jobPosting;
        }

        $jobPostings = $form->jobPostings()
            ->where('company_id', $form->company_id)
            ->get();

        return $jobPostings->count() === 1 ? $jobPostings->first() : null;
    }
}
