<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;

class StaffUserSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [];

        foreach (['admin', 'hr'] as $role) {
            $account = config("seeding.staff.{$role}");

            if (blank($account['password'])) {
                $this->command?->line("Lewati {$role}: SEED_".strtoupper($role).'_PASSWORD belum diisi.');

                continue;
            }

            $account['email'] = strtolower(trim($account['email']));
            $accounts[$role] = Validator::make($account, [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'string', 'min:12'],
            ])->validate();
        }

        foreach ($accounts as $role => $account) {
            User::firstOrCreate(
                ['email' => $account['email']],
                [...$account, 'role' => $role, 'is_active' => true],
            );
        }
    }
}
