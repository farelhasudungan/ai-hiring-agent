<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateStaffUser extends Command
{
    protected $signature = 'app:create-staff';

    protected $description = 'Create an active admin or HR account for the Filament panel';

    public function handle(): int
    {
        $data = [
            'name' => $this->ask('Nama'),
            'email' => strtolower(trim((string) $this->ask('Email'))),
            'role' => $this->choice('Role', ['admin', 'hr']),
            'password' => $this->secret('Password (minimal 12 karakter)'),
            'password_confirmation' => $this->secret('Konfirmasi password'),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:admin,hr'],
            'password' => ['required', 'confirmed', Password::min(12)],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = new User;
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = $data['password'];
        $user->role = $data['role'];
        $user->is_active = true;
        $user->save();

        $this->info('Akun berhasil dibuat. Login melalui /admin/login.');

        return self::SUCCESS;
    }
}
