<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Auth\Notifications\ResetPassword;
use Filament\Auth\Pages\Login;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Tests\TestCase;

class PanelAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_guests_can_login_but_cannot_register_or_access_dashboard(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertOk();
        $this->get('/admin/register')->assertNotFound();
        $this->get('/admin/password-reset/request')->assertOk();
    }

    public function test_active_staff_can_login_and_logout(): void
    {
        foreach (['admin', 'hr'] as $role) {
            $user = User::factory()->create(['role' => $role, 'is_active' => true]);
            Livewire::test(Login::class)
                ->fillForm(['email' => $user->email, 'password' => 'password'])
                ->call('authenticate')->assertHasNoFormErrors();
            $this->assertAuthenticatedAs($user);
            $this->get('/admin')->assertOk();
            $this->get('/admin/profile')->assertOk();
            $this->post('/admin/logout')->assertRedirect();
            $this->assertGuest();
        }
    }

    public function test_invalid_credentials_and_unauthorized_accounts_cannot_login(): void
    {
        foreach ([['role' => 'admin', 'is_active' => false], ['role' => null, 'is_active' => true]] as $attributes) {
            $user = User::factory()->create($attributes);
            Livewire::test(Login::class)
                ->fillForm(['email' => $user->email, 'password' => 'password'])
                ->call('authenticate')->assertHasFormErrors(['email']);
            $this->assertGuest();
        }

        $user = User::factory()->create(['role' => 'hr', 'is_active' => true]);
        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'wrong'])
            ->call('authenticate')->assertHasFormErrors(['email']);
        $this->assertGuest();
    }

    public function test_inactive_user_with_existing_session_is_forbidden(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'is_active' => false]);
        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_staff_can_request_password_reset(): void
    {
        Notification::fake();
        $user = User::factory()->create(['role' => 'hr', 'is_active' => true]);
        Livewire::test(RequestPasswordReset::class)
            ->fillForm(['email' => $user->email])->call('request')
            ->assertHasNoFormErrors();
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_staff_command_creates_a_hashed_active_account(): void
    {
        $this->artisan('app:create-staff')
            ->expectsQuestion('Nama', 'Admin HR')
            ->expectsQuestion('Email', 'admin@example.test')
            ->expectsChoice('Role', 'admin', ['admin', 'hr'])
            ->expectsQuestion('Password (minimal 12 karakter)', 'long-test-password')
            ->expectsQuestion('Konfirmasi password', 'long-test-password')
            ->assertSuccessful();

        $user = User::where('email', 'admin@example.test')->firstOrFail();
        $this->assertTrue($user->canAccessPanel(Filament::getPanel('admin')));
        $this->assertTrue(password_verify('long-test-password', $user->password));
    }

    public function test_password_reset_token_changes_password_and_cannot_be_reused(): void
    {
        $user = User::factory()->create(['role' => 'hr', 'is_active' => true]);
        $token = Password::broker()->createToken($user);
        Livewire::test(\Filament\Auth\Pages\PasswordReset\ResetPassword::class, [
            'email' => $user->email, 'token' => $token,
        ])->fillForm([
            'email' => $user->email,
            'password' => 'new-long-password',
            'passwordConfirmation' => 'new-long-password',
        ])->call('resetPassword')->assertHasNoFormErrors();

        $this->assertTrue(password_verify('new-long-password', $user->fresh()->password));
        $this->assertFalse(Password::broker()->tokenExists($user, $token));
    }
}
