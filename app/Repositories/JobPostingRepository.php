<?php

namespace App\Repositories;

use App\Models\Form;
use App\Models\JobPosting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class JobPostingRepository
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): JobPosting
    {
        return JobPosting::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(JobPosting $jobPosting, array $attributes): JobPosting
    {
        $jobPosting->update($attributes);

        return $jobPosting;
    }

    public function delete(JobPosting $jobPosting): void
    {
        $jobPosting->delete();
    }

    public function paginateForCompany(int $companyId, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return JobPosting::query()
            ->where('company_id', $companyId)
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->withCount('applications')
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array{all: int, draft: int, open: int, closed: int}
     */
    public function statusCounts(int $companyId): array
    {
        $counts = JobPosting::query()
            ->where('company_id', $companyId)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        return [
            'all' => array_sum($counts),
            'draft' => $counts['draft'] ?? 0,
            'open' => $counts['open'] ?? 0,
            'closed' => $counts['closed'] ?? 0,
        ];
    }

    /**
     * @return Collection<int, Form>
     */
    public function formsForCompany(int $companyId): Collection
    {
        return Form::query()
            ->where('company_id', $companyId)
            ->orderBy('title')
            ->get();
    }
}
