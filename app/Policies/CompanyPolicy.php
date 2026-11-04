<?php

namespace App\Policies;

use App\Models\User;

class CompanyPolicy
{
    public function create(User $user): bool
    {
        return $user->company_id === null;
    }
}
