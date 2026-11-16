<?php

namespace App\Policies;

use App\Models\User;

class AiProviderSettingPolicy
{
    /**
     * @var list<string>
     */
    private const MANAGER_ROLES = ['admin', 'hr'];

    public function view(User $user): bool
    {
        return $user->company_id !== null;
    }

    public function update(User $user): bool
    {
        return $user->company_id !== null && in_array($user->role, self::MANAGER_ROLES, true);
    }
}
