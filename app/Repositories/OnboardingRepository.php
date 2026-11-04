<?php

namespace App\Repositories;

use App\Models\Company;
use App\Models\User;

class OnboardingRepository
{
    /**
     * @param  array{name: string, email: string, password: string, company_id: null, role: string}  $attributes
     */
    public function createUser(array $attributes): User
    {
        return User::create($attributes);
    }

    public function lockUser(int $userId): User
    {
        return User::query()->lockForUpdate()->findOrFail($userId);
    }

    /**
     * @param  array{name: string, slug: string, plan: string}  $attributes
     */
    public function createCompany(array $attributes): Company
    {
        return Company::create($attributes);
    }

    public function assignCompany(User $user, Company $company, string $role): void
    {
        $user->company()->associate($company);
        $user->role = $role;
        $user->save();
    }
}
