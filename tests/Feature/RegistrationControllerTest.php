<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

test('guests can open the signup page', function () {
    $this->get(route('register'))->assertOk()->assertSee('Create account')
        ->assertSee(route('login'))
        ->assertDontSee('<header', false)
        ->assertDontSee('<nav', false)
        ->assertDontSee('<footer', false);
});

test('signup creates an unverified account without a company and logs the user in', function () {
    Event::fake([Registered::class]);
    $this->withSession(['marker' => 'kept']);
    $sessionId = session()->getId();

    $this->post(route('register.store'), [
        'name' => 'Taylor Recruiter',
        'email' => ' TAYLOR@EXAMPLE.COM ',
        'password' => 'secure-password',
        'password_confirmation' => 'secure-password',
    ])->assertRedirectToRoute('companies.create');

    $user = User::query()->sole();
    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('users', [
        'name' => 'Taylor Recruiter',
        'email' => 'taylor@example.com',
        'company_id' => null,
        'role' => 'viewer',
        'email_verified_at' => null,
    ]);
    $this->assertDatabaseCount('companies', 0);
    expect(Hash::check('secure-password', $user->password))->toBeTrue();
    expect(session()->getId())->not->toBe($sessionId);
    Event::assertDispatched(Registered::class, fn (Registered $event): bool => $event->user->is($user));
});

test('signup cannot select a company or grant elevated privileges', function () {
    $otherUser = User::factory()->create();

    $this->post(route('register.store'), [
        'name' => 'Taylor Recruiter',
        'email' => 'taylor@example.com',
        'password' => 'secure-password',
        'password_confirmation' => 'secure-password',
        'company_id' => $otherUser->company_id,
        'role' => 'admin',
        'email_verified_at' => now()->toDateTimeString(),
    ])->assertRedirectToRoute('companies.create');

    $this->assertDatabaseHas('users', [
        'email' => 'taylor@example.com',
        'company_id' => null,
        'role' => 'viewer',
        'email_verified_at' => null,
    ]);
    $this->assertDatabaseCount('companies', 1);
});

test('signup rejects an existing email after normalization', function () {
    User::factory()->create(['email' => 'taylor@example.com']);

    $this->post(route('register.store'), [
        'name' => 'Taylor Recruiter',
        'email' => 'TAYLOR@EXAMPLE.COM',
        'password' => 'secure-password',
        'password_confirmation' => 'secure-password',
    ])->assertSessionHasErrors(['email' => 'The email has already been taken.']);

    $this->assertGuest();
    $this->assertDatabaseCount('users', 1);
});

test('signup requires a name email and password', function () {
    $this->post(route('register.store'), [])->assertSessionHasErrors([
        'name' => 'The name field is required.',
        'email' => 'The email field is required.',
        'password' => 'The password field is required.',
    ]);

    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
});

test('signup rejects invalid account details without creating records', function (array $input, string $field, string $message) {
    Event::fake([Registered::class]);

    $this->post(route('register.store'), array_replace([
        'name' => 'Taylor Recruiter',
        'email' => 'taylor@example.com',
        'password' => 'secure-password',
        'password_confirmation' => 'secure-password',
    ], $input))->assertSessionHasErrors([$field => $message])->assertSessionMissingInput('password');

    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('companies', 0);
    Event::assertNotDispatched(Registered::class);
})->with([
    'long name' => [['name' => str_repeat('a', 256)], 'name', 'The name field must not be greater than 255 characters.'],
    'array name' => [['name' => ['bad']], 'name', 'The name field must be a string.'],
    'invalid email' => [['email' => 'not-an-email'], 'email', 'The email field must be a valid email address.'],
    'array email' => [['email' => ['bad']], 'email', 'The email field must be a string.'],
    'long email' => [['email' => str_repeat('a', 244).'@example.com'], 'email', 'The email field must not be greater than 255 characters.'],
    'short password' => [['password' => 'short', 'password_confirmation' => 'short'], 'password', 'The password field must be at least 8 characters.'],
    'long password' => [['password' => str_repeat('a', 73), 'password_confirmation' => str_repeat('a', 73)], 'password', 'The password field must not be greater than 72 characters.'],
    'unconfirmed password' => [['password_confirmation' => 'different-password'], 'password', 'The password field confirmation does not match.'],
    'missing confirmation' => [['password_confirmation' => null], 'password', 'The password field confirmation does not match.'],
    'array password' => [['password' => ['bad']], 'password', 'The password field must be a string.'],
    'multibyte password exceeding bcrypt limit' => [['password' => str_repeat('é', 40), 'password_confirmation' => str_repeat('é', 40)], 'password', 'The password must not exceed 72 bytes.'],
    'password with null bytes' => [['password' => "secure\0password", 'password_confirmation' => "secure\0password"], 'password', 'The password must not contain null characters.'],
]);

test('signup requests are rate limited', function () {
    $this->freezeTime();

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post(route('register.store'), [])->assertSessionHasErrors('email');
    }

    $this->post(route('register.store'), [])->assertTooManyRequests();

    $this->assertDatabaseCount('users', 0);
});
