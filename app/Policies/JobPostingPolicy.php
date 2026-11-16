<?php

namespace App\Policies;

use App\Models\JobPosting;
use App\Models\User;

class JobPostingPolicy
{
    /**
     * Roles allowed to create, edit, and delete job postings.
     *
     * @var list<string>
     */
    private const MANAGER_ROLES = ['admin', 'hr'];

    public function viewAny(User $user): bool
    {
        return $user->company_id !== null;
    }

    public function view(User $user, JobPosting $jobPosting): bool
    {
        return $this->belongsToSameCompany($user, $jobPosting);
    }

    public function create(User $user): bool
    {
        return $user->company_id !== null && in_array($user->role, self::MANAGER_ROLES, true);
    }

    public function update(User $user, JobPosting $jobPosting): bool
    {
        return $this->belongsToSameCompany($user, $jobPosting) && in_array($user->role, self::MANAGER_ROLES, true);
    }

    public function delete(User $user, JobPosting $jobPosting): bool
    {
        return $this->update($user, $jobPosting);
    }

    private function belongsToSameCompany(User $user, JobPosting $jobPosting): bool
    {
        return $user->company_id !== null && $user->company_id === $jobPosting->company_id;
    }
}
