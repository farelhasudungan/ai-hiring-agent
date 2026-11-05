<?php

namespace App\Services;

use App\Models\JobPosting;
use App\Models\User;
use App\Repositories\JobPostingRepository;
use DomainException;

class JobPostingService
{
    /**
     * Statuses a job posting may hold.
     *
     * @var list<string>
     */
    public const STATUSES = ['draft', 'open', 'closed'];

    public function __construct(private JobPostingRepository $repository)
    {
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(User $user, array $attributes): JobPosting
    {
        if ($user->company_id === null) {
            throw new DomainException('You must belong to a company before creating a job posting.');
        }

        $attributes = $this->normalize($attributes);
        $attributes['company_id'] = $user->company_id;
        $attributes['required_skills'] ??= [];
        $attributes['nice_to_have'] ??= [];

        return $this->repository->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(User $user, JobPosting $jobPosting, array $attributes): JobPosting
    {
        $this->ensureCompanyMatches($user, $jobPosting);

        return $this->repository->update($jobPosting, $this->normalize($attributes));
    }

    public function updateStatus(User $user, JobPosting $jobPosting, string $status): JobPosting
    {
        $this->ensureCompanyMatches($user, $jobPosting);

        if (! in_array($status, self::STATUSES, true)) {
            throw new DomainException('The selected status is invalid.');
        }

        return $this->repository->update($jobPosting, ['status' => $status]);
    }

    public function delete(User $user, JobPosting $jobPosting): void
    {
        $this->ensureCompanyMatches($user, $jobPosting);

        if ($jobPosting->applications()->exists()) {
            throw new DomainException('This job posting has applications and cannot be deleted.');
        }

        $this->repository->delete($jobPosting);
    }

    private function ensureCompanyMatches(User $user, JobPosting $jobPosting): void
    {
        if ($user->company_id === null || $user->company_id !== $jobPosting->company_id) {
            throw new DomainException('This job posting does not belong to your company.');
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function normalize(array $attributes): array
    {
        foreach (['required_skills', 'nice_to_have'] as $key) {
            if (array_key_exists($key, $attributes)) {
                $attributes[$key] = $this->parseSkills($attributes[$key]);
            }
        }

        return $attributes;
    }

    /**
     * @return list<string>
     */
    private function parseSkills(mixed $value): array
    {
        $skills = is_array($value) ? $value : explode(',', (string) $value);

        $skills = array_map(static fn (mixed $skill): string => trim((string) $skill), $skills);

        return array_values(array_unique(array_filter(
            $skills,
            static fn (string $skill): bool => $skill !== '',
        )));
    }
}
