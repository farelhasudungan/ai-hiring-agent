<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use App\Repositories\OnboardingRepository;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OnboardingService
{
    public function __construct(private OnboardingRepository $repository)
    {
    }

    public function register(string $name, string $email, string $password): User
    {
        return $this->repository->createUser([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'company_id' => null,
            'role' => 'viewer',
        ]);
    }

    public function createCompany(User $user, string $name): Company
    {
        return DB::transaction(function () use ($user, $name): Company {
            $lockedUser = $this->repository->lockUser($user->id);

            if ($lockedUser->company_id !== null) {
                throw new DomainException('You already belong to a company.');
            }

            $company = $this->repository->createCompany([
                'name' => $name,
                'slug' => (Str::substr(Str::slug($name), 0, 200) ?: 'company') . '-' . Str::uuid(),
                'plan' => 'free',
            ]);

            $this->repository->assignCompany($lockedUser, $company, 'admin');

            return $company;
        });
    }
}
