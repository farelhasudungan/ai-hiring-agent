<?php

namespace App\Policies;

use App\Models\JobPosting;
use App\Models\User;

class JobPostingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null;
    }

    public function view(User $user, JobPosting $jobPosting): bool
    {
        return $user->company_id !== null && $user->company_id === $jobPosting->company_id;
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, JobPosting $jobPosting): bool
    {
        return $this->canManage($user) && $user->company_id === $jobPosting->company_id;
    }

    public function delete(User $user, JobPosting $jobPosting): bool
    {
        return $this->update($user, $jobPosting);
    }

    private function canManage(User $user): bool
    {
        return $user->company_id !== null && in_array($user->role, ['admin', 'hr'], true);
    }
}
