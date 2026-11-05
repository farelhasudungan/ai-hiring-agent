<?php

namespace App\Policies;

use App\Models\Form;
use App\Models\User;

class FormPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null;
    }

    public function view(User $user, Form $form): bool
    {
        return $user->company_id !== null && $user->company_id === $form->company_id;
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, Form $form): bool
    {
        return $this->canManage($user) && $user->company_id === $form->company_id;
    }

    public function delete(User $user, Form $form): bool
    {
        return $this->update($user, $form);
    }

    private function canManage(User $user): bool
    {
        return $user->company_id !== null && in_array($user->role, ['admin', 'hr'], true);
    }
}
