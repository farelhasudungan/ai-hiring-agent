<?php

namespace App\Repositories;

use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FormRepository
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Form
    {
        return Form::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Form $form, array $attributes): Form
    {
        $form->update($attributes);

        return $form;
    }

    public function paginateForCompany(int $companyId, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return Form::query()
            ->where('company_id', $companyId)
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->withCount(['fields', 'submissions', 'jobPostings'])
            ->latest('updated_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array{all: int, draft: int, published: int, closed: int}
     */
    public function statusCounts(int $companyId): array
    {
        $counts = Form::query()
            ->where('company_id', $companyId)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        return [
            'all' => array_sum($counts),
            'draft' => $counts['draft'] ?? 0,
            'published' => $counts['published'] ?? 0,
            'closed' => $counts['closed'] ?? 0,
        ];
    }

    public function paginateSubmissionsForJobPosting(int $jobPostingId, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return FormSubmission::query()
            ->where('job_posting_id', $jobPostingId)
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->with(['candidate', 'application', 'answers.field'])
            ->withCount('answers')
            ->latest('submitted_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array{all: int, draft: int, submitted: int, processed: int, spam: int}
     */
    public function submissionStatusCounts(int $jobPostingId): array
    {
        $counts = FormSubmission::query()
            ->where('job_posting_id', $jobPostingId)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        return [
            'all' => array_sum($counts),
            'draft' => $counts['draft'] ?? 0,
            'submitted' => $counts['submitted'] ?? 0,
            'processed' => $counts['processed'] ?? 0,
            'spam' => $counts['spam'] ?? 0,
        ];
    }
}
