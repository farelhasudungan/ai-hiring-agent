<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_active_staff_with_hashed_passwords_and_preserves_existing_accounts(): void
    {
        foreach (['admin', 'hr'] as $role) {
            config(["seeding.staff.{$role}" => [
                'name' => strtoupper($role),
                'email' => "{$role}@example.test",
                'password' => 'seed-test-password',
            ]]);
        }

        $this->seed(DatabaseSeeder::class);

        foreach (['admin', 'hr'] as $role) {
            $user = User::where('email', "{$role}@example.test")->firstOrFail();
            $this->assertSame($role, $user->role);
            $this->assertTrue($user->canAccessPanel(Filament::getPanel('admin')));
            $this->assertTrue(Hash::check('seed-test-password', $user->password));
        }

        $admin = User::where('role', 'admin')->firstOrFail();
        $admin->forceFill(['is_active' => false, 'role' => 'hr', 'password' => 'changed-password'])->save();
        $this->seed(DatabaseSeeder::class);
        $this->assertDatabaseCount('users', 2);
        $this->assertFalse($admin->fresh()->is_active);
        $this->assertSame('hr', $admin->fresh()->role);
        $this->assertTrue(Hash::check('changed-password', $admin->fresh()->password));
    }

    public function test_seeder_skips_accounts_without_configured_passwords(): void
    {
        config(['seeding.staff.admin.password' => null, 'seeding.staff.hr.password' => null]);
        $this->seed(DatabaseSeeder::class);
        $this->assertDatabaseCount('users', 0);
    }
}
